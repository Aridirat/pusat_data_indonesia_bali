<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\Tampilan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubscriptionLimitsService
{
    /**
     * Group yang tidak terkena limit template/kuota sama sekali.
     * 1 = Administrator, 2 = Pengelola (lihat GroupSeeder).
     */
    private const UNLIMITED_TEMPLATE_GROUPS = [1, 2];

    /**
     * Admin & Pengelola tidak dibatasi jumlah template.
     */
    public function isUnlimitedTemplateUser(User $user): bool
    {
        return in_array((int) $user->group_id, self::UNLIMITED_TEMPLATE_GROUPS, true);
    }

    public function hasActiveSubscription(User $user): bool
    {
        return $this->getActiveSubscription($user) !== null;
    }

    public function getActiveSubscription(User $user): ?Layanan
    {
        $transaction = Transaksi::where('user_id', $user->user_id)
            ->where('status', 'success')
            ->where(function ($query): void {
                $query->whereNull('aktif_sampai')
                    ->orWhere('aktif_sampai', '>=', now());
            })
            ->with('layanan')
            ->latest('aktif_sampai')
            ->latest('created_at')
            ->first();

        return $transaction?->layanan;
    }

    public function getMaxConcurrentSessions(User $user): int
    {
        $subscription = $this->getActiveSubscription($user);

        if ($subscription) {
            return (int) ($subscription->max_concurrent_sessions ?? $this->getDefaultMaxConcurrentSessions($subscription));
        }

        return 1;
    }

    public function getMaxTemplates(User $user): int
    {
        // Admin & Pengelola: tidak dibatasi.
        if ($this->isUnlimitedTemplateUser($user)) {
            return PHP_INT_MAX;
        }

        $subscription = $this->getActiveSubscription($user);

        if ($subscription) {
            return (int) ($subscription->max_templates ?? $this->getDefaultMaxTemplates($subscription));
        }

        return 5;
    }

    public function getTemplateCountForUser(User $user): int
    {
        return (int) Tampilan::where('user_id', $user->user_id)->count();
    }

    public function canCreateTemplate(User $user): bool
    {
        // Admin & Pengelola: selalu boleh membuat template baru.
        if ($this->isUnlimitedTemplateUser($user)) {
            return true;
        }

        return $this->getTemplateCountForUser($user) < $this->getMaxTemplates($user);
    }

    public function enforceConcurrentSessionLimit(User $user, string $sessionId, ?Request $request = null): array
    {
        if (! $this->hasActiveSubscription($user)) {
            return ['limit' => 1, 'evicted_count' => 0];
        }

        $limit = max(1, $this->getMaxConcurrentSessions($user));

        UserSession::updateOrCreate(
            [
                'user_id' => $user->user_id,
                'session_id' => $sessionId,
            ],
            [
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'login_at' => now(),
            ]
        );

        $activeSessions = UserSession::where('user_id', $user->user_id)
            ->orderBy('login_at')
            ->orderBy('created_at')
            ->get();

        if ($activeSessions->count() <= $limit) {
            return ['limit' => $limit, 'evicted_count' => 0];
        }

        $toEvict = $activeSessions
            ->reject(fn (UserSession $session): bool => $session->session_id === $sessionId)
            ->take($activeSessions->count() - $limit);

        foreach ($toEvict as $session) {
            $this->destroySessionRecord($session);
        }

        if ($toEvict->isNotEmpty()) {
            $warningKey = 'subscription-limit-warning:' . $user->user_id;
            cache()->put($warningKey, 'Sesi lama Anda otomatis ditutup karena batas perangkat paket Anda.', now()->addMinutes(5));

            if ($request?->hasSession()) {
                $request->session()->flash('warning', 'Sesi lama Anda otomatis ditutup karena batas perangkat paket Anda.');
            }
        }

        return ['limit' => $limit, 'evicted_count' => $toEvict->count()];
    }

    protected function getDefaultMaxConcurrentSessions(Layanan $subscription): int
    {
        return $this->normalizeCategory($subscription) === 'organisasi' ? 5 : 1;
    }

    protected function getDefaultMaxTemplates(Layanan $subscription): int
    {
        return $this->normalizeCategory($subscription) === 'organisasi' ? 50 : 10;
    }

    protected function normalizeCategory(Layanan $subscription): string
    {
        $category = (string) ($subscription->category ?? $subscription->audience_type ?? 'personal');

        return match ($category) {
            'organisasi', 'organization', 'org' => 'organisasi',
            default => 'personal',
        };
    }

    protected function destroySessionRecord(UserSession $session): void
    {
        if ($session->session_id) {
            try {
                app('session')->driver()->destroy($session->session_id);
            } catch (\Throwable) {
                // Ignore storage errors and delete the tracking row.
            }
        }

        $session->delete();
    }

    public function getLockedTemplateIds($user): Collection
    {
        // Admin & Pengelola: tidak pernah ada template yang terkunci.
        if ($this->isUnlimitedTemplateUser($user)) {
            return collect();
        }

        $limit = $this->getMaxTemplates($user);

        $orderedIds = Tampilan::where('user_id', $user->user_id)
            ->orderBy('created_at', 'asc') // paling lama di depan
            ->pluck('tampilan_id');

        $total       = $orderedIds->count();
        $lockedCount = max(0, $total - $limit);

        if ($lockedCount <= 0) {
            return collect();
        }

        return $orderedIds->take($lockedCount)->values();
    }

    /**
     * Cek apakah satu template tertentu sedang terkunci.
     */
    public function isTemplateLocked(Tampilan $tampilan): bool
    {
        return $this->getLockedTemplateIds($tampilan->user)
            ->contains($tampilan->tampilan_id);
    }
}