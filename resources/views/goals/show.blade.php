<x-layouts.app title="{{ $goal->name }} - Detail Target Tabungan">
    @php
        $pct = $goal->percentage();
        $rem = $goal->remainingAmount();
        $days = $goal->daysRemaining($now);
        $monthlyRec = $goal->monthlyRecommendation($now);
        $dailyRec = $goal->dailyRecommendation($now);
        $isAchieved = $goal->isAchieved();
        $isPaused = $goal->isPaused();
    @endphp

    <!-- Top Action Bar & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('goals.index') }}" class="w-9 h-9 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 flex items-center justify-center text-muted hover:text-charcoal transition" title="Kembali ke Daftar Target">
                &larr;
            </a>
            <div>
                <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / TARGET FINANSIAL</div>
                <div class="flex items-center gap-2 mt-0.5">
                    <h1 class="font-serif text-2xl sm:text-3xl text-forest font-normal tracking-tight">
                        {{ $goal->name }}
                    </h1>
                    @if ($isAchieved)
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-forest/15 text-forest border border-forest/30">
                            🎉 Tercapai
                        </span>
                    @elseif ($isPaused)
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-muted/20 text-muted">
                            Dijeda
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-forest/10 text-forest border border-forest/20">
                            Aktif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button 
                type="button" 
                onclick="toggleBalanceVisibility()" 
                class="privacy-toggle-btn inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Nominal"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Sembunyikan Saldo</span>
            </button>

            <!-- Toggle Pause/Active -->
            <form method="POST" action="{{ route('goals.toggle-status', $goal) }}">
                @csrf
                <button type="submit" class="btn-arus-secondary text-xs h-9 px-3">
                    {{ $isPaused ? '▶️ Lanjutkan' : '⏸️ Jeda' }}
                </button>
            </form>

            <a href="{{ route('goals.edit', $goal) }}" class="btn-arus-secondary text-xs h-9 px-3">
                Edit Target
            </a>

            <!-- Delete Form -->
            <form method="POST" action="{{ route('goals.destroy', $goal) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target tabungan ini? Riwayat setoran juga akan dihapus.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="h-9 px-3 rounded-lg border border-red/30 text-red hover:bg-red/10 text-xs font-bold transition cursor-pointer">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- Hero Card Progres Target Tabungan -->
    <div class="panel-card p-0 overflow-hidden mb-6 border border-line shadow-sm" data-privacy-scope="goal-hero">
        <div class="h-3 w-full" style="background-color: {{ $goal->color ?: '#174C40' }};"></div>

        <div class="p-6 sm:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                <!-- Sisi Kiri: Ikon & Angka Progres (2 cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-cream dark:bg-line/40 flex items-center justify-center text-4xl shadow-sm flex-shrink-0">
                            {{ $goal->icon ?: '🎯' }}
                        </div>
                        <div>
                            <div class="text-xs text-muted flex items-center gap-2">
                                <span>Total Terkumpul Saat Ini:</span>
                                <button 
                                    type="button" 
                                    onclick="toggleCardPrivacy(this)" 
                                    class="privacy-card-toggle w-5 h-5 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-[10px]" 
                                    title="Sembunyikan / Tampilkan Saldo Target Ini"
                                >
                                    <span class="privacy-toggle-icon"></span>
                                </button>
                            </div>
                            <div class="font-mono text-3xl sm:text-4xl font-bold text-forest">
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($goal->current_amount, 0, ',', '.') }}">
                                    Rp {{ number_format($goal->current_amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-xs text-muted mt-0.5">
                                dari sasaran <strong class="text-charcoal font-mono"><span class="privacy-balance" data-raw-value="Rp {{ number_format($goal->target_amount, 0, ',', '.') }}">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar Besar -->
                    <div class="space-y-1.5 pt-2">
                        <div class="w-full h-4 rounded-full bg-cream dark:bg-line/60 overflow-hidden shadow-inner">
                            <div 
                                class="h-full rounded-full transition-all duration-700" 
                                style="width: {{ $pct }}%; background-color: {{ $goal->color ?: '#174C40' }};"
                            ></div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold font-mono text-charcoal text-sm">{{ $pct }}% Tercapai</span>
                            @if (! $isAchieved)
                                <span class="text-muted">Kurang: <strong class="font-mono text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($rem, 0, ',', '.') }}">Rp {{ number_format($rem, 0, ',', '.') }}</strong></span>
                            @else
                                <span class="font-bold text-forest">Target Berhasil Dipenuhi! 🎊</span>
                            @endif
                        </div>
                    </div>

                    @if ($goal->notes)
                        <div class="p-3.5 rounded-xl bg-cream/30 dark:bg-line/25 border border-line text-xs text-muted leading-relaxed">
                            <strong class="text-charcoal font-semibold block mb-0.5">Catatan Rencana:</strong>
                            {{ $goal->notes }}
                        </div>
                    @endif
                </div>

                <!-- Sisi Kanan: Panel Rekomendasi & Tombol Aksi (1 col) -->
                <div class="p-5 rounded-2xl bg-cream/40 dark:bg-line/20 border border-line space-y-4">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-line">
                            <span class="text-muted">Tanggal Mulai:</span>
                            <span class="font-bold text-charcoal">{{ $goal->start_date->translatedFormat('d M Y') }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pb-2 border-b border-line">
                            <span class="text-muted">Tenggat Target:</span>
                            @if ($goal->target_date)
                                <span class="font-bold text-charcoal">
                                    {{ $goal->target_date->translatedFormat('d M Y') }}
                                    @if ($days !== null && ! $isAchieved)
                                        <span class="text-[10px] text-muted">({{ $days > 0 ? $days . ' hari lagi' : 'Hari ini' }})</span>
                                    @endif
                                </span>
                            @else
                                <span class="font-bold text-charcoal">Fleksibel</span>
                            @endif
                        </div>

                        @if ($goal->account)
                            <div class="flex items-center justify-between text-xs pb-2 border-b border-line">
                                <span class="text-muted">Rekening Penampung:</span>
                                <span class="font-bold text-charcoal">💳 {{ $goal->account->name }}</span>
                            </div>
                        @endif

                        @if ($monthlyRec !== null && ! $isAchieved)
                            <div class="p-3 rounded-xl bg-forest-light/60 dark:bg-forest-light/20 border border-forest/20 text-xs">
                                <span class="text-[10px] uppercase font-bold text-forest block mb-0.5">Rekomendasi Cerdas</span>
                                <div class="font-mono text-base font-bold text-forest">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($monthlyRec, 0, ',', '.') }}">Rp {{ number_format($monthlyRec, 0, ',', '.') }}</span> <span class="font-sans text-xs text-muted font-normal">/ bln</span>
                                </div>
                                @if ($dailyRec !== null)
                                    <span class="text-[10px] text-muted block mt-0.5">
                                        atau sekitar ~<span class="privacy-balance" data-raw-value="Rp {{ number_format($dailyRec, 0, ',', '.') }}">Rp {{ number_format($dailyRec, 0, ',', '.') }}</span> / hari
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Tombol Aksi Setor & Tarik -->
                    <div class="flex flex-col gap-2 pt-1">
                        <button 
                            type="button" 
                            onclick="openDepositModal()" 
                            class="w-full btn-arus-primary justify-center text-xs h-10 cursor-pointer"
                        >
                            + Setor Tabungan
                        </button>

                        @if ($goal->current_amount > 0)
                            <button 
                                type="button" 
                                onclick="openWithdrawModal()" 
                                class="w-full btn-arus-secondary justify-center text-xs h-9 text-muted hover:text-charcoal cursor-pointer"
                            >
                                Tarik / Cairkan Dana
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolaborasi Kontributor (Shared Space / Anggota) -->
    @if ($memberBreakdown->isNotEmpty() && ($memberBreakdown->count() > 1 || $space?->isShared()))
        <div class="panel-card p-5 sm:p-6 mb-6">
            <div class="flex items-center justify-between mb-4 border-b border-line pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">🤝</span>
                    <h2 class="text-sm font-bold text-forest">Kolaborasi Tabungan Bersama</h2>
                </div>
                <span class="text-xs text-muted">{{ $memberBreakdown->count() }} kontributor aktif</span>
            </div>

            <!-- Progres Kontribusi per Anggota -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($memberBreakdown as $mb)
                    <div class="p-3.5 rounded-xl bg-cream/30 dark:bg-line/25 border border-line flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background-color: {{ $mb->color }};">
                                {{ strtoupper(substr($mb->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <span class="font-bold text-xs text-charcoal block truncate">{{ $mb->name }}</span>
                                <span class="text-[10px] text-muted">{{ $mb->count }} kali menyetor</span>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <div class="font-mono text-xs font-bold text-forest">
                                <span class="privacy-balance" data-raw-value="Rp {{ number_format($mb->amount, 0, ',', '.') }}">
                                    Rp {{ number_format($mb->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <span class="text-[10px] font-bold text-muted">{{ $mb->percentage }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Riwayat Transaksi Mutasi Target (Setoran & Penarikan) -->
    <div class="panel-card p-0 overflow-hidden" data-privacy-scope="goal-history">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-forest">
                    Riwayat Setoran & Pencairan ({{ $goal->contributions->count() }})
                </h2>
                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                    title="Sembunyikan / Tampilkan Mutasi Target"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>
            <span class="text-xs text-muted">Urutan mutasi tabungan</span>
        </div>

        @if ($goal->contributions->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada setoran tabungan untuk target ini. Klik tombol <strong>+ Setor Tabungan</strong> untuk mulai menabung.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[10px] tracking-wider">
                            <th class="py-2.5 px-5">Tanggal</th>
                            <th class="py-2.5 px-4">Tipe</th>
                            <th class="py-2.5 px-4">Kontributor</th>
                            <th class="py-2.5 px-4">Catatan</th>
                            <th class="py-2.5 px-5 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($goal->contributions as $item)
                            @php
                                $isDep = $item->isDeposit();
                            @endphp
                            <tr class="hover:bg-cream/20 dark:hover:bg-line/20 transition-colors">
                                <td class="py-3 px-5 whitespace-nowrap text-muted">
                                    <div class="font-medium text-charcoal">{{ $item->contribution_date->translatedFormat('d M Y') }}</div>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($isDep)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-forest/10 text-forest">
                                            SETORAN
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red/10 text-red dark:bg-red/25 dark:text-rose-400">
                                            PENARIKAN
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($item->member)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background-color: {{ $item->member->color ?: '#0f4c3a' }};">
                                            {{ $item->member->name }}
                                        </span>
                                    @elseif ($item->user)
                                        <span class="font-semibold text-charcoal">{{ $item->user->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    <div class="text-charcoal">{{ $item->notes ?: '-' }}</div>
                                    @if ($item->financialTransaction)
                                        <div class="text-[10px] text-muted flex items-center gap-1 mt-0.5">
                                            <span>🔗 Terhubung mutasi kas:</span>
                                            <a href="{{ route('transactions.show', $item->financialTransaction) }}" class="text-forest hover:underline">
                                                Lihat Transaksi
                                            </a>
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3 px-5 whitespace-nowrap text-right font-mono font-bold {{ $isDep ? 'text-forest' : 'text-red dark:text-rose-400' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $isDep ? '+' : '-' }}Rp {{ number_format($item->amount, 0, ',', '.') }}">
                                        {{ $isDep ? '+' : '-' }}Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal Setor Tabungan -->
    <div id="depositModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 hidden">
        <div class="panel-card w-full max-w-md p-6 bg-panel rounded-2xl shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <h3 class="font-bold text-base text-forest">Setor Tabungan</h3>
                <button type="button" onclick="closeDepositModal()" class="text-muted hover:text-charcoal p-1 rounded">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('goals.contribute', $goal) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Nominal Setoran (Rp) <span class="text-red">*</span></label>
                        <input 
                            type="text" 
                            name="amount" 
                            required 
                            placeholder="Contoh: 500000" 
                            class="field-input h-10 px-3 text-sm font-mono font-bold w-full"
                        />
                        <span class="text-[10px] text-muted">Sisa dana target: Rp {{ number_format($rem, 0, ',', '.') }}</span>
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

                    @if ($space && $space->isShared() && $members->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Anggota yang Menabung (Opsional)</label>
                            <select name="member_id" class="field-select h-10 px-3 text-xs w-full">
                                <option value="">Atas Nama Saya ({{ auth()->user()->name }})</option>
                                @foreach ($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($cashAccounts->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Rekening Kas Sumber (Opsional)</label>
                            <select name="account_id" class="field-select h-10 px-3 text-xs w-full">
                                <option value="">Tanpa Potong Rekening (Alokasi Pencatatan Saja)</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ $goal->account_id == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer text-xs text-charcoal">
                                <input type="checkbox" name="deduct_from_account" value="1" checked class="rounded border-line text-forest focus:ring-forest" />
                                <span>Potong saldo rekening kas di atas otomatis sebagai pengeluaran/alokasi</span>
                            </label>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Catatan Setoran (Opsional)</label>
                        <input 
                            type="text" 
                            name="notes" 
                            placeholder="Contoh: Tabungan dari bonus mingguan" 
                            class="field-input h-10 px-3 text-xs w-full"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-line">
                    <button type="button" onclick="closeDepositModal()" class="btn-arus-secondary text-xs h-9 px-4">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary text-xs h-9 px-4">
                        Simpan Setoran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tarik / Cairkan Tabungan -->
    <div id="withdrawModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 hidden">
        <div class="panel-card w-full max-w-md p-6 bg-panel rounded-2xl shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <h3 class="font-bold text-base text-red dark:text-rose-400">Tarik / Cairkan Dana</h3>
                <button type="button" onclick="closeWithdrawModal()" class="text-muted hover:text-charcoal p-1 rounded">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('goals.withdraw', $goal) }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Nominal Penarikan (Rp) <span class="text-red">*</span></label>
                        <input 
                            type="text" 
                            name="amount" 
                            required 
                            placeholder="Maksimal: {{ (int)$goal->current_amount }}" 
                            class="field-input h-10 px-3 text-sm font-mono font-bold w-full"
                        />
                        <span class="text-[10px] text-muted">Saldo tersedia saat ini: Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Tanggal Penarikan <span class="text-red">*</span></label>
                        <input 
                            type="date" 
                            name="withdrawal_date" 
                            value="{{ now()->toDateString() }}" 
                            required 
                            class="field-input h-10 px-3 text-xs w-full"
                        />
                    </div>

                    @if ($cashAccounts->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Rekening Tujuan Pencairan (Opsional)</label>
                            <select name="account_id" class="field-select h-10 px-3 text-xs w-full">
                                <option value="">Tanpa Rekening Kas (Pencatatan Saja)</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ $goal->account_id == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Saldo: Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer text-xs text-charcoal">
                                <input type="checkbox" name="deposit_to_account" value="1" checked class="rounded border-line text-forest focus:ring-forest" />
                                <span>Tambah saldo rekening kas di atas otomatis sebagai pemasukan</span>
                            </label>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Catatan Penarikan (Opsional)</label>
                        <input 
                            type="text" 
                            name="notes" 
                            placeholder="Contoh: Pencairan untuk bayar DP" 
                            class="field-input h-10 px-3 text-xs w-full"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-line">
                    <button type="button" onclick="closeWithdrawModal()" class="btn-arus-secondary text-xs h-9 px-4">
                        Batal
                    </button>
                    <button type="submit" class="h-9 px-4 rounded-lg bg-red text-white hover:bg-red/90 text-xs font-bold transition cursor-pointer">
                        Konfirmasi Penarikan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDepositModal() {
            document.getElementById('depositModal').classList.remove('hidden');
        }

        function closeDepositModal() {
            document.getElementById('depositModal').classList.add('hidden');
        }

        function openWithdrawModal() {
            document.getElementById('withdrawModal').classList.remove('hidden');
        }

        function closeWithdrawModal() {
            document.getElementById('withdrawModal').classList.add('hidden');
        }
    </script>
</x-layouts.app>
