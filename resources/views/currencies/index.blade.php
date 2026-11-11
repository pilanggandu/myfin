<x-layouts.app title="Master Mata Uang">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / MASTER DATA</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Master Mata Uang
            </h1>
            <p class="text-xs text-muted">
                Kelola daftar mata uang internasional, simbol pembukuan, dan pembaruan kurs nilai tukar real-time dari Public Currency API.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <form method="POST" action="{{ route('currencies.sync') }}">
                @csrf
                <button 
                    type="submit" 
                    class="btn-arus-secondary inline-flex items-center gap-1.5 min-h-[42px] px-4 text-xs font-bold text-forest border-forest/30 hover:bg-forest-light cursor-pointer shadow-xs"
                    title="Perbarui kurs seluruh mata uang aktif dari Public API (Frankfurter / European Central Bank)"
                >
                    <svg class="w-4 h-4 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Sinkronkan Kurs API</span>
                </button>
            </form>

            <button 
                type="button" 
                onclick="openAddCurrencyModal()" 
                class="btn-arus-primary min-h-[42px] px-4 text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>+ Tambah Mata Uang</span>
            </button>
        </div>
    </div>

    <!-- Ringkasan Metrik Mata Uang -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL MATA UANG" 
            :value="$totalCurrencies . ' Valuta'" 
            :dark="true" 
            sub="Terdaftar dalam master data" 
        />

        <x-metric-card 
            label="MATA UANG AKTIF" 
            :value="$activeCurrenciesCount . ' Aktif'" 
            :sub="($totalCurrencies - $activeCurrenciesCount) . ' mata uang nonaktif'" 
        />

        <x-metric-card 
            label="MATA UANG UTAMA" 
            :value="$defaultCurrency ? $defaultCurrency->code . ' (' . $defaultCurrency->symbol . ')' : 'IDR (Rp)'" 
            :sub="$defaultCurrency ? $defaultCurrency->name : 'Rupiah Indonesia'" 
        />

        <x-metric-card 
            label="KURS TERAKHIR SYNC" 
            :value="$lastSyncedAt ? $lastSyncedAt->format('d M Y') : 'Belum Pernah'" 
            :sub="$lastSyncedAt ? 'Pukul ' . $lastSyncedAt->format('H:i') . ' WIB' : 'Klik Sinkronkan Kurs API'" 
        />
    </div>

    <!-- Live Converter & Calculator Panel -->
    <div class="panel-card p-5 sm:p-6 mb-6 rounded-2xl border border-line bg-panel shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-line pb-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-sidebar border border-line flex items-center justify-center text-sm shadow-xs select-none">
                    💱
                </span>
                <div>
                    <h2 class="font-bold text-sm text-charcoal">Kalkulator Konversi Kurs Real-Time</h2>
                    <p class="text-[11px] text-muted">Hitung simulasi pertukaran valuta asing dengan nilai kurs master terkini.</p>
                </div>
            </div>
            <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-cream text-muted border border-line">
                Public API ECB
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-3">
                <label for="calcAmount" class="block text-xs font-bold text-charcoal mb-1">Nominal</label>
                <input 
                    type="number" 
                    id="calcAmount" 
                    value="100" 
                    min="0" 
                    step="any" 
                    class="field-input w-full text-xs font-mono font-bold"
                    oninput="runCalculator()"
                >
            </div>

            <div class="md:col-span-3">
                <label for="calcFrom" class="block text-xs font-bold text-charcoal mb-1">Dari Mata Uang</label>
                <select id="calcFrom" class="field-select w-full text-xs font-bold" onchange="runCalculator()">
                    @foreach ($allCurrencies->where('is_active', true) as $c)
                        <option value="{{ $c->code }}" {{ $c->code === 'USD' ? 'selected' : '' }}>
                            {{ $c->code }} ({{ $c->symbol }}) : {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-1 flex justify-center md:pb-1">
                <button 
                    type="button" 
                    onclick="swapCalculatorCurrencies()" 
                    class="w-11 h-11 md:w-9 md:h-9 rounded-lg border border-line bg-panel hover:bg-cream text-muted hover:text-forest transition flex items-center justify-center cursor-pointer shadow-xs"
                    title="Tukar mata uang asal dan tujuan"
                    aria-label="Tukar mata uang asal dan tujuan"
                >
                    ⇄
                </button>
            </div>

            <div class="md:col-span-3">
                <label for="calcTo" class="block text-xs font-bold text-charcoal mb-1">Ke Mata Uang</label>
                <select id="calcTo" class="field-select w-full text-xs font-bold" onchange="runCalculator()">
                    @foreach ($allCurrencies->where('is_active', true) as $c)
                        <option value="{{ $c->code }}" {{ $c->code === 'IDR' ? 'selected' : '' }}>
                            {{ $c->code }} ({{ $c->symbol }}) : {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <button 
                    type="button" 
                    onclick="runCalculator()" 
                    class="btn-arus-primary w-full min-h-[44px] md:min-h-[40px] text-xs font-bold cursor-pointer"
                >
                    Hitung Kurs
                </button>
            </div>
        </div>

        <div id="calcResultBox" class="p-3.5 rounded-xl bg-sidebar/70 border border-line flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-muted">Hasil Konversi:</span>
                <span id="calcResultDisplay" class="font-mono text-base font-bold text-forest">
                    Rp 1.813.600
                </span>
            </div>
            <div id="calcRateInfo" class="text-[11px] text-muted font-mono">
                1 USD = Rp 18.136
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="panel-card p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('currencies.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari kode (USD), nama mata uang, atau simbol..." 
                    class="field-input w-full text-xs h-10"
                >
            </div>

            <div class="w-full sm:w-48">
                <select name="status" class="field-select w-full text-xs h-10">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="btn-arus-primary h-10 px-5 text-xs font-bold flex-1 sm:flex-initial">
                    Cari
                </button>
                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ route('currencies.index') }}" class="btn-arus-secondary h-10 px-4 text-xs font-bold inline-flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Mata Uang -->
    <div class="panel-card p-0 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest">Daftar Mata Uang Master ({{ $currencies->count() }})</span>
            </div>
            <span class="text-xs text-muted">Basis Kurs: Rupiah (IDR = 1.0)</span>
        </div>

        @if ($currencies->isEmpty())
            <div class="p-10 text-center space-y-3">
                <span class="text-4xl block">💱</span>
                <div class="font-bold text-base text-charcoal">Tidak Ada Mata Uang Ditemukan</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Belum ada data mata uang yang sesuai dengan kata kunci pencarian Anda.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="openAddCurrencyModal()" class="btn-arus-primary">
                        + Tambah Mata Uang Baru
                    </button>
                </div>
            </div>
        @else
            <div class="md:hidden divide-y divide-line">
                @foreach ($currencies as $curr)
                    <div class="p-4 space-y-2.5 text-xs {{ ! $curr->is_active ? 'opacity-60' : '' }}">
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <span class="w-10 h-10 rounded-lg bg-sidebar border border-line flex items-center justify-center font-mono font-bold text-sm text-charcoal shadow-xs shrink-0">
                                    {{ $curr->symbol }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-sm text-charcoal font-mono flex items-center flex-wrap gap-1.5">
                                        <span>{{ $curr->code }}</span>
                                        @if ($curr->is_default)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/40">★ DEFAULT</span>
                                        @endif
                                        @if ($curr->is_active)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-forest-light text-forest border border-forest/20">Aktif</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">Nonaktif</span>
                                        @endif
                                    </div>
                                    <div class="text-xs font-semibold text-charcoal mt-0.5 truncate">{{ $curr->name }}</div>
                                </div>
                            </div>

                            <div class="text-right font-mono font-bold shrink-0">
                                @if ($curr->code === 'IDR')
                                    <span class="text-muted font-normal">1,00 (Basis)</span>
                                @else
                                    <span class="text-forest text-sm">Rp {{ number_format($curr->exchange_rate, 2, ',', '.') }}</span>
                                @endif
                                <div class="text-[10px] text-muted font-normal mt-0.5">{{ $curr->decimal_digits }} Digit</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-line/60">
                            <span class="text-[11px] text-muted">
                                @if ($curr->last_synced_at)
                                    Sinkron: {{ $curr->last_synced_at->format('d M Y H:i') }} WIB
                                @else
                                    Kurs Manual
                                @endif
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" onclick="openEditCurrencyModal({{ json_encode($curr) }})" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer" title="Ubah Mata Uang" aria-label="Ubah Mata Uang">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                </button>
                                @if (! $curr->is_default)
                                    <form method="POST" action="{{ route('currencies.setDefault', $curr) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-amber-500 transition inline-flex items-center justify-center cursor-pointer text-sm font-bold" title="Tetapkan sebagai Default Utama" aria-label="Tetapkan sebagai Default Utama">★</button>
                                    </form>
                                    <form method="POST" action="{{ route('currencies.toggleActive', $curr) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-charcoal transition inline-flex items-center justify-center cursor-pointer text-sm" title="{{ $curr->is_active ? 'Nonaktifkan Mata Uang' : 'Aktifkan Mata Uang' }}" aria-label="Ubah status aktif">{{ $curr->is_active ? '⏸' : '▶' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('currencies.destroy', $curr) }}" class="inline" onsubmit="return confirm('Hapus mata uang {{ $curr->code }} dari master?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-red transition inline-flex items-center justify-center cursor-pointer" title="Hapus Mata Uang" aria-label="Hapus Mata Uang">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tampilan Desktop (Table View) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 px-6">Kode & Simbol</th>
                            <th class="py-3 px-4">Nama Mata Uang</th>
                            <th class="py-3 px-4 text-right">Nilai Kurs ke IDR</th>
                            <th class="py-3 px-4 text-center">Desimal</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Sinkron Terakhir</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($currencies as $curr)
                            <tr class="hover:bg-cream/20 transition-colors {{ ! $curr->is_active ? 'opacity-60' : '' }}">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="w-9 h-9 rounded-lg bg-sidebar border border-line flex items-center justify-center font-mono font-bold text-sm text-charcoal shadow-xs">
                                            {{ $curr->symbol }}
                                        </span>
                                        <div>
                                            <div class="font-bold text-sm text-charcoal font-mono flex items-center gap-1.5">
                                                <span>{{ $curr->code }}</span>
                                                @if ($curr->is_default)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/40">
                                                        ★ DEFAULT
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-muted uppercase tracking-wider">Mata Uang</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-semibold text-charcoal">
                                    <div>{{ $curr->name }}</div>
                                </td>

                                <td class="py-4 px-4 text-right font-mono font-bold text-sm text-charcoal whitespace-nowrap">
                                    @if ($curr->code === 'IDR')
                                        <span class="text-muted font-normal">1,00 (Basis)</span>
                                    @else
                                        <span class="text-forest">Rp {{ number_format($curr->exchange_rate, 2, ',', '.') }}</span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 text-center font-mono text-muted whitespace-nowrap">
                                    {{ $curr->decimal_digits }} Digit
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($curr->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest border border-forest/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap text-muted text-[11px]">
                                    @if ($curr->last_synced_at)
                                        <div class="font-medium text-charcoal">{{ $curr->last_synced_at->format('d M Y') }}</div>
                                        <div class="text-[10px] text-muted">{{ $curr->last_synced_at->format('H:i') }} WIB</div>
                                    @else
                                        <span class="text-muted">Manual</span>
                                    @endif
                                </td>

                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <!-- Ubah Modal Trigger -->
                                        <button 
                                            type="button" 
                                            onclick="openEditCurrencyModal({{ json_encode($curr) }})" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream transition inline-flex items-center justify-center cursor-pointer"
                                            title="Ubah Mata Uang"
                                            aria-label="Ubah Mata Uang"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        <!-- Jadikan Default -->
                                        @if (! $curr->is_default)
                                            <form method="POST" action="{{ route('currencies.setDefault', $curr) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-amber-500 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 transition inline-flex items-center justify-center cursor-pointer text-xs font-bold"
                                                    title="Tetapkan sebagai Default Utama"
                                                    aria-label="Tetapkan sebagai Default Utama"
                                                >
                                                    ★
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Toggle Status Aktif -->
                                        @if (! $curr->is_default)
                                            <form method="POST" action="{{ route('currencies.toggleActive', $curr) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-charcoal hover:bg-cream transition inline-flex items-center justify-center cursor-pointer text-xs"
                                                    title="{{ $curr->is_active ? 'Nonaktifkan Mata Uang' : 'Aktifkan Mata Uang' }}"
                                                    aria-label="Ubah status aktif"
                                                >
                                                    {{ $curr->is_active ? '⏸' : '▶' }}
                                                </button>
                                            </form>

                                            <!-- Hapus -->
                                            <form method="POST" action="{{ route('currencies.destroy', $curr) }}" class="inline" onsubmit="return confirm('Hapus mata uang {{ $curr->code }} dari master?')">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Hapus Mata Uang"
                                                    aria-label="Hapus Mata Uang"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal Tambah Mata Uang -->
    <div id="addCurrencyModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        +
                    </span>
                    <div class="min-w-0">
                        <h3 id="addModalTitle" class="font-serif text-lg text-forest font-normal">
                            Tambah Mata Uang Baru
                        </h3>
                        <p class="text-[11px] text-muted">
                            Pilih dari katalog publik dunia atau isi parameter secara manual.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeAddCurrencyModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <!-- Preset Selector from Public Catalog -->
            <div class="p-3 rounded-xl bg-sidebar border border-line space-y-1.5">
                <label for="publicCatalogSelect" class="block text-[11px] font-bold text-muted uppercase">
                    Pilih Cepat dari Katalog Valuta Publik Dunia
                </label>
                <select id="publicCatalogSelect" class="field-select w-full text-xs" onchange="onCatalogSelectChange(this)">
                    <option value="">Pilih Valuta Internasional...</option>
                    @foreach ($publicCatalog as $cCode => $cName)
                        <option value="{{ $cCode }}" data-name="{{ $cName }}">
                            {{ $cCode }} : {{ $cName }}
                        </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-muted block">Memilih katalog akan mengisi kode dan nama secara otomatis.</span>
            </div>

            <form method="POST" action="{{ route('currencies.store') }}" class="space-y-3" id="addCurrencyForm">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="addCode" class="block text-xs font-bold text-charcoal mb-1">Kode ISO (3 Huruf) *</label>
                        <input 
                            type="text" 
                            name="code" 
                            id="addCode" 
                            placeholder="Contoh: USD" 
                            maxlength="3" 
                            required 
                            class="field-input w-full text-xs font-mono uppercase font-bold"
                        >
                    </div>

                    <div>
                        <label for="addSymbol" class="block text-xs font-bold text-charcoal mb-1">Simbol *</label>
                        <input 
                            type="text" 
                            name="symbol" 
                            id="addSymbol" 
                            placeholder="Contoh: $" 
                            maxlength="10" 
                            required 
                            class="field-input w-full text-xs font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label for="addName" class="block text-xs font-bold text-charcoal mb-1">Nama Mata Uang *</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="addName" 
                        placeholder="Contoh: US Dollar" 
                        maxlength="100" 
                        required 
                        class="field-input w-full text-xs"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="addRate" class="block text-xs font-bold text-charcoal mb-1">
                            Kurs ke Rupiah (Opsional)
                        </label>
                        <input 
                            type="number" 
                            name="exchange_rate" 
                            id="addRate" 
                            placeholder="Otomatis dari API" 
                            step="any" 
                            class="field-input w-full text-xs font-mono"
                        >
                        <span class="text-[10px] text-muted block mt-0.5">Biarkan kosong untuk auto-fetch dari API.</span>
                    </div>

                    <div>
                        <label for="addDecimals" class="block text-xs font-bold text-charcoal mb-1">Digit Desimal</label>
                        <select name="decimal_digits" id="addDecimals" class="field-select w-full text-xs font-mono">
                            <option value="0">0 (Contoh: IDR, JPY)</option>
                            <option value="2" selected>2 (Contoh: USD, EUR, SGD)</option>
                            <option value="3">3 (Contoh: BHD, KWD)</option>
                            <option value="4">4 Digit</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2 border-t border-line space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Aktifkan mata uang ini langsung</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Jadikan mata uang default utama sistem</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeAddCurrencyModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        Simpan Mata Uang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ubah Mata Uang -->
    <div id="editCurrencyModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        ✏️
                    </span>
                    <div class="min-w-0">
                        <h3 id="editModalTitle" class="font-serif text-lg text-forest font-normal">
                            Ubah Data Mata Uang
                        </h3>
                        <p class="text-[11px] text-muted">
                            Sesuaikan parameter nama, simbol, kurs, dan status aktif.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeEditCurrencyModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <form method="POST" id="editCurrencyForm" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editCode" class="block text-xs font-bold text-charcoal mb-1">Kode ISO (3 Huruf) *</label>
                        <input 
                            type="text" 
                            name="code" 
                            id="editCode" 
                            maxlength="3" 
                            required 
                            class="field-input w-full text-xs font-mono uppercase font-bold"
                        >
                    </div>

                    <div>
                        <label for="editSymbol" class="block text-xs font-bold text-charcoal mb-1">Simbol *</label>
                        <input 
                            type="text" 
                            name="symbol" 
                            id="editSymbol" 
                            maxlength="10" 
                            required 
                            class="field-input w-full text-xs font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label for="editName" class="block text-xs font-bold text-charcoal mb-1">Nama Mata Uang *</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="editName" 
                        maxlength="100" 
                        required 
                        class="field-input w-full text-xs"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editRate" class="block text-xs font-bold text-charcoal mb-1">
                            Kurs Nilai Tukar ke Rupiah *
                        </label>
                        <input 
                            type="number" 
                            name="exchange_rate" 
                            id="editRate" 
                            step="any" 
                            required 
                            class="field-input w-full text-xs font-mono font-bold"
                        >
                    </div>

                    <div>
                        <label for="editDecimals" class="block text-xs font-bold text-charcoal mb-1">Digit Desimal</label>
                        <select name="decimal_digits" id="editDecimals" class="field-select w-full text-xs font-mono">
                            <option value="0">0 Digit (IDR, JPY)</option>
                            <option value="2">2 Digit (USD, EUR, SGD)</option>
                            <option value="3">3 Digit</option>
                            <option value="4">4 Digit</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2 border-t border-line space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal" id="editActiveLabel">
                        <input type="checkbox" name="is_active" value="1" id="editIsActive" class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Mata uang ini aktif untuk transaksi</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal" id="editDefaultLabel">
                        <input type="checkbox" name="is_default" value="1" id="editIsDefault" class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Jadikan mata uang default utama sistem</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeEditCurrencyModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        Perbarui Mata Uang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Client Script -->
    <script>
        const currencySymbols = {
            USD: '$', EUR: '€', GBP: '£', JPY: '¥', SGD: 'S$',
            MYR: 'RM', AUD: 'A$', CNY: '¥', SAR: 'SR', CAD: 'C$',
            CHF: 'CHF', IDR: 'Rp', HKD: 'HK$', NZD: 'NZ$', THB: '฿'
        };

        function onCatalogSelectChange(select) {
            const code = select.value;
            if (!code) return;
            const opt = select.options[select.selectedIndex];
            const name = opt.getAttribute('data-name') || '';

            document.getElementById('addCode').value = code;
            document.getElementById('addName').value = name;
            document.getElementById('addSymbol').value = currencySymbols[code] || '$';
            document.getElementById('addDecimals').value = (code === 'IDR' || code === 'JPY') ? '0' : '2';
        }

        function openAddCurrencyModal() {
            document.getElementById('addCurrencyModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => document.getElementById('addCode').focus(), 100);
        }

        function closeAddCurrencyModal() {
            document.getElementById('addCurrencyModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openEditCurrencyModal(currency) {
            const form = document.getElementById('editCurrencyForm');
            form.action = '/currencies/' + currency.id;

            document.getElementById('editCode').value = currency.code;
            document.getElementById('editName').value = currency.name;
            document.getElementById('editSymbol').value = currency.symbol;
            document.getElementById('editRate').value = currency.exchange_rate;
            document.getElementById('editDecimals').value = currency.decimal_digits;
            document.getElementById('editIsActive').checked = Boolean(currency.is_active);
            document.getElementById('editIsDefault').checked = Boolean(currency.is_default);

            if (currency.is_default) {
                document.getElementById('editIsActive').disabled = true;
                document.getElementById('editIsDefault').disabled = true;
            } else {
                document.getElementById('editIsActive').disabled = false;
                document.getElementById('editIsDefault').disabled = false;
            }

            document.getElementById('editCurrencyModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditCurrencyModal() {
            document.getElementById('editCurrencyModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function swapCalculatorCurrencies() {
            const from = document.getElementById('calcFrom');
            const to = document.getElementById('calcTo');
            const tmp = from.value;
            from.value = to.value;
            to.value = tmp;
            runCalculator();
        }

        async function runCalculator() {
            const amount = parseFloat(document.getElementById('calcAmount').value) || 0;
            const from = document.getElementById('calcFrom').value;
            const to = document.getElementById('calcTo').value;

            if (amount < 0 || !from || !to) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch('{{ route("currencies.convert") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ amount, from, to })
                });

                const data = await response.json();
                if (data.success) {
                    document.getElementById('calcResultDisplay').textContent = data.formatted_result;
                    document.getElementById('calcRateInfo').textContent = `1 ${from} = ${(1 * data.exchange_rate).toLocaleString('id-ID', { maximumFractionDigits: 6 })} ${to}`;
                }
            } catch (e) {
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddCurrencyModal();
                closeEditCurrencyModal();
            }
        });
    </script>
</x-layouts.app>
