<?php

namespace App\Http\Controllers\ActivityLogs;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_ACTIONS = [
        'voided',
        'bulk_voided',
        'deleted',
        'data_cleaned',
        'password_updated',
        'password_reset',
        'adjusted',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $space = app('currentSpace');
        $user = $request->user();
        $tz = $space?->timezone ?: config('app.timezone', 'Asia/Jakarta');

        $filters = $this->resolveFilters($request, $space, $user);

        $baseScopeQuery = ActivityLog::query()->visibleTo($user, $space, $filters['scope']);

        $nowInSpace = $space ? $space->now() : Carbon::now($tz);
        $appTimezone = config('app.timezone', 'Asia/Jakarta');
        $todayStart = $nowInSpace->copy()->startOfDay()->setTimezone($appTimezone);
        $todayEnd = $nowInSpace->copy()->endOfDay()->setTimezone($appTimezone);
        $last7Start = $nowInSpace->copy()->subDays(6)->startOfDay()->setTimezone($appTimezone);

        $totalLogs = (clone $baseScopeQuery)->count();
        $todayLogs = (clone $baseScopeQuery)->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $last7DaysLogs = (clone $baseScopeQuery)->whereBetween('created_at', [$last7Start, $todayEnd])->count();
        $sensitiveLogs = (clone $baseScopeQuery)->whereIn('action', self::SENSITIVE_ACTIONS)->count();

        $filteredQuery = $this->applyFilters(clone $baseScopeQuery, $filters, $tz);

        $logs = $filteredQuery
            ->with(['actor', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $spaceActors = $space && ! $space->isPersonal()
            ? $space->activeMembers()->orderBy('users.name')->get(['users.id', 'users.name'])
            : collect([$user]);

        $moduleOptions = ActivityLog::MODULE_LABELS;
        $actionOptions = ActivityLog::ACTION_LABELS;

        return view('activity-logs.index', [
            'space' => $space,
            'tz' => $tz,
            'logs' => $logs,
            'filters' => $filters,
            'defaultScope' => $this->defaultScope($space),
            'totalLogs' => $totalLogs,
            'todayLogs' => $todayLogs,
            'last7DaysLogs' => $last7DaysLogs,
            'sensitiveLogs' => $sensitiveLogs,
            'spaceActors' => $spaceActors,
            'moduleOptions' => $moduleOptions,
            'actionOptions' => $actionOptions,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $space = app('currentSpace');
        $user = $request->user();
        $tz = $space?->timezone ?: config('app.timezone', 'Asia/Jakarta');
        $filters = $this->resolveFilters($request, $space, $user);

        $query = ActivityLog::query()->visibleTo($user, $space, $filters['scope']);
        $this->applyFilters($query, $filters, $tz);

        $logs = $query->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(2000)
            ->get();

        $filename = 'ARUS_Log_Aktivitas_'.($space ? str_replace(' ', '_', $space->name).'_' : '').now($tz)->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($logs, $tz): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM untuk kompatibilitas Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Waktu',
                'Pengguna',
                'Modul',
                'Aksi',
                'Subjek',
                'Deskripsi',
                'Alamat IP',
                'Detail JSON',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->copy()->setTimezone($tz)->format('Y-m-d H:i:s') ?? '-',
                    $log->actorDisplayName(),
                    $log->moduleLabel(),
                    $log->actionLabel(),
                    $log->subject_label ?? '-',
                    $log->description,
                    $log->ip_address ?? '-',
                    $log->properties ? json_encode($log->properties, JSON_UNESCAPED_UNICODE) : '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{
     *     scope: string,
     *     module: string,
     *     action: string,
     *     actor_id: ?int,
     *     start_date: ?string,
     *     end_date: ?string,
     *     search: string
     * }
     */
    private function resolveFilters(Request $request, ?Space $space, User $user): array
    {
        $defaultScope = $this->defaultScope($space);
        $scope = (string) $request->query('scope', $defaultScope);
        if (! in_array($scope, ['all', 'space', 'account'], true)) {
            $scope = $defaultScope;
        }

        $module = (string) $request->query('module', 'all');
        if ($module !== 'all' && ! array_key_exists($module, ActivityLog::MODULE_LABELS)) {
            $module = 'all';
        }

        $action = (string) $request->query('action', 'all');
        if ($action !== 'all' && ! array_key_exists($action, ActivityLog::ACTION_LABELS)) {
            $action = 'all';
        }

        $actorId = $request->filled('actor_id') ? (int) $request->query('actor_id') : null;
        if ($actorId !== null && $actorId > 0) {
            $allowedActorIds = $space
                ? $space->activeMembers()->pluck('users.id')->push($user->id)->unique()->all()
                : [$user->id];
            if (! in_array($actorId, $allowedActorIds, true)) {
                $actorId = null;
            }
        } else {
            $actorId = null;
        }

        $startDate = $this->normalizeDate($request->query('start_date'));
        $endDate = $this->normalizeDate($request->query('end_date'));

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'scope' => $scope,
            'module' => $module,
            'action' => $action,
            'actor_id' => $actorId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'search' => trim((string) $request->query('search', '')),
        ];
    }

    /**
     * @param  array{
     *     scope: string,
     *     module: string,
     *     action: string,
     *     actor_id: ?int,
     *     start_date: ?string,
     *     end_date: ?string,
     *     search: string
     * }  $filters
     */
    private function applyFilters(Builder $query, array $filters, string $tz): Builder
    {
        if ($filters['module'] !== 'all') {
            $query->where('module', $filters['module']);
        }

        if ($filters['action'] !== 'all') {
            $query->where('action', $filters['action']);
        }

        if ($filters['actor_id'] !== null) {
            $query->where('actor_id', $filters['actor_id']);
        }

        $appTimezone = config('app.timezone', 'Asia/Jakarta');

        if ($filters['start_date'] !== null) {
            $startBoundary = Carbon::parse($filters['start_date'], $tz)->startOfDay()->setTimezone($appTimezone);
            $query->where('created_at', '>=', $startBoundary);
        }

        if ($filters['end_date'] !== null) {
            $endBoundary = Carbon::parse($filters['end_date'], $tz)->endOfDay()->setTimezone($appTimezone);
            $query->where('created_at', '<=', $endBoundary);
        }

        if ($filters['search'] !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['search']));
            $pattern = '%'.$escaped.'%';
            $query->where(function (Builder $q) use ($pattern): void {
                $q->whereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(COALESCE(subject_label, '')) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(COALESCE(actor_name, '')) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(event) LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        return $query;
    }

    private function defaultScope(?Space $space): string
    {
        return $space && ! $space->isPersonal() ? 'space' : 'all';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))?->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
