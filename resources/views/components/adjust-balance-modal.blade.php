<div id="adjustBalanceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="adjustModalTitle" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-xs transition-opacity" onclick="closeAdjustModal()"></div>

    <div class="flex min-h-full items-start sm:items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative transform overflow-y-auto max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] rounded-2xl bg-panel border border-line text-left shadow-xl transition-all w-full my-2 sm:my-8 sm:max-w-lg">
            <form id="adjustBalanceForm" method="POST" action="">
                @csrf
                <div class="p-4 sm:p-6 space-y-4">
                    <div class="flex items-start sm:items-center justify-between gap-2 pb-3 border-b border-line">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-cream border border-line flex items-center justify-center text-base shrink-0">
                                ⚖️
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-charcoal" id="adjustModalTitle">
                                    Penyesuaian Saldo Rekening
                                </h3>
                                <p class="text-[11px] text-muted truncate" id="adjustModalAccountName">
                                    Rekening
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="closeAdjustModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center text-lg leading-none cursor-pointer shrink-0" aria-label="Tutup modal">
                            &times;
                        </button>
                    </div>

                    <div class="p-3 rounded-xl bg-sidebar border border-line flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-muted font-medium">Saldo Tercatat Saat Ini:</span>
                        <span id="adjustModalCurrentDisplay" class="font-mono font-bold text-charcoal text-sm">
                            Rp 0
                        </span>
                    </div>

                    <div>
                        <label for="adjustModalTargetDisplay" class="block text-xs font-bold text-charcoal mb-1">
                            Saldo Riil Baru (Target)
                        </label>
                        <div class="relative field-prefix-wrapper">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted font-bold text-sm select-none z-10">
                                Rp
                            </span>
                            <input 
                                type="text" 
                                inputmode="numeric" 
                                id="adjustModalTargetDisplay" 
                                placeholder="0" 
                                required
                                class="field-input has-prefix font-mono font-bold text-base w-full bg-panel"
                                style="padding-left: 44px !important;"
                                oninput="onAdjustTargetInput(this.value)"
                            />
                            <input type="hidden" name="target_balance" id="adjustModalTargetHidden" required />
                        </div>
                        <p class="text-[11px] text-muted mt-1">
                            Masukkan nominal saldo sebenarnya yang ada di rekening fisik atau mutasi bank.
                        </p>
                    </div>

                    <div id="adjustDiffContainer" class="p-3 rounded-xl border border-line bg-panel text-xs hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-muted font-medium">Selisih Penyesuaian:</span>
                            <span id="adjustDiffAmount" class="font-mono font-bold text-charcoal">
                                Rp 0
                            </span>
                        </div>
                        <div id="adjustDiffDescription" class="text-[11px] text-muted mt-1 font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal Penyesuaian
                            </label>
                            <input 
                                type="date" 
                                name="adjustment_date" 
                                id="adjustModalDate"
                                value="{{ now()->toDateString() }}" 
                                required 
                                class="field-input w-full text-xs"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1">
                                Waktu
                            </label>
                            <input 
                                type="time" 
                                name="adjustment_time" 
                                id="adjustModalTime"
                                value="{{ now()->format('H:i') }}" 
                                class="field-input w-full text-xs"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">
                            Alasan / Keterangan (Opsional)
                        </label>
                        <input 
                            type="text" 
                            name="reason" 
                            placeholder="Contoh: Opname kas fisik, selisih admin/bunga bank" 
                            class="field-input w-full text-xs"
                        />
                    </div>
                </div>

                <div class="p-4 bg-sidebar/50 border-t border-line flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2">
                    <button type="button" onclick="closeAdjustModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] text-xs font-semibold py-2 sm:py-1.5 px-3">
                        Batal
                    </button>
                    <button type="submit" id="adjustSubmitBtn" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] text-xs font-bold py-2 sm:py-1.5 px-4 inline-flex items-center justify-center gap-1.5">
                        <span>⚖️</span>
                        <span>Terapkan Penyesuaian</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentAccountRawBalance = 0;

    function openAdjustModal(accountId, accountName, currentBalance) {
        currentAccountRawBalance = parseFloat(currentBalance) || 0;
        
        const form = document.getElementById('adjustBalanceForm');
        form.action = '/accounts/' + accountId + '/adjust';

        document.getElementById('adjustModalAccountName').textContent = accountName;
        document.getElementById('adjustModalCurrentDisplay').textContent = 'Rp ' + formatRupiahNumber(currentAccountRawBalance);
        
        const targetDisplay = document.getElementById('adjustModalTargetDisplay');
        const targetHidden = document.getElementById('adjustModalTargetHidden');
        targetDisplay.value = formatRupiahNumber(currentAccountRawBalance);
        targetHidden.value = currentAccountRawBalance;

        updateAdjustDiffDisplay(currentAccountRawBalance);

        document.getElementById('adjustBalanceModal').classList.remove('hidden');
        setTimeout(() => {
            targetDisplay.focus();
            targetDisplay.select();
        }, 50);
    }

    function closeAdjustModal() {
        document.getElementById('adjustBalanceModal').classList.add('hidden');
    }

    function onAdjustTargetInput(rawVal) {
        const cleaned = rawVal.replace(/[^0-9]/g, '');
        const numVal = cleaned ? parseInt(cleaned, 10) : 0;
        
        document.getElementById('adjustModalTargetDisplay').value = formatRupiahNumber(numVal);
        document.getElementById('adjustModalTargetHidden').value = numVal;

        updateAdjustDiffDisplay(numVal);
    }

    function updateAdjustDiffDisplay(targetVal) {
        const diff = targetVal - currentAccountRawBalance;
        const diffBox = document.getElementById('adjustDiffContainer');
        const diffAmount = document.getElementById('adjustDiffAmount');
        const diffDesc = document.getElementById('adjustDiffDescription');

        diffBox.classList.remove('hidden');

        if (diff > 0) {
            diffAmount.className = 'font-mono font-bold text-emerald-600 dark:text-emerald-400';
            diffAmount.textContent = '+ Rp ' + formatRupiahNumber(diff);
            diffDesc.innerHTML = '<span class="text-emerald-700 dark:text-emerald-300 font-semibold">Saldo bertambah.</span> Sistem akan mencatat transaksi <strong>Pemasukan Penyesuaian</strong> sebesar Rp ' + formatRupiahNumber(diff) + '.';
        } else if (diff < 0) {
            diffAmount.className = 'font-mono font-bold text-rose-600 dark:text-rose-400';
            diffAmount.textContent = '- Rp ' + formatRupiahNumber(Math.abs(diff));
            diffDesc.innerHTML = '<span class="text-rose-700 dark:text-rose-300 font-semibold">Saldo berkurang.</span> Sistem akan mencatat transaksi <strong>Pengeluaran Penyesuaian</strong> sebesar Rp ' + formatRupiahNumber(Math.abs(diff)) + '.';
        } else {
            diffAmount.className = 'font-mono font-bold text-charcoal';
            diffAmount.textContent = 'Rp 0 (Sama)';
            diffDesc.innerHTML = '<span class="text-muted">Target sama dengan saldo saat ini. Tidak ada mutasi baru yang akan dibuat.</span>';
        }
    }

    function formatRupiahNumber(num) {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('adjustBalanceModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeAdjustModal();
            }
        }
    });
</script>
