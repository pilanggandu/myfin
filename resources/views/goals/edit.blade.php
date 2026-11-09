<x-layouts.app title="Edit Target Tabungan">
    <div class="w-full">
        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / TARGET & BERSAMA / EDIT</div>
            <h1 class="font-serif text-3xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Edit Target: {{ $goal->name }}
            </h1>
            <p class="text-xs text-muted">
                Perbarui rincian target impian, tenggat waktu, atau status pencapaian target di ruang <strong>{{ $space?->name }}</strong>.
            </p>
        </div>

        <form method="POST" action="{{ route('goals.update', $goal) }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="goalEditForm">
            @csrf
            @method('PUT')

            <!-- Kolom Kiri: Form Input (2 Cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Utama Target -->
                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-line pb-2">
                        <h2 class="text-sm font-bold text-forest">
                            1. Informasi Target Finansial
                        </h2>
                        <span class="text-[11px] text-muted">ID: #{{ $goal->id }}</span>
                    </div>

                    <div>
                        <label for="name" class="block text-xs font-bold text-charcoal mb-1">
                            Nama Target <span class="text-red">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="nameInput" 
                            value="{{ old('name', $goal->name) }}" 
                            required 
                            placeholder="Contoh: Dana Darurat 6 Bulan, Liburan ke Jepang, DP Rumah" 
                            class="field-input h-10 px-3 text-xs w-full @error('name') border-red @enderror"
                            oninput="updatePreview()"
                        />
                        @error('name')
                            <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Target Amount & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="target_amount" class="block text-xs font-bold text-charcoal mb-1">
                                Nominal Target (Rp) <span class="text-red">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="target_amount" 
                                id="amountInput" 
                                value="{{ old('target_amount', number_format($goal->target_amount, 0, ',', '.')) }}" 
                                required 
                                placeholder="Contoh: 15.000.000" 
                                class="field-input h-10 px-3 text-sm font-mono font-bold w-full @error('target_amount') border-red @enderror"
                                oninput="updatePreview()"
                            />
                            @error('target_amount')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-bold text-charcoal mb-1">
                                Status Target <span class="text-red">*</span>
                            </label>
                            <select name="status" id="statusSelect" class="field-select h-10 px-3 text-xs w-full @error('status') border-red @enderror" onchange="updatePreview()">
                                <option value="active" {{ old('status', $goal->status) === 'active' ? 'selected' : '' }}>Aktif (Sedang Berjalan)</option>
                                <option value="achieved" {{ old('status', $goal->status) === 'achieved' ? 'selected' : '' }}>Tercapai 🎉</option>
                                <option value="paused" {{ old('status', $goal->status) === 'paused' ? 'selected' : '' }}>Ditunda (Paused)</option>
                                <option value="cancelled" {{ old('status', $goal->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                            @error('status')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dates: Start Date & Target Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-xs font-bold text-charcoal mb-1">
                                Tanggal Mulai <span class="text-red">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="start_date" 
                                id="startDateInput" 
                                value="{{ old('start_date', $goal->start_date->toDateString()) }}" 
                                required 
                                class="field-input h-10 px-3 text-xs w-full @error('start_date') border-red @enderror"
                                onchange="updatePreview()"
                            />
                            @error('start_date')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="target_date" class="block text-xs font-bold text-charcoal mb-1">
                                Target Tanggal Tercapai (Opsional)
                            </label>
                            <input 
                                type="date" 
                                name="target_date" 
                                id="targetDateInput" 
                                value="{{ old('target_date', $goal->target_date?->toDateString()) }}" 
                                class="field-input h-10 px-3 text-xs w-full @error('target_date') border-red @enderror"
                                onchange="updatePreview()"
                            />
                            @error('target_date')
                                <p class="text-[11px] text-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Ikon Emoji & Warna Tema -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1.5">Pilih Ikon Target</label>
                            <div class="flex flex-wrap gap-1.5">
                                @php
                                    $icons = ['🎯', '🏠', '✈️', '🚗', '💻', '🛡️', '💍', '🎓', '👶', '🏖️', '📱', '🏍️', '💵', '🎁'];
                                    $currentIcon = old('icon', $goal->icon ?: '🎯');
                                @endphp
                                @foreach ($icons as $ic)
                                    <button 
                                        type="button" 
                                        onclick="selectIcon('{{ $ic }}')" 
                                        class="w-8 h-8 rounded-lg bg-cream dark:bg-line/40 hover:bg-forest/20 text-lg flex items-center justify-center transition cursor-pointer select-icon-btn border {{ $currentIcon === $ic ? 'border-forest ring-2 ring-forest/30' : 'border-transparent' }}"
                                        data-icon="{{ $ic }}"
                                    >
                                        {{ $ic }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="icon" id="selectedIconInput" value="{{ $currentIcon }}" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-charcoal mb-1.5">Warna Tema Kartu</label>
                            <div class="flex flex-wrap gap-2 pt-1">
                                @php
                                    $colors = ['#174C40', '#256353', '#0284c7', '#7c3aed', '#db2777', '#ea580c', '#ca8a04', '#475569'];
                                    $currentColor = old('color', $goal->color ?: '#174C40');
                                @endphp
                                @foreach ($colors as $col)
                                    <button 
                                        type="button" 
                                        onclick="selectColor('{{ $col }}')" 
                                        class="w-7 h-7 rounded-full border-2 transition cursor-pointer select-color-btn"
                                        style="background-color: {{ $col }}; border-color: {{ $currentColor === $col ? '#ffffff' : 'transparent' }}; box-shadow: {{ $currentColor === $col ? '0 0 0 2px ' . $col : 'none' }};"
                                        data-color="{{ $col }}"
                                    ></button>
                                @endforeach
                            </div>
                            <input type="hidden" name="color" id="selectedColorInput" value="{{ $currentColor }}" />
                        </div>
                    </div>
                </div>

                <!-- Penempatan Rekening & Kategori -->
                <div class="panel-card p-5 sm:p-6 space-y-4">
                    <h2 class="text-sm font-bold text-forest border-b border-line pb-2">
                        2. Rekening Penampung & Kategori
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="account_id" class="block text-xs font-bold text-charcoal mb-1">
                                Rekening Penampung
                            </label>
                            <select name="account_id" id="accountSelect" class="field-select h-10 px-3 text-xs w-full" onchange="updatePreview()">
                                <option value="">Semua Rekening / Virtual</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('account_id', $goal->account_id) == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Rp {{ number_format($acc->currentBalance(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-muted">Rekening bank / e-wallet tempat dana target ini disimpan.</span>
                        </div>

                        <div>
                            <label for="category_id" class="block text-xs font-bold text-charcoal mb-1">
                                Kategori Anggaran (Opsional)
                            </label>
                            <select name="category_id" id="categorySelect" class="field-select h-10 px-3 text-xs w-full">
                                <option value="">Tanpa Kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $goal->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }} ({{ ucfirst($cat->type) }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-muted">Kategori default saat penyetoran dicatat sebagai pengeluaran kas.</span>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold text-charcoal mb-1">
                            Catatan / Rencana Target (Opsional)
                        </label>
                        <textarea 
                            name="notes" 
                            id="notesInput" 
                            rows="2" 
                            placeholder="Catatan tambahan, strategi menabung, atau alasan mengapa target ini penting..."
                            class="field-input p-3 text-xs w-full"
                        >{{ old('notes', $goal->notes) }}</textarea>
                    </div>
                </div>

                <!-- Submit & Delete Actions -->
                <div class="flex items-center justify-between pt-2">
                    <button 
                        type="button" 
                        onclick="if(confirm('Apakah Anda yakin ingin menghapus target ini beserta seluruh riwayat setorannya? Tindakan ini tidak dapat dibatalkan.')) { document.getElementById('deleteGoalForm').submit(); }" 
                        class="text-xs text-red hover:underline font-semibold"
                    >
                        Hapus Target Ini
                    </button>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('goals.show', $goal) }}" class="btn-arus-secondary text-xs h-10 px-5">
                            Batal
                        </a>
                        <button type="submit" class="btn-arus-primary text-xs h-10 px-6">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Live Preview Card (1 Col) -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <div class="text-xs font-bold uppercase tracking-wider text-muted">Pratinjau Kartu Target</div>
                    
                    <div class="panel-card p-0 overflow-hidden border border-line shadow-sm">
                        <!-- Preview Color Bar -->
                        <div id="previewColorBar" class="h-2 w-full" style="background-color: {{ $goal->color ?: '#174C40' }}"></div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <div id="previewIcon" class="w-12 h-12 rounded-xl bg-cream dark:bg-line/40 flex items-center justify-center text-2xl shadow-2xs">
                                        {{ $goal->icon ?: '🎯' }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 id="previewName" class="font-bold text-base text-charcoal truncate">
                                            {{ $goal->name }}
                                        </h3>
                                        <div id="previewAccount" class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                            <span>💳</span>
                                            <span>{{ $goal->account ? $goal->account->name : 'Semua Rekening' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span id="previewStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-forest/10 text-forest border border-forest/20">
                                    {{ ucfirst($goal->status) }}
                                </span>
                            </div>

                            <!-- Preview Amount & Progress -->
                            <div class="mt-4 pt-3 border-t border-line/60">
                                <div class="flex items-baseline justify-between text-xs mb-1">
                                    <span class="text-muted">Terkumpul saat ini</span>
                                    <span class="font-mono font-bold text-forest">
                                        Rp {{ number_format($goal->current_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex items-baseline justify-between text-xs mb-2">
                                    <span class="text-muted">Target akhir</span>
                                    <span id="previewTargetAmount" class="font-mono font-bold text-charcoal">
                                        Rp {{ number_format($goal->target_amount, 0, ',', '.') }}
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-cream dark:bg-line/40 rounded-full h-2.5 overflow-hidden mb-2">
                                    <div 
                                        id="previewProgressBar" 
                                        class="h-full rounded-full transition-all duration-300"
                                        style="width: {{ min(100, $goal->percentage()) }}%; background-color: {{ $goal->color ?: '#174C40' }}"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-muted">
                                    <span id="previewPercentage" class="font-bold text-charcoal">{{ $goal->percentage() }}%</span>
                                    <span id="previewRemaining">Sisa: Rp {{ number_format($goal->remainingAmount(), 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Target Date & Recommendation Info -->
                            <div id="previewRecommendationBox" class="mt-4 p-3 rounded-lg bg-cream/70 dark:bg-line/30 text-xs space-y-1">
                                <div class="text-[10px] uppercase font-bold tracking-wider text-muted">Rekomendasi Rutin</div>
                                <div class="font-bold text-forest" id="previewMonthlyRec">
                                    Rp {{ number_format($goal->monthlyRecommendation(), 0, ',', '.') }} <span class="text-[11px] font-normal text-muted">/ bulan</span>
                                </div>
                                <div class="text-[10px] text-muted" id="previewDaysRemaining">
                                    Tenggat: {{ $goal->target_date ? $goal->target_date->translatedFormat('d M Y') : 'Belum ditentukan' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-card p-4 text-xs text-muted space-y-2">
                        <div class="font-bold text-charcoal flex items-center gap-1.5">
                            <span>💡</span>
                            <span>Tips Perubahan Target</span>
                        </div>
                        <p class="leading-relaxed">
                            Bila Anda mengubah nominal target ke angka yang lebih rendah atau lebih tinggi, kalkulasi sisa dana dan rekomendasi bulanan akan disesuaikan secara otomatis.
                        </p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Hidden Delete Form -->
        <form id="deleteGoalForm" method="POST" action="{{ route('goals.destroy', $goal) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @push('scripts')
    <script>
        const currentAmount = {{ (float) $goal->current_amount }};

        function parseCurrency(str) {
            if (!str) return 0;
            const cleaned = str.toString().replace(/[^0-9]/g, '');
            return parseFloat(cleaned) || 0;
        }

        function formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function selectIcon(icon) {
            document.getElementById('selectedIconInput').value = icon;
            document.getElementById('previewIcon').textContent = icon;
            document.querySelectorAll('.select-icon-btn').forEach(btn => {
                if (btn.dataset.icon === icon) {
                    btn.classList.add('border-forest', 'ring-2', 'ring-forest/30');
                    btn.classList.remove('border-transparent');
                } else {
                    btn.classList.remove('border-forest', 'ring-2', 'ring-forest/30');
                    btn.classList.add('border-transparent');
                }
            });
        }

        function selectColor(color) {
            document.getElementById('selectedColorInput').value = color;
            document.getElementById('previewColorBar').style.backgroundColor = color;
            document.getElementById('previewProgressBar').style.backgroundColor = color;

            document.querySelectorAll('.select-color-btn').forEach(btn => {
                if (btn.dataset.color === color) {
                    btn.style.borderColor = '#ffffff';
                    btn.style.boxShadow = `0 0 0 2px ${color}`;
                } else {
                    btn.style.borderColor = 'transparent';
                    btn.style.boxShadow = 'none';
                }
            });
        }

        function updatePreview() {
            const name = document.getElementById('nameInput').value.trim() || 'Nama Target Anda';
            const targetAmount = parseCurrency(document.getElementById('amountInput').value);
            const targetDateStr = document.getElementById('targetDateInput').value;
            const accountSelect = document.getElementById('accountSelect');
            const accountText = accountSelect.options[accountSelect.selectedIndex]?.text || '';
            const statusSelect = document.getElementById('statusSelect');
            const statusVal = statusSelect.value;

            document.getElementById('previewName').textContent = name;
            document.getElementById('previewAccount').querySelector('span:last-child').textContent = 
                accountSelect.value ? accountText.split('(')[0].trim() : 'Semua Rekening';

            // Status Badge
            const statusBadge = document.getElementById('previewStatusBadge');
            statusBadge.textContent = statusSelect.options[statusSelect.selectedIndex]?.text || statusVal;

            // Target Amount & Remaining
            document.getElementById('previewTargetAmount').textContent = 'Rp ' + formatRupiah(targetAmount);

            const remaining = Math.max(0, targetAmount - currentAmount);
            document.getElementById('previewRemaining').textContent = 'Sisa: Rp ' + formatRupiah(remaining);

            const pct = targetAmount > 0 ? Math.min(100, Math.round((currentAmount / targetAmount) * 100)) : 0;
            document.getElementById('previewProgressBar').style.width = pct + '%';
            document.getElementById('previewPercentage').textContent = pct + '%';

            // Recommendations
            if (targetDateStr) {
                const targetDate = new Date(targetDateStr);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const diffTime = targetDate - today;
                const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
                const months = Math.max(1, diffDays / 30.44);
                const monthlyRec = Math.ceil(remaining / months);

                document.getElementById('previewMonthlyRec').innerHTML = 
                    'Rp ' + formatRupiah(monthlyRec) + ' <span class="text-[11px] font-normal text-muted">/ bulan</span>';
                document.getElementById('previewDaysRemaining').textContent = 
                    diffDays + ' hari tersisa (' + targetDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) + ')';
            } else {
                document.getElementById('previewMonthlyRec').innerHTML = 
                    '<span class="text-xs text-muted">Tanpa tenggat waktu</span>';
                document.getElementById('previewDaysRemaining').textContent = 'Estimasi tidak terbatas';
            }
        }

        // Masking auto ribuan input
        document.getElementById('amountInput').addEventListener('input', function(e) {
            let val = this.value.replace(/[^0-9]/g, '');
            if (val) {
                this.value = new Intl.NumberFormat('id-ID').format(val);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
    @endpush
</x-layouts.app>
