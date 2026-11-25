<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ $gymName ?? config('app.name', 'PulseCore Fitness') }}</title>

    <!-- Google Fonts: Plus Jakarta Sans + Inter + Noto Sans Khmer -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F5F5EF] text-[#202520] min-h-screen antialiased">
@auth
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-xs hidden lg:hidden"></div>

    <!-- Sidebar (Dark Charcoal #171A18) -->
    <aside id="app-sidebar"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-[#171A18] text-white flex flex-col transition-all duration-200 -translate-x-full lg:translate-x-0 border-r border-white/10">
        <!-- Brand Header -->
        <div class="h-16 px-4 flex items-center justify-between border-b border-white/10 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-[#C5F467] text-[#171A18] flex items-center justify-center shrink-0 shadow-sm">
                    <x-icon name="dumbbell" class="w-5 h-5" />
                </div>
                <div class="sidebar-brand-text truncate">
                    <div class="font-display font-bold text-sm tracking-tight text-white truncate">{{ $gymName ?? 'PulseCore Fitness' }}</div>
                    <div class="text-[11px] text-[#858B84] font-medium">{{ __('Gym Operations OS') }}</div>
                </div>
            </a>

            <button id="mobile-menu-close" type="button" class="lg:hidden p-1.5 rounded-lg text-[#858B84] hover:text-white hover:bg-white/10">
                <x-icon name="x" class="w-5 h-5" />
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <div class="sidebar-section-label px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-[#858B84]">
                {{ __('Operations') }}
            </div>

            @php
                $navItems = [
                    ['route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => __('Dashboard')],
                    ['route' => 'members.index', 'pattern' => 'members.*', 'icon' => 'users', 'label' => __('Members')],
                    ['route' => 'membership-plans.index', 'pattern' => 'membership-plans.*', 'icon' => 'layers', 'label' => __('Membership Plans')],
                    ['route' => 'memberships.index', 'pattern' => ['memberships.*', 'subscriptions.*'], 'icon' => 'id-card', 'label' => __('Memberships')],
                    ['route' => 'payments.index', 'pattern' => 'payments.*', 'icon' => 'wallet', 'label' => __('Payments')],
                    ['route' => 'attendance.index', 'pattern' => 'attendance.*', 'icon' => 'calendar-check', 'label' => __('Attendance')],
                    ['route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'bar-chart-3', 'label' => __('Reports')],
                    ['route' => 'settings.index', 'pattern' => 'settings.*', 'icon' => 'settings', 'label' => __('Settings')],
                ];
            @endphp

            @foreach($navItems as $item)
                @php
                    $patterns = (array) $item['pattern'];
                    $active = false;
                    foreach ($patterns as $p) {
                        if (request()->routeIs($p)) { $active = true; break; }
                    }
                @endphp
                <a href="{{ route($item['route']) }}"
                   title="{{ $item['label'] }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $active ? 'bg-[#C5F467] text-[#171A18] font-semibold shadow-sm' : 'text-[#C6CBC4] hover:bg-white/8 hover:text-white' }}">
                    <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                    <span class="sidebar-label truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-3 border-t border-white/10 space-y-2 shrink-0">
            <button id="sidebar-collapse-btn" type="button"
                    class="hidden lg:flex w-full items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-[#858B84] hover:text-white hover:bg-white/8 transition">
                <x-icon name="chevron-left" class="w-4 h-4" />
                <span class="sidebar-label">{{ __('Collapse Sidebar') }}</span>
            </button>
        </div>
    </aside>

    <!-- Main Shell -->
    <div id="app-main-shell" class="lg:pl-64 min-h-screen flex flex-col transition-all duration-200">
        <!-- Top Navigation -->
        <header id="app-topbar" class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-sm border-b border-[#E8EAE3] px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <button id="mobile-menu-open" type="button" class="lg:hidden p-2 rounded-xl border border-[#E8EAE3] text-[#202520] hover:bg-[#F5F5EF]">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>

                <div class="min-w-0">
                    <h1 class="font-display font-bold text-base sm:text-lg text-[#202520] truncate">
                        @yield('page-title', 'Dashboard')
                    </h1>
                    @hasSection('breadcrumbs')
                        <div class="hidden sm:flex items-center gap-1.5 text-xs text-[#858B84] truncate">
                            @yield('breadcrumbs')
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Language Switcher (Khmer / English) -->
                <div class="inline-flex items-center rounded-xl border border-[#E8EAE3] bg-[#F5F5EF] p-0.5 text-xs font-semibold">
                    <a href="{{ route('locale.switch', 'km') }}"
                       class="px-2.5 py-1 rounded-lg transition {{ app()->getLocale() === 'km' ? 'bg-[#171A18] text-[#C5F467]' : 'text-[#565C55] hover:text-[#202520]' }}">
                        ខ្មែរ
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="px-2 py-1 rounded-lg transition {{ app()->getLocale() === 'en' ? 'bg-[#171A18] text-[#C5F467]' : 'text-[#565C55] hover:text-[#202520]' }}">
                        EN
                    </a>
                </div>

                <!-- Quick Global Member Search -->
                <form action="{{ route('members.index') }}" method="GET" class="hidden md:flex items-center relative">
                    <x-icon name="search" class="w-4 h-4 text-[#858B84] absolute left-3 pointer-events-none" />
                    <input type="text" name="search" value="{{ request()->routeIs('members.index') ? request('search') : '' }}"
                           placeholder="{{ __('Search member ID or name...') }}"
                           class="w-56 lg:w-64 pl-9 pr-3 py-1.5 text-sm bg-[#F5F5EF] border border-[#E8EAE3] rounded-xl text-[#202520] placeholder-[#858B84] focus:outline-none focus:bg-white focus:border-[#171A18] focus:ring-2 focus:ring-[#C5F467]/60 transition">
                </form>

                <!-- Notifications Bell -->
                <div class="relative">
                    <button type="button" data-dropdown-toggle="notifications-menu"
                            class="relative p-2 rounded-xl border border-[#E8EAE3] bg-white text-[#202520] hover:bg-[#F5F5EF] transition"
                            aria-label="Notifications">
                        <x-icon name="bell" class="w-5 h-5" />
                        @if(($headerAlerts ?? collect())->count() > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#FF795B] text-white text-[10px] font-bold flex items-center justify-center">
                                {{ $headerAlerts->count() }}
                            </span>
                        @endif
                    </button>

                    <div id="notifications-menu" data-dropdown-menu
                         class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-[#E8EAE3] shadow-xl overflow-hidden z-50">
                        <div class="px-4 py-3 border-b border-[#E8EAE3] flex items-center justify-between bg-[#F5F5EF]/60">
                            <span class="font-display font-bold text-sm text-[#202520]">{{ __('Alerts & Reminders') }}</span>
                            <span class="badge-pill badge-lime">{{ ($headerAlerts ?? collect())->count() }}</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto divide-y divide-[#E8EAE3]">
                            @forelse($headerAlerts ?? [] as $alert)
                                <a href="{{ $alert['url'] }}" class="flex items-start gap-3 px-4 py-3 hover:bg-[#F5F5EF]/70 transition">
                                    <span class="mt-0.5 w-2 h-2 rounded-full shrink-0 {{ $alert['type'] === 'danger' ? 'bg-[#E76B62]' : 'bg-[#F2B84B]' }}"></span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-[#202520] truncate">{{ $alert['title'] }}</p>
                                        <p class="text-xs text-[#858B84] truncate">{{ $alert['subtitle'] }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="px-4 py-8 text-center text-xs text-[#858B84]">
                                    {{ __('All caught up. No urgent membership or payment alerts.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="relative">
                    <button type="button" data-dropdown-toggle="profile-menu"
                            class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1 rounded-xl border border-[#E8EAE3] bg-white hover:bg-[#F5F5EF] transition">
                        <div class="w-8 h-8 rounded-lg bg-[#171A18] text-[#C5F467] font-display font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="hidden sm:block text-left leading-tight">
                            <div class="text-xs font-semibold text-[#202520] max-w-[120px] truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] font-medium text-[#858B84] uppercase tracking-wide">{{ Auth::user()->role }}</div>
                        </div>
                        <x-icon name="chevron-down" class="w-4 h-4 text-[#858B84]" />
                    </button>

                    <div id="profile-menu" data-dropdown-menu
                         class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl border border-[#E8EAE3] shadow-xl overflow-hidden z-50">
                        <div class="px-4 py-3 border-b border-[#E8EAE3] bg-[#F5F5EF]/60">
                            <div class="text-xs font-bold text-[#202520] truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[11px] text-[#858B84] truncate">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="p-1.5 space-y-0.5">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-[#202520] hover:bg-[#F5F5EF]">
                                <x-icon name="layout-dashboard" class="w-4 h-4 text-[#858B84]" /> {{ __('Dashboard') }}
                            </a>
                            <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-[#202520] hover:bg-[#F5F5EF]">
                                <x-icon name="settings" class="w-4 h-4 text-[#858B84]" /> {{ __('Account & Settings') }}
                            </a>
                            <div class="my-1 border-t border-[#E8EAE3]"></div>
                            <form action="{{ route('logout') }}" method="POST" data-no-loading>
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-[#C0392B] hover:bg-[#E76B62]/10 cursor-pointer">
                                    <x-icon name="log-out" class="w-4 h-4" /> {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1600px] w-full mx-auto">
            @if(session('success'))
                <div data-auto-dismiss class="mb-5 flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-[#4CAF78]/12 border border-[#4CAF78]/30 text-[#1F6A40] text-sm font-medium transition-opacity duration-300">
                    <div class="flex items-center gap-2.5">
                        <x-icon name="check-circle" class="w-5 h-5 text-[#4CAF78] shrink-0" />
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="p-1 rounded-lg hover:bg-[#4CAF78]/15">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div data-auto-dismiss class="mb-5 flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-[#E76B62]/12 border border-[#E76B62]/30 text-[#A83229] text-sm font-medium transition-opacity duration-300">
                    <div class="flex items-center gap-2.5">
                        <x-icon name="alert-circle" class="w-5 h-5 text-[#E76B62] shrink-0" />
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="p-1 rounded-lg hover:bg-[#E76B62]/15">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 px-4 py-3.5 rounded-2xl bg-[#E76B62]/12 border border-[#E76B62]/30 text-[#A83229] text-sm">
                    <div class="flex items-center gap-2 font-semibold mb-1.5">
                        <x-icon name="alert-triangle" class="w-4 h-4 text-[#E76B62]" />
                        <span>Please fix the following issues:</span>
                    </div>
                    <ul class="list-disc pl-6 space-y-0.5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Global Accessible Confirmation Modal -->
    <div id="global-confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-[#E8EAE3] shadow-2xl max-w-md w-full p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#FF795B]/15 text-[#FF795B] flex items-center justify-center shrink-0">
                    <x-icon name="alert-triangle" class="w-5 h-5" />
                </div>
                <div>
                    <h3 id="confirm-modal-title" class="font-display font-bold text-base text-[#202520]">{{ __('Confirm Action') }}</h3>
                    <p id="confirm-modal-message" class="text-sm text-[#858B84] mt-1">{{ __('Are you sure you want to continue?') }}</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5">
                <button id="confirm-modal-cancel" type="button" class="btn-outline">{{ __('Cancel') }}</button>
                <button id="confirm-modal-ok" type="button" class="btn-coral">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
@else
    @yield('content')
@endauth

@stack('scripts')
</body>
</html>
