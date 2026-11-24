@props(['title' => null])
@php
    $sysConfig = \App\Models\SystemConfiguration::first();
    $bgTimestamp = 1;
    if ($sysConfig && $sysConfig->login_background_updated_at) {
        $val = $sysConfig->login_background_updated_at;
        $bgTimestamp = is_numeric($val)
            ? (int) $val
            : (is_object($val) && method_exists($val, 'getTimestamp')
                ? $val->getTimestamp()
                : (strtotime((string) $val) ?: 1));
    }
    $hasCustomBg = $sysConfig && !empty($sysConfig->login_background_path);
    $bgUrl = $hasCustomBg
        ? route('login.background', ['v' => $bgTimestamp])
        : 'https://upload.wikimedia.org/wikipedia/commons/b/b6/Front_view_of_SMKN_1_Bantul.jpg';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F1014">
    <meta name="text-scale" content="scale">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ (isset($title) && filled($title)) ? $title . ' - ' : '' }}{{ config('app.name', 'School Class Progress') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Typography: Self-hosted Plus Jakarta Sans & JetBrains Mono via Vite -->

    <!-- Theme Preference Initialization (Auto Device Detection / Stored / Cross-Tab Listener) -->
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (function() {
            function applyTheme(override) {
                try {
                    const savedTheme = override || localStorage.getItem('theme') || 'system';
                    const colorMeta = document.querySelector('meta[name="color-scheme"]');
                    if (colorMeta) {
                        colorMeta.content = savedTheme === 'dark' ? 'dark' : (savedTheme === 'light' ? 'light' : 'light dark');
                    }
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const isDark = savedTheme === 'dark' || (savedTheme === 'system' && prefersDark);
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                        document.documentElement.classList.remove('light');
                    } else {
                        document.documentElement.classList.remove('dark');
                        document.documentElement.classList.add('light');
                    }
                    const themeMeta = document.querySelector('meta[name="theme-color"]');
                    if (themeMeta) {
                        themeMeta.content = isDark ? '#0F1014' : '#F4F4F6';
                    }
                } catch (e) {
                    document.documentElement.classList.add('dark');
                }
            }

            // Execute immediately before first paint
            applyTheme();

            // Listen for storage event across tabs
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme') {
                    applyTheme(e.newValue);
                }
            });

            // Listen for system appearance changes when set to 'system'
            try {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                const listener = function() {
                    const current = localStorage.getItem('theme') || 'system';
                    if (current === 'system') {
                        applyTheme('system');
                    }
                };
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', listener);
                } else if (mediaQuery.addListener) {
                    mediaQuery.addListener(listener);
                }
            } catch (e) {}
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Scoped Auth / Guest Design Tokens & Fallback Styles (Independent of Vite build) -->
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        [x-cloak] { display: none !important; }

        :root, html.dark {
            --auth-bg: #0F1014;
            --auth-surface: #20222A;
            --auth-text: #EDEDF0;
            --auth-muted: #A1A1A6;
            --auth-border: #303342;
            --auth-input-bg: #15161D;
            --auth-input-border: #3A3D4E;
            --auth-input-text: #EDEDF0;
            --auth-input-placeholder: #7E8294;
            --auth-link: #38BDF8;
            --auth-btn-bg: #0073B1;
            --auth-btn-text: #FFFFFF;
            --auth-btn-hover: #005D8F;
            --auth-btn-disabled: #272832;
            --auth-ring: #38BDF8;
            --auth-error: #F87171;
            --auth-success: #34D399;
            --auth-overlay-bg: linear-gradient(to top, rgba(0, 0, 0, 0.85), rgba(10, 16, 26, 0.65), rgba(10, 16, 26, 0.40));
            --auth-gradient-from: #0c1f38;
            --auth-gradient-via: #0d2a4a;
            --auth-gradient-to: #08182b;
        }

        html.light, html:not(.dark) {
            --auth-bg: #F4F4F6;
            --auth-surface: #FFFFFF;
            --auth-text: #161619;
            --auth-muted: #52525B;
            --auth-border: #8C8C91;
            --auth-input-bg: #FFFFFF;
            --auth-input-border: #8C8C91;
            --auth-input-text: #161619;
            --auth-input-placeholder: #52525B;
            --auth-link: #0073B1;
            --auth-btn-bg: #0073B1;
            --auth-btn-text: #FFFFFF;
            --auth-btn-hover: #005D8F;
            --auth-btn-disabled: #E4E4E7;
            --auth-ring: #0073B1;
            --auth-error: #DC2626;
            --auth-success: #059669;
            --auth-overlay-bg: linear-gradient(to top, rgba(0, 0, 0, 0.85), rgba(10, 16, 26, 0.65), rgba(10, 16, 26, 0.40));
            --auth-gradient-from: #0c1f38;
            --auth-gradient-via: #0d2a4a;
            --auth-gradient-to: #08182b;
        }

        body.auth-body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            letter-spacing: -0.011em;
            background-color: var(--auth-bg) !important;
            color: var(--auth-text) !important;
        }

        .auth-card {
            background-color: var(--auth-surface) !important;
            border: 1px solid var(--auth-border) !important;
            color: var(--auth-text) !important;
        }

        html.dark .auth-card {
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.6), 0 4px 6px -4px rgba(0, 0, 0, 0.5) !important;
        }

        html.light .auth-card, html:not(.dark) .auth-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
        }

        .auth-header-title {
            color: var(--auth-text) !important;
        }

        .auth-footer {
            color: var(--auth-muted) !important;
            border-top: 1px solid var(--auth-border) !important;
        }

        .auth-overlay {
            background: var(--auth-overlay-bg) !important;
        }

        .auth-gradient {
            background: linear-gradient(135deg, var(--auth-gradient-from), var(--auth-gradient-via), var(--auth-gradient-to)) !important;
        }

        .auth-card label {
            color: var(--auth-text) !important;
        }

        .auth-card a:not(.btn) {
            color: var(--auth-link) !important;
        }

        .auth-checkbox { background-color: var(--auth-input-bg) !important; border-color: var(--auth-input-border) !important; }
        .auth-card .text-muted-token {
            color: var(--auth-muted) !important;
        }
    </style>
