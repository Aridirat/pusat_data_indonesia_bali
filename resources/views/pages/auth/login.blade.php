<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if(app()->environment('testing'))
    {{-- Vite dimatikan saat testing --}}
    @else
        @vite(['resources/js/app.ts', 'resources/css/app.css'])
    @endif
    <title>Login</title>
</head>

<body class="h-screen flex items-center justify-center bg-stikom/10">

    <div class="flex min-h-full w-100 flex-col justify-center px-4 py-12 lg:px-4  bg-red">

        <div class="w-full max-w-md shadow-xl border-b p-7 bg-stikom-blue">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <h2 class="text-center text-xl/9 font-bold tracking-tight bg-linear-to-r from-white to-gray-300 bg-clip-text text-transparent">LOGIN PUSAT DATA</h2>
            </div>
        </div>

        <div class="w-full max-w-md bg-white shadow-xl p-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <form action="/login" method="POST" class="space-y-6" novalidate>
                    @csrf

                    @if(request('invitation_token'))
                        <div class="rounded-sm bg-blue-50 border border-blue-200 px-4 py-3">
                            <p class="text-sm text-blue-700">Anda memiliki undangan organisasi untuk email <strong>{{ request('email') }}</strong>. Silakan login dengan akun yang diundang.</p>
                        </div>
                    @endif

                    {{-- Badge error di atas form --}}
                    @if (session('success'))
                        <div class="rounded-sm bg-green-50 border border-green-200 px-4 py-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-sm bg-red-50 border border-red-200 px-4 py-3">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-600">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                <div>
                    <label for="username" class="block text-sm/6 font-medium text-gray-900">Username</label>
                    <div class="mt-2">
                    <input id="username" tabindex="1" type="username" name="username" required autocomplete="username" class="block w-full rounded-sm bg-black/1 px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-black/30 placeholder:text-stikom-blue focus:outline-2 focus:-outline-offset-2 focus:outline-stikom-blue sm:text-sm/6" />
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
                    </div>

                    <div class="mt-2 relative">
                        <input 
                            id="password" 
                            tabindex="2"
                            type="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            class="block w-full rounded-sm bg-black/1 px-3 py-1.5 pr-10 text-base text-black outline-1 -outline-offset-1 outline-black/30 placeholder:text-stikom-blue focus:outline-2 focus:-outline-offset-2 focus:outline-stikom-blue sm:text-sm/6"
                        />
                        <button 
                            type="button"
                            id="togglePassword"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                        >
                            <!-- Eye Icon -->
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-1 text-right">
                        <a href="/forgot-password" class="text-sm text-blue-600 hover:underline">Lupa password?</a>
                    </div>
                </div>

                <div class="mt-2">
                    <input type="hidden" name="invitation_token" value="{{ old('invitation_token', request('invitation_token')) }}">
                    <input type="hidden" name="invitation_email" value="{{ old('invitation_email', request('email')) }}">
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
                    <button type="submit" tabindex="3" class="btn flex w-full justify-center rounded-sm bg-linear-to-r from-blue-500 to-blue-400 px-3 py-1.5 text-white text-sm/6 font-semibold hover:from-blue-700 hover:to-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 shadow-lg shadow-blue-500/30 transition duration-300 ease-in-out hover:scale-104">Login</button>
                    <p class="text-center text-sm text-gray-500 mt-4">
                        Belum punya akun?
                        <a href="/register" class="text-blue-600 hover:underline font-medium">Daftar</a>
                    </p>
                </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('forced_logout_message'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Berakhir',
                    text: '{{ session('forced_logout_message') }}',
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#2563eb',
                });
            });
        </script>
    @endif

    @if (session('reset_success'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Berhasil Direset!',
                    text: 'Silakan login dengan password baru Anda.',
                    confirmButtonText: 'Login Sekarang',
                    confirmButtonColor: '#2563eb',
                    allowOutsideClick: false,
                });
            });
        </script>
    @endif

    @if (session('reset_error'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Reset Password Gagal',
                    text: '{{ session('reset_error') }}',
                    confirmButtonText: 'Coba Lagi',
                    confirmButtonColor: '#2563eb',
                });
            });
        </script>
    @endif

    @php $recaptchaSiteKey = env('RECAPTCHA_SITE_KEY'); @endphp
    @if($recaptchaSiteKey)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
    @endif

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const password = document.getElementById("password");
        const toggle = document.getElementById("togglePassword");
        const eyeIcon = document.getElementById("eyeIcon");

        toggle.addEventListener("click", function () {

            if (password.type === "password") {
                password.type = "text";

                eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58a3 3 0 004.24 4.24" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.1A9.77 9.77 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a15.3 15.3 0 01-4.24 5.33" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.53 6.53A15.05 15.05 0 002.25 12s3.75 7.5 9.75 7.5c1.24 0 2.39-.22 3.44-.61" />
                `;

            } else {
                password.type = "password";

                eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />
                `;
            }

        });

        // ── Anti-spam-klik + reCAPTCHA (alur token TIDAK diubah dari versi asli) ──
        const form = document.querySelector('form[action="/login"]');
        const tokenInput = document.getElementById('g-recaptcha-response');
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitBtnOriginalText = submitBtn.innerHTML;
        const recaptchaSiteKey = '{{ $recaptchaSiteKey }}';

        function lockSubmitButton() {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
            submitBtn.innerHTML = 'Memproses...';
        }

        function unlockSubmitButton() {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            submitBtn.innerHTML = submitBtnOriginalText;
        }

        // Blokir klik ganda di level tombol juga (bukan cuma di form).
        submitBtn.addEventListener('click', function (event) {
            if (submitBtn.disabled) {
                event.preventDefault();
            }
        });

        if (form && tokenInput && recaptchaSiteKey) {
            // ── Alur SAMA PERSIS dengan versi asli, cuma disisipi lock/unlock ──
            form.addEventListener('submit', function (event) {
                if (tokenInput.value) {
                    lockSubmitButton();
                    return; // submit native jalan seperti biasa (token sudah ada)
                }

                event.preventDefault();

                if (!window.grecaptcha) {
                    lockSubmitButton();
                    form.submit();
                    return;
                }

                lockSubmitButton();

                window.grecaptcha.ready(function () {
                    window.grecaptcha.execute(recaptchaSiteKey, { action: 'login' })
                        .then(function (token) {
                            tokenInput.value = token;
                            form.submit();
                        })
                        .catch(function (err) {
                            console.error('reCAPTCHA execute gagal:', err);
                            unlockSubmitButton();
                        });
                });
            });
        } else {
            // Tanpa reCAPTCHA: cuma kunci tombol, submit native jalan seperti biasa.
            form.addEventListener('submit', function () {
                lockSubmitButton();
            });
        }

        // Halaman di-restore dari bfcache (misal user pencet Back) -> buka lagi tombolnya.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                unlockSubmitButton();
            }
        });

    });
    </script>

</body>
</html>