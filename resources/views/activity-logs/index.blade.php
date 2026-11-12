@php
    $hasActiveFilter = $filters['scope'] !== $defaultScope
        || $filters['module'] !== 'all'
        || $filters['action'] !== 'all'
        || $filters['actor_id'] !== null
        || ! empty($filters['start_date'])
        || ! empty($filters['end_date'])
        || $filters['search'] !== '';

    $toneBadgeClasses = [
        'positive' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/60',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/60',
        'danger' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/60',
        'neutral' => 'bg-cream text-charcoal border-line dark:bg-white/10 dark:text-gray-200 dark:border-white/15',
    ];
@endphp

<x-layouts.app title="Log Aktivitas">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-bold tracking-[1.5px] uppercase text-forest dark:text-blue-400">
                    Audit & Riwayat Sistem
                </p>
                <h1 class="font-serif text-3xl sm:text-4xl text-forest dark:text-white tracking-tight mt-0.5">
                    Log Aktivitas
                </h1>
                <p class="text-sm text-muted mt-1 max-w-2xl">
                    Pantau jejak perubahan data, transaksi, pengelolaan rekening, hingga aktivitas masuk akun secara kronologis.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a
                    href="{{ route('activity-logs.export-csv', request()->query()) }}"
                    class="min-h-[44px] sm:min-h-[38px] px-4 py-2 bg-panel border border-line text-charcoal text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-cream transition inline-flex items-center justify-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Ekspor CSV</span>
                </a>
            </div>
        </div>

        {{-- Summary Metric Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <x-metric-card
                label="Total Tercatat"
                :value="number_format($totalLogs, 0, ',', '.')"
                :sub="$filters['scope'] === 'space' ? 'Dalam ruang aktif' : ($filters['scope'] === 'account' ? 'Aktivitas akun personal' : 'Ruang & akun')"
                :dark="true"
                notch="forest"
            />
            <x-metric-card
                label="Aktivitas Hari Ini"
                :value="number_format($todayLogs, 0, ',', '.')"
                :sub="'Zona ' . ($space?->timezone ?? 'Asia/Jakarta')"
                notch="emerald"
            />
            <x-metric-card
                label="7 Hari Terakhir"
                :value="number_format($last7DaysLogs, 0, ',', '.')"
                sub="Termasuk hari ini"
                notch="slate"
            />
            <x-metric-card
                label="Aksi Sensitif"
                :value="number_format($sensitiveLogs, 0, ',', '.')"
                sub="Void, hapus, sandi, saldo"
                notch="crimson"
            />
        </div>

        {{-- Filter & Search Panel --}}
        <x-panel title="Filter Riwayat" subtitle="Saring aktivitas berdasarkan cakupan, modul, jenis aksi, pelaku, atau rentang tanggal.">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label for="filter-scope" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Cakupan</label>
                        <select
                            id="filter-scope"
                            name="scope"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        >
                            <option value="all" {{ $filters['scope'] === 'all' ? 'selected' : '' }}>Semua (Ruang + Akun Saya)</option>
                            <option value="space" {{ $filters['scope'] === 'space' ? 'selected' : '' }}>Ruang Keuangan Aktif</option>
                            <option value="account" {{ $filters['scope'] === 'account' ? 'selected' : '' }}>Akun Saya (Login & Profil)</option>
                        </select>
                    </div>

                    <div>
                        <label for="filter-module" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Modul</label>
                        <select
                            id="filter-module"
                            name="module"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        >
                            <option value="all" {{ $filters['module'] === 'all' ? 'selected' : '' }}>Semua Modul</option>
                            @foreach ($moduleOptions as $moduleKey => $moduleLabel)
                                <option value="{{ $moduleKey }}" {{ $filters['module'] === $moduleKey ? 'selected' : '' }}>
                                    {{ $moduleLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-action" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Jenis Aksi</label>
                        <select
                            id="filter-action"
                            name="action"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        >
                            <option value="all" {{ $filters['action'] === 'all' ? 'selected' : '' }}>Semua Aksi</option>
                            @foreach ($actionOptions as $actionKey => $actionLabel)
                                <option value="{{ $actionKey }}" {{ $filters['action'] === $actionKey ? 'selected' : '' }}>
                                    {{ $actionLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-actor" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Pelaku</label>
                        <select
                            id="filter-actor"
                            name="actor_id"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        >
                            <option value="">Semua Pengguna</option>
                            @foreach ($spaceActors as $actorOption)
                                <option value="{{ $actorOption->id }}" {{ (int) ($filters['actor_id'] ?? 0) === (int) $actorOption->id ? 'selected' : '' }}>
                                    {{ $actorOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div>
                        <label for="filter-start-date" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Dari Tanggal</label>
                        <input
                            id="filter-start-date"
                            type="date"
                            name="start_date"
                            value="{{ $filters['start_date'] ?? '' }}"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        />
                    </div>

                    <div>
                        <label for="filter-end-date" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Sampai Tanggal</label>
                        <input
                            id="filter-end-date"
                            type="date"
                            name="end_date"
                            value="{{ $filters['end_date'] ?? '' }}"
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        />
                    </div>

                    <div>
                        <label for="filter-search" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Kata Kunci</label>
                        <input
                            id="filter-search"
                            type="text"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Cari deskripsi, subjek, pelaku..."
                            class="w-full min-h-[44px] sm:min-h-[38px] px-3 py-2 text-xs bg-ivory border border-line rounded-lg text-charcoal placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-forest/30 focus:border-forest"
                        />
                    </div>

                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                        <button
                            type="submit"
                            class="flex-1 min-h-[44px] sm:min-h-[38px] px-4 py-2 bg-forest text-white text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-forest/90 transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                        >
                            Terapkan
                        </button>
                        @if ($hasActiveFilter)
                            <a
                                href="{{ route('activity-logs.index') }}"
                                class="min-h-[44px] sm:min-h-[38px] px-3.5 py-2 bg-cream border border-line text-charcoal text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-ivory transition inline-flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </x-panel>

        {{-- Activity Feed / Table --}}
        <x-panel
            title="Daftar Aktivitas"
            :subtitle="'Menampilkan ' . $logs->total() . ' entri sesuai filter saat ini'"
            :noPadding="true"
        >
            @if ($logs->isEmpty())
                <div class="p-8 sm:p-12 text-center">
                    <div class="w-12 h-12 rounded-xl bg-cream border border-line mx-auto flex items-center justify-center text-muted mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    @if ($hasActiveFilter)
                        <h3 class="text-sm font-bold text-charcoal">Tidak ada aktivitas yang cocok dengan filter</h3>
                        <p class="text-xs text-muted mt-1 max-w-md mx-auto">
                            Ubah kriteria filter atau reset pencarian untuk melihat seluruh log aktivitas pada cakupan ini.
                        </p>
                        <div class="mt-4">
                            <a
                                href="{{ route('activity-logs.index') }}"
                                class="min-h-[44px] sm:min-h-[38px] px-4 py-2 bg-forest text-white text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-forest/90 transition inline-flex items-center justify-center"
                            >
                                Reset Semua Filter
                            </a>
                        </div>
                    @else
                        <h3 class="text-sm font-bold text-charcoal">Belum ada aktivitas tercatat</h3>
                        <p class="text-xs text-muted mt-1 max-w-md mx-auto">
                            Setiap aksi seperti membuat transaksi, mengubah rekening, atau memperbarui profil akan langsung tercatat di sini.
                        </p>
                    @endif
                </div>
            @else
                {{-- Desktop Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-line bg-ivory/70 text-[11px] font-bold uppercase tracking-wider text-muted">
                                <th class="py-3 px-4 whitespace-nowrap">Waktu</th>
                                <th class="py-3 px-4 whitespace-nowrap">Pelaku</th>
                                <th class="py-3 px-4 whitespace-nowrap">Modul & Aksi</th>
                                <th class="py-3 px-4">Deskripsi Aktivitas</th>
                                <th class="py-3 px-4 text-right whitespace-nowrap">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line text-sm">
                            @foreach ($logs as $log)
                                @php
                                    $localTime = $log->created_at?->copy()->setTimezone($tz);
                                    $toneClass = $toneBadgeClasses[$log->actionTone()] ?? $toneBadgeClasses['neutral'];
                                    $subjectUrl = $log->subjectUrl($space);
                                    $payload = [
                                        'id' => $log->id,
                                        'time' => $localTime ? $localTime->translatedFormat('d M Y, H:i:s') : '-',
                                        'actor' => $log->actorDisplayName(),
                                        'module' => $log->moduleLabel(),
                                        'action' => $log->actionLabel(),
                                        'event' => $log->event,
                                        'subject' => $log->subject_label,
                                        'subjectUrl' => $subjectUrl,
                                        'description' => $log->description,
                                        'ip' => $log->ip_address,
                                        'userAgent' => $log->user_agent,
                                        'scopeLabel' => $log->space_id ? 'Ruang Keuangan' : 'Akun Pengguna',
                                        'properties' => $log->properties,
                                    ];
                                @endphp
                                <tr class="hover:bg-ivory/50 transition">
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        @if ($localTime)
                                            <div class="text-xs font-semibold text-charcoal tabular-nums">
                                                {{ $localTime->format('d M Y') }}
                                            </div>
                                            <div class="text-[11px] text-muted font-mono tabular-nums mt-0.5">
                                                {{ $localTime->format('H:i:s') }}
                                            </div>
                                        @else
                                            <span class="text-xs text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        <div class="text-xs font-semibold text-charcoal">
                                            {{ $log->actorDisplayName() }}
                                        </div>
                                        <div class="text-[11px] text-muted mt-0.5">
                                            {{ $log->space_id ? 'Ruang' : 'Akun' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted">
                                                {{ $log->moduleLabel() }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $toneClass }}">
                                                {{ $log->actionLabel() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 align-top">
                                        <p class="text-xs sm:text-sm text-charcoal leading-relaxed">
                                            {{ $log->description }}
                                        </p>
                                        @if ($log->subject_label)
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-muted">
                                                <span>Subjek:</span>
                                                @if ($subjectUrl)
                                                    <a href="{{ $subjectUrl }}" class="font-semibold text-forest dark:text-blue-400 hover:underline">
                                                        {{ $log->subject_label }}
                                                    </a>
                                                @else
                                                    <span class="font-medium text-charcoal">{{ $log->subject_label }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 align-top text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick='openActivityDetailModal(@json($payload))'
                                            class="min-h-[36px] px-3 py-1.5 text-xs font-semibold text-charcoal bg-ivory border border-line rounded-lg hover:bg-cream transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                                        >
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden divide-y divide-line">
                    @foreach ($logs as $log)
                        @php
                            $localTime = $log->created_at?->copy()->setTimezone($tz);
                            $toneClass = $toneBadgeClasses[$log->actionTone()] ?? $toneBadgeClasses['neutral'];
                            $subjectUrl = $log->subjectUrl($space);
                            $payload = [
                                'id' => $log->id,
                                'time' => $localTime ? $localTime->translatedFormat('d M Y, H:i:s') : '-',
                                'actor' => $log->actorDisplayName(),
                                'module' => $log->moduleLabel(),
                                'action' => $log->actionLabel(),
                                'event' => $log->event,
                                'subject' => $log->subject_label,
                                'subjectUrl' => $subjectUrl,
                                'description' => $log->description,
                                'ip' => $log->ip_address,
                                'userAgent' => $log->user_agent,
                                'scopeLabel' => $log->space_id ? 'Ruang Keuangan' : 'Akun Pengguna',
                                'properties' => $log->properties,
                            ];
                        @endphp
                        <div class="p-4 space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $toneClass }}">
                                        {{ $log->actionLabel() }}
                                    </span>
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-muted">
                                        {{ $log->moduleLabel() }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-muted font-mono tabular-nums shrink-0">
                                    {{ $localTime ? $localTime->format('d/m H:i') : '-' }}
                                </span>
                            </div>

                            <p class="text-sm text-charcoal leading-relaxed">
                                {{ $log->description }}
                            </p>

                            @if ($log->subject_label)
                                <div class="text-xs text-muted">
                                    Subjek:
                                    @if ($subjectUrl)
                                        <a href="{{ $subjectUrl }}" class="font-semibold text-forest dark:text-blue-400 hover:underline">
                                            {{ $log->subject_label }}
                                        </a>
                                    @else
                                        <span class="font-medium text-charcoal">{{ $log->subject_label }}</span>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center justify-between gap-2 pt-1">
                                <span class="text-xs text-muted truncate">
                                    Oleh <strong class="text-charcoal font-semibold">{{ $log->actorDisplayName() }}</strong>
                                    ({{ $log->space_id ? 'Ruang' : 'Akun' }})
                                </span>
                                <button
                                    type="button"
                                    onclick='openActivityDetailModal(@json($payload))'
                                    class="min-h-[44px] px-3.5 py-2 text-xs font-semibold text-charcoal bg-ivory border border-line rounded-lg hover:bg-cream transition cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                                >
                                    Lihat Detail
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($logs->hasPages())
                    <div class="px-4 py-3 border-t border-line bg-ivory/40">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </x-panel>
    </div>

    {{-- Activity Detail Modal --}}
    <div
        id="activityDetailModal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="activityModalTitle"
        onclick="if (event.target === this) closeActivityDetailModal();"
    >
        <div class="bg-panel border border-line rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 py-4 border-b border-line flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p id="activityModalEvent" class="text-[11px] font-mono uppercase tracking-wider text-muted">-</p>
                    <h2 id="activityModalTitle" class="text-base font-bold text-charcoal mt-0.5">Detail Aktivitas</h2>
                </div>
                <button
                    type="button"
                    id="activityModalCloseBtn"
                    onclick="closeActivityDetailModal()"
                    class="min-w-[44px] min-h-[44px] sm:min-w-[36px] sm:min-h-[36px] -mr-2 -mt-1 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition inline-flex items-center justify-center cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                    aria-label="Tutup detail aktivitas"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 overflow-y-auto space-y-4 text-xs sm:text-sm">
                <div class="p-3.5 rounded-xl bg-ivory border border-line">
                    <p id="activityModalDescription" class="text-charcoal font-medium leading-relaxed"></p>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-lg bg-ivory/60 border border-line">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Waktu Kejadian</dt>
                        <dd id="activityModalTime" class="mt-1 font-semibold text-charcoal font-mono"></dd>
                    </div>
                    <div class="p-3 rounded-lg bg-ivory/60 border border-line">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Pelaku & Cakupan</dt>
                        <dd id="activityModalActor" class="mt-1 font-semibold text-charcoal"></dd>
                    </div>
                    <div class="p-3 rounded-lg bg-ivory/60 border border-line">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Modul & Aksi</dt>
                        <dd id="activityModalModuleAction" class="mt-1 font-semibold text-charcoal"></dd>
                    </div>
                    <div class="p-3 rounded-lg bg-ivory/60 border border-line">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-muted">Alamat IP</dt>
                        <dd id="activityModalIp" class="mt-1 font-semibold text-charcoal font-mono"></dd>
                    </div>
                </dl>

                <div id="activityModalSubjectWrap" class="hidden p-3 rounded-lg bg-ivory/60 border border-line text-xs">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Subjek Terkait</div>
                    <div id="activityModalSubject" class="mt-1 font-semibold text-charcoal"></div>
                </div>

                <div id="activityModalChangesWrap" class="hidden space-y-2">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted">Perubahan Data</h3>
                    <div id="activityModalChanges" class="border border-line rounded-xl overflow-hidden divide-y divide-line"></div>
                </div>

                <div id="activityModalPropertiesWrap" class="hidden space-y-2">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted">Metadata Tambahan</h3>
                    <pre id="activityModalProperties" class="p-3 rounded-xl bg-ivory border border-line text-[11px] font-mono text-charcoal overflow-x-auto whitespace-pre-wrap break-words"></pre>
                </div>

                <div id="activityModalAgentWrap" class="hidden text-[11px] text-muted">
                    <span class="font-bold uppercase tracking-wider">Perangkat / Browser:</span>
                    <span id="activityModalAgent" class="block mt-0.5 break-all font-mono"></span>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-line bg-ivory/50 flex justify-end">
                <button
                    type="button"
                    onclick="closeActivityDetailModal()"
                    class="min-h-[44px] sm:min-h-[38px] px-4 py-2 bg-forest text-white text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-forest/90 transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-forest"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        function formatPropertyValue(val) {
            if (val === null || val === undefined || val === '') {
                return '-';
            }
            if (typeof val === 'boolean') {
                return val ? 'Ya' : 'Tidak';
            }
            if (typeof val === 'object') {
                return JSON.stringify(val);
            }
            return String(val);
        }

        function openActivityDetailModal(data) {
            const modal = document.getElementById('activityDetailModal');
            document.getElementById('activityModalEvent').textContent = data.event || '-';
            document.getElementById('activityModalDescription').textContent = data.description || '-';
            document.getElementById('activityModalTime').textContent = data.time || '-';
            document.getElementById('activityModalActor').textContent = (data.actor || '-') + ' (' + (data.scopeLabel || '-') + ')';
            document.getElementById('activityModalModuleAction').textContent = (data.module || '-') + ' / ' + (data.action || '-');
            document.getElementById('activityModalIp').textContent = data.ip || '-';

            const subjectWrap = document.getElementById('activityModalSubjectWrap');
            const subjectEl = document.getElementById('activityModalSubject');
            if (data.subject) {
                subjectWrap.classList.remove('hidden');
                subjectEl.innerHTML = '';
                if (data.subjectUrl) {
                    const a = document.createElement('a');
                    a.href = data.subjectUrl;
                    a.className = 'text-forest dark:text-blue-400 hover:underline';
                    a.textContent = data.subject;
                    subjectEl.appendChild(a);
                } else {
                    subjectEl.textContent = data.subject;
                }
            } else {
                subjectWrap.classList.add('hidden');
            }

            const changesWrap = document.getElementById('activityModalChangesWrap');
            const changesContainer = document.getElementById('activityModalChanges');
            changesContainer.innerHTML = '';

            const propsWrap = document.getElementById('activityModalPropertiesWrap');
            const propsEl = document.getElementById('activityModalProperties');

            const properties = data.properties && typeof data.properties === 'object' ? { ...data.properties } : null;
            const changed = properties && properties.changed && typeof properties.changed === 'object'
                ? properties.changed
                : null;

            if (changed && Object.keys(changed).length > 0) {
                changesWrap.classList.remove('hidden');
                Object.values(changed).forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'p-3 bg-ivory/40 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs';

                    const labelCell = document.createElement('div');
                    labelCell.className = 'font-bold text-charcoal';
                    labelCell.textContent = item.label || '-';

                    const beforeCell = document.createElement('div');
                    beforeCell.className = 'text-muted break-words';
                    const beforeTag = document.createElement('span');
                    beforeTag.className = 'text-[10px] uppercase tracking-wider font-bold block text-rose-700 dark:text-rose-300';
                    beforeTag.textContent = 'Sebelum';
                    const beforeVal = document.createElement('span');
                    beforeVal.textContent = formatPropertyValue(item.before);
                    beforeCell.appendChild(beforeTag);
                    beforeCell.appendChild(beforeVal);

                    const afterCell = document.createElement('div');
                    afterCell.className = 'text-charcoal break-words';
                    const afterTag = document.createElement('span');
                    afterTag.className = 'text-[10px] uppercase tracking-wider font-bold block text-emerald-700 dark:text-emerald-300';
                    afterTag.textContent = 'Sesudah';
                    const afterVal = document.createElement('span');
                    afterVal.textContent = formatPropertyValue(item.after);
                    afterCell.appendChild(afterTag);
                    afterCell.appendChild(afterVal);

                    row.appendChild(labelCell);
                    row.appendChild(beforeCell);
                    row.appendChild(afterCell);
                    changesContainer.appendChild(row);
                });
                delete properties.changed;
            } else {
                changesWrap.classList.add('hidden');
            }

            if (properties && Object.keys(properties).length > 0) {
                propsWrap.classList.remove('hidden');
                propsEl.textContent = JSON.stringify(properties, null, 2);
            } else {
                propsWrap.classList.add('hidden');
            }

            const agentWrap = document.getElementById('activityModalAgentWrap');
            const agentEl = document.getElementById('activityModalAgent');
            if (data.userAgent) {
                agentWrap.classList.remove('hidden');
                agentEl.textContent = data.userAgent;
            } else {
                agentWrap.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('activityModalCloseBtn').focus();
        }

        function closeActivityDetailModal() {
            const modal = document.getElementById('activityDetailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('activityDetailModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeActivityDetailModal();
                }
            }
        });
    </script>
</x-layouts.app>
