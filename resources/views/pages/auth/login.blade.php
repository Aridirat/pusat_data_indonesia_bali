<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>Login</title>
</head>

<body class="h-screen flex items-center justify-center bg-stikom/10">

    <div class="flex min-h-full w-100 flex-col justify-center px-4 py-12 lg:px-4  bg-red">

        <div class="w-full max-w-md shadow-xl border-b rounded-t-2xl p-7 bg-white">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <h2 class="text-center text-xl/9 font-bold tracking-tight bg-linear-to-r from-blue-500 to-sky-400 bg-clip-text text-transparent">LOGIN PUSAT DATA</h2>
            </div>
        </div>

        <div class="w-full max-w-md bg-white shadow-xl rounded-b-2xl p-8">

            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <form action="/login" method="POST" class="space-y-6" novalidate>
                    @csrf
                <div>
                    <label for="username" class="block text-sm/6 font-medium text-gray-900">Username</label>
                    <div class="mt-2">
                    <input id="username" type="username" name="username" required autocomplete="username" class="block w-full rounded-md bg-black/1 px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-black/30 placeholder:text-stikom_accent focus:outline-2 focus:-outline-offset-2 focus:outline-stikom_accent sm:text-sm/6" />
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
                    </div>

                    <div class="mt-2 relative">
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            class="block w-full rounded-sm bg-black/1 px-3 py-1.5 pr-10 text-base text-black outline-1 -outline-offset-1 outline-black/30 placeholder:text-stikom_accent focus:outline-2 focus:-outline-offset-2 focus:outline-stikom_accent sm:text-sm/6"
                        />

                        <!-- ICON TOGGLE -->
                        <button 
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                        >
                            <!-- Eye Icon -->
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-10">
                    <button type="submit" class="btn flex w-full justify-center rounded-sm bg-linear-to-r from-amber-500 to-amber-400 px-3 py-1.5 text-white text-sm/6 font-semibold hover:from-amber-700 hover:to-amber-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 shadow-lg shadow-orange-500/30 transition duration-300 ease-in-out hover:scale-104">Login</button>
                </div>
                </form>
            </div>
        </div>
    </div>

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

});
</script>

</body>
</html>