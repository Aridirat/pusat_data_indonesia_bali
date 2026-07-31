<?php

namespace App\Services;

/**
 * AnomalyStatisticsService
 *
 * Service terpusat untuk perhitungan statistik z-score dan deteksi anomali.
 * Memastikan semua lapisan (DataImport, AnomalyDetectionService, AnomalyControlController)
 * menggunakan algoritma IDENTIK.
 *
 * Dipakai di:
 * - DataImport::detectOutliersViaDb() — saat preview
 * - DataImport::createAnomaliesForPendingKeys() — saat import
 * - AnomalyDetectionService::checkUnreasonableValue() — saat screening data baru
 * - AnomalyControlController::enrichAnomalyContext() — saat menampilkan control
 */
class AnomalyStatisticsService
{
    /**
     * Konstanta bersama untuk semua deteksi anomali.
     */

    // Ambang batas minimal histori agar deteksi bermakna
    public const MIN_HISTORY_FOR_MEANINGFUL_STATS = 3;

    // Threshold z-score untuk deteksi outlier vs histori
    public const DB_ZSCORE_THRESHOLD = 3.0;

    // Threshold Modified Z-Score untuk deteksi outlier intra-series (Iglewicz & Hoaglin)
    public const OUTLIER_MZSCORE_THRESHOLD = 3.5;

