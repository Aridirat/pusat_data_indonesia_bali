<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Checkout — Pusat Data Indonesia Bali</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-slate-50 text-gray-900 antialiased">

    @include('pages.landing.components.navbar')

    <main class="min-h-screen py-30">
        <div class="max-w-xl mx-auto px-4">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-8">
                <div class="w-0.5 h-7 bg-stikom-blue shrink-0"></div>
                <div>
                    <h1 class="text-xl font-black text-stikom font-display">Konfirmasi Pesanan</h1>
                    <p class="text-xs text-gray-400 font-body mt-0.5">Periksa detail langganan sebelum membayar</p>
                </div>
            </div>

            {{-- Order Summary Card --}}
            <div class="bg-white border border-gray-100 shadow-sm mb-4 overflow-hidden">

                {{-- Top bar --}}
                <div class="bg-stikom-blue px-5 py-3 flex items-center justify-between">
                    <span class="text-[10px] font-bold text-white uppercase tracking-widest font-display">
                        Ringkasan Pesanan
                    </span>
                    <span class="text-[10px] text-white font-mono">{{ $transaksi->order_id }}</span>
                </div>

                <div class="p-6">
                    {{-- Layanan --}}
                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-10 h-10 bg-stikom-red flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-black text-stikom font-display text-base">{{ $layanan->nama_layanan }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 font-body">Durasi: {{ $layanan->durasi_label }}</p>
                        </div>
                    </div>

                    {{-- Fitur --}}
                    @if($layanan->fiturs->isNotEmpty())
                    <ul class="space-y-2 mb-6 border-t border-gray-50 pt-4">
                        @foreach($layanan->fiturs as $fitur)
                        <li class="flex items-center gap-2.5 text-sm font-body
                                   {{ $fitur->aktif ? 'text-gray-700' : 'text-gray-300' }}">
                            @if($fitur->aktif)
                                <div class="w-4 h-4 bg-stikom-blue flex items-center justify-center shrink-0">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="white" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-4 h-4 border border-gray-200 flex items-center justify-center shrink-0">
                                    <svg class="w-2.5 h-2.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                            @endif
                            {{ $fitur->nama_fitur }}
                        </li>
                        @endforeach
                    </ul>
                    @endif

                    {{-- Total --}}
                    <div class="border-t border-gray-100 pt-4 flex items-center justify-between">
                        <span class="text-sm text-gray-500 font-body">Total Pembayaran</span>
                        <span class="text-2xl font-black text-stikom font-display">{{ $layanan->harga_format }}</span>
                    </div>
                </div>
            </div>

            {{-- Note --}}
            <p class="text-xs text-gray-400 text-center mb-4 font-body">
                Pembayaran diproses secara aman.<br>
                Data kartu kamu tidak disimpan di sistem kami.
            </p>

            {{-- Syarat & Ketentuan --}}
            <div class="flex items-start gap-3 mb-6 px-1">
                <input
                    id="agree_terms" type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-slate-300 text-stikom-blue focus:ring-stikom-blue cursor-pointer"
                />
                <label for="agree_terms" class="text-xs text-gray-600 font-body">
                    Saya telah membaca dan menyetujui
                    <button type="button" id="openTermsModal" class="text-stikom-blue hover:underline font-medium">
                        Syarat & Ketentuan serta Kebijakan Pengembalian Dana
                    </button>
                </label>
            </div>

            {{-- Pay Button --}}
            <button id="pay-button" disabled
                    class="w-full py-4 bg-stikom-accent hover:bg-yellow-600 text-stikom hover:text-white font-black
                        text-sm tracking-wide transition-colors duration-200 font-display
                        flex items-center justify-center gap-2
                        disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-stikom-accent disabled:hover:text-stikom">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Checkout
            </button>

            <a href="{{ route('langganan') }}"
            class="block text-center mt-4 text-xs text-gray-400 hover:text-gray-600 transition font-body">
                Kembali ke halaman langganan
            </a>

        </div>
    </main>

    {{-- Modal Syarat & Ketentuan --}}
    <div id="termsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-xl">

            <div class="flex items-center justify-between bg-stikom-blue px-6 py-4 rounded-t-xl">
                <h3 class="text-white font-bold text-base tracking-wide">Syarat & Ketentuan Langganan</h3>
                <button type="button" id="closeTermsModal" class="text-white hover:text-gray-200 text-xl font-bold">
                    &times;
                </button>
            </div>

            <div class="px-6 py-5 max-h-96 overflow-y-auto text-xs text-gray-700 space-y-4">
                <p class="font-semibold text-gray-900">1. Ketentuan Langganan</p>
                <p>Dengan melakukan pembayaran, Anda menyetujui untuk berlangganan layanan {{ $layanan->nama_layanan }} selama {{ $layanan->durasi_label }} sesuai fitur yang tercantum pada ringkasan pesanan. Masa aktif langganan dihitung sejak pembayaran berhasil diverifikasi.</p>

                <p class="font-semibold text-gray-900">2. Pembayaran</p>
                <p>Pembayaran diproses melalui Midtrans sebagai penyedia payment gateway pihak ketiga. Pusat Data Indonesia Bali tidak menyimpan data kartu atau rekening Anda. Status pembayaran mengikuti konfirmasi resmi dari Midtrans.</p>

                <p class="font-semibold text-gray-900">3. Kebijakan Pengembalian Dana</p>
                <p>Karena layanan berupa akses data digital yang aktif segera setelah pembayaran berhasil, pengembalian dana <strong>tidak dapat dilakukan</strong> setelah akses langganan aktif, kecuali:</p>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Terjadi kesalahan sistem yang mengakibatkan pembayaran ganda (double charge) untuk transaksi yang sama.</li>
                    <li>Pembayaran berhasil diverifikasi namun fitur langganan gagal diaktifkan oleh sistem dalam waktu 1x24 jam.</li>
                </ul>
                <p>Pengajuan pengembalian dana untuk kondisi di atas dapat dilakukan dengan menghubungi administrator melalui kontak yang tersedia pada platform, disertai bukti transaksi (order ID).</p>

                <p class="font-semibold text-gray-900">4. Pembatalan Langganan</p>
                <p>Langganan yang telah aktif tidak dapat dibatalkan secara sepihak oleh pengguna untuk mendapatkan pengembalian dana proporsional, kecuali diatur lain oleh kebijakan administrator.</p>

                <p class="font-semibold text-gray-900">5. Perubahan Ketentuan</p>
                <p>Pusat Data Indonesia Bali dapat memperbarui Syarat & Ketentuan ini sewaktu-waktu. Perubahan akan berlaku untuk transaksi baru setelah tanggal pembaruan.</p>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-3">
                <button type="button" id="closeTermsModalBtn"
                    class="px-4 py-2 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Tutup
                </button>
                <button type="button" id="agreeTermsBtn"
                    class="px-4 py-2 text-xs text-white bg-stikom-blue rounded-lg hover:bg-blue-700 font-semibold">
                    Saya Setuju
                </button>
            </div>
        </div>
    </div>

    @include('pages.landing.components.footer')

    {{-- Midtrans Snap JS --}}
    <script src="{{ config('midtrans.snap_url') }}"
            data-client-key="{{ config('midtrans.client_key') }}"></script>

    <script>
    const snapToken  = @json($snapToken);
    const statusUrl  = @json(route('transaksi.status',  $transaksi->transaksi_id));
    const suksesUrl  = @json(route('transaksi.sukses',  $transaksi->transaksi_id));
    const riwayatUrl = @json(route('transaksi.riwayat'));

    // ── Gate: T&C harus dicentang sebelum bisa bayar ───────────────
    const agreeCheckbox = document.getElementById('agree_terms');
    const payButton      = document.getElementById('pay-button');
    const termsModal     = document.getElementById('termsModal');
    const openTerms      = document.getElementById('openTermsModal');
    const closeTerms     = document.getElementById('closeTermsModal');
    const closeTermsBtn  = document.getElementById('closeTermsModalBtn');
    const agreeTermsBtn  = document.getElementById('agreeTermsBtn');

    agreeCheckbox.addEventListener('change', function () {
        payButton.disabled = !this.checked;
    });

    openTerms.addEventListener('click', () => {
        termsModal.classList.remove('hidden');
        termsModal.classList.add('flex');
    });

    function closeTermsModalFn() {
        termsModal.classList.add('hidden');
        termsModal.classList.remove('flex');
    }

    closeTerms.addEventListener('click', closeTermsModalFn);
    closeTermsBtn.addEventListener('click', closeTermsModalFn);
    termsModal.addEventListener('click', (e) => {
        if (e.target === termsModal) closeTermsModalFn();
    });

    agreeTermsBtn.addEventListener('click', () => {
        agreeCheckbox.checked = true;
        payButton.disabled = false;
        closeTermsModalFn();
    });

    document.getElementById('pay-button').addEventListener('click', function () {
        snap.pay(snapToken, {

            onSuccess: function(result) {
                // Snap bilang sukses, tapi webhook mungkin belum diproses Laravel.
                // Poll dulu sampai status di DB benar-benar 'success'.
                showStatusCheck('Memverifikasi pembayaran...');
                pollStatus(suksesUrl);
            },

            onPending: function(result) {
                // Pembayaran pending (transfer bank, dll) — arahkan ke riwayat
                window.location.href = riwayatUrl + '?status=pending';
            },

            onError: function(result) {
                console.error('Snap onError:', result);
                showStatusCheck('Pembayaran gagal. Silakan coba lagi.', 'error');
            },

            onClose: function() {
                // User menutup popup — biarkan tetap di halaman checkout
                // Jangan redirect agar user bisa mencoba lagi
            }
        });
    });

    // ── Polling status ke database ─────────────────────────────────
    let pollCount = 0;

    function pollStatus(redirectOnSuccess) {
        pollCount++;

        // Batas maksimal 20x polling (~60 detik)
        if (pollCount > 20) {
            showStatusCheck(
                'Status belum terkonfirmasi otomatis. Silakan cek riwayat transaksi.',
                'warning'
            );
            setTimeout(() => window.location.href = riwayatUrl, 3000);
            return;
        }

        fetch(statusUrl + '?t=' + Date.now()) // cache buster
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    // Webhook sudah diproses, redirect ke halaman sukses
                    window.location.href = redirectOnSuccess;

                } else if (data.status === 'failed' || data.status === 'cancelled') {
                    showStatusCheck('Pembayaran ' + data.status_label + '.', 'error');

                } else {
                    // Masih pending, coba lagi setelah 3 detik
                    setTimeout(() => pollStatus(redirectOnSuccess), 3000);
                }
            })
            .catch(() => {
                // Error network, coba lagi
                setTimeout(() => pollStatus(redirectOnSuccess), 3000);
            });
    }

    // ── UI helpers ─────────────────────────────────────────────────
    function showStatusCheck(msg, type = 'loading') {
        const el = document.getElementById('status-check');
        if (!el) return;

        el.classList.remove('hidden');

        const icons = {
            loading: `<div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-3"></div>`,
            error:   `<i class="fas fa-times-circle text-red-500 text-3xl mb-3 block text-center"></i>`,
            warning: `<i class="fas fa-exclamation-circle text-yellow-500 text-3xl mb-3 block text-center"></i>`,
        };

        const textColors = {
            loading: 'text-gray-600',
            error:   'text-red-600',
            warning: 'text-yellow-700',
        };

        el.innerHTML = `
            <div class="px-6 py-5 text-center">
                ${icons[type] ?? icons.loading}
                <p class="text-sm font-medium ${textColors[type] ?? textColors.loading}">${msg}</p>
                ${type !== 'loading' ? `
                <a href="${riwayatUrl}"
                class="mt-3 inline-block text-xs text-blue-600 hover:underline">
                    Lihat Riwayat Transaksi →
                </a>` : `
                <p class="text-xs text-gray-400 mt-1">Mohon tunggu, jangan tutup halaman ini.</p>
                `}
            </div>`;

        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }
</script>


</body>
</html>