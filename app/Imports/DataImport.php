<?php

namespace App\Imports;

use App\Models\Data;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Waktu;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DataImport implements ToCollection, WithHeadingRow
{
    protected int $userId;
    protected bool $skipDuplicates;

    protected array $rows       = [];
    protected array $errors     = [];
    protected array $duplicates = [];
    protected int $importedCount = 0;
    protected int $skippedCount  = 0;

    // Kolom wajib ada di file Excel
    protected array $requiredColumns = [
        'metadata_id', 'location_id', 'time_id'
    ];

    public function __construct(int $userId = 0, bool $skipDuplicates = true)
    {
        $this->userId         = $userId;
        $this->skipDuplicates = $skipDuplicates;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 karena baris 1 = heading
            $rowArray  = $row->toArray();

            // ── Validasi kolom wajib ada ──
            foreach ($this->requiredColumns as $col) {
                if (empty($rowArray[$col])) {
                    $this->errors[] = [
                        'row'     => $rowNumber,
                        'data'    => $rowArray,
                        'message' => "Kolom '{$col}' tidak boleh kosong.",
                    ];
                    continue 2;
                }
            }

            // ── Validasi referensi ke tabel dimensi ──
            $metadataExists = Metadata::where('metadata_id', $rowArray['metadata_id'])->exists();
            $locationExists = Location::where('location_id', $rowArray['location_id'])->exists();
            $timeExists     = Waktu::where('time_id', $rowArray['time_id'])->exists();

            if (!$metadataExists || !$locationExists || !$timeExists) {
                $this->errors[] = [
                    'row'     => $rowNumber,
                    'data'    => $rowArray,
                    'message' => 'metadata_id, location_id, atau time_id tidak ditemukan di database.',
                ];
                continue;
            }

            // ── Cek duplikat ──
            $duplicate = Data::where('metadata_id', $rowArray['metadata_id'])
                ->where('location_id', $rowArray['location_id'])
                ->where('time_id',     $rowArray['time_id'])
                ->exists();

            if ($duplicate) {
                $this->duplicates[] = [
                    'row'     => $rowNumber,
                    'data'    => $rowArray,
                    'message' => 'Data duplikat ditemukan.',
                ];
                if ($this->skipDuplicates) {
                    $this->skippedCount++;
                    continue;
                }
            }

            // ── Simpan data (hanya saat import final, bukan preview) ──
            if ($this->userId > 0) {
                Data::create([
                    'user_id'           => $this->userId,
                    'metadata_id'       => $rowArray['metadata_id'],
                    'location_id'       => $rowArray['location_id'],
                    'time_id'           => $rowArray['time_id'],
                    'text_value'        => $rowArray['text_value']        ?? null,
                    'number_value'      => $rowArray['number_value']      ?? null,
                    'kategori_value'    => $rowArray['kategori_value']    ?? null,
                    'other'             => $rowArray['other']             ?? null,
                    'analisis_fenomena' => $rowArray['analisis_fenomena'] ?? null,
                    'status'            => Data::STATUS_PENDING,
                    'date_inputed'      => Carbon::now(),
                ]);
                $this->importedCount++;
            }

            $this->rows[] = $rowArray;
        }
    }

    // ── Getters ──
    public function getRows(): array       { return $this->rows; }
    public function getErrors(): array     { return $this->errors; }
    public function getDuplicates(): array { return $this->duplicates; }
    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int  { return $this->skippedCount; }
}