    /**
     * Parsing nilai numerik dari string (handle lokalisasi Indonesia).
     *
     * Contoh:
     *   "1.234,56"  → 1234.56  (pemisah ribuan: titik, desimal: koma)
     *   "1234.56"   → 1234.56  (format US)
     *   "1,234.56"  → 1234.56  (format alternatif)
     *
     * @param  mixed  $value  String atau numerik
     * @return float
     *
     * @throws \InvalidArgumentException jika tidak bisa diparsing
     */
    public static function parseLocaleNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException("Nilai harus string atau numerik, dapat: " . gettype($value));
        }

        $s = trim($value);
        if (empty($s)) {
            throw new \InvalidArgumentException("Nilai kosong");
        }

        // Hapus semua spasi
        $s = str_replace(' ', '', $s);

        // Deteksi format:
        // Jika ada koma dan titik: tentukan mana desimal, mana pemisah ribuan
        $hasComma = strpos($s, ',') !== false;
        $hasDot   = strpos($s, '.') !== false;

        if ($hasComma && $hasDot) {
            // Ambil karakter terakhir sebelum angka → itu desimal
            $lastCommaPos = strrpos($s, ',');
            $lastDotPos   = strrpos($s, '.');

            if ($lastCommaPos > $lastDotPos) {
                // Koma lebih belakang → koma = desimal (format Indonesia "1.234,56")
                $s = str_replace('.', '', $s);        // hapus pemisah ribuan
                $s = str_replace(',', '.', $s);       // ubah desimal
            } else {
                // Titik lebih belakang → titik = desimal (format US "1,234.56")
                $s = str_replace(',', '', $s);        // hapus pemisah ribuan
                // $s tetap dengan titik sebagai desimal
            }
        } elseif ($hasComma) {
            // Hanya koma: cek apakah itu desimal atau pemisah ribuan
            // Jika 3 digit atau lebih setelah koma → pemisah ribuan
            // Jika 1-2 digit → desimal
            $parts = explode(',', $s);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                // Desimal
                $s = str_replace(',', '.', $s);
            } else {
                // Pemisah ribuan
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasDot) {
            // Hanya titik: cek sama seperti koma
            $parts = explode('.', $s);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                // Desimal
                // tetap dengan titik
            } else {
                // Pemisah ribuan
                $s = str_replace('.', '', $s);
            }
        }

        if (!is_numeric($s)) {
            throw new \InvalidArgumentException("Nilai '$value' tidak bisa diparsing menjadi angka");
        }

        return (float) $s;
    }

    /**
     * Hitung mean dari array nilai.
     *
     * @param  array<float>  $values
     * @return float
     */
    public static function mean(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        return array_sum($values) / count($values);
    }

    /**
     * Hitung median dari array nilai.
     *
     * @param  array<float>  $values
     * @return float
     */
    public static function median(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $sorted = $values;
        sort($sorted);
        $count = count($sorted);
        $mid   = (int) floor($count / 2);

        return $count % 2 === 0
            ? ($sorted[$mid - 1] + $sorted[$mid]) / 2
            : (float) $sorted[$mid];
    }

    /**
     * Hitung standar deviasi (POPULASI).
     *
     * Formula: σ = sqrt(Σ(x_i - μ)² / n)
     *
     * @param  array<float>  $values
     * @param  float  $mean   Mean yang sudah dihitung (opsional, jika null akan dihitung)
     * @return float
     */
    public static function stdDev(array $values, ?float $mean = null): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        if ($mean === null) {
            $mean = self::mean($values);
        }

        $variance = array_sum(
            array_map(fn($v) => ($v - $mean) ** 2, $values)
        ) / count($values);

        return sqrt($variance);
    }

    /**
     * Hitung Median Absolute Deviation (MAD).
     *
     * Digunakan untuk Modified Z-Score calculation (Iglewicz & Hoaglin).
     *
     * @param  array<float>  $values
     * @param  float  $median  Median yang sudah dihitung (opsional, jika null akan dihitung)
     * @return float
     */
    public static function medianAbsoluteDeviation(array $values, ?float $median = null): float
    {
        if ($median === null) {
            $median = self::median($values);
        }

        $deviations = array_map(fn($v) => abs($v - $median), $values);
        return self::median($deviations);
    }

    /**
     * Hitung Modified Z-Score untuk array nilai.
     *
     * MZ_i = 0.6745 × (x_i − median) / MAD
     *
     * Referensi: Iglewicz & Hoaglin (1993), "How to Detect and Handle Outliers"
     *
     * @param  array<float>  $values
     * @return array<float>  Array modified z-scores yang sesuai urutan input
     */
    public static function modifiedZScores(array $values): array
    {
        $median = self::median($values);
        $mad    = self::medianAbsoluteDeviation($values, $median);

        if ($mad < 1e-10) {
            return array_fill(0, count($values), 0.0);
        }

        return array_map(
            fn($v) => 0.6745 * ($v - $median) / $mad,
            $values
        );
    }

    /**
     * Hitung standard z-score untuk satu nilai.
     *
     * z = |value - mean| / stddev
     *
     * @param  float  $value
     * @param  float  $mean
     * @param  float  $stddev
     * @return float|null  null jika stddev = 0 (tidak bisa hitung z)
     */
    public static function zScore(float $value, float $mean, float $stddev): ?float
    {
        if ($stddev < 1e-10) {
            return null;
        }
        return abs(($value - $mean) / $stddev);
    }

    /**
     * Hitung statistik deskriptif dari array nilai.
     *
     * @param  array<float>  $values
     * @return array{n: int, mean: float, median: float, stddev: float, min: float, max: float, lower_3sigma: float, upper_3sigma: float}
     */
    public static function descriptiveStats(array $values): array
    {
        if (empty($values)) {
            return [
                'n'              => 0,
                'mean'           => 0.0,
                'median'         => 0.0,
                'stddev'         => 0.0,
                'min'            => 0.0,
                'max'            => 0.0,
                'lower_3sigma'   => 0.0,
                'upper_3sigma'   => 0.0,
            ];
        }

        $n      = count($values);
        $mean   = self::mean($values);
        $median = self::median($values);
        $stddev = self::stdDev($values, $mean);
        $min    = min($values);
        $max    = max($values);

        return [
            'n'              => $n,
            'mean'           => round($mean, 4),
            'median'         => round($median, 4),
            'stddev'         => round($stddev, 4),
            'min'            => round($min, 4),
            'max'            => round($max, 4),
            'lower_3sigma'   => round($mean - 3 * $stddev, 4),
            'upper_3sigma'   => round($mean + 3 * $stddev, 4),
        ];
    }

    /**
     * Deteksi outlier untuk satu nilai berdasarkan histori.
     *
     * Mengembalikan info detail jika outlier, atau null jika bukan.
     *
     * @param  float  $currentValue
     * @param  array<float>  $history
     * @param  array{
     *     severity_rules?: array{low: int, medium: int, high: int, critical: int},
     *     threshold?: float,
     * }  $options
     * @return array{
     *     is_outlier: bool,
     *     z_score: float|null,
     *     mean: float,
     *     stddev: float,
     *     n: int,
     *     lower_bound: float,
     *     upper_bound: float,
     *     severity: string|null,
     *     reason: string|null,
     * }|null
     */
    public static function detectOutlier(
        float $currentValue,
        array $history,
        array $options = []
    ): ?array
    {
        $minHistoryRequired = $options['min_history'] ?? self::MIN_HISTORY_FOR_MEANINGFUL_STATS;
        $threshold          = $options['threshold'] ?? self::DB_ZSCORE_THRESHOLD;

        if (count($history) < $minHistoryRequired) {
            return null;
        }

        $mean   = self::mean($history);
        $stddev = self::stdDev($history, $mean);
        $n      = count($history);

        // Jika stddev sangat kecil, gunakan deviation absolut
        if ($stddev < 1e-10) {
            $absDiff = abs($currentValue - $mean);
            if ($absDiff < 1e-10) {
                return null;  // identik → bukan outlier
            }

            return [
                'is_outlier'   => true,
                'z_score'      => null,
                'mean'         => round($mean, 4),
                'stddev'       => 0.0,
                'n'            => $n,
                'lower_bound'  => round($mean, 4),
                'upper_bound'  => round($mean, 4),
                'severity'     => 'medium',
                'reason'       => 'stddev_zero',
            ];
        }

        // Hitung z-score
        $zScore = self::zScore($currentValue, $mean, $stddev);

        if ($zScore === null || $zScore <= $threshold) {
            return null;  // bukan outlier
        }
        
        // Tentukan severity berdasarkan z-score
        $severity = match (true) {
            $zScore > 40  => 'critical',
            $zScore >= 31 => 'high',
            $zScore >= 10 => 'medium',
            default       => 'low',
        };

        return [
            'is_outlier'   => true,
            'z_score'      => round($zScore, 4),
            'mean'         => round($mean, 4),
            'stddev'       => round($stddev, 4),
            'n'            => $n,
            'lower_bound'  => round($mean - 3 * $stddev, 4),
            'upper_bound'  => round($mean + 3 * $stddev, 4),
            'severity'     => $severity,
            'reason'       => 'z_score_exceeded',
        ];
    }

    /**
     * Batch detect outliers untuk multiple values berdasarkan histori yang sama.
     *
     * @param  array<float>  $currentValues
     * @param  array<float>  $history
     * @param  array  $options
     * @return array<array>
     */
    public static function detectOutliersBatch(
        array $currentValues,
        array $history,
        array $options = []
    ): array
    {
        return array_map(
            fn($val) => self::detectOutlier($val, $history, $options),
            $currentValues
        );
    }
}