<x-layouts.app title="Anggaran Bulanan">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / ANGGARAN</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Anggaran Pengeluaran
            </h1>
            <p class="text-xs text-muted">
                Rencana batas pengeluaran per kategori untuk periode <strong>{{ $periodDate->translatedFormat('F Y') }}</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Period Selector Form -->
            <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-2">
                <select name="month" class="field-select text-xs h-10 py-1.5 min-w-[120px]" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>

                <select name="year" class="field-select text-xs h-10 py-1.5 min-w-[90px]" onchange="this.form.submit()">
                    @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </form>

            <button 
                type="button" 
                onclick="toggleCardPrivacy(this, '[data-privacy-scope=\'budgets-metrics\']')" 
                class="privacy-card-toggle inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-panel hover:bg-cream dark:hover:bg-line/40 text-xs font-semibold text-charcoal shadow-sm transition-colors cursor-pointer"
                title="Sembunyikan / Tampilkan Saldo Ringkasan Anggaran"
            >
                <span class="privacy-toggle-icon text-muted"></span>
                <span class="privacy-toggle-text">Nominal Ringkasan</span>
            </button>

            @if ($availableCategories->isNotEmpty())
                <button type="button" onclick="document.getElementById('add-budget-modal').classList.remove('hidden')" class="btn-arus-primary whitespace-nowrap text-xs">
                    + Pasang Anggaran
                </button>
            @endif
        </div>
    </div>

    <!-- Metric Overview Cards -->
    <div data-privacy-scope="budgets-metrics" class="privacy-scope grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="TOTAL BATAS ANGGARAN" 
            :value="'Rp ' . number_format($totalBudget, 0, ',', '.')" 
            :dark="true" 
            :sub="$budgets->count() . ' kategori ditetapkan'" 
        />

        <x-metric-card 
            label="REALISASI PENGELUARAN" 
            :value="'Rp ' . number_format($totalSpent, 0, ',', '.')" 
            :sub="$overallPercentage . '% dari total limit'" 
        />

        <x-metric-card 
            label="SISA ANGGARAN" 
            :value="($totalRemaining < 0 ? '-' : '') . 'Rp ' . number_format(abs($totalRemaining), 0, ',', '.')" 
            :sub="$totalRemaining >= 0 ? 'Tersedia untuk digunakan' : 'Defisit melebihi limit'" 
        />

        <x-metric-card 
            label="STATUS KONTROL" 
            :value="$overBudgetCount === 0 ? 'Terkendali' : $overBudgetCount . ' Melebihi Batas'" 
            :sub="$overBudgetCount === 0 ? 'Semua kategori dalam batas aman' : 'Evaluasi pengeluaran Anda'" 
        />
    </div>

    <!-- Budgets List Panel -->
    <div class="panel-card p-0 overflow-hidden mb-6" data-privacy-scope="budgets-list">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-forest">Rincian Anggaran Kategori ({{ $periodDate->translatedFormat('F Y') }})</span>
                <button 
                    type="button" 
                    onclick="toggleCardPrivacy(this)" 
                    class="privacy-card-toggle w-6 h-6 rounded border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer text-xs" 
                    title="Sembunyikan / Tampilkan Nominal Anggaran Kategori"
                >
                    <span class="privacy-toggle-icon"></span>
                </button>
            </div>
            <span class="text-xs text-muted">{{ $budgets->count() }} kategori beranggaran</span>
        </div>

        @if ($budgets->isEmpty())
            <div class="p-8 text-center text-xs text-muted">
                Belum ada anggaran pengeluaran yang ditetapkan untuk periode {{ $periodDate->translatedFormat('F Y') }}.
                @if ($availableCategories->isNotEmpty())
                    <div class="mt-3">
                        <button type="button" onclick="document.getElementById('add-budget-modal').classList.remove('hidden')" class="btn-arus-primary inline-flex text-xs">
                            Tetapkan Anggaran Pertama
                        </button>
                    </div>
                @else
                    <div class="mt-2 text-[11px] text-muted">
                        Tambahkan kategori pengeluaran aktif terlebih dahulu pada menu <a href="{{ route('categories.index') }}" class="text-forest hover:underline font-bold">Kategori</a>.
                    </div>
                @endif
            </div>
        @else
            <!-- Tampilan Mobile (Card List) -->
            <div class="divide-y divide-line md:hidden">
                @foreach ($budgets as $budget)
                    @php
                        $percentage = $budget->calculated_percentage;
                        $isOver = $budget->calculated_is_over;
                        $barColor = $isOver ? 'bg-red' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-forest');
                    @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-charcoal">{{ $budget->category->name }}</span>
                            @if ($isOver)
                                <span class="text-[10px] font-bold text-red bg-red/10 border border-red/20 px-2 py-0.5 rounded-full">
                                    LEWAT {{ round($percentage - 100) }}%
                                </span>
                            @else
                                <span class="text-[11px] font-bold text-muted">{{ $percentage }}%</span>
                            @endif
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-cream/60 dark:bg-sidebar/80 rounded-full h-2 overflow-hidden border border-line/40">
                            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $percentage) }}%"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-[11px] pt-1">
                            <div>
                                <span class="text-muted block text-[10px] uppercase font-bold">Limit</span>
                                <span class="font-semibold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}">Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}</span>
                                </span>
                            </div>
                            <div>
                                <span class="text-muted block text-[10px] uppercase font-bold">Terpakai</span>
                                <span class="font-semibold {{ $isOver ? 'text-red' : 'text-charcoal' }}">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($budget->calculated_spent, 0, ',', '.') }}">Rp {{ number_format($budget->calculated_spent, 0, ',', '.') }}</span>
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-muted block text-[10px] uppercase font-bold">Sisa</span>
                                <span class="font-semibold {{ $budget->calculated_remaining < 0 ? 'text-red' : 'text-forest' }}">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($budget->calculated_remaining, 0, ',', '.') }}">Rp {{ number_format($budget->calculated_remaining, 0, ',', '.') }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-line/60">
                            <button 
                                type="button" 
                                onclick="openEditBudgetModal({{ $budget->id }}, '{{ addslashes($budget->category->name) }}', {{ $budget->limit_amount }})"
                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                title="Ubah Limit Anggaran"
                                aria-label="Ubah Limit Anggaran"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" class="inline" onsubmit="return confirm('Hapus anggaran kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                    title="Hapus Anggaran"
                                    aria-label="Hapus Anggaran"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Tampilan Desktop (Table View) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 px-6">Kategori</th>
                            <th class="py-3 px-4 text-right">Batas Limit</th>
                            <th class="py-3 px-4 text-right">Pengeluaran</th>
                            <th class="py-3 px-4 text-right">Sisa Anggaran</th>
                            <th class="py-3 px-6 min-w-[180px]">Progres Realisasi</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($budgets as $budget)
                            @php
                                $percentage = $budget->calculated_percentage;
                                $isOver = $budget->calculated_is_over;
                                $barColor = $isOver ? 'bg-red' : ($percentage >= 80 ? 'bg-amber-500' : 'bg-forest');
                            @endphp
                            <tr class="hover:bg-cream/20 transition-colors">
                                <td class="py-4 px-6 font-bold text-charcoal">
                                    {{ $budget->category->name }}
                                </td>
                                <td class="py-4 px-4 text-right font-mono font-semibold text-charcoal">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}">Rp {{ number_format($budget->limit_amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-right font-mono font-bold {{ $isOver ? 'text-red' : 'text-charcoal' }}">
                                    <span class="privacy-balance" data-raw-value="Rp {{ number_format($budget->calculated_spent, 0, ',', '.') }}">Rp {{ number_format($budget->calculated_spent, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-right font-mono font-bold {{ $budget->calculated_remaining < 0 ? 'text-red' : 'text-forest' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $budget->calculated_remaining < 0 ? '-' : '' }}Rp {{ number_format(abs($budget->calculated_remaining), 0, ',', '.') }}">{{ $budget->calculated_remaining < 0 ? '-' : '' }}Rp {{ number_format(abs($budget->calculated_remaining), 0, ',', '.') }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-cream/60 dark:bg-sidebar/80 rounded-full h-2 overflow-hidden border border-line/40">
                                            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $percentage) }}%"></div>
                                        </div>
                                        <span class="text-[11px] font-bold min-w-[42px] text-right {{ $isOver ? 'text-red' : 'text-muted' }}">
                                            {{ $percentage }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button 
                                            type="button" 
                                            onclick="openEditBudgetModal({{ $budget->id }}, '{{ addslashes($budget->category->name) }}', {{ $budget->limit_amount }})"
                                            class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                            title="Ubah Limit Anggaran"
                                            aria-label="Ubah Limit Anggaran"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        <form method="POST" action="{{ route('budgets.destroy', $budget) }}" class="inline" onsubmit="return confirm('Hapus anggaran kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="w-8 h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                title="Hapus Anggaran"
                                                aria-label="Hapus Anggaran"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal Tambah Anggaran -->
    @if ($availableCategories->isNotEmpty())
        <div id="add-budget-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
            <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-serif text-xl text-forest font-normal mb-1">Tetapkan Anggaran Baru</h3>
                        <p class="text-xs text-muted">
                            Periode: <strong class="text-charcoal">{{ $periodDate->translatedFormat('F Y') }}</strong>
                        </p>
                    </div>
                    <button type="button" onclick="document.getElementById('add-budget-modal').classList.add('hidden')" class="p-1 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('budgets.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <div>
                        <label for="budget_category_id" class="block text-xs font-bold text-charcoal mb-1.5 uppercase tracking-wider">
                            Kategori Pengeluaran
                        </label>
                        <select name="category_id" id="budget_category_id" class="field-select text-xs w-full" required>
                            @foreach ($availableCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="budget_limit_amount" class="block text-xs font-bold text-charcoal mb-1.5 uppercase tracking-wider">
                            Batas Limit Anggaran (Rupiah)
                        </label>
                        <input 
                            type="number" 
                            name="limit_amount" 
                            id="budget_limit_amount" 
                            placeholder="Contoh: 1500000" 
                            class="field-input text-xs w-full font-mono font-bold" 
                            required 
                            min="1"
                            onfocus="this.select()"
                            onclick="this.select()"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                        <button type="button" onclick="document.getElementById('add-budget-modal').classList.add('hidden')" class="btn-arus-secondary cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="btn-arus-primary cursor-pointer">
                            Simpan Anggaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Edit Anggaran -->
    <div id="edit-budget-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-serif text-xl text-forest font-normal mb-1">Ubah Limit Anggaran</h3>
                    <p class="text-xs text-muted" id="edit-budget-category-label"></p>
                </div>
                <button type="button" onclick="document.getElementById('edit-budget-modal').classList.add('hidden')" class="p-1 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="edit-budget-form" method="POST" action="" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_budget_limit_amount" class="block text-xs font-bold text-charcoal mb-1.5 uppercase tracking-wider">
                        Batas Limit Baru (Rupiah)
                    </label>
                    <input 
                        type="number" 
                        name="limit_amount" 
                        id="edit_budget_limit_amount" 
                        class="field-input text-xs w-full font-mono font-bold" 
                        required 
                        min="1"
                        onfocus="this.select()"
                        onclick="this.select()"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="document.getElementById('edit-budget-modal').classList.add('hidden')" class="btn-arus-secondary cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditBudgetModal(id, categoryName, currentLimit) {
            var form = document.getElementById('edit-budget-form');
            form.action = '/budgets/' + id;
            document.getElementById('edit-budget-category-label').innerText = 'Kategori: ' + categoryName + ' ({{ $periodDate->translatedFormat("F Y") }})';
            document.getElementById('edit_budget_limit_amount').value = currentLimit;
            document.getElementById('edit-budget-modal').classList.remove('hidden');
        }
    </script>
</x-layouts.app>
