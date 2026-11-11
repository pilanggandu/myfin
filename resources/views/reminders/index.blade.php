<x-layouts.app title="Pengingat Keuangan">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="text-[11px] font-bold tracking-[1.5px] text-muted uppercase">ARUS / NOTIFIKASI & PENGINGAT</div>
            <h1 class="font-serif text-3xl sm:text-4xl text-forest font-normal tracking-tight mt-1 mb-0.5">
                Pengingat Keuangan
            </h1>
            <p class="text-xs text-muted">
                Jadwalkan pengingat jatuh tempo tagihan, batas anggaran, dan notifikasi pembukuan harian via Email atau WhatsApp.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
            <button 
                type="button" 
                onclick="openSettingsModal()" 
                class="btn-arus-secondary inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[42px] px-4 text-xs font-bold cursor-pointer shadow-xs"
                title="Atur nomor WhatsApp, email, dan preferensi saluran"
            >
                <svg class="w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span>Pengaturan Saluran</span>
            </button>

            <button 
                type="button" 
                onclick="openTestModal()" 
                class="btn-arus-secondary inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[42px] px-4 text-xs font-bold text-forest border-forest/30 hover:bg-forest-light cursor-pointer shadow-xs"
                title="Kirim notifikasi uji coba langsung ke Email atau WhatsApp"
            >
                <span>⚡ Tes Kirim</span>
            </button>

            <button 
                type="button" 
                onclick="openAddReminderModal()" 
                class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[42px] px-4 text-xs font-bold inline-flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>+ Buat Pengingat</span>
            </button>
        </div>
    </div>

    <!-- Alert Khusus Pengiriman WhatsApp Direct Link -->
    @if (session('wa_direct_link'))
        <div class="mb-6 p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">💬</span>
                <div>
                    <span class="font-bold">Pesan WhatsApp Siap Dikirim!</span>
                    <p class="text-[11px] text-emerald-800/90 dark:text-emerald-300/90 mt-0.5">
                        Teks pengingat otomatis telah disiapkan tanpa biaya langganan. Klik tombol untuk membuka WhatsApp.
                    </p>
                </div>
            </div>
            <a 
                href="{{ session('wa_direct_link') }}" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center justify-center gap-1.5 shadow-xs transition cursor-pointer self-start sm:self-auto"
            >
                <span>Buka WhatsApp Sekarang &rarr;</span>
            </a>
        </div>
    @endif

    <!-- Free & Paid Channel Disclosure Banner -->
    <div class="mb-6 p-4 rounded-xl border border-line bg-sidebar/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-start sm:items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-panel border border-line flex items-center justify-center text-sm shadow-xs select-none flex-shrink-0">
                🛡️
            </span>
            <div>
                <span class="font-bold text-charcoal">Status Layanan: 100% Gratis Tanpa Biaya Langganan</span>
                <span class="text-muted block text-[11px] mt-0.5">
                    Pengiriman pengingat di ARUS menggunakan saluran bebas biaya: <strong>Email SMTP</strong> dan <strong>WhatsApp Direct Link (wa.me) / CallMeBot Free API</strong>. Kami tidak menggunakan API berbayar tanpa persetujuan Anda.
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-forest-light text-forest border border-forest/20">
                EMAIL: GRATIS
            </span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-forest-light text-forest border border-forest/20">
                WHATSAPP: GRATIS
            </span>
        </div>
    </div>

    <!-- Metrik Pengingat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-metric-card 
            label="PENGINGAT AKTIF" 
            :value="$totalActive . ' Pengingat'" 
            :dark="true" 
            sub="Terjadwal di ruang aktif" 
        />

        <x-metric-card 
            label="SALURAN EMAIL" 
            :value="$emailCount . ' Terhubung'" 
            :sub="$user->email" 
        />

        <x-metric-card 
            label="SALURAN WHATSAPP" 
            :value="$waCount . ' Terhubung'" 
            :sub="$user->phone ? $user->phone : 'Belum isi no. HP'" 
        />

        <x-metric-card 
            label="JATUH TEMPO BULAN INI" 
            :value="$dueThisMonth . ' Tagihan'" 
            sub="Periode berjalan" 
        />
    </div>

    <!-- Tabel Daftar Pengingat -->
    <div class="panel-card p-0 overflow-hidden mb-6">
        <div class="px-5 sm:px-6 py-4 border-b border-line flex items-center justify-between">
            <span class="text-sm font-bold text-forest">Daftar Jadwal Pengingat ({{ $reminders->count() }})</span>
            <span class="text-xs text-muted">Ruang: {{ $space->name }}</span>
        </div>

        @if ($reminders->isEmpty())
            <div class="p-10 text-center space-y-3">
                <span class="text-4xl block">🔔</span>
                <div class="font-bold text-base text-charcoal">Belum Ada Pengingat Terjadwal</div>
                <p class="text-xs text-muted max-w-md mx-auto">
                    Buat pengingat untuk pembayaran tagihan listrik, internet, batas kuota belanja, atau pengingat pencatatan transaksi harian via email dan WhatsApp.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="openAddReminderModal()" class="btn-arus-primary">
                        + Buat Pengingat Pertama
                    </button>
                </div>
            </div>
        @else
            <div class="md:hidden divide-y divide-line">
                @foreach ($reminders as $rem)
                    <div class="p-4 space-y-2.5 text-xs {{ ! $rem->is_active ? 'opacity-60' : '' }}">
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-sm text-charcoal">{{ $rem->title }}</div>
                                <div class="text-[11px] text-muted mt-1 flex items-center flex-wrap gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded bg-cream border border-line text-[10px] font-semibold">{{ $rem->typeLabel() }}</span>
                                    <span>&bull;</span>
                                    @if ($rem->channel === 'both')
                                        <span class="font-semibold text-charcoal">📧 + 💬 Email & WA</span>
                                    @elseif ($rem->channel === 'whatsapp')
                                        <span class="font-semibold text-emerald-700 dark:text-emerald-400">💬 WhatsApp</span>
                                    @else
                                        <span class="font-semibold text-forest">📧 Email</span>
                                    @endif
                                    <span>&bull;</span>
                                    <span>{{ $rem->frequencyLabel() }}</span>
                                </div>
                                @if ($rem->notes)
                                    <div class="text-[11px] italic text-charcoal/80 mt-1 line-clamp-2">"{{ $rem->notes }}"</div>
                                @endif
                            </div>

                            <div class="text-right shrink-0">
                                @if ($rem->amount && $rem->amount > 0)
                                    <div class="font-mono font-bold text-sm text-charcoal privacy-balance" data-raw-value="Rp {{ number_format($rem->amount, 0, ',', '.') }}">
                                        Rp {{ number_format($rem->amount, 0, ',', '.') }}
                                    </div>
                                @endif
                                <div class="mt-1">
                                    @if ($rem->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-forest-light text-forest border border-forest/20">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">Nonaktif</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                            <span>
                                @if ($rem->due_date)
                                    Tempo: <strong class="{{ $rem->due_date->isPast() ? 'text-red' : 'text-charcoal' }}">{{ $rem->due_date->format('d M Y') }}</strong> &bull;
                                @endif
                                Jam <span class="font-mono">{{ $rem->reminder_time ?? '09:00' }} WIB</span>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-line/60">
                            <form method="POST" action="{{ route('reminders.sendNow', $rem) }}" class="flex-1 min-w-[100px]">
                                @csrf
                                <button type="submit" class="w-full min-h-[44px] px-3 py-2 rounded-lg border border-line bg-panel hover:bg-forest-light text-forest font-bold text-xs inline-flex items-center justify-center gap-1 transition cursor-pointer shadow-xs">
                                    🚀 Kirim Sekarang
                                </button>
                            </form>
                            <button type="button" onclick="openEditReminderModal({{ json_encode($rem) }})" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-forest transition inline-flex items-center justify-center cursor-pointer" title="Ubah Pengingat" aria-label="Ubah Pengingat">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                            </button>
                            <form method="POST" action="{{ route('reminders.toggleActive', $rem) }}" class="inline">
                                @csrf
                                <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-charcoal transition inline-flex items-center justify-center cursor-pointer text-sm" title="{{ $rem->is_active ? 'Nonaktifkan Pengingat' : 'Aktifkan Pengingat' }}" aria-label="Ubah status pengingat">
                                    {{ $rem->is_active ? '⏸' : '▶' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('reminders.destroy', $rem) }}" class="inline" onsubmit="return confirm('Hapus pengingat ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-11 h-11 rounded-lg border border-line bg-panel text-muted hover:text-red transition inline-flex items-center justify-center cursor-pointer" title="Hapus Pengingat" aria-label="Hapus Pengingat">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-cream/40 text-muted uppercase font-bold text-[11px] tracking-wider">
                            <th class="py-3 px-6">Pengingat & Kategori</th>
                            <th class="py-3 px-4">Saluran</th>
                            <th class="py-3 px-4 text-right">Nominal Tagihan</th>
                            <th class="py-3 px-4">Jatuh Tempo / Waktu</th>
                            <th class="py-3 px-4">Frekuensi</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($reminders as $rem)
                            <tr class="hover:bg-cream/20 transition-colors {{ ! $rem->is_active ? 'opacity-60' : '' }}">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-sm text-charcoal">{{ $rem->title }}</div>
                                    <div class="text-[11px] text-muted mt-0.5 flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded bg-cream border border-line text-[10px] font-semibold">
                                            {{ $rem->typeLabel() }}
                                        </span>
                                        @if ($rem->notes)
                                            <span class="truncate max-w-xs italic text-charcoal/80">"{{ $rem->notes }}"</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($rem->channel === 'both')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-charcoal">
                                            <span>📧</span> <span>+</span> <span>💬</span> <span>Email & WA</span>
                                        </span>
                                    @elseif ($rem->channel === 'whatsapp')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                            <span>💬</span> WhatsApp
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-forest">
                                            <span>📧</span> Email
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 text-right font-mono font-bold text-sm text-charcoal whitespace-nowrap">
                                    @if ($rem->amount && $rem->amount > 0)
                                        <span class="privacy-balance" data-raw-value="Rp {{ number_format($rem->amount, 0, ',', '.') }}">
                                            Rp {{ number_format($rem->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-muted font-normal">-</span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap text-charcoal">
                                    @if ($rem->due_date)
                                        <div class="font-medium {{ $rem->due_date->isPast() ? 'text-red font-bold' : '' }}">
                                            {{ $rem->due_date->format('d M Y') }}
                                        </div>
                                    @endif
                                    <div class="text-[10px] text-muted font-mono">
                                        Jam {{ $rem->reminder_time ?? '09:00' }} WIB
                                    </div>
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap text-muted text-xs">
                                    {{ $rem->frequencyLabel() }}
                                </td>

                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($rem->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-forest-light text-forest border border-forest/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <!-- Kirim Sekarang -->
                                        <form method="POST" action="{{ route('reminders.sendNow', $rem) }}" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="min-h-[44px] lg:min-h-0 px-2.5 py-1.5 rounded-lg border border-line bg-panel hover:bg-forest-light text-forest font-bold text-[11px] inline-flex items-center gap-1 transition cursor-pointer shadow-xs"
                                                title="Kirimkan notifikasi pengingat ini sekarang juga"
                                            >
                                                <span>🚀 Kirim</span>
                                            </button>
                                        </form>

                                        <!-- Ubah Modal Trigger -->
                                        <button 
                                            type="button" 
                                            onclick="openEditReminderModal({{ json_encode($rem) }})" 
                                            class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-forest hover:border-forest/40 hover:bg-cream transition inline-flex items-center justify-center cursor-pointer"
                                            title="Ubah Pengingat"
                                            aria-label="Ubah Pengingat"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        <!-- Toggle Status Aktif -->
                                        <form method="POST" action="{{ route('reminders.toggleActive', $rem) }}" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-charcoal hover:bg-cream transition inline-flex items-center justify-center cursor-pointer text-xs"
                                                title="{{ $rem->is_active ? 'Nonaktifkan Pengingat' : 'Aktifkan Pengingat' }}"
                                                aria-label="Ubah status pengingat"
                                            >
                                                {{ $rem->is_active ? '⏸' : '▶' }}
                                            </button>
                                        </form>

                                        <!-- Hapus -->
                                        <form method="POST" action="{{ route('reminders.destroy', $rem) }}" class="inline" onsubmit="return confirm('Hapus pengingat ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="w-11 h-11 lg:w-8 lg:h-8 rounded-lg border border-line bg-panel text-muted hover:text-red hover:border-red/40 hover:bg-red/5 transition inline-flex items-center justify-center cursor-pointer"
                                                title="Hapus Pengingat"
                                                aria-label="Hapus Pengingat"
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

    <!-- Riwayat Log Pengiriman Terakhir -->
    @if ($recentLogs->isNotEmpty())
        <div class="panel-card p-5 sm:p-6 rounded-2xl border border-line bg-panel space-y-4">
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-sidebar border border-line flex items-center justify-center text-xs select-none">
                        📜
                    </span>
                    <h3 class="font-bold text-xs uppercase tracking-wider text-charcoal">
                        Riwayat Log Pengiriman Notifikasi Terakhir
                    </h3>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-cream text-muted border border-line">
                    {{ $recentLogs->count() }} Terkirim
                </span>
            </div>

            <div class="divide-y divide-line/60 text-xs">
                @foreach ($recentLogs as $log)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-charcoal flex items-center gap-2">
                                <span class="px-1.5 py-0.2 rounded text-[10px] uppercase font-bold {{ $log->channel === 'whatsapp' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ strtoupper($log->channel) }}
                                </span>
                                <span>{{ $log->title }}</span>
                                <span class="text-muted font-normal text-[11px]">&rarr; {{ $log->recipient }}</span>
                            </div>
                            <div class="text-muted text-[11px] mt-0.5 truncate max-w-xl">
                                {{ Str::limit($log->content, 120) }}
                            </div>
                        </div>
                        <div class="text-[11px] text-muted sm:text-right flex-shrink-0">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $log->status === 'sent' || $log->status === 'direct_ready' || $log->status === 'simulated' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ strtoupper($log->status) }}
                            </span>
                            <div class="font-mono mt-0.5">{{ $log->created_at->format('d M H:i') }} WIB</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Modal Tambah Pengingat -->
    <div id="addReminderModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        +
                    </span>
                    <div class="min-w-0">
                        <h3 id="addModalTitle" class="font-serif text-lg text-forest font-normal">
                            Buat Pengingat Baru
                        </h3>
                        <p class="text-[11px] text-muted">
                            Jadwalkan notifikasi otomatis via Email atau WhatsApp.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeAddReminderModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('reminders.store') }}" class="space-y-3" id="addReminderForm">
                @csrf
                <div>
                    <label for="addTitle" class="block text-xs font-bold text-charcoal mb-1">Judul Pengingat *</label>
                    <input 
                        type="text" 
                        name="title" 
                        id="addTitle" 
                        placeholder="Contoh: Bayar Tagihan Listrik PLN" 
                        maxlength="150" 
                        required 
                        class="field-input w-full text-xs"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="addType" class="block text-xs font-bold text-charcoal mb-1">Tipe Pengingat *</label>
                        <select name="type" id="addType" class="field-select w-full text-xs">
                            <option value="bill_payment">Tagihan & Pembayaran</option>
                            <option value="daily_bookkeeping">Catat Keuangan Harian</option>
                            <option value="budget_alert">Peringatan Anggaran</option>
                            <option value="custom">Pengingat Kustom</option>
                        </select>
                    </div>

                    <div>
                        <label for="addChannel" class="block text-xs font-bold text-charcoal mb-1">Saluran Pengiriman *</label>
                        <select name="channel" id="addChannel" class="field-select w-full text-xs font-bold">
                            <option value="email">📧 Email Saja</option>
                            <option value="whatsapp">💬 WhatsApp Saja</option>
                            <option value="both" selected>📧 + 💬 Email & WhatsApp</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="addAmount" class="block text-xs font-bold text-charcoal mb-1">Nominal Tagihan (Opsional)</label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="addAmount" 
                            placeholder="Rp 0" 
                            step="any" 
                            class="field-input w-full text-xs font-mono font-bold"
                        >
                    </div>

                    <div>
                        <label for="addFrequency" class="block text-xs font-bold text-charcoal mb-1">Frekuensi *</label>
                        <select name="frequency" id="addFrequency" class="field-select w-full text-xs">
                            <option value="once">Satu Kali</option>
                            <option value="daily">Harian (Setiap Hari)</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly" selected>Bulanan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="addDueDate" class="block text-xs font-bold text-charcoal mb-1">Tanggal Jatuh Tempo</label>
                        <input 
                            type="date" 
                            name="due_date" 
                            id="addDueDate" 
                            value="{{ (app('currentSpace') ? app('currentSpace')->now() : now())->format('Y-m-d') }}" 
                            class="field-input w-full text-xs"
                        >
                    </div>

                    <div>
                        <label for="addTime" class="block text-xs font-bold text-charcoal mb-1">Jam Notifikasi (WIB)</label>
                        <input 
                            type="time" 
                            name="reminder_time" 
                            id="addTime" 
                            value="09:00" 
                            class="field-input w-full text-xs font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label for="addNotes" class="block text-xs font-bold text-charcoal mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea 
                        name="notes" 
                        id="addNotes" 
                        rows="2" 
                        placeholder="Contoh: ID Pelanggan PLN 5312345678, bayar via BCA mobile." 
                        class="field-input w-full text-xs resize-none"
                    ></textarea>
                </div>

                <div class="pt-2 border-t border-line">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Aktifkan jadwal pengingat ini langsung</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeAddReminderModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        Simpan Pengingat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ubah Pengingat -->
    <div id="editReminderModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        ✏️
                    </span>
                    <div class="min-w-0">
                        <h3 id="editModalTitle" class="font-serif text-lg text-forest font-normal">
                            Ubah Jadwal Pengingat
                        </h3>
                        <p class="text-[11px] text-muted">Perbarui parameter judul, saluran, dan waktu jatuh tempo.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditReminderModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <form method="POST" id="editReminderForm" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="editTitle" class="block text-xs font-bold text-charcoal mb-1">Judul Pengingat *</label>
                    <input 
                        type="text" 
                        name="title" 
                        id="editTitle" 
                        maxlength="150" 
                        required 
                        class="field-input w-full text-xs"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editType" class="block text-xs font-bold text-charcoal mb-1">Tipe Pengingat *</label>
                        <select name="type" id="editType" class="field-select w-full text-xs">
                            <option value="bill_payment">Tagihan & Pembayaran</option>
                            <option value="daily_bookkeeping">Catat Keuangan Harian</option>
                            <option value="budget_alert">Peringatan Anggaran</option>
                            <option value="custom">Pengingat Kustom</option>
                        </select>
                    </div>

                    <div>
                        <label for="editChannel" class="block text-xs font-bold text-charcoal mb-1">Saluran Pengiriman *</label>
                        <select name="channel" id="editChannel" class="field-select w-full text-xs font-bold">
                            <option value="email">📧 Email Saja</option>
                            <option value="whatsapp">💬 WhatsApp Saja</option>
                            <option value="both">📧 + 💬 Email & WhatsApp</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editAmount" class="block text-xs font-bold text-charcoal mb-1">Nominal Tagihan (Opsional)</label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="editAmount" 
                            step="any" 
                            class="field-input w-full text-xs font-mono font-bold"
                        >
                    </div>

                    <div>
                        <label for="editFrequency" class="block text-xs font-bold text-charcoal mb-1">Frekuensi *</label>
                        <select name="frequency" id="editFrequency" class="field-select w-full text-xs">
                            <option value="once">Satu Kali</option>
                            <option value="daily">Harian (Setiap Hari)</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="editDueDate" class="block text-xs font-bold text-charcoal mb-1">Tanggal Jatuh Tempo</label>
                        <input 
                            type="date" 
                            name="due_date" 
                            id="editDueDate" 
                            class="field-input w-full text-xs"
                        >
                    </div>

                    <div>
                        <label for="editTime" class="block text-xs font-bold text-charcoal mb-1">Jam Notifikasi (WIB)</label>
                        <input 
                            type="time" 
                            name="reminder_time" 
                            id="editTime" 
                            class="field-input w-full text-xs font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label for="editNotes" class="block text-xs font-bold text-charcoal mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea 
                        name="notes" 
                        id="editNotes" 
                        rows="2" 
                        class="field-input w-full text-xs resize-none"
                    ></textarea>
                </div>

                <div class="pt-2 border-t border-line">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                        <input type="checkbox" name="is_active" value="1" id="editIsActive" class="rounded border-line text-forest focus:ring-forest">
                        <span class="font-semibold">Aktifkan pengingat ini</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeEditReminderModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        Perbarui Pengingat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pengaturan Saluran Notifikasi (Email & WhatsApp Gratis) -->
    <div id="settingsModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        ⚙️
                    </span>
                    <div class="min-w-0">
                        <h3 id="settingsModalTitle" class="font-serif text-lg text-forest font-normal">
                            Pengaturan Saluran Notifikasi
                        </h3>
                        <p class="text-[11px] text-muted">
                            Konfigurasi nomor WhatsApp dan preferensi email tanpa biaya langganan.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeSettingsModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('reminders.updateSettings') }}" class="space-y-4">
                @csrf
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-charcoal uppercase tracking-wider">1. Saluran WhatsApp (100% Gratis)</h4>
                    <div>
                        <label for="settingsPhone" class="block text-xs font-bold text-charcoal mb-1">
                            Nomor WhatsApp Anda *
                        </label>
                        <input 
                            type="text" 
                            name="phone" 
                            id="settingsPhone" 
                            value="{{ old('phone', $user->phone) }}" 
                            placeholder="Contoh: 08123456789 atau 628123456789" 
                            class="field-input w-full text-xs font-mono font-bold"
                        >
                        <span class="text-[10px] text-muted block mt-0.5">Format lokal (08...) akan otomatis dikonversi ke kode negara (628...).</span>
                    </div>

                    <div class="p-3 rounded-xl bg-sidebar border border-line space-y-2 text-xs">
                        <div class="font-bold text-forest">Metode Pengiriman WhatsApp yang Didukung:</div>
                        <ul class="list-disc list-inside space-y-1 text-muted text-[11px]">
                            <li><strong>Direct Link (wa.me)</strong>: 100% Gratis, siap pakai tanpa konfigurasi tambahan.</li>
                            <li><strong>CallMeBot Free API</strong>: Masukkan API Key gratis jika ingin bot mengirim pesan otomatis ke nomor Anda.</li>
                            <li><strong>Custom Gateway URL</strong>: Jika Anda memiliki bot/gateway sendiri (Fonnte/Waha).</li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="settingsApiKey" class="block text-xs font-bold text-charcoal mb-1">
                                CallMeBot API Key (Opsional)
                            </label>
                            <input 
                                type="text" 
                                name="whatsapp_api_key" 
                                id="settingsApiKey" 
                                value="{{ old('whatsapp_api_key', $user->whatsapp_api_key) }}" 
                                placeholder="Contoh: 123456" 
                                class="field-input w-full text-xs font-mono"
                            >
                        </div>
                        <div>
                            <label for="settingsGatewayUrl" class="block text-xs font-bold text-charcoal mb-1">
                                Custom Gateway URL (Opsional)
                            </label>
                            <input 
                                type="url" 
                                name="whatsapp_gateway_url" 
                                id="settingsGatewayUrl" 
                                value="{{ old('whatsapp_gateway_url', $user->whatsapp_gateway_url) }}" 
                                placeholder="https://gateway.example.com/send" 
                                class="field-input w-full text-xs font-mono"
                            >
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-3 border-t border-line">
                    <h4 class="text-xs font-bold text-charcoal uppercase tracking-wider">2. Preferensi Notifikasi & Rutinitas</h4>
                    
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                            <input type="checkbox" name="email_notifications_enabled" value="1" {{ $user->email_notifications_enabled ? 'checked' : '' }} class="rounded border-line text-forest focus:ring-forest">
                            <span>Izinkan pengiriman pengingat via Email (<strong>{{ $user->email }}</strong>)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                            <input type="checkbox" name="whatsapp_notifications_enabled" value="1" {{ $user->whatsapp_notifications_enabled ? 'checked' : '' }} class="rounded border-line text-forest focus:ring-forest">
                            <span>Izinkan pengiriman pengingat via WhatsApp</span>
                        </label>

                        <div class="p-3 rounded-xl bg-sidebar/50 border border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-charcoal">
                                <input type="checkbox" name="daily_reminder_enabled" value="1" {{ $user->daily_reminder_enabled ? 'checked' : '' }} class="rounded border-line text-forest focus:ring-forest">
                                <div>
                                    <span class="font-bold block">Pengingat Catat Pembukuan Harian</span>
                                    <span class="text-[11px] text-muted">Kirimkan notifikasi ramah setiap malam agar tidak lupa catat transaksi.</span>
                                </div>
                            </label>
                            <input 
                                type="time" 
                                name="daily_reminder_time" 
                                value="{{ old('daily_reminder_time', $user->daily_reminder_time ?? '20:00') }}" 
                                class="field-input w-full sm:w-28 text-xs font-mono text-center"
                            >
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeSettingsModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Uji Coba Pengiriman Notifikasi (Test Dispatch) -->
    <div id="testModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="testModalTitle">
        <div class="panel-card bg-panel border border-line rounded-2xl max-w-md w-full p-4 sm:p-6 shadow-2xl space-y-4 my-3 sm:my-0 max-h-[calc(100dvh-1.5rem)] sm:max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start sm:items-center justify-between gap-2 border-b border-line pb-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-forest text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        ⚡
                    </span>
                    <div class="min-w-0">
                        <h3 id="testModalTitle" class="font-serif text-lg text-forest font-normal">
                            Tes Kirim Notifikasi
                        </h3>
                        <p class="text-[11px] text-muted">
                            Uji coba pengiriman pesan ke Email atau WhatsApp Anda saat ini.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeTestModal()" class="w-11 h-11 sm:w-8 sm:h-8 rounded-lg border border-line text-muted hover:text-charcoal hover:bg-cream transition flex items-center justify-center cursor-pointer text-sm shrink-0" aria-label="Tutup modal">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('reminders.sendTest') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="testChannel" class="block text-xs font-bold text-charcoal mb-1">Pilih Saluran Tes *</label>
                    <select name="channel" id="testChannel" class="field-select w-full text-xs font-bold">
                        <option value="both" selected>📧 + 💬 Email & WhatsApp (Keduanya)</option>
                        <option value="email">📧 Email Saja ({{ $user->email }})</option>
                        <option value="whatsapp">💬 WhatsApp Saja</option>
                    </select>
                </div>

                <div>
                    <label for="testTargetPhone" class="block text-xs font-bold text-charcoal mb-1">
                        Nomor WhatsApp Tujuan
                    </label>
                    <input 
                        type="text" 
                        name="target_phone" 
                        id="testTargetPhone" 
                        value="{{ $user->phone }}" 
                        placeholder="Contoh: 081234567890" 
                        class="field-input w-full text-xs font-mono font-bold"
                    >
                    <span class="text-[10px] text-muted block mt-0.5">Jika kosong, akan menggunakan nomor pada profil akun Anda.</span>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="closeTestModal()" class="btn-arus-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="btn-arus-primary w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-5 text-xs font-bold cursor-pointer">
                        🚀 Jalankan Tes Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Client Scripts for Modals -->
    <script>
        function openAddReminderModal() {
            document.getElementById('addReminderModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => document.getElementById('addTitle').focus(), 100);
        }

        function closeAddReminderModal() {
            document.getElementById('addReminderModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openEditReminderModal(rem) {
            const form = document.getElementById('editReminderForm');
            form.action = '/reminders/' + rem.id;

            document.getElementById('editTitle').value = rem.title;
            document.getElementById('editType').value = rem.type;
            document.getElementById('editChannel').value = rem.channel;
            document.getElementById('editAmount').value = rem.amount || '';
            document.getElementById('editFrequency').value = rem.frequency;
            document.getElementById('editDueDate').value = rem.due_date ? rem.due_date.substring(0, 10) : '';
            document.getElementById('editTime').value = rem.reminder_time || '09:00';
            document.getElementById('editNotes').value = rem.notes || '';
            document.getElementById('editIsActive').checked = Boolean(rem.is_active);

            document.getElementById('editReminderModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditReminderModal() {
            document.getElementById('editReminderModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openSettingsModal() {
            document.getElementById('settingsModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => document.getElementById('settingsPhone').focus(), 100);
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openTestModal() {
            document.getElementById('testModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeTestModal() {
            document.getElementById('testModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddReminderModal();
                closeEditReminderModal();
                closeSettingsModal();
                closeTestModal();
            }
        });
    </script>
</x-layouts.app>
