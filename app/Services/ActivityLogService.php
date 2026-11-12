<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Catat satu entri log aktivitas secara konsisten untuk ruang atau akun pengguna.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?Space $space = null,
        ?User $actor = null,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        array $properties = [],
        ?Request $request = null,
    ): ActivityLog {
        $resolvedActor = $actor ?? Auth::user();
        $resolvedRequest = $request ?? (app()->bound('request') ? request() : null);

        $ipAddress = null;
        $userAgent = null;
        if ($resolvedRequest instanceof Request) {
            $ipAddress = $resolvedRequest->ip();
            $rawAgent = $resolvedRequest->userAgent();
            $userAgent = $rawAgent !== null ? Str::limit($rawAgent, 497, '...') : null;
        }

        return ActivityLog::create([
            'space_id' => $space?->id,
            'actor_id' => $resolvedActor?->id,
            'actor_name' => $resolvedActor?->name,
            'module' => $module,
            'action' => $action,
            'event' => "{$module}.{$action}",
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subjectLabel !== null ? Str::limit($subjectLabel, 252, '...') : null,
            'description' => $description,
            'properties' => $properties !== [] ? $properties : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Susun ringkasan perubahan atribut sebelum dan sesudah pembaruan untuk metadata log.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, string>  $labels
     * @return array{changed?: array<string, array{label: string, before: mixed, after: mixed}>}
     */
    public function buildDiff(array $before, array $after, array $labels = []): array
    {
        $changed = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            if ($this->valuesEqual($oldValue, $newValue)) {
                continue;
            }

            $changed[$key] = [
                'label' => $labels[$key] ?? ucfirst(str_replace('_', ' ', (string) $key)),
                'before' => $oldValue,
                'after' => $newValue,
            ];
        }

        return $changed !== [] ? ['changed' => $changed] : [];
    }

    /**
     * Format nominal rupiah yang konsisten untuk teks deskripsi aktivitas.
     */
    public function formatCurrency(int|float|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    private function valuesEqual(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return round((float) $a, 2) === round((float) $b, 2);
        }

        return $a === $b;
    }
}
