<x-layouts.app title="Master Member">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / MASTER DATA</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Master Member
            </h1>
            <p class="text-xs text-muted">Tandai pihak atau anggota keluarga yang terkait dengan pengeluaran, tagihan, dan mutasi keuangan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('members.index', ['status' => 'active']) }}" class="min-h-[44px] sm:min-h-0 px-3.5 py-2 sm:py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center justify-center {{ $status !== 'archived' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}">
                Aktif ({{ $activeCount }})
            </a>
            <a href="{{ route('members.index', ['status' => 'archived']) }}" class="min-h-[44px] sm:min-h-0 px-3.5 py-2 sm:py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center justify-center {{ $status === 'archived' ? 'bg-forest text-white' : 'bg-panel border border-line text-muted hover:text-charcoal' }}">
                Diarsipkan ({{ $archivedCount }})
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Form Tambah Member Baru -->
        <div class="lg:col-span-4">
            <div class="panel-card p-5 rounded-2xl border border-line bg-panel shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-line pb-3">
                    <span class="w-7 h-7 rounded-lg bg-forest/10 text-forest flex items-center justify-center text-sm font-bold">👤</span>
                    <div>
                        <h3 class="text-xs font-bold text-charcoal">Tambah Member Baru</h3>
                        <p class="text-[11px] text-muted">Contoh: Me, Parent, Family, Anak</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('members.store') }}" class="space-y-3" novalidate>
                    @csrf
                    <div>
                        <label for="memberName" class="block text-xs font-bold text-charcoal mb-1">
                            Nama Member <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="memberName" 
                            value="{{ old('name') }}"
                            placeholder="Contoh: Me, Parent, Family" 
                            class="field-input w-full text-xs" 
                            required 
                        />
                        @error('name')
                            <div class="field-error mt-1 text-xs text-rose-500">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-charcoal mb-1">
                            Warna Aksen Badge
                        </label>
                        <div class="flex items-center gap-2 flex-wrap">
                            @php
                                $colors = ['#0f4c3a', '#1e3a8a', '#7c2d12', '#4c1d95', '#065f46', '#831843', '#334155'];
                            @endphp
                            @foreach ($colors as $color)
                                <label class="cursor-pointer p-1 sm:p-0 inline-flex items-center justify-center">
                                    <input type="radio" name="color" value="{{ $color }}" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="w-8 h-8 sm:w-6 sm:h-6 rounded-full border-2 border-transparent peer-checked:border-charcoal peer-checked:scale-110 transition shadow-xs" style="background-color: {{ $color }};"></div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="memberNotes" class="block text-xs font-bold text-charcoal mb-1">
                            Catatan (Opsional)
                        </label>
                        <textarea 
                            name="notes" 
                            id="memberNotes" 
                            rows="2" 
                            placeholder="Keterangan tambahan terkait member..." 
                            class="field-input w-full text-xs"
                        >{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn-arus-primary w-full min-h-[44px] sm:min-h-[40px] text-xs font-bold cursor-pointer">
                        + Simpan Member
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Member -->
        <div class="lg:col-span-8">
            <div class="panel-card rounded-2xl border border-line bg-panel shadow-xs overflow-hidden">
                <div class="p-4 border-b border-line flex items-center justify-between">
                    <span class="text-xs font-bold text-charcoal">
                        Daftar Member ({{ $members->count() }})
                    </span>
                    <span class="text-[11px] text-muted">
                        Isolasi ruang aktif
                    </span>
                </div>

                @if ($members->isEmpty())
                    <div class="p-8 text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-sidebar border border-line flex items-center justify-center mx-auto text-xl">
                            👥
                        </div>
                        <div class="text-sm font-bold text-charcoal">Belum Ada Member {{ $status === 'archived' ? 'yang Diarsipkan' : '' }}</div>
                        <p class="text-xs text-muted max-w-sm mx-auto">
                            {{ $status === 'archived' ? 'Tidak ada member dalam daftar arsip ruang ini.' : 'Tambahkan entitas pihak (seperti Me, Parent, Family) untuk menandai tagihan dan transaksi keuangan.' }}
                        </p>
                    </div>
                @else
                    <div class="md:hidden divide-y divide-line">
                        @foreach ($members as $m)
                            <div class="p-4 space-y-2.5 {{ $m->isArchived() ? 'opacity-60' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                        <div class="w-9 h-9 rounded-full text-white font-bold flex items-center justify-center text-xs shadow-xs shrink-0" style="background-color: {{ $m->color ?: '#0f4c3a' }};">
                                            {{ strtoupper(substr($m->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-charcoal text-sm flex items-center flex-wrap gap-1.5">
                                                <span class="truncate">{{ $m->name }}</span>
                                                @if ($m->isArchived())
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-muted/20 text-muted">ARSIP</span>
                                                @endif
                                            </div>
                                            @if ($m->notes)
                                                <p class="text-[11px] text-muted mt-0.5 line-clamp-2">{{ $m->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button 
                                            type="button" 
                                            onclick="openEditMemberModal({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ $m->color ?: '#0f4c3a' }}', '{{ addslashes($m->notes ?? '') }}')"
                                            class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                            title="Ubah Member"
                                            aria-label="Ubah Member"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <form method="POST" action="{{ route('members.destroy', $m) }}" class="inline" onsubmit="return confirm('{{ $m->isArchived() ? 'Pulihkan member ini?' : 'Arsipkan member ini?' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-rose-600 hover:border-rose-500/40 hover:bg-rose-500/10 transition inline-flex items-center justify-center cursor-pointer"
                                                title="{{ $m->isArchived() ? 'Pulihkan Member' : 'Arsipkan Member' }}"
                                                aria-label="{{ $m->isArchived() ? 'Pulihkan Member' : 'Arsipkan Member' }}"
                                            >
                                                @if ($m->isArchived())
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 text-[11px] pt-1">
                                    <span class="px-2 py-0.5 rounded bg-sidebar border border-line text-muted">Transaksi: <strong class="font-mono text-charcoal">{{ $m->transactions_count }}</strong></span>
                                    <span class="px-2 py-0.5 rounded bg-sidebar border border-line text-muted">Recurring: <strong class="font-mono text-forest">{{ $m->recurring_schedules_count }}</strong></span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-line bg-sidebar/50 text-[10px] uppercase font-bold text-muted tracking-wider">
                                    <th class="py-3 px-4">Member</th>
                                    <th class="py-3 px-4 text-center">Transaksi</th>
                                    <th class="py-3 px-4 text-center">Jadwal Recurring</th>
                                    <th class="py-3 px-4">Catatan</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($members as $m)
                                    <tr class="hover:bg-cream/40 transition {{ $m->isArchived() ? 'opacity-60' : '' }}">
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-full text-white font-bold flex items-center justify-center text-xs shadow-xs" style="background-color: {{ $m->color ?: '#0f4c3a' }};">
                                                    {{ strtoupper(substr($m->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="font-bold text-charcoal text-sm flex items-center gap-1.5">
                                                        <span>{{ $m->name }}</span>
                                                        @if ($m->isArchived())
                                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-muted/20 text-muted">ARSIP</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-mono font-bold text-charcoal">
                                            {{ $m->transactions_count }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-mono font-bold text-forest">
                                            {{ $m->recurring_schedules_count }}
                                        </td>
                                        <td class="py-3.5 px-4 text-muted max-w-xs truncate">
                                            {{ $m->notes ?: '-' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button 
                                                    type="button" 
                                                    onclick="openEditMemberModal({{ $m->id }}, '{{ addslashes($m->name) }}', '{{ $m->color ?: '#0f4c3a' }}', '{{ addslashes($m->notes ?? '') }}')"
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Ubah Member"
                                                    aria-label="Ubah Member"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                    </svg>
                                                </button>

                                                <form method="POST" action="{{ route('members.destroy', $m) }}" class="inline" onsubmit="return confirm('{{ $m->isArchived() ? 'Pulihkan member ini?' : 'Arsipkan member ini?' }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button 
                                                        type="submit" 
                                                        class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-rose-600 hover:border-rose-500/40 hover:bg-rose-500/10 transition inline-flex items-center justify-center cursor-pointer"
                                                        title="{{ $m->isArchived() ? 'Pulihkan Member' : 'Arsipkan Member' }}"
                                                        aria-label="{{ $m->isArchived() ? 'Pulihkan Member' : 'Arsipkan Member' }}"
                                                    >
                                                        @if ($m->isArchived())
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                            </svg>
                                                        @else
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                            </svg>
                                                        @endif
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
        </div>
    </div>

    <!-- Modal Ubah Member -->
    <div id="editMemberModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-start sm:items-center justify-center p-3 sm:p-4">
        <div class="panel-card w-full max-w-md p-4 sm:p-6 rounded-2xl border border-line bg-panel shadow-xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2 border-b border-line pb-3">
                <h3 class="text-sm font-bold text-charcoal">Ubah Data Member</h3>
                <button type="button" onclick="closeEditMemberModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg inline-flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream font-bold text-base cursor-pointer" aria-label="Tutup modal">&times;</button>
            </div>

            <form method="POST" id="editMemberForm" action="" class="space-y-4" novalidate>
                @csrf
                @method('PUT')
                <div>
                    <label for="editMemberName" class="block text-xs font-bold text-charcoal mb-1">
                        Nama Member <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        id="editMemberName" 
                        class="field-input w-full text-xs" 
                        required 
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-charcoal mb-1">
                        Warna Aksen Badge
                    </label>
                    <div class="flex items-center gap-2 flex-wrap" id="editColorPicker">
                        @foreach ($colors as $color)
                            <label class="cursor-pointer p-1 sm:p-0 inline-flex items-center justify-center">
                                <input type="radio" name="color" value="{{ $color }}" class="sr-only peer edit-color-input">
                                <div class="w-8 h-8 sm:w-6 sm:h-6 rounded-full border-2 border-transparent peer-checked:border-charcoal peer-checked:scale-110 transition shadow-xs" style="background-color: {{ $color }};"></div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="editMemberNotes" class="block text-xs font-bold text-charcoal mb-1">
                        Catatan
                    </label>
                    <textarea 
                        name="notes" 
                        id="editMemberNotes" 
                        rows="2" 
                        class="field-input w-full text-xs"
                    ></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2 border-t border-line">
                    <button type="button" onclick="closeEditMemberModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 py-2 text-xs font-bold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 py-2 text-xs font-bold cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditMemberModal(id, name, color, notes) {
            const modal = document.getElementById('editMemberModal');
            const form = document.getElementById('editMemberForm');
            form.action = '/members/' + id;
            document.getElementById('editMemberName').value = name;
            document.getElementById('editMemberNotes').value = notes;

            document.querySelectorAll('.edit-color-input').forEach(input => {
                if (input.value.toLowerCase() === color.toLowerCase()) {
                    input.checked = true;
                }
            });

            modal.classList.remove('hidden');
        }

        function closeEditMemberModal() {
            document.getElementById('editMemberModal').classList.add('hidden');
        }
    </script>
</x-layouts.app>
