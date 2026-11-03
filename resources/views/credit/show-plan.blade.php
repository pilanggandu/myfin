<x-layouts.app :title="'Cicilan: ' . $plan->name">
    <!-- Back Link & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('credit.index', ['tab' => $plan->isActive() ? 'plans' : 'history']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-charcoal transition mb-2">
                <span>←</span>
                <span>Kembali ke Daftar Kredit & Cicilan</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider {{ $plan->isCompleted() ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($plan->isActive() ? 'bg-forest/10 text-forest' : 'bg-red/10 text-red') }}">
                    {{ $plan->isCompleted() ? 'Lunas Selesai' : ($plan->isActive() ? 'Cicilan Berjalan' : 'Dibatalkan') }}
                </span>
                <span class="text-xs text-muted">ID Rencana: #{{ $plan->id }}</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl text-charcoal font-medium mt-1">
                {{ $plan->name }}
            </h1>
        </div>

        <div class="flex items-center gap-2">
            @if ($plan->purchaseTransaction)
                <a 
                    href="{{ route('transactions.show', $plan->purchaseTransaction) }}" 
                    class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2 border border-line rounded-lg text-xs font-semibold text-charcoal bg-panel hover:bg-cream transition"
                >
                    <span>🔍 Lihat Transaksi Belanja Awal</span>
                </a>
            @endif

            @if ($plan->isActive() && $plan->paidInstallments->isEmpty())
                <form method="POST" action="{{ route('credit.plans.void', $plan) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan rencana cicilan dan mem-void transaksi pembelian ini? Tindakan tidak dapat diurungkan.')">
                    @csrf
                    <button 
                        type="submit" 
                        class="min-h-[44px] inline-flex items-center gap-1 px-3.5 py-2 border border-red/30 text-red hover:bg-red/10 rounded-lg text-xs font-bold transition cursor-pointer"
                    >
                        <span>Batalkan Rencana (Void)</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Alert Status Feedback -->
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl border border-emerald-500/20 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
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
                    {{ $plan->formatted_total_amount }}
                </div>
                <span class="text-xs text-muted">0% Bunga (Tanpa Bunga)</span>
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
                <div class="flex items-center gap-4">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Terbayar: {{ $plan->formatted_paid }}</span>
                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Sisa Pokok: {{ $plan->formatted_remaining }}</span>
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

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b border-line text-muted">
                        <th class="py-3 px-3 font-semibold">No</th>
                        <th class="py-3 px-3 font-semibold">Jatuh Tempo</th>
                        <th class="py-3 px-3 font-semibold text-right">Pokok Hutang</th>
                        <th class="py-3 px-3 font-semibold text-right">Bunga & Biaya</th>
                        <th class="py-3 px-3 font-semibold text-right">Total Tagihan</th>
                        <th class="py-3 px-3 font-semibold text-center">Status</th>
                        <th class="py-3 px-3 font-semibold">Keterangan / Pembayaran</th>
                        <th class="py-3 px-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60">
                    @foreach ($plan->installments as $inst)
                        <tr class="hover:bg-panel transition {{ $inst->isPaid() ? 'bg-cream/20' : '' }}">
                            <td class="py-3.5 px-3 font-bold text-charcoal">
                                #{{ $inst->installment_number }}
                            </td>
                            <td class="py-3.5 px-3 text-muted whitespace-nowrap">
                                {{ $inst->due_date->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-semibold text-charcoal">
                                {{ $inst->formatted_principal }}
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono text-muted">
                                Rp 0
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-bold text-charcoal">
                                {{ $inst->formatted_total_amount }}
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if ($inst->isPaid())
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold px-2 py-0.5 rounded">
                                        <span>✓</span> Lunas
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold px-2 py-0.5 rounded">
                                        <span>⏳</span> Belum Bayar
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
                                @else
                                    <span class="text-[11px] text-muted">Menunggu pembayaran</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right">
                                @if ($inst->isPending() && $plan->isActive())
                                    <button 
                                        type="button" 
                                        onclick="openPaymentModal({{ json_encode($inst) }})" 
                                        class="min-h-[38px] inline-flex items-center justify-center px-3 py-1.5 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs whitespace-nowrap"
                                    >
                                        Bayar Cicilan
                                    </button>
                                @elseif ($inst->isPaid())
                                    <span class="text-xs text-muted font-mono">Tuntas</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Pembayaran Cicilan -->
    <div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-charcoal/60 backdrop-blur-xs">
        <div class="panel-card bg-panel w-full max-w-md rounded-2xl border border-line shadow-2xl p-6 relative">
            <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
                <div>
                    <h3 id="paymentModalTitle" class="font-serif text-lg text-charcoal font-medium">Bayar Cicilan</h3>
                    <p id="paymentModalSubtitle" class="text-xs text-muted">{{ $plan->name }}</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-muted hover:text-charcoal cursor-pointer">✕</button>
            </div>

            <form id="paymentForm" method="POST" action="" class="space-y-4">
                @csrf

                <div>
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

                <div>
                    <label for="payment_amount" class="block text-xs font-semibold text-charcoal mb-1">
                        Nominal Pembayaran Pokok (Rp) <span class="text-red">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        id="payment_amount" 
                        required 
                        min="1"
                        placeholder="Contoh: 400000"
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

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-line">
                    <button 
                        type="button" 
                        onclick="closePaymentModal()" 
                        class="min-h-[44px] px-4 py-2 border border-line rounded-lg text-xs font-bold text-muted hover:text-charcoal bg-panel transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        id="btnSubmitPayment"
                        class="min-h-[44px] px-5 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer flex items-center gap-2"
                    >
                        <span id="btnSubmitPaymentText">Konfirmasi Pembayaran</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openPaymentModal(installment) {
            document.getElementById('paymentModalTitle').textContent = 'Bayar Cicilan #' + installment.installment_number;
            document.getElementById('paymentForm').action = "/credit/installments/" + installment.id + "/pay";
            document.getElementById('payment_amount').value = installment.principal_amount;
            document.getElementById('maxAmountHint').textContent = 'Maksimal pembayaran pokok cicilan: Rp ' + parseInt(installment.principal_amount, 10).toLocaleString('id-ID');
            document.getElementById('paymentModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('source_account_id').focus(), 50);
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        document.getElementById('paymentForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitPayment');
            const txt = document.getElementById('btnSubmitPaymentText');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            txt.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> Memproses Pembayaran...';
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('paymentModal').classList.contains('hidden')) {
                closePaymentModal();
            }
        });
    </script>
    @endpush
</x-layouts.app>
