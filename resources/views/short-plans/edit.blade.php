<x-layouts.app title="Edit Short Plan">
    <div class="max-w-3xl mx-auto w-full">
        <div class="mb-6">
            <a href="{{ route('short-plans.show', $shortPlan) }}" class="text-xs font-bold text-forest hover:underline inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Detail Rencana
            </a>
            <h1 class="font-serif text-3xl text-forest font-normal tracking-tight mt-2 mb-0.5">
                Edit Short Plan
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                Perbarui detail rencana pengeluaran di ruang <strong class="text-charcoal">{{ $space?->name }}</strong>.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-xs text-rose-800 dark:text-rose-200 space-y-1" role="alert">
                <div class="font-bold">Periksa kembali isian form:</div>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('short-plans.update', $shortPlan) }}" id="editShortPlanForm" class="panel-card p-5 sm:p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="editTitle" class="field-label">
                    Nama Rencana <span class="text-red">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    id="editTitle"
                    value="{{ old('title', $shortPlan->title) }}"
                    required
                    maxlength="255"
                    class="field-input @error('title') has-error @enderror"
                >
                @error('title')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="editPlannedAmount" class="field-label">
                        Estimasi Biaya (Rp) <span class="text-red">*</span>
                    </label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="planned_amount"
                        id="editPlannedAmount"
                        value="{{ old('planned_amount', number_format((float) $shortPlan->planned_amount, 0, ',', '.')) }}"
                        required
                        class="field-input font-mono font-bold @error('planned_amount') has-error @enderror"
                        oninput="this.value = this.value.replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                    >
                    @error('planned_amount')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="editPriority" class="field-label">Prioritas <span class="text-red">*</span></label>
                    <select name="priority" id="editPriority" class="field-select text-xs">
                        <option value="high" {{ old('priority', $shortPlan->priority) === 'high' ? 'selected' : '' }}>Mendesak</option>
                        <option value="medium" {{ old('priority', $shortPlan->priority) === 'medium' ? 'selected' : '' }}>Normal</option>
                        <option value="low" {{ old('priority', $shortPlan->priority) === 'low' ? 'selected' : '' }}>Santai</option>
                    </select>
                </div>

                <div>
                    <label for="editStatus" class="field-label">Status</label>
                    <select name="status" id="editStatus" class="field-select text-xs">
                        <option value="planned" {{ old('status', $shortPlan->status) === 'planned' ? 'selected' : '' }}>Direncanakan</option>
                        <option value="done" {{ old('status', $shortPlan->status) === 'done' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status', $shortPlan->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="editTargetDate" class="field-label">Target Tanggal (Opsional)</label>
                    <input
                        type="date"
                        name="target_date"
                        id="editTargetDate"
                        value="{{ old('target_date', $shortPlan->target_date?->toDateString()) }}"
                        class="field-input text-xs"
                    >
                </div>

                <div>
                    <label for="editAccountId" class="field-label">Rekening Rencana (Opsional)</label>
                    <select
                        name="account_id"
                        id="editAccountId"
                        class="field-select text-xs"
                        data-searchable-picker="true"
                        data-picker-kind="account"
                        data-allow-empty="true"
                        data-recent-ids='@json($recentAccountIds ?? [])'
                        data-search-placeholder="Cari rekening..."
                    >
                        <option value="">Belum ditentukan</option>
                        @foreach ($liquidAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ (string) old('account_id', $shortPlan->account_id) === (string) $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="editCategoryId" class="field-label">Kategori Pengeluaran (Opsional)</label>
                    <select
                        name="category_id"
                        id="editCategoryId"
                        class="field-select text-xs"
                        data-category-picker="true"
                        data-allow-empty="true"
                        data-recent-ids='@json($recentExpenseCategoryIds ?? [])'
                        data-search-placeholder="Cari kategori..."
                    >
                        <option value="">Pilih Kategori...</option>
                        @foreach ($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('category_id', $shortPlan->category_id) === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($members->isNotEmpty())
                    <div>
                        <label for="editMemberId" class="field-label">Member Terkait (Opsional)</label>
                        <select name="member_id" id="editMemberId" class="field-select text-xs">
                            <option value="">Tidak ditentukan</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" {{ (string) old('member_id', $shortPlan->member_id) === (string) $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div>
                <label for="editNotes" class="field-label">Catatan (Opsional)</label>
                <textarea
                    name="notes"
                    id="editNotes"
                    rows="3"
                    maxlength="1000"
                    class="field-input h-auto py-2.5 text-xs"
                >{{ old('notes', $shortPlan->notes) }}</textarea>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-line">
                <a href="{{ route('short-plans.show', $shortPlan) }}" class="btn-arus-secondary min-h-[44px] sm:min-h-[38px] text-xs">
                    Batal
                </a>
                <button type="submit" id="editPlanSubmitBtn" class="btn-arus-primary min-h-[44px] sm:min-h-[38px] text-xs cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.getElementById('editShortPlanForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('editPlanSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            }
        });
    </script>
    @endpush
</x-layouts.app>
