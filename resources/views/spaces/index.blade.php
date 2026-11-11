<x-layouts.app title="Ruang Keuangan">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / RUANG</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Ruang Keuangan
            </h1>
            <p class="text-xs text-muted">Kelola ruang personal dan ruang bersama Anda.</p>
        </div>
        <div>
            <a href="{{ route('spaces.create') }}" class="btn-arus-primary w-full sm:w-auto">
                + Buat Ruang Bersama
            </a>
        </div>
    </div>

    <div class="panel-card p-0 overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <span class="text-sm font-bold text-forest block">Daftar Ruang Terdaftar ({{ $spaces->count() }})</span>
                <span class="text-xs text-muted">Setiap ruang memiliki isolasi data dan catatan mutasi independen</span>
            </div>
            <div>
                <x-badge variant="default">
                    ISOLASI AKTIF
                </x-badge>
            </div>
        </div>

        <div class="md:hidden divide-y divide-line">
            @foreach ($spaces as $space)
                @php
                    $isActive = (int) $currentSpace?->id === (int) $space->id;
                    $userRole = $space->getRoleOf(auth()->user());
                    $isOwner = $space->isOwner(auth()->user());
                @endphp
                <div class="p-5 space-y-4 hover:bg-cream/20 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-base text-charcoal">{{ $space->name }}</span>
                                <x-badge :variant="$space->isPersonal() ? 'personal' : 'shared'">
                                    {{ $space->isPersonal() ? 'Personal' : 'Bersama' }}
                                </x-badge>
                            </div>
                            @if ($space->isPersonal())
                                <div class="text-xs text-muted mt-1">Ruang pribadi utama Anda</div>
                            @else
                                <div class="text-xs text-muted mt-1">Pemilik: {{ $space->owner->name }}</div>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @if ($isActive)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest border border-forest/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                    Aktif
                                </span>
                            @else
                                <x-badge variant="inactive">
                                    Tidak Aktif
                                </x-badge>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs py-2.5 px-3.5 rounded-lg bg-sidebar/50 border border-line">
                        <div>
                            <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Peran Anda</span>
                            <span class="font-bold text-charcoal uppercase mt-0.5 block">{{ $userRole }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Zona Waktu</span>
                            <span class="text-muted mt-0.5 block">{{ $space->timezone }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 pt-1 flex-wrap">
                        @if (! $isActive)
                            <form method="POST" action="{{ route('spaces.switch', $space) }}" class="flex-1 min-w-[120px]">
                                @csrf
                                <button type="submit" class="w-full min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold bg-forest text-white hover:bg-forest-hover transition cursor-pointer inline-flex items-center justify-center">
                                    Gunakan Ruang
                                </button>
                            </form>
                        @endif

                        @if ($isOwner)
                            <a href="{{ route('spaces.edit', $space) }}" class="{{ ! $isActive ? 'flex-1 min-w-[80px]' : 'w-full' }} min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold border border-line bg-panel text-charcoal hover:bg-cream transition inline-flex items-center justify-center">
                                Ubah
                            </a>

                            @if (! $space->isPersonal())
                                <form method="POST" action="{{ route('spaces.destroy', $space) }}" class="w-full" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruang bersama ini? Semua data di dalamnya akan dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full min-h-[44px] px-4 py-2.5 rounded-lg text-xs font-bold text-red hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900 transition cursor-pointer inline-flex items-center justify-center">
                                        Hapus Ruang
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                        <th class="py-3 px-6">Nama Ruang</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4">Peran Anda</th>
                        <th class="py-3 px-4">Zona Waktu</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($spaces as $space)
                        @php
                            $isActive = (int) $currentSpace?->id === (int) $space->id;
                            $userRole = $space->getRoleOf(auth()->user());
                            $isOwner = $space->isOwner(auth()->user());
                        @endphp
                        <tr class="hover:bg-cream/30 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-bold text-sm text-charcoal">{{ $space->name }}</div>
                                @if ($space->isPersonal())
                                    <div class="text-xs text-muted mt-0.5">Ruang pribadi utama Anda</div>
                                @else
                                    <div class="text-xs text-muted mt-0.5">Pemilik: {{ $space->owner->name }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <x-badge :variant="$space->isPersonal() ? 'personal' : 'shared'">
                                    {{ $space->isPersonal() ? 'Personal' : 'Bersama' }}
                                </x-badge>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="uppercase font-bold text-xs text-charcoal">{{ $userRole }}</span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap text-muted text-xs">
                                {{ $space->timezone }}
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if ($isActive)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest border border-forest/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                        Sedang Aktif
                                    </span>
                                @else
                                    <x-badge variant="inactive">
                                        Tidak Aktif
                                    </x-badge>
                                @endif
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if (! $isActive)
                                        <form method="POST" action="{{ route('spaces.switch', $space) }}" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center cursor-pointer"
                                                title="Gunakan Ruang Ini"
                                                aria-label="Gunakan Ruang Ini"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($isOwner)
                                        <a 
                                            href="{{ route('spaces.edit', $space) }}" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream/40 transition inline-flex items-center justify-center"
                                            title="Ubah Pengaturan Ruang"
                                            aria-label="Ubah Pengaturan Ruang"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>

                                        @if (! $space->isPersonal())
                                            <form method="POST" action="{{ route('spaces.destroy', $space) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruang bersama ini? Semua data di dalamnya akan dihapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                    title="Hapus Ruang Bersama"
                                                    aria-label="Hapus Ruang Bersama"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