</head>
<body class="h-full auth-body relative overflow-x-hidden min-h-dvh transition-colors duration-150">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <div id="app-announcer" role="status" aria-live="polite" class="sr-only"></div>
    <div class="min-h-dvh w-full lg:grid lg:grid-cols-2">
        
        {{-- Form autentikasi --}}
        <div class="flex flex-col justify-between min-h-dvh p-5 sm:p-8 lg:p-10 xl:p-12 z-10">
            <!-- Top Minimalist Brand Header -->
            <header class="w-full flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 group">
                    <picture class="shrink-0">
                        <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo.png') }}" alt="" width="32" height="32" class="w-8 h-8 object-contain shrink-0">
                    </picture>
                    <span translate="no" class="text-base font-bold auth-header-title tracking-tight">School Class Progress</span>
                </a>

                <!-- Theme Preference Dropdown -->
                <x-theme-dropdown align="right" />
            </header>

            <!-- Main Central Card Area -->
            <main id="main-content" tabindex="-1" class="my-auto py-8 flex flex-col justify-center items-center w-full focus-visible:outline-none">
                <div class="w-full sm:max-w-md px-6 py-7 sm:p-8 auth-card rounded-2xl shadow-xl dark:shadow-none transition-colors duration-150">
                    {{ $slot }}
                </div>
            </main>

            <!-- Bottom Minimalist Footer -->
            <footer class="w-full py-4 text-center text-xs font-mono auth-footer">
                <span>School Class Progress &copy; {{ date('Y') }} &bull; SMK Negeri 1 Bantul</span>
            </footer>
        </div>

        {{-- Latar visual desktop --}}
        <div class="hidden lg:block relative h-full w-full overflow-hidden auth-gradient">
            <picture class="absolute inset-0 w-full h-full block">
                <source media="(min-width: 1024px)" srcset="{{ $bgUrl }}">
                <img src="{{ $bgUrl }}" alt="SMK Negeri 1 Bantul" width="1280" height="960" fetchpriority="high" decoding="async" referrerpolicy="no-referrer" class="w-full h-full object-cover" data-bg-fallback="{{ route('login.background', ['v' => $bgTimestamp]) }}">
            </picture>

            {{-- Subtle Gradient Overlay ensuring contrast over all image areas --}}
            <div class="absolute inset-0 auth-overlay"></div>

            {{-- Branding Text Overlay (Clean, no decorative status pulse or fake badge) --}}
            <div class="absolute bottom-10 left-10 right-10 z-10 text-white pointer-events-none">
                <p translate="no" class="text-2xl xl:text-3xl font-extrabold tracking-tight">School Class Progress</p>
                <p class="text-xs xl:text-sm text-white/85 mt-1.5 max-w-lg leading-relaxed">
                    Sistem monitoring jurnal kelas, absensi siswa terpadu, dan manajemen kurikulum SMK Negeri 1 Bantul.
                </p>
            </div>
        </div>

    </div>

</body>
</html>
