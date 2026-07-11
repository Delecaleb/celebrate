{{-- resources/views/components/top-nav.blade.php --}}

<nav class="w-full bg-white border-b border-gray-200 px-4 lg:px-6 h-16 flex items-center justify-between sticky top-0 z-50">

    <!-- LEFT -->
    <div class="flex items-center gap-4 min-w-0">

        <!-- LOGO -->
        <a href="/" class="flex items-center gap-3 shrink-0">

            <div class="relative">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center shadow-md">
                    <span class="text-white text-xl">🎉</span>
                </div>

                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-green-500 border-2 border-white"></div>
            </div>

            <div class="hidden sm:block">
                <h1 class="font-black text-lg tracking-tight text-gray-900">
                    CelebrateMi
                </h1>

                <p class="text-xs text-gray-400 -mt-1">
                    Celebrate moments beautifully
                </p>
            </div>

        </a>

        <!-- PAGE TITLE -->
        <div class="hidden md:flex items-center gap-3 ml-4 min-w-0">

            <div class="w-px h-6 bg-gray-200"></div>

            <h2 class="truncate text-lg font-semibold text-gray-800">
                {{ $title ?? 'Celebration Event' }}
            </h2>

            <!-- LIVE BADGE -->
            <div class="flex items-center gap-1 px-2 py-1 rounded-full bg-rose-100 text-rose-600 text-xs font-semibold">

                <span class="w-2 h-2 rounded-full bg-rose-500 pumpFs"></span>

                Live

            </div>

        </div>

    </div>

    <!-- CENTER ACTIONS -->
    <div class="hidden lg:flex items-center gap-2">

        <!-- THEME -->
        <div class="relative group">

            <button class="w-11 h-11 rounded-xl hover:bg-gray-100 transition flex items-center justify-center text-gray-600">

                <i class="mdi mdi-palette-outline text-2xl"></i>

            </button>

            <div class="absolute left-1/2 -translate-x-1/2 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Customize Theme
            </div>

        </div>

        <!-- SHARE -->
        <div class="relative group">

            <button class="w-11 h-11 rounded-xl hover:bg-gray-100 transition flex items-center justify-center text-gray-600">

                <i class="mdi mdi-share-variant-outline text-2xl"></i>

            </button>

            <div class="absolute left-1/2 -translate-x-1/2 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Share Celebration
            </div>

        </div>

        <!-- COPY LINK -->
        <div class="relative group">

            <button class="w-11 h-11 rounded-xl hover:bg-gray-100 transition flex items-center justify-center text-gray-600">

                <i class="mdi mdi-link-variant text-2xl"></i>

            </button>

            <div class="absolute left-1/2 -translate-x-1/2 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Copy Celebration Link
            </div>

        </div>

        <!-- GUESTS -->
        <div class="relative group">

            <button class="w-11 h-11 rounded-xl hover:bg-gray-100 transition flex items-center justify-center text-gray-600">

                <i class="mdi mdi-account-group-outline text-2xl"></i>

            </button>

            <div class="absolute left-1/2 -translate-x-1/2 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Guests & Visitors
            </div>

        </div>

        <!-- WISHES / GIFTS -->
        <div class="relative group">

            <button class="relative w-11 h-11 rounded-xl bg-rose-50 hover:bg-rose-100 transition flex items-center justify-center">

                <!-- Pumping Giftbox -->
                <div class="pumpFs">

                    <i class="mdi mdi-gift-outline text-2xl text-rose-500"></i>

                </div>

                <!-- Notification Dot -->
                <div class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></div>

            </button>

            <div class="absolute left-1/2 -translate-x-1/2 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Wishes, Gifts & Contributions
            </div>

        </div>

    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-3">

        <!-- STATUS -->
        <div class="relative group">

            <button class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-rose-500 to-orange-400 hover:opacity-90 text-white px-5 py-2.5 rounded-xl font-semibold shadow-md transition">

                <span class="w-2 h-2 rounded-full bg-white pumpFs"></span>

                Published

                <i class="mdi mdi-tune-vertical text-lg"></i>

            </button>

            <div class="absolute right-0 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Celebration Visibility & Settings
            </div>

        </div>

        <!-- MORE -->
        <div class="relative group">

            <button class="w-10 h-10 rounded-xl hover:bg-gray-100 transition flex items-center justify-center text-gray-600">

                <i class="mdi mdi-dots-vertical text-2xl"></i>

            </button>

            <div class="absolute right-0 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                More Actions
            </div>

        </div>

        <!-- USER -->
        <div class="relative group">

            <button class="w-11 h-11 rounded-full overflow-hidden ring-2 ring-rose-100 hover:ring-rose-300 transition">

                @if(auth()->user()?->avatar)
                    <img
                        src="{{ auth()->user()->avatar }}"
                        class="w-full h-full object-cover"
                    >
                @else
                    <div class="w-full h-full bg-gradient-to-br from-rose-500 to-orange-400 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}
                    </div>
                @endif

            </button>

            <!-- ONLINE -->
            <div class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full bg-green-500 border-2 border-white"></div>

            <!-- TOOLTIP -->
            <div class="absolute right-0 top-14 opacity-0 group-hover:opacity-100 pointer-events-none transition bg-gray-900 text-white text-xs px-3 py-2 rounded-lg whitespace-nowrap shadow-lg">
                Your Profile
            </div>

        </div>

    </div>

</nav>

