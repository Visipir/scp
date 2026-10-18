<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SCP - School Class Progress</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">
    
    <!-- Top Navigation -->
    <nav class="absolute top-0 w-full px-6 py-4 flex justify-between items-center z-10">
        <div class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-8 h-8 object-contain shrink-0">
            <span class="font-extrabold text-2xl tracking-tighter text-[#0093dd]">SCP.</span>
        </div>
        @if (Route::has('login'))
            <div>
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-[#0093dd] transition">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-[#0093dd] transition">Log in</a>
                @endauth
            </div>
        @endif
    </nav>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto">
            
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 text-[#0093dd] text-sm font-medium mb-8 border border-sky-100">
                <span class="flex h-2 w-2 rounded-full bg-[#0093dd]"></span>
                Sistem Terpadu v1.0
            </div>

            <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 leading-tight">
                School Class <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0093dd] to-sky-500">Progress</span>
            </h1>
            
            <p class="text-lg sm:text-xl text-slate-500 mb-10 max-w-2xl mx-auto leading-relaxed">
                Platform digital modern untuk manajemen jurnal mengajar, presensi siswa, dan sinkronisasi perizinan secara real-time.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex justify-center items-center w-full sm:w-auto px-8 py-3.5 text-sm font-semibold text-white bg-[#0093dd] rounded-xl hover:bg-[#0082c4] shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0093dd]">
                        Masuk ke Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex justify-center items-center w-full sm:w-auto px-8 py-3.5 text-sm font-semibold text-white bg-[#0093dd] rounded-xl hover:bg-[#0082c4] shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0093dd]">
                        Login Akun
                        <svg class="ml-2 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @endauth
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-sm text-slate-400">
        &copy; {{ date('Y') }} School Class Progress. All rights reserved.
    </footer>

</body>
</html>
