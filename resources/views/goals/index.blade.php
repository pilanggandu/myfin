<x-layouts.app title="Target Tabungan & Bersama">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / PERENCANAAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Target Tabungan & Bersama
            </h1>
            <p class="text-xs text-muted">
                Rencanakan dana impian, tabungan darurat, dan kolaborasi menabung di ruang <strong>{{ $space?->name }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'goals-metrics\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal Ringkasan Target"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>

            <a href="{{ route('goals.create') }}" class="btn-arus-primary text-xs">
                + Buat Target Baru
            </a>
        </div>
    </div>

    <!-- Ringkasan Metrik Target -->
    <div data-privacy-scope="goals-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL TARGET TABUNGAN" 
            :value="'Rp ' . number_format($totalTargetAmount, 0, ',', '.')" 
            :dark="true" 
            :sub="$activeCount . ' target aktif'" 
        />

        <x-metric-card 
            label="TOTAL DANA TERKUMPUL" 
            :value="'Rp ' . number_format($totalCurrentAmount, 0, ',', '.')" 
            :sub="'Tersimpan dari target aktif'" 
        />

        <x-metric-card 
            label="RATA-RATA PENCAPAIAN" 
            :value="$overallProgress . '%'" 
            :sub="$overallProgress >= 100 ? 'Semua target tercapai!' : 'Progres tabungan berjalan'" 
        />

        <x-metric-card 
            label="TARGET SELESAI" 
            :value="$achievedCount . ' Target'" 
            sub="Pencapaian berhasil terlaksana" 
        />
    </div>

    <!-- Filter Status Tabs -->
    <div class="flex items-center gap-1.5 mb-6 overflow-x-auto pb-1">
        <a 
            href="{{ route('goals.index', ['status' => 'active']) }}" 
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'active' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}"
        >
            Aktif ({{ $activeCount }})
        </a>
        <a 
            href="{{ route('goals.index', ['status' => 'achieved']) }}" 
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'achieved' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}"
        >
            Tercapai 🎉 ({{ $achievedCount }})
        </a>
        <a 
            href="{{ route('goals.index', ['status' => 'paused']) }}" 
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'paused' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}"
        >
            Dijeda
        </a>
        <a 
            href="{{ route('goals.index', ['status' => 'all']) }}" 
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $status === 'all' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}"
        >
            Semua
        </a>
    </div>

    <!-- Grid Target Cards -->
    @if ($goals->isEmpty())
        <div class="panel-card p-12 text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-full bg-cream dark:bg-line/30 flex items-center justify-center text-3xl">
                🎯
            </div>
            <div class="font-bold text-base text-charcoal">Belum Ada Target Tabungan</div>
            <p class="text-xs text-muted max-w-md mx-auto">
                Rencanakan tujuan keuangan Anda seperti dana darurat, liburan impian, DP rumah, atau gadget baru bersama keluarga di ARUS.
            </p>
            <div class="pt-2">
                <a href="{{ route('goals.create') }}" class="btn-arus-primary inline-flex text-xs">
                    + Buat Target Tabungan Pertama
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($goals as $goal)
                @php
                    $pct = $goal->percentage();
                    $rem = $goal->remainingAmount();
                    $days = $goal->daysRemaining($now);
                    $monthlyRec = $goal->monthlyRecommendation($now);
                    $isAchieved = $goal->isAchieved();
                    $isPaused = $goal->isPaused();
                @endphp

                <div class="panel-card p-0 overflow-hidden flex flex-col justify-between border border-line hover:border-forest/50 transition duration-200 group">
                    <div>
                        <!-- Header Card dengan Banner Warna -->
                        <div class="h-2 w-full" style="background-color: {{ $goal->color ?: '#174C40' }};"></div>
                        
                        <div class="p-5 sm:p-6 pb-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-cream dark:bg-line/40 flex items-center justify-center text-2xl flex-shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                                        {{ $goal->icon ?: '🎯' }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-base text-charcoal truncate group-hover:text-forest transition" title="{{ $goal->name }}">
                                            <a href="{{ route('goals.show', $goal) }}">
                                                {{ $goal->name }}
                                            </a>
                                        </h3>
                                        @if ($goal->account)
                                            <div class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                                <span>💳</span>
                                                <span class="truncate">{{ $goal->account->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    @if ($isAchieved)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-forest/15 text-forest border border-forest/30 whitespace-nowrap">
                                            🎉 Tercapai
                                        </span>
                                    @elseif ($isPaused)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-muted/20 text-muted whitespace-nowrap">
                                            Dijeda
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-forest/10 text-forest border border-forest/20 whitespace-nowrap">
                                            Aktif
                                        </span>
                                    @endif
                                    <button 
                                        type="button" 
                                        onclick="toggleCardPrivacy(this)" 
                                        class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                                        title="Sembunyikan / Tampilkan Saldo Target Ini"
                                    >
                                        <span class="privacy-toggle-icon"></span>
                                    </button>
                                </div>
                            </div>

                            @if ($goal->notes)
                                <p class="text-xs text-muted line-clamp-2 mb-4">
                                    {{ $goal->notes }}
                                </p>
                            @endif

                            <!-- Nominal Progress -->
                            <div class="mt-4 pt-3 border-t border-line/60">
                                <div class="flex items-baseline justify-between gap-2 mb-1.5">
                                    <div class="text-xs text-muted">
                                        Terkumpul:
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono text-base font-bold text-forest">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($goal->current_amount, 0, ',', '.') }}">
                                                Rp {{ number_format($goal->current_amount, 0, ',', '.') }}
                                            </span>
                                        </span>
                                        <span class="text-[11px] text-muted"> / <span class="privacy-balance" data-raw-value="Rp {{ number_format($goal->target_amount, 0, ',', '.') }}">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</span></span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full h-2.5 rounded-full bg-cream dark:bg-line/60 overflow-hidden mb-2">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500" 
                                        style="width: {{ $pct }}%; background-color: {{ $goal->color ?: '#174C40' }};"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between text-xs text-muted">
                                    <span class="font-bold font-mono text-charcoal">{{ $pct }}%</span>
                                    @if (! $isAchieved)
                                        <span>Sisa: <strong class="font-mono text-charcoal"><span class="privacy-balance" data-raw-value="Rp {{ number_format($rem, 0, ',', '.') }}">Rp {{ number_format($rem, 0, ',', '.') }}</span></strong></span>
                                    @else
                                        <span class="text-forest font-bold">100% Tercapai!</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Info Rekomendasi & Tanggal Target -->
                            <div class="mt-4 pt-3 border-t border-line/60 flex items-center justify-between text-xs text-muted">
                                @if ($goal->target_date)
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-muted/80">Tenggat Target</div>
                                        <div class="font-semibold text-charcoal text-[11px] mt-0.5">
                                            {{ $goal->target_date->translatedFormat('d M Y') }}
                                            @if ($days !== null && ! $isAchieved)
                                                <span class="text-[10px] text-muted">({{ $days > 0 ? $days . ' hari lagi' : 'Hari ini' }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-muted/80">Tipe Target</div>
                                        <div class="font-semibold text-charcoal text-[11px] mt-0.5">Fleksibel (Tanpa tenggat)</div>
                                    </div>
                                @endif

                                @if ($monthlyRec !== null && ! $isAchieved)
                                    <div class="text-right">
                                        <div class="text-[10px] uppercase font-bold text-muted/80">Saran Menabung</div>
                                        <div class="font-mono font-bold text-forest text-[11px] mt-0.5">
                                            <span class="privacy-balance" data-raw-value="Rp {{ number_format($monthlyRec, 0, ',', '.') }}">Rp {{ number_format($monthlyRec, 0, ',', '.') }}</span> <span class="font-sans text-[10px] text-muted">/ bln</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-3 bg-cream/20 border-t border-line flex items-center justify-between gap-2">
                        <a href="{{ route('goals.show', $goal) }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1">
                            <span>Detail & Riwayat</span>
                            <span>&rarr;</span>
                        </a>

                        @if (! $isAchieved)
                            <button 
                                type="button" 
                                onclick="openContributeModal({{ $goal->id }}, '{{ addslashes($goal->name) }}', {{ $rem }})" 
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest-hover transition cursor-pointer"
                            >
                                + Setor Tabungan
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Modal Setor Tabungan Cepat -->
    <div id="contributeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 hidden">
        <div class="panel-card w-full max-w-md p-6 bg-panel rounded-2xl shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h3 class="font-bold text-base text-forest">Setor Tabungan</h3>
                    <p class="text-xs text-muted" id="modalGoalNameSubtitle">Target Tabungan</p>
                </div>
                <button type="button" onclick="closeContributeModal()" class="text-muted hover:text-charcoal p-1 rounded">
                    &times;
                </button>
            </div>

            <form id="contributeForm" method="POST" action="">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Nominal Setoran (Rp) <span class="text-red">*</span></label>
                        <input 
                            type="text" 
                            name="amount" 
                            id="contributeAmountInput" 
                            required 
                            placeholder="Contoh: 500000" 
                            class="field-input h-10 px-3 text-sm font-mono font-bold w-full"
                        />
                        <span class="text-[10px] text-muted" id="modalRemainingHint"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Tanggal Setoran <span class="text-red">*</span></label>
                        <input 
                            type="date" 
                            name="contribution_date" 
                            value="{{ now()->toDateString() }}" 
                            required 
                            class="field-input h-10 px-3 text-xs w-full"
                        />
                    </div>

                    @if ($space && $space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet', 'investment'])->count() > 0)
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Rekening Sumber (Opsional)</label>
                            <select name="account_id" class="field-select h-10 px-3 text-xs w-full">
                                <option value="">Tanpa Rekening Kas (Pencatatan Alokasi Saja)</option>
                                @foreach ($space->accounts()->whereNull('archived_at')->whereIn('type', ['bank', 'cash', 'e-wallet', 'investment'])->get() as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer text-xs text-charcoal">
                                <input type="checkbox" name="deduct_from_account" value="1" checked class="rounded border-line text-forest focus:ring-forest" />
                                <span>Potong saldo rekening kas di atas otomatis sebagai transaksi</span>
                            </label>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Catatan Setoran (Opsional)</label>
                        <input 
                            type="text" 
                            name="notes" 
                            placeholder="Contoh: Tabungan dari gaji bulanan" 
                            class="field-input h-10 px-3 text-xs w-full"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-line">
                    <button type="button" onclick="closeContributeModal()" class="btn-arus-secondary text-xs h-9 px-4">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary text-xs h-9 px-4">
                        Simpan Setoran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openContributeModal(goalId, goalName, remaining) {
            document.getElementById('contributeForm').action = '/goals/' + goalId + '/contribute';
            document.getElementById('modalGoalNameSubtitle').textContent = 'Target: ' + goalName;
            document.getElementById('modalRemainingHint').textContent = 'Sisa dana menuju target: Rp ' + remaining.toLocaleString('id-ID');
            document.getElementById('contributeAmountInput').value = '';
            document.getElementById('contributeModal').classList.remove('hidden');
        }

        function closeContributeModal() {
            document.getElementById('contributeModal').classList.add('hidden');
        }
    </script>
</x-layouts.app>
