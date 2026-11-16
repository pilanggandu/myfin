<x-layouts.app title="Aturan Otomatis Transaksi">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / ATURAN TRANSAKSI</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Aturan Otomatis Transaksi
            </h1>
            <p class="text-xs text-muted">
                Tetapkan kategori, member, atau catatan secara otomatis berdasarkan kata kunci dan kondisi nominal saat transaksi diimpor.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button 
                type="button" 
                onclick="openSimulatorModal()" 
                class="btn-arus-secondary inline-flex items-center gap-1.5 min-h-[40px] px-3 text-xs font-semibold"
                title="Uji coba evaluasi aturan terhadap data simulasi"
            >
                <svg class="w-4 h-4 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                </svg>
                <span>Uji Simulator</span>
            </button>
            <a 
                href="{{ route('transactions.import') }}" 
                class="btn-arus-secondary inline-flex items-center gap-1.5 min-h-[40px] px-3 text-xs font-semibold"
            >
                <svg class="w-4 h-4 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                <span>Impor Transaksi</span>
            </a>
            <button 
                type="button" 
                onclick="openCreateRuleModal()" 
                class="btn-arus-primary inline-flex items-center gap-1.5 min-h-[40px] px-4 text-xs font-bold"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>+ Buat Aturan Baru</span>
            </button>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="panel-card p-4 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Total Aturan</div>
                <div class="text-2xl font-serif text-charcoal font-bold mt-1">{{ $stats['total'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
            </div>
        </div>
        <div class="panel-card p-4 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Aturan Aktif</div>
                <div class="text-2xl font-serif text-emerald-600 dark:text-emerald-400 font-bold mt-1">{{ $stats['active'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
        <div class="panel-card p-4 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-muted">Aturan Nonaktif</div>
                <div class="text-2xl font-serif text-charcoal/60 font-bold mt-1">{{ $stats['inactive'] }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-line/40 text-muted flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="panel-card p-4 mb-6">
        <form method="GET" action="{{ route('transaction-rules.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama aturan, kata kunci, atau catatan..." 
                    class="field-input h-10 text-xs w-full"
                >
            </div>
            <div>
                <select name="type" class="field-select h-10 text-xs w-full">
                    <option value="">Semua Jenis Transaksi</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Hanya Pengeluaran</option>
                    <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Hanya Pemasukan</option>
                    <option value="all" {{ request('type') === 'all' ? 'selected' : '' }}>Semua Jenis (Bebas)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select name="status" class="field-select h-10 text-xs flex-1">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hanya Aktif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Hanya Nonaktif</option>
                </select>
                <button type="submit" class="btn-arus-secondary h-10 px-3 text-xs font-semibold shrink-0">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('transaction-rules.index') }}" class="btn-arus-secondary h-10 px-2.5 text-xs text-muted hover:text-red shrink-0" title="Reset filter">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Daftar Aturan Transaksi -->
    <div class="panel-card overflow-hidden">
        @if ($rules->isEmpty())
            <div class="p-12 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-forest/10 dark:bg-forest/20 text-forest dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                    </svg>
                </div>
                <h3 class="font-serif text-lg text-charcoal font-bold">Belum Ada Aturan Transaksi</h3>
                <p class="text-xs text-muted max-w-md mx-auto mt-1 mb-5">
                    Buat aturan untuk secara otomatis mengenali kategori, member, atau catatan transaksi saat Anda mengimpor mutasi rekening atau CSV.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button type="button" onclick="openCreateRuleModal()" class="btn-arus-primary px-4 py-2 text-xs font-bold">
                        + Buat Aturan Pertama
                    </button>
                    <a href="{{ route('transactions.import') }}" class="btn-arus-secondary px-4 py-2 text-xs font-semibold">
                        Impor Transaksi
                    </a>
                </div>
            </div>
        @else
            <div class="divide-y divide-line">
                @foreach ($rules as $rule)
                    <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 transition hover:bg-cream/40 dark:hover:bg-line/20 {{ ! $rule->is_active ? 'opacity-60 bg-sidebar/30' : '' }}">
                        <div class="flex-1 min-w-0 space-y-2">
                            <!-- Baris Atas: Nama Aturan, Prioritas & Status Badge -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-serif font-bold text-base text-charcoal">{{ $rule->name }}</span>
                                
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $rule->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900' : 'bg-line text-muted' }}">
                                    {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>

                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sidebar text-muted border border-line" title="Urutan prioritas evaluasi (semakin tinggi semakin diprioritaskan)">
                                    Prioritas: {{ $rule->priority }}
                                </span>

                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $rule->type_criterion === 'expense' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : ($rule->type_criterion === 'income' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300') }}">
                                    {{ $rule->typeCriterionLabel() }}
                                </span>

                                @if ($rule->stop_processing)
                                    <span class="text-[10px] text-muted flex items-center gap-1" title="Jika aturan ini cocok, evaluasi aturan berikutnya dihentikan">
                                        <svg class="w-3 h-3 text-forest dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        Hentikan aturan lain
                                    </span>
                                @endif
                            </div>

                            <!-- Baris Tengah: Kondisi Pemicu -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Kondisi:</span>
                                
                                @if ($rule->keyword)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-panel border border-line text-charcoal">
                                        <span class="text-muted">{{ $rule->keywordOperatorLabel() }}:</span>
                                        <strong class="font-mono text-forest dark:text-emerald-400">"{{ $rule->keyword }}"</strong>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-sidebar text-muted">
                                        Kata kunci: Bebas
                                    </span>
                                @endif

                                @if ($rule->amount_operator !== 'any')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-panel border border-line text-charcoal">
                                        <span class="text-muted">{{ $rule->amountOperatorLabel() }}:</span>
                                        <strong class="font-semibold text-charcoal">
                                            @if ($rule->amount_operator === 'between')
                                                Rp {{ number_format((float) $rule->amount_min, 0, ',', '.') }} - Rp {{ number_format((float) $rule->amount_max, 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format((float) $rule->amount_min, 0, ',', '.') }}
                                            @endif
                                        </strong>
                                    </span>
                                @endif

                                @if ($rule->account)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-panel border border-line text-charcoal">
                                        <span class="text-muted">Rekening:</span>
                                        <strong class="font-semibold">{{ $rule->account->name }}</strong>
                                    </span>
                                @endif
                            </div>

                            <!-- Baris Bawah: Tindakan Otomatis yang Dilakukan -->
                            <div class="flex flex-wrap items-center gap-2 text-xs pt-1">
                                <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Tindakan:</span>

                                @if ($rule->actionCategory)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.386l5.228-3.076c.827-.486 1.164-1.503.744-2.316L17.72 6.84a2.25 2.25 0 0 0-1.591-.659H11.5" />
                                        </svg>
                                        Kategori: {{ $rule->actionCategory->name }}
                                    </span>
                                @endif

                                @if ($rule->actionMember)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-800 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                        Member: {{ $rule->actionMember->name }}
                                    </span>
                                @endif

                                @if ($rule->action_note)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.502 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                        </svg>
                                        Catatan: "{{ Str::limit($rule->action_note, 30) }}"
                                    </span>
                                @endif

                                @if ($rule->action_description)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-purple-50 text-purple-800 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900">
                                        Ubah Keterangan: "{{ Str::limit($rule->action_description, 25) }}"
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Tombol Aksi Kontrol -->
                        <div class="flex items-center gap-2 self-end lg:self-center shrink-0">
                            <!-- Toggle Aktif/Nonaktif -->
                            <form method="POST" action="{{ route('transaction-rules.toggle-active', $rule) }}" class="inline">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="px-3 py-1.5 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-semibold text-charcoal transition"
                                    title="{{ $rule->is_active ? 'Nonaktifkan aturan ini' : 'Aktifkan aturan ini' }}"
                                >
                                    {{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <!-- Terapkan ke Transaksi Lama -->
                            <form method="POST" action="{{ route('transaction-rules.apply-existing', $rule) }}" class="inline" onsubmit="return confirm('Terapkan aturan ini ke semua transaksi lama di ruang ini yang belum berkategori?');">
                                @csrf
                                <input type="hidden" name="only_uncategorized" value="1">
                                <button 
                                    type="submit" 
                                    class="px-3 py-1.5 rounded-lg border border-line bg-panel hover:bg-forest/10 hover:text-forest text-xs font-semibold text-charcoal transition inline-flex items-center gap-1"
                                    title="Terapkan aturan ini ke transaksi yang sudah ada di ruang ini"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    <span>Terapkan</span>
                                </button>
                            </form>

                            <!-- Edit -->
                            <button 
                                type="button" 
                                onclick='openEditRuleModal(@json($rule))' 
                                class="w-9 h-9 rounded-lg border border-line bg-panel hover:bg-cream text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer"
                                title="Ubah aturan"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </button>

                            <!-- Hapus -->
                            <form method="POST" action="{{ route('transaction-rules.destroy', $rule) }}" class="inline" onsubmit="return confirm('Hapus aturan transaksi \'{{ $rule->name }}\'?');">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="w-9 h-9 rounded-lg border border-line bg-panel hover:bg-rose-50 text-muted hover:text-rose-600 transition inline-flex items-center justify-center cursor-pointer"
                                    title="Hapus aturan"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Buat / Edit Aturan -->
    <div id="ruleModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-charcoal/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-panel rounded-2xl border border-line shadow-2xl overflow-hidden my-8">
            <div class="p-5 border-b border-line flex items-center justify-between">
                <div>
                    <h3 id="modalTitle" class="font-serif text-lg font-bold text-charcoal">Buat Aturan Transaksi Baru</h3>
                    <p class="text-xs text-muted">Tentukan kriteria pencocokan kata kunci dan nominal untuk aksi otomatis.</p>
                </div>
                <button type="button" onclick="closeRuleModal()" class="w-8 h-8 rounded-lg hover:bg-cream text-muted hover:text-charcoal flex items-center justify-center">
                    ✕
                </button>
            </div>

            <form id="ruleForm" method="POST" action="{{ route('transaction-rules.store') }}" class="p-5 space-y-4">
                @csrf
                <div id="methodSpoof"></div>

                <!-- Nama & Prioritas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-charcoal mb-1">Nama Aturan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="ruleName" required placeholder="Contoh: Auto Kopi Kenangan / Starbucks" class="field-input w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Prioritas Urutan</label>
                        <input type="number" name="priority" id="rulePriority" value="0" placeholder="0" class="field-input w-full text-xs">
                    </div>
                </div>

                <!-- Jenis & Rekening -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Jenis Transaksi</label>
                        <select name="type_criterion" id="ruleTypeCriterion" class="field-select w-full text-xs">
                            <option value="all">Semua Jenis Transaksi</option>
                            <option value="expense">Hanya Pengeluaran</option>
                            <option value="income">Hanya Pemasukan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Berlaku untuk Rekening</label>
                        <select name="account_id" id="ruleAccountId" class="field-select w-full text-xs">
                            <option value="">Semua Rekening & Wallet</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Bagian Kondisi: Kata Kunci -->
                <div class="p-3.5 rounded-xl bg-sidebar/50 border border-line space-y-3">
                    <div class="text-xs font-bold text-forest dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span>1. Kondisi Kata Kunci (Keterangan)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] text-muted mb-1">Metode Pencocokan</label>
                            <select name="keyword_operator" id="ruleKeywordOperator" class="field-select w-full text-xs">
                                <option value="contains">Mengandung (Contains)</option>
                                <option value="starts_with">Diawali (Starts With)</option>
                                <option value="ends_with">Diakhiri (Ends With)</option>
                                <option value="exact">Sama Persis (Exact)</option>
                                <option value="regex">Ekspresi Reguler (RegEx)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-muted mb-1">Kata Kunci / Pola Teks</label>
                            <input type="text" name="keyword" id="ruleKeyword" placeholder="Contoh: kopi, starbucks, gaji, alfamart" class="field-input w-full text-xs">
                        </div>
                    </div>
                </div>

                <!-- Bagian Kondisi: Nominal -->
                <div class="p-3.5 rounded-xl bg-sidebar/50 border border-line space-y-3">
                    <div class="text-xs font-bold text-forest dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>2. Kondisi Nominal Transaksi</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] text-muted mb-1">Kondisi Nominal</label>
                            <select name="amount_operator" id="ruleAmountOperator" onchange="toggleAmountInputs()" class="field-select w-full text-xs">
                                <option value="any">Berapapun Nominal (Tanpa Batas)</option>
                                <option value="greater_than">Lebih Dari (&gt;)</option>
                                <option value="greater_than_or_equal">Minimal (&gt;=)</option>
                                <option value="less_than">Kurang Dari (&lt;)</option>
                                <option value="less_than_or_equal">Maksimal (&lt;=)</option>
                                <option value="equals">Sama Dengan (=)</option>
                                <option value="between">Rentang Nilai (Antara)</option>
                            </select>
                        </div>
                        <div id="amountMinContainer" class="hidden">
                            <label id="amountMinLabel" class="block text-[11px] text-muted mb-1">Nominal (Rp)</label>
                            <input type="text" name="amount_min" id="ruleAmountMin" placeholder="Contoh: 50.000" class="field-input w-full text-xs">
                        </div>
                        <div id="amountMaxContainer" class="hidden">
                            <label class="block text-[11px] text-muted mb-1">Nominal Maksimum (Rp)</label>
                            <input type="text" name="amount_max" id="ruleAmountMax" placeholder="Contoh: 200.000" class="field-input w-full text-xs">
                        </div>
                    </div>
                </div>

                <!-- Bagian Tindakan Otomatis -->
                <div class="p-3.5 rounded-xl bg-forest/5 dark:bg-forest/10 border border-forest/20 space-y-3">
                    <div class="text-xs font-bold text-forest dark:text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" />
                        </svg>
                        <span>3. Tindakan Otomatis Saat Cocok (Pilih Minimal 1)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Tetapkan Kategori</label>
                            <select name="action_category_id" id="ruleActionCategoryId" class="field-select w-full text-xs">
                                <option value="">-- Jangan Ubah Kategori --</option>
                                <optgroup label="Pengeluaran">
                                    @foreach ($categories->where('type', 'expense') as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Pemasukan">
                                    @foreach ($categories->where('type', 'income') as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Tetapkan Member</label>
                            <select name="action_member_id" id="ruleActionMemberId" class="field-select w-full text-xs">
                                <option value="">-- Jangan Ubah Member --</option>
                                @foreach ($members as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-charcoal mb-1">Tambahkan Catatan Otomatis</label>
                            <input type="text" name="action_note" id="ruleActionNote" placeholder="Contoh: Pembelian rutin, Reimbursement kantor" class="field-input w-full text-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-muted mb-1">Ubah Keterangan Transaksi (Opsional)</label>
                            <input type="text" name="action_description" id="ruleActionDescription" placeholder="Biarkan kosong untuk mempertahankan teks asli mutasi" class="field-input w-full text-xs">
                        </div>
                    </div>
                </div>

                <!-- Opsi Tambahan -->
                <div class="flex flex-wrap items-center gap-5 pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="is_active" id="ruleIsActive" value="1" checked class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-medium">Aturan Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="stop_processing" id="ruleStopProcessing" value="1" checked class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-medium">Hentikan evaluasi aturan lain setelah aturan ini cocok</span>
                    </label>
                </div>

                <!-- Footer Tombol -->
                <div class="pt-3 border-t border-line flex items-center justify-end gap-2">
                    <button type="button" onclick="closeRuleModal()" class="btn-arus-secondary px-4 py-2 text-xs font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary px-5 py-2 text-xs font-bold">
                        Simpan Aturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Simulator Aturan -->
    <div id="simulatorModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-charcoal/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="relative w-full max-w-xl bg-panel rounded-2xl border border-line shadow-2xl overflow-hidden my-8">
            <div class="p-5 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-lg font-bold text-charcoal">Simulator Uji Coba Aturan</h3>
                    <p class="text-xs text-muted">Masukkan contoh mutasi untuk melihat aturan mana yang akan terpicu secara otomatis.</p>
                </div>
                <button type="button" onclick="closeSimulatorModal()" class="w-8 h-8 rounded-lg hover:bg-cream text-muted hover:text-charcoal flex items-center justify-center">
                    ✕
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">Contoh Keterangan Transaksi</label>
                        <input type="text" id="simDesc" placeholder="Contoh: KOPI KENANGAN SENAYAN, TRANSFER GAJI, SUPERINDO" class="field-input w-full text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Nominal (Rp)</label>
                            <input type="text" id="simAmount" placeholder="Contoh: 35.000" class="field-input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">Jenis Transaksi</label>
                            <select id="simType" class="field-select w-full text-xs">
                                <option value="expense">Pengeluaran</option>
                                <option value="income">Pemasukan</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" onclick="runSimulation()" class="btn-arus-primary w-full py-2.5 text-xs font-bold flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                        </svg>
                        <span>Jalankan Pengujian Aturan</span>
                    </button>
                </div>

                <!-- Hasil Simulasi -->
                <div id="simResult" class="hidden p-4 rounded-xl border border-line bg-sidebar/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">Hasil Evaluasi:</span>
                        <span id="simStatusBadge" class="px-2 py-0.5 rounded text-[11px] font-bold"></span>
                    </div>

                    <div id="simMatchedList" class="space-y-1.5 text-xs"></div>

                    <div class="pt-2 border-t border-line text-xs space-y-1">
                        <div class="font-bold text-charcoal">Hasil Transaksi Setelah Diproses:</div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>Kategori: <strong id="resCat" class="text-forest dark:text-emerald-400">-</strong></div>
                            <div>Member: <strong id="resMem" class="text-blue-600 dark:text-blue-400">-</strong></div>
                            <div class="col-span-2">Catatan: <strong id="resNote" class="text-amber-600 dark:text-amber-400">-</strong></div>
                            <div class="col-span-2">Keterangan Akhir: <strong id="resDesc" class="text-charcoal">-</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAmountInputs() {
            const op = document.getElementById('ruleAmountOperator').value;
            const minCont = document.getElementById('amountMinContainer');
            const maxCont = document.getElementById('amountMaxContainer');
            const minLabel = document.getElementById('amountMinLabel');

            if (op === 'any') {
                minCont.classList.add('hidden');
                maxCont.classList.add('hidden');
            } else if (op === 'between') {
                minCont.classList.remove('hidden');
                maxCont.classList.remove('hidden');
                minLabel.textContent = 'Nominal Minimum (Rp)';
            } else {
                minCont.classList.remove('hidden');
                maxCont.classList.add('hidden');
                minLabel.textContent = 'Nilai Batas (Rp)';
            }
        }

        function openCreateRuleModal() {
            document.getElementById('modalTitle').textContent = 'Buat Aturan Transaksi Baru';
            document.getElementById('ruleForm').action = "{{ route('transaction-rules.store') }}";
            document.getElementById('methodSpoof').innerHTML = '';
            
            // Reset fields
            document.getElementById('ruleName').value = '';
            document.getElementById('rulePriority').value = '0';
            document.getElementById('ruleTypeCriterion').value = 'all';
            document.getElementById('ruleAccountId').value = '';
            document.getElementById('ruleKeywordOperator').value = 'contains';
            document.getElementById('ruleKeyword').value = '';
            document.getElementById('ruleAmountOperator').value = 'any';
            document.getElementById('ruleAmountMin').value = '';
            document.getElementById('ruleAmountMax').value = '';
            document.getElementById('ruleActionCategoryId').value = '';
            document.getElementById('ruleActionMemberId').value = '';
            document.getElementById('ruleActionNote').value = '';
            document.getElementById('ruleActionDescription').value = '';
            document.getElementById('ruleIsActive').checked = true;
            document.getElementById('ruleStopProcessing').checked = true;

            toggleAmountInputs();
            document.getElementById('ruleModal').classList.remove('hidden');
        }

        function openEditRuleModal(rule) {
            document.getElementById('modalTitle').textContent = 'Ubah Aturan: ' + rule.name;
            document.getElementById('ruleForm').action = "/transaction-rules/" + rule.id;
            document.getElementById('methodSpoof').innerHTML = '@method("PUT")';

            document.getElementById('ruleName').value = rule.name || '';
            document.getElementById('rulePriority').value = rule.priority ?? 0;
            document.getElementById('ruleTypeCriterion').value = rule.type_criterion || 'all';
            document.getElementById('ruleAccountId').value = rule.account_id || '';
            document.getElementById('ruleKeywordOperator').value = rule.keyword_operator || 'contains';
            document.getElementById('ruleKeyword').value = rule.keyword || '';
            document.getElementById('ruleAmountOperator').value = rule.amount_operator || 'any';
            document.getElementById('ruleAmountMin').value = rule.amount_min ? Math.round(rule.amount_min) : '';
            document.getElementById('ruleAmountMax').value = rule.amount_max ? Math.round(rule.amount_max) : '';
            document.getElementById('ruleActionCategoryId').value = rule.action_category_id || '';
            document.getElementById('ruleActionMemberId').value = rule.action_member_id || '';
            document.getElementById('ruleActionNote').value = rule.action_note || '';
            document.getElementById('ruleActionDescription').value = rule.action_description || '';
            document.getElementById('ruleIsActive').checked = Boolean(rule.is_active);
            document.getElementById('ruleStopProcessing').checked = Boolean(rule.stop_processing);

            toggleAmountInputs();
            document.getElementById('ruleModal').classList.remove('hidden');
        }

        function closeRuleModal() {
            document.getElementById('ruleModal').classList.add('hidden');
        }

        function openSimulatorModal() {
            document.getElementById('simulatorModal').classList.remove('hidden');
        }

        function closeSimulatorModal() {
            document.getElementById('simulatorModal').classList.add('hidden');
        }

        async function runSimulation() {
            const desc = document.getElementById('simDesc').value;
            const amount = document.getElementById('simAmount').value;
            const type = document.getElementById('simType').value;

            if (!desc) {
                alert('Masukkan contoh keterangan transaksi');
                return;
            }

            try {
                const response = await fetch("{{ route('transaction-rules.simulate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        description: desc,
                        amount: amount || 0,
                        type: type
                    })
                });

                const data = await response.json();
                const resultBox = document.getElementById('simResult');
                const badge = document.getElementById('simStatusBadge');
                const list = document.getElementById('simMatchedList');

                resultBox.classList.remove('hidden');

                if (data.matched) {
                    badge.className = 'px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
                    badge.textContent = `Cocok dengan ${data.matched_count} Aturan`;

                    list.innerHTML = data.matched_rules.map(r => `
                        <div class="p-2 rounded bg-panel border border-line flex items-center justify-between">
                            <span class="font-bold text-charcoal">⚡ ${r.name} (Prio: ${r.priority})</span>
                            <span class="text-muted text-[10px]">${r.stop_processing ? 'Menghentikan aturan lain' : 'Lanjut'}</span>
                        </div>
                    `).join('');

                    document.getElementById('resCat').textContent = data.result.category_name || '(Tidak berubah)';
                    document.getElementById('resMem').textContent = data.result.member_name || '(Tidak berubah)';
                    document.getElementById('resNote').textContent = data.result.notes || '(Tidak ada)';
                    document.getElementById('resDesc').textContent = data.result.description || desc;
                } else {
                    badge.className = 'px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
                    badge.textContent = 'Tidak Ada Aturan yang Cocok';
                    list.innerHTML = '<p class="text-muted text-xs">Transaksi ini akan menggunakan kategori bawaan tanpa aturan otomatis.</p>';
                    document.getElementById('resCat').textContent = '-';
                    document.getElementById('resMem').textContent = '-';
                    document.getElementById('resNote').textContent = '-';
                    document.getElementById('resDesc').textContent = desc;
                }
            } catch (err) {
                alert('Gagal menjalankan simulasi: ' + err.message);
            }
        }
    </script>
</x-layouts.app>
