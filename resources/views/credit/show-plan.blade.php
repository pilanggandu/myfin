<x-layouts.app :title="'Cicilan: ' . $plan->name">
    <!-- Back Link & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('credit.index', ['tab' => $plan->isActive() ? 'plans' : 'history']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-charcoal transition mb-2">
                <span>←</span>
                <span>Kembali ke Daftar Kredit & Cicilan</span>
            </a>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider {{ $plan->isCompleted() ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($plan->isActive() ? 'bg-forest/10 text-forest' : 'bg-red/10 text-red') }}">
                    {{ $plan->isCompleted() ? 'Lunas Selesai' : ($plan->isActive() ? 'Cicilan Berjalan' : 'Dibatalkan') }}
                </span>
                <span class="text-xs text-muted">ID Rencana: #{{ $plan->id }}</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl text-charcoal font-medium mt-1">
                {{ $plan->name }}
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            @if ($plan->isActive())
                <button 
                    type="button" 
                    onclick="openNoteModal()" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 border border-line rounded-lg text-xs font-bold text-charcoal bg-panel hover:bg-cream transition cursor-pointer shadow-xs"
                    title="Tambah Nota Kredit atau Nota Debit untuk penyesuaian selisih nominal/pembulatan"
                >
                    <span>⚖️ Nota Kredit / Debit</span>
                </button>
            @endif

            @if ($plan->purchaseTransaction)
                <a 
                    href="{{ route('transactions.show', $plan->purchaseTransaction) }}" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 border border-line rounded-lg text-xs font-semibold text-charcoal bg-panel hover:bg-cream transition shadow-xs"
                >
                    <span>🔍 Transaksi Awal</span>
                </a>
            @endif

            @if ($plan->isActive() && $plan->pendingInstallments->count() > 0)
                <button 
                    type="button" 
                    onclick="openPayoffModal()" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 border border-emerald-600/40 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition cursor-pointer shadow-xs"
                >
                    <span>⚡ Lunasi Sekaligus ({{ $plan->pendingInstallments->count() }} Cicilan)</span>
                </button>
            @endif

            @if ($plan->isActive() && $plan->paidInstallments->isEmpty())
                <form method="POST" action="{{ route('credit.plans.void', $plan) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan rencana cicilan dan mem-void transaksi pembelian ini? Tindakan tidak dapat diurungkan.')">
                    @csrf
                    <button 
                        type="submit" 
                        class="min-h-[44px] inline-flex items-center gap-1 px-3.5 py-2 border border-red/30 text-red hover:bg-red/10 rounded-lg text-xs font-bold transition cursor-pointer shadow-xs"
                    >
                        <span>Batalkan (Void)</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Alert Status Feedback -->
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl border border-emerald-500/20 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <span>✓</span>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold ml-4">✕</button>
        </div>
    @endif

    <!-- Error Validation Feedback -->
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl border border-red/20 bg-red/10 text-red text-xs">
            <div class="font-bold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Plan Summary Header Card -->
    <div class="panel-card p-6 mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div>
                <span class="text-[11px] text-muted uppercase tracking-wider font-semibold block mb-1">Kartu / Pembiayaan</span>
                <div class="font-semibold text-sm text-charcoal flex items-center gap-1.5">
                    <span>💳</span>
                    <span>{{ $plan->creditAccount->name }}</span>
                </div>
                <span class="text-xs text-muted capitalize">{{ str_replace('_', ' ', $plan->creditAccount->type) }}</span>
            </div>

            <div>
                <span class="text-[11px] text-muted uppercase tracking-wider font-semibold block mb-1">Kategori Pengeluaran</span>
                <div class="font-semibold text-sm text-charcoal flex items-center gap-1.5">
                    <span>🏷️</span>
                    <span>{{ $plan->category->name }}</span>
                </div>
                <span class="text-xs text-muted">Beban diakui di awal</span>
            </div>

            <div>
                <span class="text-[11px] text-muted uppercase tracking-wider font-semibold block mb-1">Tanggal Transaksi</span>
                <div class="font-semibold text-sm text-charcoal">
                    {{ $plan->start_date->format('d M Y') }}
                </div>
                <span class="text-xs text-muted">Tenor: {{ $plan->tenor }} bulan</span>
            </div>

            <div>
                <span class="text-[11px] text-muted uppercase tracking-wider font-semibold block mb-1">Total Pokok Hutang</span>
                <div class="font-serif text-xl font-bold text-charcoal">
                    <span class="privacy-balance" data-raw-value="{{ $plan->formatted_total_amount }}">{{ $plan->formatted_total_amount }}</span>
                </div>
                @if ($plan->hasInterestOrFees())
                    <span class="text-xs text-muted block mt-0.5">
                        + Bunga: <strong class="text-amber-600 privacy-balance" data-raw-value="{{ $plan->formatted_interest }}">{{ $plan->formatted_interest }}</strong> | Admin: <strong class="privacy-balance" data-raw-value="{{ $plan->formatted_fee }}">{{ $plan->formatted_fee }}</strong>
                    </span>
                    <span class="text-xs text-muted block">
                        Grand Total: <strong class="text-forest privacy-balance" data-raw-value="{{ $plan->formatted_grand_total }}">{{ $plan->formatted_grand_total }}</strong>
                    </span>
                @else
                    <span class="text-xs text-muted">0% Bunga (Tanpa Bunga)</span>
                @endif
            </div>
        </div>

        <!-- Progress Pelunasan -->
        @php
            $paidSum = $plan->paidPrincipal();
            $remainingSum = $plan->remainingPrincipal();
            $percent = $plan->total_amount > 0 ? min(100, round(($paidSum / $plan->total_amount) * 100)) : 0;
        @endphp
        <div class="border-t border-line pt-4">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="text-muted">
                    Progress Pelunasan: <strong class="text-charcoal">{{ $plan->paidInstallments->count() }} dari {{ $plan->tenor }} cicilan lunas</strong> ({{ $percent }}%)
                </span>
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Terbayar: <span class="privacy-balance" data-raw-value="{{ $plan->formatted_paid }}">{{ $plan->formatted_paid }}</span></span>
                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Sisa Pokok: <span class="privacy-balance" data-raw-value="{{ $plan->formatted_remaining }}">{{ $plan->formatted_remaining }}</span></span>
                    @if ($plan->hasInterestOrFees())
                        <span class="text-forest font-semibold">Sisa Total: <span class="privacy-balance" data-raw-value="{{ $plan->formatted_remaining_total }}">{{ $plan->formatted_remaining_total }}</span></span>
                    @endif
                </div>
            </div>
            <div class="w-full bg-cream rounded-full h-2.5 overflow-hidden border border-line/40">
                <div class="h-full bg-forest rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
            </div>
        </div>

        @if ($plan->notes)
            <div class="mt-4 p-3 bg-panel rounded-lg border border-line text-xs text-muted">
                <strong class="text-charcoal font-semibold">Catatan:</strong> {{ $plan->notes }}
            </div>
        @endif
    </div>

    <!-- Visualisasi Jadwal Cicilan -->
    <div class="panel-card p-6">
        <div class="flex items-center justify-between pb-4 border-b border-line mb-4">
            <div>
                <h3 class="font-serif text-lg text-charcoal font-medium">Jadwal & Riwayat Pelunasan Cicilan</h3>
                <p class="text-xs text-muted">
                    Setiap pembayaran pokok mengurangi saldo kas dan mengurangi kewajiban hutang tanpa mencatat beban pengeluaran ganda.
                </p>
            </div>
        </div>

        @php
            $overduePending = $plan->installments->where('status', 'pending')->filter(fn($i) => $i->isOverdue());
        @endphp

        @if ($overduePending->isNotEmpty() && $plan->isActive())
            <div class="mb-5 p-4 rounded-xl border border-amber-500/30 bg-amber-50/60 dark:bg-amber-950/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <span class="font-bold text-amber-900 dark:text-amber-200 text-sm block">Pencatatan Baru Dimulai Bulan Ini?</span>
                        <span class="text-muted leading-relaxed block mt-0.5">
                            Ada <strong>{{ $overduePending->count() }} cicilan</strong> jatuh tempo masa lampau. Jika cicilan ini sebenarnya sudah Anda lunasi di dunia nyata sebelum mulai mencatat di ARUS, Anda dapat menandainya lunas sekaligus tanpa memotong saldo rekening kas/bank saat ini.
                        </span>
                    </div>
                </div>
                <form method="POST" action="{{ route('credit.plans.markOverduePaidWithoutCash', $plan) }}" onsubmit="return confirm('Tandai seluruh {{ $overduePending->count() }} cicilan lampau sebagai lunas tanpa memotong saldo kas/bank?')">
                    @csrf
                    <button type="submit" class="min-h-[40px] px-4 py-2 bg-charcoal text-white hover:bg-charcoal/90 rounded-lg font-bold text-xs whitespace-nowrap shadow-xs cursor-pointer flex items-center gap-1.5">
                        <span>⚡</span>
                        <span>Tandai {{ $overduePending->count() }} Cicilan Lampau Lunas Sekaligus</span>
                    </button>
                </form>
            </div>
        @endif

        @php
            $sortedInstallments = $plan->installments->sortBy(function ($inst) {
                return [$inst->isPaid() ? 1 : 0, $inst->installment_number];
            });
            $hasPaid = $plan->installments->contains(fn($i) => $i->isPaid());
            $shownPaidDivider = false;
        @endphp

        <div class="md:hidden space-y-3 mb-2">
            @foreach ($sortedInstallments as $inst)
                @if ($hasPaid && $inst->isPaid() && ! $shownPaidDivider)
                    @php $shownPaidDivider = true; @endphp
                    <div class="py-2 px-3 rounded-lg bg-cream/60 border border-line text-muted font-semibold text-[11px] uppercase tracking-wider flex items-center gap-2">
                        <span>✓</span>
                        <span>Cicilan Sudah Lunas ({{ $plan->installments->where('status', 'paid')->count() }})</span>
                    </div>
                @endif
                <div class="p-3.5 rounded-xl border border-line {{ $inst->isPaid() ? 'bg-cream/20 opacity-85' : ($inst->isOverdue() ? 'bg-red/5 border-red/30' : 'bg-panel') }} space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-charcoal text-sm">#{{ $inst->installment_number }}</span>
                                <span class="text-muted font-mono">{{ $inst->due_date->format('d M Y') }}</span>
                            </div>
                            @if ($inst->isOverdue())
                                <span class="text-[10px] text-red font-bold block mt-0.5">Terlambat {{ $inst->daysOverdue() }} hari</span>
                            @elseif ($inst->isDueToday())
                                <span class="text-[10px] text-amber-600 font-bold block mt-0.5">Jatuh tempo hari ini</span>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-mono font-bold text-sm text-charcoal">
                                <span class="privacy-balance" data-raw-value="{{ $inst->formatted_total_amount }}">{{ $inst->formatted_total_amount }}</span>
                            </div>
                            <div class="mt-1">
                                @if ($inst->isPaid())
                                    <span class="inline-flex items-center gap-1 text-[10px] bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-bold px-2 py-0.5 rounded">
                                        ✓ {{ $inst->isPaidWithoutCash() ? 'Lunas (Pra)' : 'Lunas' }}
                                    </span>
                                @elseif ($inst->isOverdue())
                                    <span class="inline-flex items-center gap-1 text-[10px] bg-red/15 text-red font-bold px-2 py-0.5 rounded">⚠️ Terlambat</span>
                                @elseif ($inst->isDueToday())
                                    <span class="inline-flex items-center gap-1 text-[10px] bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold px-2 py-0.5 rounded">⚡ Hari Ini</span>
                                @elseif ($inst->isDueSoon())
                                    <span class="inline-flex items-center gap-1 text-[10px] bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold px-2 py-0.5 rounded">⏳ Segera</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] bg-muted/15 text-muted font-bold px-2 py-0.5 rounded">📅 Mendatang</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 p-2.5 rounded-lg bg-sidebar/50 border border-line/70 text-[11px]">
                        <div>
                            <span class="text-[9.5px] uppercase text-muted font-bold block">Pokok</span>
                            <span class="font-mono font-semibold text-charcoal privacy-balance" data-raw-value="{{ $inst->formatted_principal }}">{{ $inst->formatted_principal }}</span>
                        </div>
                        <div>
                            <span class="text-[9.5px] uppercase text-muted font-bold block">Bunga</span>
                            <span class="font-mono text-muted privacy-balance" data-raw-value="{{ $inst->formatted_interest }}">{{ $inst->formatted_interest }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[9.5px] uppercase text-muted font-bold block">Admin</span>
                            <span class="font-mono text-muted privacy-balance" data-raw-value="{{ $inst->formatted_fee }}">{{ $inst->formatted_fee }}</span>
                        </div>
                    </div>

                    @if ($inst->isPaid() && $inst->payments->isNotEmpty())
                        @php $lastPay = $inst->payments->first(); @endphp
                        <div class="text-[11px] text-muted">
                            Dibayar: <strong class="text-charcoal">{{ $lastPay->payment_date->format('d M Y') }}</strong> &bull; Sumber: <strong class="text-charcoal">{{ $lastPay->sourceAccount->name }}</strong>
                        </div>
                    @elseif ($inst->isPaid())
                        <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold">
                            Lunas di luar sistem (Pra-pencatatan ARUS)
                        </div>
                    @endif

                    @if ($inst->isPending() && $plan->isActive())
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-line/60">
                            <button type="button" onclick="openPaymentModal({{ json_encode($inst) }})" class="flex-1 min-w-[110px] min-h-[44px] px-3 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer inline-flex items-center justify-center">
                                Bayar Cicilan
                            </button>
                            <button type="button" onclick="openMarkPaidModal({{ json_encode($inst) }})" class="min-h-[44px] px-3 py-2 border border-line text-muted hover:text-charcoal bg-panel rounded-lg text-xs font-semibold hover:bg-cream transition cursor-pointer inline-flex items-center justify-center">
                                Tanpa Kas
                            </button>
                            <button type="button" onclick="openNoteModal({{ json_encode($inst) }})" class="min-h-[44px] px-3 py-2 border border-line text-muted hover:text-charcoal bg-panel rounded-lg text-xs font-semibold hover:bg-cream transition cursor-pointer inline-flex items-center justify-center">
                                ± Nota
                            </button>
                            <a href="{{ $inst->whatsAppReminderUrl($plan->member?->phone) }}" target="_blank" class="min-h-[44px] px-3 py-2 border border-line text-muted hover:text-emerald-600 bg-panel rounded-lg text-xs font-semibold hover:bg-cream transition inline-flex items-center justify-center">
                                💬 WA
                            </a>
                        </div>
                    @elseif ($inst->isPaidWithoutCash() && $plan->isActive())
                        <div class="pt-1 border-t border-line/60 flex justify-end">
                            <form method="POST" action="{{ route('credit.installments.resetToPending', $inst) }}" onsubmit="return confirm('Kembalikan status cicilan #{{ $inst->installment_number }} menjadi menunggu pembayaran?')">
                                @csrf
                                <button type="submit" class="min-h-[40px] px-2 text-[11px] text-muted hover:text-red underline cursor-pointer">
                                    Reset ke Pending
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @php $shownPaidDivider = false; @endphp
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b border-line text-muted">
                        <th class="py-3 px-3 font-semibold">No</th>
                        <th class="py-3 px-3 font-semibold">Jatuh Tempo</th>
                        <th class="py-3 px-3 font-semibold text-right">Pokok</th>
                        <th class="py-3 px-3 font-semibold text-right">Bunga</th>
                        <th class="py-3 px-3 font-semibold text-right">Admin</th>
                        <th class="py-3 px-3 font-semibold text-right">Total Tagihan</th>
                        <th class="py-3 px-3 font-semibold text-center">Status</th>
                        <th class="py-3 px-3 font-semibold">Keterangan / Pembayaran</th>
                        <th class="py-3 px-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">

                    @foreach ($sortedInstallments as $inst)
                        @if ($hasPaid && $inst->isPaid() && ! $shownPaidDivider)
                            @php $shownPaidDivider = true; @endphp
                            <tr class="bg-panel/80 border-y border-line">
                                <td colspan="9" class="py-2.5 px-3 text-muted font-semibold text-[11px] uppercase tracking-wider">
                                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                        <span>✓</span>
                                        <span>Cicilan yang Sudah Lunas ({{ $plan->installments->where('status', 'paid')->count() }} cicilan)</span>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        <tr class="hover:bg-panel transition {{ $inst->isPaid() ? 'bg-cream/20 opacity-80' : ($inst->isOverdue() ? 'bg-red/5' : '') }}">
                            <td class="py-3.5 px-3 font-bold text-charcoal">
                                #{{ $inst->installment_number }}
                            </td>
                            <td class="py-3.5 px-3 text-muted whitespace-nowrap">
                                <div>{{ $inst->due_date->format('d M Y') }}</div>
                                @if ($inst->isOverdue())
                                    <span class="text-[10px] text-red font-bold">Terlambat {{ $inst->daysOverdue() }} hari</span>
                                @elseif ($inst->isDueToday())
                                    <span class="text-[10px] text-amber-600 font-bold">Jatuh tempo hari ini</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-medium text-charcoal">
                                <span class="privacy-balance" data-raw-value="{{ $inst->formatted_principal }}">{{ $inst->formatted_principal }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono text-muted">
                                <span class="privacy-balance" data-raw-value="{{ $inst->formatted_interest }}">{{ $inst->formatted_interest }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono text-muted">
                                <span class="privacy-balance" data-raw-value="{{ $inst->formatted_fee }}">{{ $inst->formatted_fee }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-bold text-charcoal">
                                <span class="privacy-balance" data-raw-value="{{ $inst->formatted_total_amount }}">{{ $inst->formatted_total_amount }}</span>
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if ($inst->isPaid())
                                    @if ($inst->isPaidWithoutCash())
                                        <span class="inline-flex items-center gap-1 text-[11px] bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-bold px-2 py-0.5 rounded" title="Lunas di luar sistem sebelum pencatatan ARUS">
                                            <span>✓</span> Lunas (Pra-Pencatatan)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold px-2 py-0.5 rounded">
                                            <span>✓</span> Lunas
                                        </span>
                                    @endif
                                @elseif ($inst->isOverdue())
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-red/15 text-red font-bold px-2 py-0.5 rounded">
                                        <span>⚠️</span> Terlambat
                                    </span>
                                @elseif ($inst->isDueToday())
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold px-2 py-0.5 rounded">
                                        <span>⚡</span> Hari Ini
                                    </span>
                                @elseif ($inst->isDueSoon())
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold px-2 py-0.5 rounded">
                                        <span>⏳</span> Segera
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-muted/15 text-muted font-bold px-2 py-0.5 rounded">
                                        <span>📅</span> Mendatang
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-muted">
                                @if ($inst->isPaid() && $inst->payments->isNotEmpty())
                                    @php $lastPay = $inst->payments->first(); @endphp
                                    <div class="text-[11px]">
                                        <span>Dibayar: <strong>{{ $lastPay->payment_date->format('d M Y') }}</strong></span>
                                        <span class="block text-charcoal font-semibold">Sumber: {{ $lastPay->sourceAccount->name }}</span>
                                    </div>
                                @elseif ($inst->isPaid())
                                    <div class="text-[11px]">
                                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Lunas di luar sistem</span>
                                        <span class="block text-muted text-[10px]">Pra-pencatatan ARUS (Tanpa potong kas)</span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-muted">Menunggu pembayaran</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    @if ($inst->isPending() && $plan->isActive())
                                        <a 
                                            href="{{ $inst->whatsAppReminderUrl($plan->member?->phone) }}" 
                                            target="_blank" 
                                            title="Kirim Pengingat WhatsApp"
                                            class="min-h-[44px] lg:min-h-[36px] px-2.5 py-1.5 border border-line text-muted hover:text-emerald-600 rounded-lg text-xs font-semibold hover:bg-cream transition inline-flex items-center gap-1"
                                        >
                                            <span>💬 WA</span>
                                        </a>
                                        <button 
                                            type="button" 
                                            onclick="openPaymentModal({{ json_encode($inst) }})" 
                                            class="min-h-[44px] lg:min-h-[36px] inline-flex items-center justify-center px-3 py-1.5 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs whitespace-nowrap"
                                        >
                                            Bayar Cicilan
                                        </button>
                                        <button 
                                            type="button" 
                                            onclick="openMarkPaidModal({{ json_encode($inst) }})" 
                                            class="min-h-[44px] lg:min-h-[36px] inline-flex items-center justify-center px-2.5 py-1.5 border border-line text-muted hover:text-charcoal bg-panel rounded-lg text-xs font-semibold hover:bg-cream transition cursor-pointer shadow-xs whitespace-nowrap"
                                            title="Tandai cicilan ini lunas di masa lampau tanpa memotong kas/bank"
                                        >
                                            <span>Tanpa Kas</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            onclick="openNoteModal({{ json_encode($inst) }})" 
                                            class="min-h-[44px] lg:min-h-[36px] inline-flex items-center justify-center px-2 py-1.5 border border-line text-muted hover:text-charcoal bg-panel rounded-lg text-xs font-semibold hover:bg-cream transition cursor-pointer shadow-xs whitespace-nowrap"
                                            title="Sesuaikan selisih pembulatan dengan Nota Kredit / Debit"
                                        >
                                            <span>± Nota</span>
                                        </button>
                                    @elseif ($inst->isPaid())
                                        <div class="inline-flex items-center gap-2">
                                            <span class="text-xs text-muted font-mono">Tuntas</span>
                                            @if ($inst->isPaidWithoutCash() && $plan->isActive())
                                                <form method="POST" action="{{ route('credit.installments.resetToPending', $inst) }}" onsubmit="return confirm('Kembalikan status cicilan #{{ $inst->installment_number }} menjadi menunggu pembayaran?')">
                                                    @csrf
                                                    <button type="submit" class="min-h-[44px] lg:min-h-0 px-2 lg:px-0 inline-flex items-center text-[10px] text-muted hover:text-red underline cursor-pointer" title="Batalkan status lunas pra-pencatatan">
                                                        Reset
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (\Illuminate\Support\Facades\Schema::hasTable('credit_debit_notes') && $plan->creditDebitNotes->isNotEmpty())
        <!-- Riwayat Nota Kredit & Debit -->
        <div class="panel-card p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-4 border-b border-line mb-4">
                <div>
                    <h3 class="font-serif text-lg text-charcoal font-medium">Riwayat Nota Kredit & Debit</h3>
                    <p class="text-xs text-muted">
                        Penyesuaian selisih pembulatan nominal, diskon, atau biaya tambahan agar saldo kas dan kewajiban tetap presisi.
                    </p>
                </div>
                <button 
                    type="button" 
                    onclick="openNoteModal()" 
                    class="min-h-[44px] sm:min-h-[36px] px-3 py-1.5 border border-line bg-panel text-charcoal hover:bg-cream rounded-lg text-xs font-semibold cursor-pointer shadow-xs inline-flex items-center gap-1.5"
                >
                    <span>+ Tambah Nota</span>
                </button>
            </div>

            <div class="md:hidden divide-y divide-line">
                @foreach ($plan->creditDebitNotes as $note)
                    <div class="py-3 first:pt-0 last:pb-0 space-y-1.5 text-xs">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center flex-wrap gap-1.5">
                                @if ($note->isCreditNote())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">▼ Nota Kredit</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">▲ Nota Debit</span>
                                @endif
                                <span class="font-mono text-muted">{{ $note->note_date->format('d M Y') }}</span>
                            </div>
                            <span class="font-mono font-bold shrink-0 {{ $note->isCreditNote() ? 'text-forest' : 'text-amber-700 dark:text-amber-400' }}">
                                <span class="privacy-balance" data-raw-value="{{ $note->isCreditNote() ? '-' : '+' }} {{ $note->formatted_amount }}">{{ $note->isCreditNote() ? '-' : '+' }} {{ $note->formatted_amount }}</span>
                            </span>
                        </div>
                        <div class="text-charcoal font-medium">{{ $note->description }}</div>
                        <div class="text-[11px] text-muted">
                            Target: {{ $note->installment ? 'Cicilan #' . $note->installment->installment_number : 'Total Rencana' }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line text-muted">
                            <th class="py-2.5 px-3 font-semibold">Tanggal</th>
                            <th class="py-2.5 px-3 font-semibold">Jenis</th>
                            <th class="py-2.5 px-3 font-semibold">Terkait Cicilan</th>
                            <th class="py-2.5 px-3 font-semibold text-right">Nominal</th>
                            <th class="py-2.5 px-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/60">
                        @foreach ($plan->creditDebitNotes as $note)
                            <tr class="hover:bg-cream/40 transition">
                                <td class="py-2.5 px-3 whitespace-nowrap font-mono text-muted">
                                    {{ $note->note_date->format('d M Y') }}
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    @if ($note->isCreditNote())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            <span>▼</span> Nota Kredit
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                            <span>▲</span> Nota Debit
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    @if ($note->installment)
                                        <span class="font-semibold text-charcoal">Cicilan #{{ $note->installment->installment_number }}</span>
                                    @else
                                        <span class="text-muted italic">Total Rencana</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-right font-mono font-bold {{ $note->isCreditNote() ? 'text-forest' : 'text-amber-700 dark:text-amber-400' }}">
                                    <span class="privacy-balance" data-raw-value="{{ $note->isCreditNote() ? '-' : '+' }} {{ $note->formatted_amount }}">{{ $note->isCreditNote() ? '-' : '+' }} {{ $note->formatted_amount }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-muted">
                                    {{ $note->description }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Modal Pembayaran Cicilan -->
    <div id="paymentModal" onclick="if (event.target === this) closePaymentModal()" class="hidden fixed inset-0 z-50 flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-4 sm:p-6 relative my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line mb-4">
                <div class="min-w-0">
                    <h3 id="paymentModalTitle" class="font-serif text-lg text-charcoal font-medium">Bayar Cicilan</h3>
                    <p id="paymentModalSubtitle" class="text-xs text-muted truncate">{{ $plan->name }}</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream cursor-pointer shrink-0" aria-label="Tutup modal">✕</button>
            </div>

            <form id="paymentForm" method="POST" action="" class="space-y-4">
                @csrf

                <div class="p-3 rounded-lg border border-line bg-cream/40 space-y-1">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="without_cash" 
                            id="payment_without_cash" 
                            value="1" 
                            onchange="toggleWithoutCashMode(this.checked)"
                            class="mt-0.5 rounded border-line text-forest focus:ring-forest cursor-pointer"
                        >
                        <div>
                            <span class="text-xs font-bold text-charcoal block">Lunas Sebelum Pakai ARUS (Tanpa Kas)</span>
                            <span class="text-[11px] text-muted block leading-relaxed">
                                Centang jika cicilan ini telah dibayar di masa lampau. Saldo kas/bank tidak akan dipotong.
                            </span>
                        </div>
                    </label>
                </div>

                <div id="sourceAccountWrapper">
                    <label for="source_account_id" class="block text-xs font-semibold text-charcoal mb-1">
                        Rekening Kas / Bank Sumber Dana <span class="text-red">*</span>
                    </label>
                    <select 
                        name="source_account_id" 
                        id="source_account_id" 
                        required 
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="">-- Pilih Rekening Kas / Bank --</option>
                        @foreach ($cashAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo: {{ $acc->formatted_balance }})
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-muted">Saldo rekening ini akan berkurang sebesar nominal pembayaran pokok.</span>
                </div>

                <div id="paymentAmountWrapper">
                    <label for="payment_amount" class="block text-xs font-semibold text-charcoal mb-1">
                        Nominal Pembayaran Pokok (Rp) <span class="text-red">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="payment_amount" 
                        required 
                        min="0.01"
                        step="any"
                        placeholder="Contoh: 150166.67"
                        onfocus="this.select()"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono font-bold text-base focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                    <span id="maxAmountHint" class="text-[10px] text-muted"></span>
                </div>

                <div>
                    <label for="payment_date" class="block text-xs font-semibold text-charcoal mb-1">
                        Tanggal Pembayaran <span class="text-red">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="payment_date" 
                        id="payment_date" 
                        required 
                        value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString() }}"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div>
                    <label for="payment_notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan Pembayaran (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="payment_notes" 
                        placeholder="Contoh: Transfer m-banking BCA, bukti tagihan..."
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closePaymentModal()" 
                        class="w-full sm:w-auto min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitPayment"
                        class="w-full sm:w-auto min-h-[44px] px-5 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="btnSubmitPaymentText">Konfirmasi Pembayaran</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tandai Lunas Tanpa Potong Kas -->
    <div id="markPaidModal" onclick="if (event.target === this) closeMarkPaidModal()" class="hidden fixed inset-0 z-50 flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-4 sm:p-6 relative my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line mb-4">
                <div class="min-w-0">
                    <h3 id="markPaidModalTitle" class="font-serif text-lg text-charcoal font-medium">Tandai Lunas Tanpa Kas</h3>
                    <p id="markPaidModalSubtitle" class="text-xs text-muted truncate">{{ $plan->name }}</p>
                </div>
                <button type="button" onclick="closeMarkPaidModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream cursor-pointer shrink-0" aria-label="Tutup modal">✕</button>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-500/20 text-xs text-emerald-800 dark:text-emerald-300">
                <span class="block font-bold mb-1">💡 Pelunasan Pra-Pencatatan ARUS</span>
                <span class="leading-relaxed block">
                    Gunakan opsi ini jika cicilan ini sebenarnya sudah Anda lunasi di masa lampau sebelum mulai mencatat di ARUS. Pokok cicilan akan ditandai lunas dan sisa hutang kartu kredit berkurang, <strong>tanpa ada pemotongan saldo kas atau bank</strong> Anda saat ini.
                </span>
            </div>

            <form id="markPaidForm" method="POST" action="" class="space-y-4">
                @csrf
                <div>
                    <label for="mark_paid_notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="mark_paid_notes" 
                        placeholder="Contoh: Lunas di m-banking sebelum migrasi ke ARUS"
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeMarkPaidModal()" 
                        class="w-full sm:w-auto min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitMarkPaid"
                        class="w-full sm:w-auto min-h-[44px] px-5 py-2 border border-emerald-600/30 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition cursor-pointer flex items-center justify-center gap-2 shadow-xs"
                    >
                        <span id="btnSubmitMarkPaidText">Konfirmasi Lunas Tanpa Kas</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pelunasan Dipercepat Sekaligus -->
    <div id="payoffModal" onclick="if (event.target === this) closePayoffModal()" class="hidden fixed inset-0 z-50 flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-4 sm:p-6 relative my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line mb-4">
                <div class="min-w-0">
                    <h3 class="font-serif text-lg text-charcoal font-medium">⚡ Pelunasan Sekaligus</h3>
                    <p class="text-xs text-muted truncate">{{ $plan->name }} ({{ $plan->pendingInstallments->count() }} cicilan tersisa)</p>
                </div>
                <button type="button" onclick="closePayoffModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream cursor-pointer shrink-0" aria-label="Tutup modal">✕</button>
            </div>

            <div class="mb-4 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-500/20 text-xs">
                <span class="text-emerald-800 dark:text-emerald-300 block font-medium">
                    Pelunasan sekaligus akan melunasi seluruh sisa <strong>{{ $plan->pendingInstallments->count() }} cicilan pokok</strong> dan mengembalikan limit kredit Anda sepenuhnya.
                </span>
                <div class="mt-2 text-sm font-serif font-bold text-emerald-700 dark:text-emerald-300">
                    Total Pelunasan: <span class="privacy-balance" data-raw-value="{{ $plan->formatted_remaining }}">{{ $plan->formatted_remaining }}</span>
                </div>
            </div>

            <form id="payoffForm" method="POST" action="{{ route('credit.plans.payoff', $plan) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="payoff_source_account_id" class="block text-xs font-semibold text-charcoal mb-1">
                        Rekening Kas / Bank Pembayaran <span class="text-red">*</span>
                    </label>
                    <select 
                        name="source_account_id" 
                        id="payoff_source_account_id" 
                        required 
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="">-- Pilih Rekening Kas / Bank --</option>
                        @foreach ($cashAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo: {{ $acc->formatted_balance }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payoff_payment_date" class="block text-xs font-semibold text-charcoal mb-1">
                        Tanggal Pelunasan <span class="text-red">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="payment_date" 
                        id="payoff_payment_date" 
                        required 
                        value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString() }}"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div>
                    <label for="payoff_notes" class="block text-xs font-semibold text-charcoal mb-1">Catatan (Opsional)</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="payoff_notes" 
                        placeholder="Contoh: Pelunasan dipercepat bulan ke-..."
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closePayoffModal()" 
                        class="w-full sm:w-auto min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitPayoff"
                        class="w-full sm:w-auto min-h-[44px] px-5 py-2 border border-emerald-600/40 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition cursor-pointer flex items-center justify-center gap-2 shadow-xs"
                    >
                        <span id="btnSubmitPayoffText">Konfirmasi Lunasi Sekaligus</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Catat Nota Kredit / Debit -->
    <div id="noteModal" onclick="if (event.target === this) closeNoteModal()" class="hidden fixed inset-0 z-50 flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-lg rounded-2xl border border-line shadow-2xl p-4 sm:p-6 relative my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-line mb-4">
                <div class="min-w-0">
                    <h3 id="noteModalTitle" class="font-serif text-lg text-charcoal font-medium">Nota Kredit / Debit</h3>
                    <p id="noteModalSubtitle" class="text-xs text-muted truncate">{{ $plan->name }}</p>
                </div>
                <button type="button" onclick="closeNoteModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream cursor-pointer shrink-0" aria-label="Tutup modal">✕</button>
            </div>

            <form id="noteForm" method="POST" action="{{ route('credit.notes.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="credit_account_id" value="{{ $plan->credit_account_id }}">
                <input type="hidden" name="installment_plan_id" value="{{ $plan->id }}">

                <div>
                    <label for="note_installment_id" class="block text-xs font-semibold text-charcoal mb-1">
                        Target Penyesuaian (Cicilan)
                    </label>
                    <select 
                        name="installment_id" 
                        id="note_installment_id" 
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest cursor-pointer"
                    >
                        <option value="">-- Seluruh Rencana Cicilan (Global) --</option>
                        @foreach ($plan->installments as $inst)
                            <option value="{{ $inst->id }}">
                                Cicilan #{{ $inst->installment_number }} (Jatuh tempo {{ $inst->due_date->format('d M Y') }} - {{ $inst->formatted_principal }}) {{ $inst->isPaid() ? '[Lunas]' : '[Pending]' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-muted">Pilih cicilan spesifik jika ingin langsung menyesuaikan pokok tagihan bulan tersebut (misal selisih pembulatan).</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-charcoal mb-2">Jenis Nota <span class="text-red">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-forest transition bg-forest-light/50 border-forest text-forest" id="labelNoteTypeCredit">
                            <div class="flex items-center gap-2 mb-1">
                                <input type="radio" name="type" value="credit_note" checked onchange="onNoteTypeChange(this.value)" class="text-forest focus:ring-forest">
                                <span class="font-bold text-xs">▼ Nota Kredit</span>
                            </div>
                            <span class="text-[11px] text-muted leading-tight">Mengurangi hutang pokok (diskon, potongan, selisih bayar lebih kecil).</span>
                        </label>

                        <label class="relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-amber-500 transition border-line text-muted" id="labelNoteTypeDebit">
                            <div class="flex items-center gap-2 mb-1">
                                <input type="radio" name="type" value="debit_note" onchange="onNoteTypeChange(this.value)" class="text-amber-600 focus:ring-amber-500">
                                <span class="font-bold text-xs text-charcoal">▲ Nota Debit</span>
                            </div>
                            <span class="text-[11px] text-muted leading-tight">Menambah hutang pokok (biaya tambahan, pembulatan ke atas, selisih lebih besar).</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="note_amount" class="block text-xs font-semibold text-charcoal mb-1">
                        Nominal Nota (Rp) <span class="text-red">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="note_amount" 
                        required 
                        min="0.01"
                        step="any"
                        placeholder="Contoh: 7.33 atau 8"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal font-mono font-bold text-base focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                    <span id="noteAmountHelp" class="text-[10px] text-muted">Nominal selisih atau penyesuaian yang akan dicatat.</span>
                </div>

                <div>
                    <label for="note_date" class="block text-xs font-semibold text-charcoal mb-1">
                        Tanggal Nota <span class="text-red">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="note_date" 
                        id="note_date" 
                        required 
                        value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->toDateString() }}"
                        class="w-full px-3 py-2.5 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div>
                    <label for="note_description" class="block text-xs font-semibold text-charcoal mb-1">
                        Keterangan / Alasan <span class="text-red">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="description" 
                        id="note_description" 
                        required 
                        placeholder="Contoh: Selisih pembulatan cicilan pertama Rp 8"
                        class="w-full px-3 py-2 text-xs border border-line rounded-lg bg-panel text-charcoal focus:outline-none focus:ring-1 focus:ring-forest"
                    >
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closeNoteModal()" 
                        class="w-full sm:w-auto min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitNote"
                        class="w-full sm:w-auto min-h-[44px] px-5 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="btnSubmitNoteText">Simpan Nota</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPayingInstallment = null;

        function openNoteModal(installment = null) {
            const select = document.getElementById('note_installment_id');
            const desc = document.getElementById('note_description');
            const amt = document.getElementById('note_amount');

            if (installment) {
                select.value = installment.id;
                document.getElementById('noteModalTitle').textContent = 'Nota Penyesuaian Cicilan #' + installment.installment_number;
                desc.value = 'Penyesuaian selisih pembulatan cicilan #' + installment.installment_number;
            } else {
                select.value = '';
                document.getElementById('noteModalTitle').textContent = 'Nota Kredit / Debit';
                desc.value = '';
            }

            amt.value = '';
            onNoteTypeChange('credit_note');
            const radioCredit = document.querySelector('input[name="type"][value="credit_note"]');
            if (radioCredit) radioCredit.checked = true;

            document.getElementById('noteModal').classList.remove('hidden');
            setTimeout(() => (installment ? amt.focus() : select.focus()), 50);
        }

        function closeNoteModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }

        function onNoteTypeChange(type) {
            const labelCredit = document.getElementById('labelNoteTypeCredit');
            const labelDebit = document.getElementById('labelNoteTypeDebit');
            if (!labelCredit || !labelDebit) return;

            if (type === 'credit_note') {
                labelCredit.className = 'relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-forest transition bg-forest-light/50 border-forest text-forest';
                labelDebit.className = 'relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-amber-500 transition border-line text-muted';
            } else {
                labelDebit.className = 'relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-amber-500 transition bg-amber-50/50 dark:bg-amber-950/20 border-amber-500 text-amber-800 dark:text-amber-300';
                labelCredit.className = 'relative flex flex-col p-3 border rounded-xl cursor-pointer hover:border-forest transition border-line text-muted';
            }
        }

        function openPayoffModal() {
            document.getElementById('payoffModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('payoff_source_account_id').focus(), 50);
        }

        function closePayoffModal() {
            document.getElementById('payoffModal').classList.add('hidden');
        }

        function openPaymentModal(installment) {
            currentPayingInstallment = installment;
            document.getElementById('paymentModalTitle').textContent = 'Bayar Cicilan #' + installment.installment_number;
            document.getElementById('payment_without_cash').checked = false;
            toggleWithoutCashMode(false);

            document.getElementById('paymentForm').action = "/credit/installments/" + installment.id + "/pay";
            document.getElementById('payment_amount').value = installment.principal_amount;
            document.getElementById('maxAmountHint').textContent = 'Maksimal pembayaran pokok cicilan: Rp ' + parseInt(installment.principal_amount, 10).toLocaleString('id-ID');
            document.getElementById('paymentModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('source_account_id').focus(), 50);
        }

        function toggleWithoutCashMode(isChecked) {
            const srcWrap = document.getElementById('sourceAccountWrapper');
            const amtWrap = document.getElementById('paymentAmountWrapper');
            const srcInput = document.getElementById('source_account_id');
            const amtInput = document.getElementById('payment_amount');
            const btnText = document.getElementById('btnSubmitPaymentText');

            if (isChecked) {
                srcWrap.classList.add('hidden');
                srcInput.required = false;
                amtWrap.classList.add('hidden');
                amtInput.required = false;
                btnText.textContent = 'Konfirmasi Lunas Tanpa Kas';
                if (currentPayingInstallment) {
                    document.getElementById('paymentForm').action = "/credit/installments/" + currentPayingInstallment.id + "/mark-paid";
                }
            } else {
                srcWrap.classList.remove('hidden');
                srcInput.required = true;
                amtWrap.classList.remove('hidden');
                amtInput.required = true;
                btnText.textContent = 'Konfirmasi Pembayaran';
                if (currentPayingInstallment) {
                    document.getElementById('paymentForm').action = "/credit/installments/" + currentPayingInstallment.id + "/pay";
                }
            }
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        function openMarkPaidModal(installment) {
            document.getElementById('markPaidModalTitle').textContent = 'Tandai Lunas Cicilan #' + installment.installment_number;
            document.getElementById('markPaidForm').action = "/credit/installments/" + installment.id + "/mark-paid";
            document.getElementById('mark_paid_notes').value = '';
            document.getElementById('markPaidModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('mark_paid_notes').focus(), 50);
        }

        function closeMarkPaidModal() {
            document.getElementById('markPaidModal').classList.add('hidden');
        }

        document.getElementById('paymentForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitPayment');
            const txt = document.getElementById('btnSubmitPaymentText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Memproses...';
        });

        document.getElementById('markPaidForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitMarkPaid');
            const txt = document.getElementById('btnSubmitMarkPaidText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Memproses...';
        });

        document.getElementById('payoffForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitPayoff');
            const txt = document.getElementById('btnSubmitPayoffText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Melunasi...';
        });

        document.getElementById('noteForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitNote');
            const txt = document.getElementById('btnSubmitNoteText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Menyimpan...';
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('paymentModal').classList.contains('hidden')) {
                    closePaymentModal();
                }
                if (!document.getElementById('markPaidModal').classList.contains('hidden')) {
                    closeMarkPaidModal();
                }
                if (!document.getElementById('payoffModal').classList.contains('hidden')) {
                    closePayoffModal();
                }
                if (!document.getElementById('noteModal').classList.contains('hidden')) {
                    closeNoteModal();
                }
            }
        });
    </script>
</x-layouts.app>
