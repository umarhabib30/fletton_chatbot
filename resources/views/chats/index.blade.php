<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/logo/logo.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">

    <title>Flettons Chatbot</title>

    <!-- TailwindCSS v2 CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- font awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Pusher -->
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <style>
        #contactsList {
            -webkit-overflow-scrolling: touch;
            /* smooth scroll for iOS */
            overflow-y: auto !important;
            /* vertical scroll always active */
            max-height: 100vh;
            /* prevent overflow freeze */
            touch-action: pan-y;
            /* allow vertical touch scroll */
        }

        @media (max-width: 768px) {
            #contactsList {
                height: calc(100vh - 120px);
                /* adjust depending on header/footer height */
            }
        }

        /*
        ========================================
        WhatsApp Clone - Professional Layout
        ========================================

        Architecture:
        - Fixed header and composer (position: fixed)
        - Scrollable messages area in between
        - No browser UI animation interference
        - Consistent across iPhone Safari, Android Chrome
        - Keyboard-aware with Visual Viewport API
        - Safe area support for notched devices

        Key Features:
        ✓ Fixed layout prevents browser bar animations
        ✓ Overscroll prevention (no bounce/pull-to-refresh)
        ✓ Keyboard detection with proper spacing
        ✓ Touch optimizations for native feel
        ✓ Desktop responsive with sidebar support
        */

        /* CSS Variables for Safe Areas & Keyboard */
        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --kb: 0px;
            --header-height: 72px;
            --composer-height: 64px;
        }



        /* Prevent overscroll bounce on iOS */
        html,
        body {
            position: fixed;

            overflow: hidden;
            width: 100%;
            height: 100%;
            -webkit-overflow-scrolling: touch;
        }

        /* App Container - Fixed Height */
        #app {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            display: flex;
        }

        /* Chat Pane - Full Height with Fixed Header/Footer */
        #chatPane {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #F5F1EB;
        }

        /* Fixed Header - Always on Top */
        #chat-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: #fff;
            padding: 1rem;
            padding-top: calc(1rem + var(--safe-top));
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            min-height: var(--header-height);
        }

        /* Messages Area - Scrollable Middle Section */
        #messagesArea {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding: 1rem;
            padding-top: calc(var(--header-height) + 1rem);
            padding-bottom: calc(var(--composer-height) + 1rem);
            background: #F5F1EB;
        }

        /* Fixed Composer - Always at Bottom */
        #chat-composer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: #F5F1EB;
            padding: 0.75rem;
            padding-bottom: calc(0.75rem + var(--safe-bottom));
            box-shadow: 0 -2px 10px rgba(0, 0, 0, .05);
            min-height: var(--composer-height);
        }

        /* Keyboard Open State */
        .kb-open #chat-composer {
            bottom: 0;
            padding-bottom: 0.75rem;
        }

        /* Banner Positioning */
        #chat-blocked-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 99;
            padding-bottom: var(--safe-bottom);
        }

        /* Contacts Pane */
        #contactsPane {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        #contactsList {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        /* Prevent pull-to-refresh and overscroll */
        .scrollable-area {
            overscroll-behavior-y: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* Contact Info Sidebar */
        #contactSidebar {
            overscroll-behavior: contain;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Starred Sidebar */
        #starredSidebar {
            overscroll-behavior: contain;
        }

        #starredList {
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* === WhatsApp-like Light Theme === */
        body {
            background: #f5f7fb;
            color: #111b21;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .scrollbar-hide {
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Touch action optimization */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }

        input,
        textarea,
        button,
        select,
        a {
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
            -webkit-touch-callout: default;
        }

        /* Smooth scrolling for all scrollable areas */
        #messagesArea,
        #contactsList,
        #contactSidebar,
        #starredList {
            scroll-behavior: smooth;
            overscroll-behavior: contain;
        }

        /* Prevent zoom on input focus (iOS) */
        input[type="text"],
        input[type="search"],
        textarea {
            font-size: 16px !important;
        }

        /* Standalone mode optimizations */
        html.standalone-mode {
            height: 100vh;
            height: 100dvh;
        }

        html.standalone-mode body {
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
        }

        /* Light palette */
        .wa-bg-primary {
            background: #ffffff;
        }

        /* panels/cards */
        . {
            background: #f6f5f4;
        }

        /* headers/toolbars */
        .wa-bg-chat {
            background: #ffffff;
        }

        /* app container */
        .wa-bg-message-in {
            background: #ffffff;
            /* border: 1px solid #e6e6e6; */
        }

        .wa-bg-message-out {
            background: #d9fdd3;
        }

        .wa-text-primary {
            color: #111b21;
        }

        .wa-text-secondary {
            color: #667781;
        }

        .wa-border {
            border-color: #e6e6e6;
        }

        .wa-hover:hover {
            background: #f5f6f6;
        }

        .chat-menu-trigger {
            pointer-events: auto;
        }

        .contact-item:hover .chat-menu-trigger {
            opacity: 1;
        }

        /* Message tails (match bubble colors) */
        .message-tail-in::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 14px 10px 0;
            border-color: transparent #ffffff transparent transparent;
            /* add a subtle border to match incoming bubble border */
            /* filter: drop-shadow(-1px 0 0 #e6e6e6); */
        }

        .message-tail-out::before {
            content: '';
            position: absolute;
            right: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 10px 14px;
            border-color: transparent transparent transparent #d9fdd3;
        }

        /* Chat background */
        .chat-bg-pattern {
            background-color: #f5f1eb;
        }

        /* Unread badge (better contrast on light) */
        .unread-badge {
            min-width: 20px;
            height: 20px;
            background: #25d366;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            border-radius: 10px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Fix chat composer disappearing in Chrome */
        /*#chatPane {*/
        /* position: relative;*/
        /* display: flex;*/
        /* flex-direction: column;*/
        /* height: 100%;*/
        /*}*/
        /* Scrollable message area with bottom padding */
        /*#messagesArea {*/
        /* flex: 1;*/
        /* overflow-y: auto;*/
        /* padding: 1rem 1rem 100px; */
        /* scroll-behavior: smooth;*/
        /*}*/
        /* Fix composer position at bottom */
        /*#chat-composer {*/
        /* position: absolute;*/
        /* bottom: 0;*/
        /* left: 0;*/
        /* right: 0;*/
        /* background: #F5F1EB;*/
        /* z-index: 50;*/
        /* border-top: 1px solid #e5e7eb;*/
        /*}*/
        /* Ensure the overlay banners stack correctly */
        #chat-blocked-banner {
            z-index: 49;
        }

        /* Mobile view state */
        @media (max-width: 767px) {
            #contactsPane {
                display: block;
                position: absolute;
                width: 100%;
                height: 100%;
                left: 0;
                top: 0;
                z-index: 1;
            }

            .submenu {
                right: 10px;
            }

            #chatPane {
                display: none;
                position: absolute;
                width: 100%;
                height: 100%;
                left: 0;
                top: 0;
                z-index: 2;
            }

            #app.is-chat-open #contactsPane {
                display: none;
            }

            #app.is-chat-open #chatPane {
                display: flex;
            }

            .wa-bg-message-out {
                max-width: 80%;
            }

            .wa-bg-message-in {
                max-width: 80%;
            }

            /* Mobile: Full width header and composer */
            #chat-header {
                left: 0;
                right: 0;
                width: 100%;
            }

            #chat-composer {
                left: 0;
                right: 0;
                width: 100%;
            }
        }

        /* Custom scrollbar for desktop */
        @media (min-width: 768px) {
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cfd6db;
                border-radius: 3px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: #b9c1c7;
            }

            /* Desktop: Header and composer positioned relative to chatPane */
            #chatPane {
                flex: 1;
            }

            #chat-header {
                left: 25%;
                right: 0;
            }

            #chat-composer {
                left: 25%;
                right: 0;
            }

            #messagesArea {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            /* When sidebar is open (2/3 width) */
            .w-2\/3 #chat-header,
            #chat-header.w-2\/3 {
                left: 25%;
                right: 25%;
            }

            .w-2\/3 #chat-composer,
            #chat-composer.w-2\/3 {
                left: 25%;
                right: 25%;
            }

            .w-2\/3 #messagesArea,
            #messagesArea.w-2\/3 {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
        }

        /* Message input focus */
        #messageInput:focus {
            outline: none;
            box-shadow: none;
        }

        /* Contact item active state */
        .contact-item.active {
            background: #f6f5f4;
            border: none !important;
        }

        /* Submenu */
        .submenu {
            display: none;
            min-width: 200px;
            border-radius: 8px;
            position: absolute;
            top: 50px;
            left: 150px;
            background-color: #ffffff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            z-index: 9999999 !important;
        }

        .submenu ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        .submenu li {
            padding: 10px;
            cursor: pointer;
        }

        .submenu li:hover {
            background-color: #f6f5f4;
        }

        .contact-item:hover .unread-badge {
            margin-right: 1.5rem;
            /* or adjust to match the chevron width */
        }

        .swal2-cancel-btn {
            background: transparent;
            color: #25d366;
            /* WhatsApp green */
            font-weight: 500;
            border: none;
            outline: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            margin-right: 20px !important;
        }

        .swal2-cancel-btn:hover {
            background: #f0f0f0;
        }

        .swal2-delete-btn {
            background: #ea0038;
            /* WhatsApp-like red/pink */
            color: #fff;
            font-weight: 600;
            border: none;
            outline: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
        }

        .swal2-delete-btn:hover {
            background: #d30132;
        }

        /* Overlay Background for loading */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* background: rgba(0, 0, 0, 0.6); */
            /* black with transparency */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            /* keep it above everything */
        }

        /* Rotating Loader Image */
        .loading-image {
            width: 50px;
            /* Adjust the size as needed */
            height: 50px;
            /* Adjust the size as needed */
            animation: rotateImage 2s linear infinite;
        }

        /* Keyframes for rotating the image */
        @keyframes rotateImage {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* message pop up css */
        @media (max-width: 767px) {
            #messageActionMenu {
                width: 90%;
                border-radius: 14px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            }
        }

        #messageActionMenu {
            transform-origin: top left;
            transition: transform 0.15s ease, opacity 0.15s ease;
            opacity: 0;
            transform: scale(0.95);
        }

        #messageActionMenu:not(.hidden) {
            opacity: 1;
            transform: scale(1);
        }

        /* starred drawer */
        @media (max-width: 767px) {
            #starredList .max-w-\[85\%\] {
                max-width: 92%;
            }
        }
    </style>
</head>

<body class="min-h-screen">
    <div id="app" class="max-w-full mx-auto h-screen flex wa-bg-chat">
        <!-- Sidebar / Contact List -->
        <aside id="contactsPane" class="w-full md:w-1/4 flex flex-col wa-bg-primary border-r wa-border">
            <!-- Profile / Brand -->
            <div class="p-4 ">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <img src="{{ asset('assets/logo/fletton_logo.jpeg') }}" alt="Flettons"
                                class="w-10 h-10 rounded-full object-cover" />
                            <span
                                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold wa-text-primary">Flettons</h3>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 rounded-full wa-hover" title="New chat">
                            <svg class="w-5 h-5 wa-text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M19.005 3.175H4.674C3.642 3.175 3 3.789 3 4.821V21.02l3.544-3.514h12.461c1.033 0 2.064-1.06 2.064-2.093V4.821c-.001-1.032-1.032-1.646-2.064-1.646zm-4.989 9.869h-7.03V11.1h7.03v1.944zm3-4h-10.03V7.1h10.03v1.944z" />
                            </svg>
                        </button>
                        <!-- Menu Button -->
                        <button class="p-2 rounded-full wa-hover" title="Menu" id="menuButton">
                            <svg class="w-5 h-5 wa-text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>

                        <!-- Submenu -->
                        <div id="submenu" class="submenu hidden absolute bg-white shadow-lg rounded-lg p-2">
                            <ul>
                                <a href="{{ route('admin.dashboard') }}">
                                    <li class="hover:bg-gray-200 rounded-lg"><i class="fa fa-dashboard "></i> Dashboard
                                    </li>
                                </a>
                                <a href="{{ route('admin.logout') }}">
                                    <li class="hover:bg-gray-200 rounded-lg"><i class="fa fa-sign-out "></i> Log out
                                    </li>
                                </a>

                            </ul>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="px-3 py-2 wa-bg-primary">
                <label class="relative block">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 wa-text-secondary w-4 h-4"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="searchInput" type="text" placeholder="Search or start new chat"
                        class="w-full border wa-text-primary pl-12 pr-4 py-3 rounded-full focus:outline-none placeholder-gray-500 text-sm" />
                </label>
            </div>

            <!-- Chats -->
            <div id="contactsList" class="flex-1 overflow-y-auto scrollbar-hide custom-scrollbar p-3">
                @foreach ($conversations as $conversation)
                    <button
                        class="contact-item relative w-full text-left px-4 py-3  wa-hover wa-border transition-colors duration-150 p-2 rounded-2xl"
                        sid="{{ $conversation->sid }}" data-name="{{ $conversation->contact }}"
                        data-firstname="{{ $conversation->first_name }} {{ $conversation->last_name }}"
                        data-last-message="{{ $conversation->last_message_body ?? '' }}"
                        data-last-time="{{ $conversation->last_message }}">
                        <div class="flex items-start space-x-3">
                            <img src="{{ asset('assets/images/profile.png') }}" alt="{{ $conversation->contact }}"
                                class="w-12 h-12 rounded-full object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between ">
                                    <h4 class="wa-text-primary truncate flex-1">
                                        @if ($conversation->first_name)
                                            {{ $conversation->first_name }} {{ $conversation->last_name }}
                                        @else
                                            {{ $conversation->contact }}
                                        @endif
                                    </h4>
                                    @php
                                        $lastMessageTime = Carbon\Carbon::parse($conversation->last_message);

                                        if ($lastMessageTime->isToday()) {
                                            $formattedTime = $lastMessageTime->format('g:i A'); // e.g., 3:45 PM
                                        } elseif ($lastMessageTime->isYesterday()) {
                                            $formattedTime = 'Yesterday';
                                        } elseif ($lastMessageTime->greaterThanOrEqualTo(now()->subDays(7))) {
                                            $formattedTime = $lastMessageTime->format('l'); // e.g., Monday, Tuesday
                                        } else {
                                            $formattedTime = $lastMessageTime->format('d/m/Y'); // e.g., 28/09/2023
                                        }
                                    @endphp

                                    <span class="text-xs wa-text-secondary ml-2 whitespace-nowrap">
                                        {{ $formattedTime }}
                                    </span>

                                </div>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm wa-text-secondary truncate flex-1 last-message-preview"
                                        title="{{ $conversation->unread_message }}">
                                        {{ Str::limit($conversation->unread_message ?? 'No messages yet', 40) }}
                                    </p>

                                    @if ($conversation->unread_count > 0)
                                        <span class="unread-badge ml-2 ">{{ $conversation->unread_count }}</span>
                                    @else
                                        <span class="unread-badge ml-2" style="display: none"></span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Menu Trigger -->
                        <div
                            class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition duration-150 chat-menu-trigger">
                            <i class="fa fa-chevron-down text-gray-500 cursor-pointer"></i>
                        </div>

                        <!-- Dropdown -->
                        <div class="hidden absolute right-7 top-15 -translate-y-1/2 bg-white shadow-lg rounded-xl py-1 z-50 chat-menu-dropdown px-2 py-2"
                            style="width: 45%;">
                            <ul>
                                <li class="px-4 py-2  hover:bg-gray-100 cursor-pointer text-md  block-chat-btn wa-text-primary rounded-lg"
                                    data-name="{{ $conversation->name }}">
                                    <i class="fa fa-ban"></i>
                                    @if ($conversation->is_blocked)
                                        Unblock
                                    @else
                                        Block
                                    @endif
                                </li>
                                <li
                                    class="px-4 py-2  hover:bg-gray-100 cursor-pointer text-md  delete-chat-btn wa-text-primary rounded-lg">
                                    <i class="fa fa-trash-o"></i> Delete chat
                                </li>
                            </ul>
                        </div>
                    </button>
                @endforeach
            </div>
        </aside>

        <input type="hidden" id="user_profile" value="{{ asset('assets/images/profile.png') }}" />

        <!-- Chat Pane -->
        <main id="chatPane" class="flex-1 flex flex-col md:flex w-full h-full">
            <!-- Chat Header -->
            <!-- Chat Header -->
            <div id="chat-header" class="chat-header hidden py-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                        <!-- Back (mobile only) -->
                        <button id="backToList" class="md:hidden p-2 -ml-2 rounded-full wa-hover" title="Back">
                            <svg class="w-6 h-6 wa-text-primary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <img id="chatHeaderAvatar"
                            src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=40&h=40&fit=crop&crop=face"
                            class="w-10 h-10 rounded-full object-cover flex-shrink-0 open-infobox" alt="Avatar" />

                        <div class="flex-1 min-w-0 open-infobox">
                            <h3 id="chatHeaderName" class="font-semibold wa-text-primary truncate">Chat</h3>
                            <p class="text-xs wa-text-secondary">online</p>
                            <input type="hidden" id="chat-sid" />
                        </div>
                    </div>

                    <!-- RIGHT SIDE ACTIONS -->
                    <div class="flex items-center space-x-1">
                        <!-- DESKTOP SEARCH AREA (inline) -->
                        <div id="chatSearchAreaDesktop" class="hidden md:flex items-center space-x-2 relative">
                            <!-- Hidden initially on desktop too -->
                            <input id="chatSearchInputDesktop" type="text" placeholder="Search messages..."
                                class="hidden border border-gray-300 rounded-full px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition w-56" />
                            <button id="chatSearchCloseDesktop" class="hidden p-2 rounded-full wa-hover"
                                title="Close">
                                <i class="fa fa-close"></i>
                            </button>
                            <div id="chatSearchResultsDesktop"
                                class="absolute top-full right-0 mt-2 w-72 bg-white shadow-lg rounded-lg hidden z-50 max-h-60 overflow-y-auto border border-gray-200">
                            </div>
                        </div>

                        <!-- Search Button (works for both desktop & mobile) -->
                        <button id="chatSearchBtn" class="p-2 rounded-full wa-hover" title="Search">
                            <svg class="w-5 h-5 wa-text-secondary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        <!-- Menu -->
                        <button class="p-2 rounded-full wa-hover" title="Menu">
                            <svg class="w-5 h-5 wa-text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- MOBILE SEARCH BAR (full width under header info) -->
                <div id="chatSearchBarMobile" class="md:hidden hidden px-4 mt-3">
                    <div class="flex items-center space-x-2">
                        <input id="chatSearchInputMobile" type="text" placeholder="Search messages..."
                            class="flex-1 border border-gray-300 rounded-full px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400" />
                        <button id="chatSearchCloseMobile" class="p-2 rounded-full wa-hover" title="Close">
                            <i class="fa fa-close"></i>
                        </button>
                    </div>
                    <div id="chatSearchResultsMobile"
                        class="mt-2 w-full bg-white shadow-lg rounded-lg hidden z-50 max-h-60 overflow-y-auto border border-gray-200">
                    </div>
                </div>
            </div>


            <!-- Messages -->
            <div id="messagesArea"
                class="flex-1  p-4 md:p-6 space-y-3 scrollbar-hide custom-scrollbar chat-bg-pattern">
            </div>

            <div id="chat-blocked-banner"
                class="hidden py-4 text-center text-gray-600 text-sm border-t border-gray-200 bg-white">
                <span class="flex items-center justify-center space-x-2">
                    <i class="fa fa-ban text-gray-500"></i>
                    <span>You blocked this contact</span>
                </span>
                <button id="unblockBtn"
                    class="mt-2 px-4 py-1 border border-green-500 text-green-600 rounded-full text-sm hover:bg-green-50 block-chat-btn">
                    Unblock
                </button>
            </div>

            <!-- Composer -->
            <div id="chat-composer" class="chat-input hidden  p-3" style="background: #F5F1EB;">
                <div class="flex items-end space-x-2 rounded-full focus:outline-none  border border-gray-200  px-2 bg-white shadow-lg"
                    style="padding-top:3px; padding-bottom:3px;">
                    <div class="flex items-center space-x-2 mb-1">
                        <!-- Pause Auto-reply Icon -->
                        <button id="pause_autoreply"
                            class="p-2 text-red-600 bg-red-100 hover:bg-red-200 rounded-full border border-red-200 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V4z" />
                            </svg>
                        </button>

                        <!-- Resume Auto-reply Icon -->
                        <button id="resume_autoreply"
                            class="p-2 text-green-700 bg-green-100 hover:bg-green-200 rounded-full border border-green-200 transition hidden">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Message Input -->
                    <div class="flex-1 relative">
                        <input id="messageInput" type="text" placeholder="Type a message"
                            class="w-full wa-bg-primary wa-text-primary px-4 py-3 " />
                    </div>

                    <!-- Send Button -->
                    <button id="sendBtn" class="p-3 rounded-full transition-colors duration-200 mb-1"
                        style="background-color: #25d366;" title="Send">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Loader -->
    {{-- <div id="overlay" class="overlay">
        <div class="loader"></div>
    </div> --}}

    <div class="overlay" style="display: none" id="overlay">
        <img src="{{ asset('assets/icons/Loading.png') }}" class="loading-image" alt="Loading...">
    </div>


    <!-- Contact Info Sidebar -->
    <div id="contactSidebar"
        class="fixed top-0 right-0 h-full bg-white border-l transform translate-x-full transition-transform duration-300 z-50 w-full md:w-1/4 ">
        <!-- Full width mobile, wider on desktop -->

        <!-- Header -->
        <div class="flex items-center justify-between p-4 bg-white">
            <button id="closeSidebar" class=" text-lg font-light"><i class="fa fa-close"></i> <span
                    class="ml-3">Contact info</span></button>
            {{-- <button class="text-teal-600 text-sm font-medium">Edit</button> --}}
        </div>

        <!-- Profile Section -->
        <div class="bg-white p-6 text-center">
            <img id="sidebarAvatar" src="{{ asset('assets/images/profile.png') }}"
                class="w-32 h-32 rounded-full object-cover mx-auto mb-3">
            <h2 id="sidebarName" class="text-xl font-semibold mb-1"></h2>
            <p id="sidebarNumber" class="text-gray-500 text-sm"></p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 px-6 py-4 bg-white justify-center">
            <button class="w-1/4 text-center border p-1 rounded-lg border-2" id="startmessage">
                <svg class="w-6 h-6 mx-auto text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                    </path>
                </svg>
                <span class="block mt-1 text-xs text-teal-600 font-medium">Message</span>
            </button>

            <button class="w-1/4 text-center border p-1 rounded-lg border-2" id="chatSearchBtn2">
                <svg class="w-6 h-6 mx-auto text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="block mt-1 text-xs text-teal-600 font-medium">Search</span>
            </button>
        </div>

        <!-- Status -->
        <div class=" px-6 py-3 border-b">
            <div class="flex items-center justify-between">
                <p class="text-gray-600 text-sm">On the road.....</p>
                <span class="text-gray-400 text-xs">26 Jul 2025</span>
            </div>
        </div>

        <!-- Media / Starred -->
        <div class="bg-white mt-2">
            <button class="w-full flex items-center justify-between px-6 py-4 ">
                <span class="text-gray-900"><i class="fa fa-folder-open-o mr-3"></i> Media, links and docs</span>
                <span class="text-gray-500 text-sm">0</span>
            </button>

            <button id="openStarredBtn" class="w-full flex items-center justify-between px-6 py-4 ">
                <span class="text-gray-900"><i class="fa fa-star-o mr-3"></i> Starred messages</span>
                <span id="starredCount" class="text-gray-500 text-sm">0</span>
            </button>
        </div>
    </div>

    <!-- Starred Messages Drawer -->
    <div id="starredSidebar"
        class="fixed top-0 right-0 h-full w-full md:w-1/4 z-50 bg-white border-l transform translate-x-full transition-transform duration-300 flex flex-col"
        style="background:#f7f5f3">

        <!-- Header (non-scrolling) -->
        <div class="flex items-center justify-between p-4 bg-white border-b"
            style="padding-top:22px; padding-bottom:22px;">
            <button id="backToInfo" class="text-lg font-light">
                <i class="fa fa-angle-left"></i>
                <span class="ml-2">Starred messages</span>
            </button>
            <button id="closeStarred" class="text-gray-500"><i class="fa fa-close"></i></button>
        </div>

        <!-- Scrollable list -->
        <div id="starredList" class="flex-1 overflow-y-auto custom-scrollbar px-4">
            <!-- filled by JS -->
        </div>
    </div>




    {{-- action button for the star --}}
    <div id="messageActionMenu" class="fixed bg-white shadow-lg rounded-xl py-2 w-60 hidden z-50 border">
        <ul class="text-sm text-gray-700">
            <li class="menu-item px-4 py-2 hover:bg-gray-100 cursor-pointer" data-action="copy"><i
                    class="fa fa-copy"></i> Copy</li>
            <li class="menu-item px-4 py-2 hover:bg-gray-100 cursor-pointer" data-action="star"><i
                    class="fa fa-star-o"></i> Star</li>

        </ul>
    </div>

    {{-- show image inside the chat model --}}
    <div id="imagePreviewModal"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 p-2">
        <div class="relative bg-white rounded-lg shadow-lg max-w-[90vw] max-h-[90vh] flex flex-col">

            <!-- Header Controls -->
            <div class="absolute top-2 right-2 flex gap-3 text-gray-700">

                <!-- Download Icon -->
                <a id="downloadImageBtn" href="#" download class="text-gray-700 hover:text-blue-600 text-xl">
                    <i class="fa fa-download"></i>
                </a>

                <!-- Close Icon -->
                <button onclick="closeImagePreview()" class="text-gray-700 hover:text-red-600 text-xl">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Image Content -->
            <img id="previewImage" src="" class="object-contain max-h-[85vh] max-w-full rounded-lg" />

        </div>
    </div>



    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // contact details tab
        $(document).on('click', '.open-infobox', function() {
            openContactInfo();
        });

        // Close Sidebar
        $(document).on('click', '#closeSidebar', function() {
            closeContactInfo();
        });


        function openContactInfo() {
            $('#contactSidebar').removeClass('translate-x-full');
            $('#chat-header').addClass('w-2/3');
            $('#messagesArea').addClass('w-2/3');
            $('#chat-composer').addClass('w-2/3');
            $('#chat-blocked-banner').addClass('w-2/3');
            refreshStarredCountFromDOM();
        }

        function closeContactInfo() {
            $('#chat-header').removeClass('w-2/3');
            $('#messagesArea').removeClass('w-2/3');
            $('#chat-composer').removeClass('w-2/3');
            $('#chat-blocked-banner').removeClass('w-2/3');
            $('#contactSidebar').addClass('translate-x-full');
        }

        function refreshStarredCountFromDOM() {
            const count = $('#messagesArea p.text-xs .fa-star').length;
            const $count = $('#starredCount');
            if ($count.length) $count.text(count);
        }




        function scrollToBottom() {
            var messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                requestAnimationFrame(() => {
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                });
            }
        }

        // Pusher (dev logging)
        Pusher.logToConsole = true;
        var pusher = new Pusher('1643ce51c4a3d4c535e9', {
            cluster: 'ap2'
        });
        var channel = pusher.subscribe('chat');

        channel.bind('my-event', function(data) {
            console.log('Received data:', data);
            const $contact = $(`.contact-item[sid="${data.sid}"]`);

            if ($contact.length) {
                $contact.find('.last-message-preview').text(
                    data.message.substring(0, 40) + (data.message.length > 40 ? '...' : '')
                );

                // ✅ Only increment unread count for incoming user messages
                if (data.sender === 'user') {
                    $contact.find('span.unread-badge').css('display', 'block');
                    $contact.find('span.unread-badge').text(function(i, oldText) {
                        return oldText === '' ? '1' : (parseInt(oldText) + 1).toString();
                    }).removeClass('hidden');
                }

                $contact.prependTo('#contactsList');
            }


            var contactImg = $('#user_profile').val();
            var sid = $('#chat-sid').val();
            var currenttime = new Date().toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit'
            });
            // Update last message in contact list
            updateLastMessage(data.sid, data.message);
            // Check if the received message is for the current conversation
            if (data.sid !== sid) return;

            var messageHtml = '';
            if (data.sender === 'user') {
                messageHtml = `
            <div class="flex items-end space-x-2">
                <img src="${contactImg}" alt="Contact" class="w-8 h-8 rounded-full flex-shrink-0" />
                <div class="relative wa-bg-message-in rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-in">
                    <p class="wa-text-primary text-sm break-words mr-3">${data.message}</p>
                    <p class="text-xs wa-text-secondary text-right mt-1">${currenttime}</p>
                </div>
            </div>`;
            } else {
                messageHtml = `
            <div class="flex items-end justify-end space-x-2">
                <div class="relative wa-bg-message-out rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-out">
                    <p class="wa-text-primary text-sm break-words whitespace-pre-wrap mr-3">${data.message}</p>
                    <p class="text-xs wa-text-secondary text-right mt-1">
                        ${currenttime}
                        <svg class="w-4 h-4 inline-block ml-1" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/></svg>
                    </p>
                </div>
            </div>`;
            }

            // Append the new message to the messages area
            $('#messagesArea').append(messageHtml);
            // Scroll to the bottom of the chat window
            scrollToBottom();
        });


        function updateLastMessage(sid, message) {
            console.log('Updating last message for SID:', sid, 'Message:', message);
            const $contact = $(`.contact-item[sid="${sid}"]`);
            if ($contact.length) {
                $contact.find('.last-message-preview').text(message.substring(0, 40) + (message.length > 40 ? '...' : ''));
                $contact.attr('data-last-message', message);
                // Move to top of list
                $contact.prependTo('#contactsList');
            } else {

                $.ajax({
                    url: "{{ url('contact/details') }}/" + sid,
                    type: 'GET',
                    success: function(response) {
                        console.log('Contact details response:', response);

                        var newContactHtml = `
                         <button
                                class="contact-item relative w-full text-left px-4 py-3  wa-hover wa-border transition-colors duration-150 p-2 rounded-2xl"
                                sid="${response.sid}" data-name="${response.contact}"
                                data-firstname="${response.first_name} ${response.last_name}"
                                data-last-message="${response.unread_message}"
                                data-last-time="${response.last_message}">
                                <div class="flex items-start space-x-3">
                                    <img src="{{ asset('assets/images/profile.png') }}" alt="${response.contact}"
                                        class="w-12 h-12 rounded-full object-cover flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between ">
                                            <h4 class="wa-text-primary truncate flex-1">
                                                ${response.first_name ? response.first_name + ' ' + response.last_name : response.contact}
                                            </h4>
                                            <span class="text-xs wa-text-secondary ml-2 whitespace-nowrap">
                                               ${new Date(response.last_message).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}
                                            </span>

                                        </div>
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm wa-text-secondary truncate flex-1 last-message-preview"
                                                title="${response.unread_message}">
                                                ${response.unread_message ? response.unread_message.substring(0, 40) + (response.unread_message.length > 40 ? '...' : '') : 'No messages yet'}
                                            </p>

                                            <span class="unread-badge ml-2 ">${response.unread_count}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Menu Trigger -->
                                <div
                                    class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition duration-150 chat-menu-trigger">
                                    <i class="fa fa-chevron-down text-gray-500 cursor-pointer"></i>
                                </div>

                                <!-- Dropdown -->
                                <div class="hidden absolute right-7 top-15 -translate-y-1/2 bg-white shadow-lg rounded-xl py-1 z-50 chat-menu-dropdown px-2 py-2"
                                    style="width: 45%;">
                                    <ul>
                                          <li class="px-4 py-2  hover:bg-gray-100 cursor-pointer text-md  block-chat-btn wa-text-primary rounded-lg"
                                            data-name="${conversation.name}" >
                                            <i class="fa fa-ban"></i>
                                            Block
                                        </li>
                                        <li
                                            class="px-4 py-2  hover:bg-gray-100 cursor-pointer text-md  delete-chat-btn wa-text-primary rounded-lg">
                                            <i class="fa fa-trash-o"></i> Delete chat
                                        </li>
                                    </ul>
                                </div>
                            </button>
                        `;

                        $('#contactsList').prepend(newContactHtml);
                    },
                    error: function() {
                        console.log('Error fetching contact details');
                    }
                });
            }
        }


        // delete chat
        // Toggle dropdown on click
        $(document).on('click', '.chat-menu-trigger', function(e) {
            e.stopPropagation();
            e.preventDefault();

            let $dropdown = $(this).siblings('.chat-menu-dropdown');

            if ($dropdown.is(':visible')) {
                // if it's already open, just close it
                $dropdown.hide();
            } else {
                // close all others, then open this one
                $('.chat-menu-dropdown').hide();
                $dropdown.show();
            }
        });

        // block chat
        $(document).on('click', '.block-chat-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();

            var $contact = $(this).closest('.contact-item');
            var sid = $contact.attr('sid');
            var contactName = $contact.data('name');
            var isBlocked = $(this).text().trim() === 'Unblock'; // Check current label

            if (!sid || !contactName) {
                sid = $('#chat-sid').val();
                contactName = $('#chatHeaderName').text().trim();
                isBlocked = 'Unblock';
            }

            Swal.fire({
                title: `${isBlocked ? 'Unblock' : 'Block'} chat with ${contactName}?`,
                text: isBlocked ? "They will be able to message you again." :
                    "They won't be able to message you.",
                showCancelButton: true,
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-2xl p-6',
                    title: 'text-lg font-semibold text-gray-800',
                    htmlContainer: 'text-sm text-gray-600',
                    confirmButton: 'swal2-delete-btn',
                    cancelButton: 'swal2-cancel-btn'
                },
                buttonsStyling: false,
                confirmButtonText: isBlocked ? 'Unblock' : 'Block',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('chat/block') }}/" + sid,
                        method: 'GET',
                        success: function(response) {
                            console.log(response);
                            if (response.success) {
                                // ✅ Update button text instantly
                                const $btn = $(`.contact-item[sid="${sid}"] .block-chat-btn`);
                                $btn.html(
                                    `<i class="fa fa-ban"></i> ${response.blocked ? 'Unblock' : 'Block'}`
                                );

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // ✅ Only update UI if the currently opened chat matches this SID
                                if ($('#chat-sid').val() === sid) {

                                    // ✅ If now blocked → show banner, hide composer
                                    if (response.blocked) {
                                        $('#chat-blocked-banner').removeClass('hidden');
                                        $('.chat-input').addClass('hidden');
                                    }

                                    // ✅ If now unblocked → show composer, hide banner
                                    else {
                                        $('#chat-blocked-banner').addClass('hidden');
                                        $('.chat-input').removeClass('hidden');
                                    }
                                }


                            } else {
                                Swal.fire('Error', response.error || 'Unknown error', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error updating block status!', 'error');
                        }
                    });
                }
            });
        });



        // Delete handler
        $(document).on('click', '.delete-chat-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();

            const $contact = $(this).closest('.contact-item');
            const sid = $contact.attr('sid');
            const contactName = $contact.data('name');

            Swal.fire({
                title: `Delete chat with ${contactName}?`,
                text: "Messages will be removed from all devices.",
                showCancelButton: true,
                reverseButtons: true, // puts Cancel on the left, Delete on the right
                customClass: {
                    popup: 'rounded-2xl p-6', // rounded corners, padding
                    title: 'text-lg font-semibold text-gray-800',
                    htmlContainer: 'text-sm text-gray-600',
                    confirmButton: 'swal2-delete-btn',
                    cancelButton: 'swal2-cancel-btn'
                },
                buttonsStyling: false, // we’ll apply custom classes instead
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('chat/delete') }}/" + sid,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                $(`.contact-item[sid="${sid}"]`).remove();

                                if ($('#chat-sid').val() === sid) {
                                    $('#messagesArea').empty();
                                    $('.chat-header, .chat-input').addClass('hidden');
                                    $('#chat-sid').val('');
                                    $('#chatHeaderName').text('Chat');
                                    $('#chatHeaderAvatar').attr('src', '');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: `Chat with ${contactName} has been deleted.`,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Error', response.error || 'Unknown error', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error deleting chat!', 'error');
                        }
                    });
                }
            });
        });



        $(document).ready(function() {

            var $app = $('#app');
            var $overlay = $('#overlay');

            function openChatMobile() {
                $app.addClass('is-chat-open');
            }

            function closeChatMobile() {
                $app.removeClass('is-chat-open');
            }


            $('body').on('click', '#menuButton', function() {
                if ($('#submenu').css('display') === 'none') {
                    $('#submenu').css('display', 'block');
                } else {
                    $('#submenu').css('display', 'none');

                }
            });

            // Pause Autoreply
            $('body').on('click', '#pause_autoreply', function(e) {
                e.preventDefault();
                var sid = $('#chat-sid').val();
                $.ajax({
                        url: "{{ url('autoreply/stop') }}/" + sid,
                        method: 'GET'
                    })
                    .done(function(response) {
                        if (response.success) {
                            $('#pause_autoreply').addClass('hidden');
                            $('#resume_autoreply').removeClass('hidden');
                        }
                    })
                    .fail(function() {
                        alert('Error while changing status!');
                    });
            });

            // Resume Autoreply
            $('body').on('click', '#resume_autoreply', function(e) {
                e.preventDefault();
                var sid = $('#chat-sid').val();
                $.ajax({
                        url: "{{ url('autoreply/resume') }}/" + sid,
                        method: 'GET'
                    })
                    .done(function(response) {
                        if (response.success) {
                            $('#pause_autoreply').removeClass('hidden');
                            $('#resume_autoreply').addClass('hidden');
                        }
                    })
                    .fail(function() {
                        alert('Error while changing status!');
                    });
            });

            // Search filter
            $('#searchInput').on('input', function() {
                const term = $(this).val().toLowerCase().trim();

                $('.contact-item').each(function() {
                    const name = ($(this).data('name') || '').toString().toLowerCase();
                    const firstName = ($(this).data('firstname') || '').toString().toLowerCase();
                    const lastMsg = ($(this).data('last-message') || '').toString().toLowerCase();

                    $(this).toggle(
                        name.includes(term) ||
                        firstName.includes(term) ||
                        lastMsg.includes(term)
                    );
                });

                if (!term) $('.contact-item').show();
            }).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $(this).val('');
                    $('.contact-item').show();
                }
            });


            // Contact selection
            $('.contact-item').on('click', function() {
                if ($('.chat-menu-trigger:hover').length) return; // ignore if menu trigger is clicked
                if ($('.delete-chat-btn:hover').length) return; // ignore if menu trigger is clicked
                if ($('.block-chat-btn:hover').length) return; // ignore if menu trigger is clicked

                $('.contact-item').removeClass('active');
                $(this).addClass('active');
                $(this).find('span.unread-badge').css('display', 'none');
                const contactName = $(this).data('firstname');
                const contactImg = $(this).find('img').attr('src');
                const sid = $(this).attr('sid');
                const contactNumber = $(this).data('name');


                $('#chat-sid').val(sid);

                // contact info box update
                $('#sidebarName').text(contactName);
                $('#sidebarNumber').text(contactNumber);

                $('#chatHeaderName').text(contactName);
                $('#chatHeaderAvatar').attr({
                    src: contactImg,
                    alt: contactName
                });

                $overlay.css('display', 'flex');
                $.ajax({
                        url: "{{ url('chat/messages') }}/" + sid,
                        method: 'GET'
                    })
                    .done(function(data) {
                        if (data.auto_reply == 1) {
                            $('#pause_autoreply').removeClass('hidden');
                            $('#resume_autoreply').addClass('hidden');
                        } else {
                            $('#pause_autoreply').addClass('hidden');
                            $('#resume_autoreply').removeClass('hidden');
                        }

                        if (data.blocked) {
                            $('.chat-input').addClass('hidden');
                            $('#chat-blocked-banner').removeClass('hidden');
                        } else {
                            $('#chat-blocked-banner').addClass('hidden');
                            $('.chat-input').removeClass('hidden');
                        }
                        $('.chat-header').removeClass('hidden');
                        const $messages = $('#messagesArea');
                        $messages.empty();
                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(function(message) {
                                let html = '';
                                if (message.author === 'system') {
                                    html = `
                                        <div class="flex items-end justify-end space-x-2">
                                            <div class="relative wa-bg-message-out rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-out">
                                                <p class="wa-text-primary text-sm break-words whitespace-pre-wrap mr-3">${message.body}</p>
                                                <p class="text-xs wa-text-secondary text-right mt-1">
                                                    ${message.is_starred ? '<i class="fa fa-star text-sm"></i>' : ''}
                                                    ${formatDate(message.date_created)}
                                                    <svg class="w-4 h-4 inline-block ml-1" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/></svg>
                                                </p>
                                                 <button data-messageid=${message.id} class="message-menu-btn absolute top-1 right-1 text-gray-500 opacity-0 hover:opacity-100">
                                                    <i class="fa fa-chevron-down"></i>
                                                  </button>
                                            </div>
                                        </div>`;
                                } else {
                                    if (message.has_images == true) {
                                        let images = JSON.parse(message.attachments || '[]');
                                        let imgsHtml = images.map(img => `
                                            <img src="${img.image}" class="max-w-[200px] rounded-lg mb-2 shadow-sm cursor-pointer" onclick="showImagePreview('${img.image}')" />
                                        `).join('');

                                        html = `
                                            <div class="flex items-end space-x-2">
                                                <img src="${contactImg}" alt="${contactName}" class="w-8 h-8 rounded-full flex-shrink-0" />
                                                <div class="relative wa-bg-message-in rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-in">
                                                    ${imgsHtml}
                                                    <p class="text-xs wa-text-secondary text-right mt-1">${formatDate(message.date_created)}</p>
                                                </div>
                                            </div>`;

                                    } else {

                                        html = `
                                            <div class="flex items-end space-x-2">
                                                <img src="${contactImg}" alt="${contactName}" class="w-8 h-8 rounded-full flex-shrink-0" />
                                                <div class="relative wa-bg-message-in rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-in">
                                                    <p class="wa-text-primary text-sm break-words mr-3">${message.body}</p>
                                                    <p class="text-xs wa-text-secondary text-right mt-1"> ${message.is_starred ? '<i class="fa fa-star text-sm"></i>' : ''}   ${formatDate(message.date_created)}</p>
                                                      <button data-messageid=${message.id} class="message-menu-btn absolute top-1 right-1 text-gray-500 opacity-0 hover:opacity-100">
                                                        <i class="fa fa-chevron-down"></i>
                                                      </button>
                                                </div>
                                            </div>`;
                                    }

                                }
                                $messages.append(html);
                            });
                        } else {
                            $('#messagesArea').append(
                                '<div class="flex items-center justify-center h-full"><p class="wa-text-secondary">No messages yet</p></div>'
                            );
                        }
                        $overlay.hide();
                        scrollToBottom();
                        openChatMobile();
                    })
                    .fail(function() {
                        alert('Error fetching messages!');
                        $overlay.hide();
                    });
            });

            // Close/Back to list
            $('#backToList, #closeChat').on('click', function() {
                closeChatMobile();
            });

            // Send message (Enter or button)
            function sendCurrentMessage() {
                const $input = $('#messageInput');
                const message = $input.val().trim();
                if (!message) return;
                const currenttime = new Date().toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit'
                });
                const html = `
                    <div class="flex items-end justify-end space-x-2">
                        <div class="relative wa-bg-message-out rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-out">
                            <p class="wa-text-primary text-sm break-words ">${message}</p>
                            <p class="text-xs wa-text-secondary text-right mt-1">
                                ${currenttime}
                                <svg class="w-4 h-4 inline-block ml-1" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512z"/></svg>
                            </p>
                        </div>
                    </div>`;

                if ($('#messagesArea .flex.items-center.justify-center').length > 0) $('#messagesArea').html('');

                $.ajax({
                    url: "{{ url('send-message') }}",
                    method: 'POST',
                    data: {
                        message: message,
                        sid: $('#chat-sid').val(),
                        _token: '{{ csrf_token() }}'
                    }
                }).fail(function(xhr) {
                    console.error(xhr.responseText);
                });

                $('#messagesArea').append(html);

                // Update last message in contact list
                updateLastMessage($('#chat-sid').val(), message);
                scrollToBottom();
                $input.val('');



                // Keep keyboard open (re-focus input after send)
                requestAnimationFrame(() => {
                    $input.focus({
                        preventScroll: true
                    });
                    const el = $input[0];
                    if (el && el.setSelectionRange) {
                        try {
                            el.setSelectionRange(el.value.length, el.value.length);
                        } catch (e) {}
                    }
                });

            }

            $('#messageInput').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // form submit/blur stop
                    sendCurrentMessage();
                }
            });


            // $('#messageInput').on('keypress', function(e) {
            //     if (e.which === 13) sendCurrentMessage();
            // });
            $('#sendBtn').on('click', sendCurrentMessage);

            function formatDate(dateStr) {
                const date = new Date(dateStr);
                let hours = date.getHours();
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                return `${hours}:${minutes} ${ampm}`;
            }


            // search in chat
            // ===== Responsive In-Chat Search =====
            (function() {
                const isDesktop = () => window.matchMedia('(min-width: 768px)').matches;

                function closeAllSearchUIs() {
                    // Desktop
                    $('#chatSearchInputDesktop').val('').addClass('hidden');
                    $('#chatSearchCloseDesktop').addClass('hidden');
                    $('#chatSearchResultsDesktop').addClass('hidden').empty();

                    // Mobile
                    $('#chatSearchBarMobile').addClass('hidden');
                    $('#chatSearchInputMobile').val('');
                    $('#chatSearchResultsMobile').addClass('hidden').empty();
                }

                // Toggle search on button click
                // $(document).on('click', '#chatSearchBtn', function() {
                //     if (isDesktop()) {
                //         // Desktop: inline input
                //         $('#chatSearchInputDesktop').toggleClass('hidden').focus();
                //         $('#chatSearchCloseDesktop').toggleClass('hidden');
                //         $('#chatSearchResultsDesktop').addClass('hidden').empty();
                //     } else {
                //         // Mobile: full width bar under header
                //         $('#chatSearchBarMobile').toggleClass('hidden');
                //         if (!$('#chatSearchBarMobile').hasClass('hidden')) {
                //             $('#chatSearchInputMobile').val('').focus();
                //             $('#chatSearchResultsMobile').addClass('hidden').empty();
                //         }
                //     }
                // });

                // Toggle search on button click
                // $(document).on('click', '#chatSearchBtn2', function() {
                //     if (isDesktop()) {
                //         // Desktop: inline input
                //         $('#chatSearchInputDesktop').toggleClass('hidden').focus();
                //         $('#chatSearchCloseDesktop').toggleClass('hidden');
                //         $('#chatSearchResultsDesktop').addClass('hidden').empty();
                //     } else {
                //         // Mobile: full width bar under header
                //         $('#chatSearchBarMobile').toggleClass('hidden');
                //         if (!$('#chatSearchBarMobile').hasClass('hidden')) {
                //             $('#chatSearchInputMobile').val('').focus();
                //             $('#chatSearchResultsMobile').addClass('hidden').empty();
                //         }
                //         closeContactInfo();
                //     }

                // });

                $(document).on('click', '#startmessage', function() {
                    closeContactInfo();
                })

                // Close buttons
                $(document).on('click', '#chatSearchCloseDesktop', function() {
                    $('#chatSearchInputDesktop').addClass('hidden').val('');
                    $('#chatSearchCloseDesktop').addClass('hidden');
                    $('#chatSearchResultsDesktop').addClass('hidden').empty();
                });
                $(document).on('click', '#chatSearchCloseMobile', function() {
                    $('#chatSearchBarMobile').addClass('hidden');
                    $('#chatSearchInputMobile').val('');
                    $('#chatSearchResultsMobile').addClass('hidden').empty();
                });

                // Helpers
                function escapeHtml(s) {
                    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }

                function escapeRegExp(s) {
                    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }

                function buildMatches(query) {
                    const results = [];
                    $('#messagesArea .wa-text-primary').each(function(i, el) {
                        const full = $(el).text();
                        const lower = full.toLowerCase();
                        const idx = lower.indexOf(query);
                        if (idx !== -1) {
                            // Center snippet around first match
                            const start = Math.max(0, idx - 30);
                            const end = Math.min(full.length, idx + query.length + 50);
                            const snippetRaw = full.slice(start, end);
                            results.push({
                                index: i,
                                full,
                                snippetRaw
                            });
                        }
                    });
                    return results;
                }

                function renderResults(query, $target) {
                    $target.empty();
                    if (!query) {
                        $target.addClass('hidden');
                        return;
                    }

                    const q = query.toLowerCase();
                    const matches = buildMatches(q);

                    if (!matches.length) {
                        $target.html('<p class="text-gray-500 text-sm p-3">No matches found</p>').removeClass(
                            'hidden');
                        return;
                    }

                    const rx = new RegExp(escapeRegExp(query), 'ig');

                    matches.forEach(m => {
                        const esc = escapeHtml(m.snippetRaw);
                        const highlighted = esc.replace(rx, (match) =>
                            `<span class="bg-yellow-100">${escapeHtml(match)}</span>`);
                        $target.append(`
                            <div class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer" data-index="${m.index}">
                            ${highlighted}
                            </div>
                        `);
                    });

                    $target.removeClass('hidden');
                }

                // Input handlers (desktop & mobile)
                $(document).on('input', '#chatSearchInputDesktop', function() {
                    renderResults($(this).val().trim(), $('#chatSearchResultsDesktop'));
                });
                $(document).on('input', '#chatSearchInputMobile', function() {
                    renderResults($(this).val().trim(), $('#chatSearchResultsMobile'));
                });

                // Click a suggestion (works for both result boxes)
                $(document).on('click',
                    '#chatSearchResultsDesktop div[data-index], #chatSearchResultsMobile div[data-index]',
                    function() {
                        const index = $(this).data('index');
                        const $message = $('#messagesArea .wa-text-primary').eq(index);
                        const $bubble = $message.closest('.relative'); // bubble wrapper
                        const $container = $('#messagesArea');

                        // Scroll into view
                        $container.animate({
                            scrollTop: $message.parent().offset().top - $container.offset().top +
                                $container.scrollTop() - 100
                        }, 400);

                        // Remove prior overlays
                        $('#messagesArea .highlight-overlay').remove();
                        $bubble.addClass('overflow-hidden');

                        // Add temporary gray shade overlay (Tailwind utilities only)
                        const $overlay = $(
                            '<span class="highlight-overlay absolute inset-0 rounded-lg bg-gray-300 bg-opacity-60 pointer-events-none"></span>'
                        );
                        $bubble.append($overlay);
                        setTimeout(() => {
                            $overlay.fadeOut(150, function() {
                                $(this).remove();
                            });
                        }, 1200);

                        // Hide result boxes
                        $('#chatSearchResultsDesktop, #chatSearchResultsMobile').addClass('hidden').empty();
                    });

                // Hide results when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#chatSearchAreaDesktop, #chatSearchBarMobile').length) {
                        $('#chatSearchResultsDesktop, #chatSearchResultsMobile').addClass('hidden');
                    }
                });

                // On resize, close UIs to avoid layout glitches
                // $(window).on('resize', closeAllSearchUIs);






            })();

            (function() {
                let lastVVH = (window.visualViewport && visualViewport.height) || window.innerHeight;
                $(window).off('resize.searchui');

                $(window).on('resize.searchui', function() {
                    const ae = document.activeElement;
                    const isSearchFocused = ae && (
                        ae.id === 'chatSearchInputMobile' ||
                        ae.id === 'chatSearchInputDesktop'
                    );

                    const vv = window.visualViewport;
                    const cur = (vv && vv.height) || window.innerHeight;
                    const delta = Math.abs(cur - lastVVH);
                    const keyboardLikely = delta > 50;

                    if (keyboardLikely && isSearchFocused) {
                        lastVVH = cur;
                        return;
                    }

                    if (typeof window.closeAllSearchUIs === 'function') {
                        window.closeAllSearchUIs();
                    }
                    lastVVH = cur;
                });
            })();




            (function() {
                let lastVVH = (window.visualViewport && visualViewport.height) || window.innerHeight;

                // remove old binding if kisi aur jaga laga ho
                $(window).off('resize', closeAllSearchUIs);

                $(window).on('resize', function() {
                    const ae = document.activeElement;
                    const isSearchFocused = ae && (ae.id === 'chatSearchInputMobile' || ae.id ===
                        'chatSearchInputDesktop');

                    const vv = window.visualViewport;
                    const cur = (vv && vv.height) || window.innerHeight;
                    const delta = Math.abs(cur - lastVVH);
                    const keyboardLikely = delta > 50; // ~keyboard threshold

                    // Agar keyboard ki wajah se resize hua & search input focused hai -> close mat karo
                    if (keyboardLikely && isSearchFocused) {
                        lastVVH = cur;
                        return;
                    }

                    closeAllSearchUIs();
                    lastVVH = cur;
                });
            })();

            let selectedMessageText = "";
            let selectedMessageBubble = null;

            $(document).on('click', '.message-menu-btn', function(e) {
                e.stopPropagation();

                const menu = $('#messageActionMenu');
                const isSameTarget = selectedMessageBubble && selectedMessageBubble.is($(this).closest(
                    '.relative'));
                const isVisible = !menu.hasClass('hidden');

                // If clicking same chevron while menu is open → close it
                if (isVisible && isSameTarget) {
                    menu.addClass('hidden');
                    return;
                }

                // Otherwise, proceed with opening logic
                selectedMessageBubble = $(this).closest('.relative');
                selectedMessageText = selectedMessageBubble.find('.wa-text-primary').text();

                const btnOffset = $(this).offset();
                const menuWidth = menu.outerWidth();
                const menuHeight = menu.outerHeight();
                const viewportWidth = $(window).width();
                const viewportHeight = $(window).height();

                if (viewportWidth < 768) {
                    // 📱 Mobile - bottom centered
                    menu.css({
                        left: '50%',
                        top: viewportHeight - menuHeight - 20 + 'px',
                        transform: 'translateX(-50%)'
                    }).removeClass('hidden');
                } else {
                    // 💻 Desktop - smart positioning
                    let left = btnOffset.left + 20;
                    let top = btnOffset.top + 20;

                    if (left + menuWidth > viewportWidth) left = btnOffset.left - menuWidth - 10;
                    if (top + menuHeight > viewportHeight) top = btnOffset.top - menuHeight - 10;

                    menu.css({
                        top: top + 'px',
                        left: left + 'px',
                        transform: 'none'
                    }).removeClass('hidden');
                }
            });



            $(document).on('click', '.menu-item', function() {
                const action = $(this).data('action');
                $('#messageActionMenu').addClass('hidden');

                if (action === 'copy') {
                    navigator.clipboard.writeText(selectedMessageText);
                    toastr.success("Message Copied!");
                } else if (action === 'star') {
                    const messageId = selectedMessageBubble.find('.message-menu-btn').data('messageid');
                    $.ajax({
                        url: "{{ url('message/star') }}/" + messageId,
                        type: "GET",
                        success: function(response) {

                            const timestampLine = selectedMessageBubble.find('p.text-xs');
                            const existingStar = timestampLine.find('.fa-star');

                            if (existingStar.length) {
                                // Star exists → remove it
                                existingStar.remove();
                            } else {
                                // Star doesn't exist → add it
                                timestampLine.prepend(
                                    '<i class="fa fa-star text-sm mr-1"></i> ');
                            }

                            refreshStarredCountFromDOM();
                        },
                        error: function(error) {

                        }
                    });
                } else if (action === 'delete') {
                    selectedMessageBubble.closest('.flex').remove();
                }
                // you can expand reply/pin/react...
            });

            $(document).on('click', function() {
                $('#messageActionMenu').addClass('hidden');
                $('.chat-menu-dropdown').hide();
            });


        });

        // --- Helpers to open/close side drawers ---

        function openStarred() {
            $('#starredSidebar').removeClass('translate-x-full');
            $('#chat-header, #messagesArea, #chat-composer, #chat-blocked-banner').addClass('md:w-2/3');
        }

        function closeStarred() {
            $('#starredSidebar').addClass('translate-x-full');
            $('#chat-header, #messagesArea, #chat-composer, #chat-blocked-banner').removeClass('md:w-2/3');
        }

        // Close starred on X
        $(document).on('click', '#closeStarred', function() {
            closeStarred();
        });

        // Back to Contact Info
        $(document).on('click', '#backToInfo', function() {
            closeStarred();
            openContactInfo();
        });

        // Open starred from Contact Info (closes Contact Info first)
        $(document).on('click', '#openStarredBtn', function() {
            $('#messageActionMenu').addClass('hidden'); // ensure the bubble menu is closed
            closeContactInfo();
            loadStarredMessages().then(() => {
                openStarred();
            });
        });

        // --- Fetch & render starred messages ---
        // Try backend first; if not available, fall back to DOM scan.
        async function loadStarredMessages() {
            const sid = $('#chat-sid').val();
            const $list = $('#starredList');
            $list.empty().append(loader());

            try {
                const data = await $.ajax({
                    url: "{{ url('message/starred') }}/" + sid, // expects [{id, body, date_created, author}]
                    method: "GET"
                });

                if (Array.isArray(data) && data.length) {
                    renderStarred(data);
                    $('#starredCount').text(data.length);
                    return;
                }
                // fallback if empty array or unexpected
                const local = collectStarredFromDOM();
                renderStarred(local);
                $('#starredCount').text(local.length);
            } catch (e) {
                // fallback to DOM if API not present
                const local = collectStarredFromDOM();
                renderStarred(local);
                $('#starredCount').text(local.length);
            }
        }

        function loader() {
            return $('<div class="p-4 text-sm text-gray-500">Loading…</div>');
        }

        // Build list UI
        function renderStarred(items) {
            const $list = $('#starredList');
            $list.empty();

            if (!items.length) {
                $list.append('<div class="p-6 text-gray-500 text-sm">No starred messages yet.</div>');
                return;
            }

            const contactName = getCurrentContactName();
            const chatTitle = contactName; // adjust if you show group titles elsewhere
            const youAvatar = $('#user_profile').val() || $('#chatHeaderAvatar').attr('src') || '';
            const contactAvatar = $('#chatHeaderAvatar').attr('src') || $('#user_profile').val() || '';

            items.forEach(m => {
                const safeBody = escapeHtml(m.body || '');
                const ts = m.date_created || new Date().toISOString();

                // Infer sender name (prefer server-provided)
                const isIncoming = (m.author && String(m.author).toLowerCase() !== 'system'); // 'system' = you
                const senderName = m.author_name || (isIncoming ? contactName : 'You');
                const avatar = isIncoming ? contactAvatar : youAvatar;

                // Use your existing bubble styles (colors + tails)
                const bubbleBase =
                    isIncoming ?
                    'wa-bg-message-in message-tail-in' :
                    'wa-bg-message-out message-tail-out';

                const row = $(`
                    <div class="px-3 py-4 border-b">
                        <!-- Header: avatar + sender ▸ chat + date/time (top-right) -->
                        <div class="flex items-center justify-between text-xs text-gray-600 mb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <img src="${avatar}" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                            <div class="truncate">
                                <span class="font-medium text-gray-800">${escapeHtml(senderName)}</span>
                                <span class="mx-1 text-gray-400">▸</span>
                                <span class="text-gray-600 truncate">${escapeHtml(chatTitle)}</span>
                            </div>
                        </div>
                        <span class="whitespace-nowrap text-gray-500">${formatDateHeader(ts)}</span>
                        </div>

                        <!-- Bubble -->
                        <div class="relative ${bubbleBase} rounded-lg px-3 py-4 max-w-[85%] shadow-sm" style="max-width:85%;">
                           <div class="wa-text-primary text-sm whitespace-pre-line">${safeBody}</div>

                            <!-- Bottom-right star + time -->
                            <div class="absolute -mb-1 -mr-1 right-2 bottom-1 flex items-center gap-1 text-xs wa-text-secondary bg-transparent">
                                <i class="fa fa-star text-xs"></i>
                                <span>${formatTimeOnly(ts)}</span>
                            </div>
                        </div>
                    </div>
                    `);

                // Click → jump to message
                row.on('click', function() {
                    closeStarred();
                    jumpToMessageInChat(m.id);
                });

                $list.append(row);
            });
        }



        // Fallback: scan current chat DOM for starred messages
        function collectStarredFromDOM() {
            const items = [];
            $('#messagesArea .fa-star').each(function() {
                const bubble = $(this).closest('.relative');
                const body = bubble.find('.wa-text-primary').text();
                const timeNode = bubble.find('p.text-xs').clone();
                timeNode.find('.fa-star').remove();
                const msgId = bubble.find('.message-menu-btn').data('messageid');

                // Infer author from alignment: outgoing bubbles are in a "justify-end" wrapper
                const wrapper = bubble.closest('.flex');
                const isOutgoing = wrapper.hasClass('justify-end');

                items.push({
                    id: msgId || null,
                    body: body,
                    date_created: new Date().toISOString(), // or parse if you store timestamp text
                    author: isOutgoing ? 'system' : 'user' // 'system' = you; 'user' = contact
                    // author_name (optional) can be added here if you have it
                });
            });
            return items;
        }


        // Try to parse "5:37 PM" into an ISO-ish string; fallback to now
        function parseTimeFromText(t) {
            return new Date().toISOString();
        }

        // Escape helpers
        function escapeHtml(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Reuse your existing formatDate(dateStr)
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${hours}:${minutes} ${ampm}`;
        }

        // Scroll to a starred message in the chat and highlight it
        function jumpToMessageInChat(messageId) {
            if (!messageId) return;
            const targetBtn = $(`.message-menu-btn[data-messageid="${messageId}"]`);
            if (!targetBtn.length) return;

            const bubble = targetBtn.closest('.relative');
            const $container = $('#messagesArea');

            $container.animate({
                scrollTop: bubble.offset().top - $container.offset().top + $container.scrollTop() - 100
            }, 300);

            // Temporary highlight
            const overlay = $(
                '<span class="absolute inset-0 rounded-lg bg-gray-300 bg-opacity-40 pointer-events-none"></span>');
            bubble.addClass('overflow-hidden').append(overlay);
            setTimeout(() => overlay.fadeOut(150, () => overlay.remove()), 800);
        }

        // Keep the count in sync when user toggles star from the message menu
        function updateStarCountDelta(delta) {
            const $count = $('#starredCount');
            const current = parseInt($count.text() || '0', 10) || 0;
            $count.text(Math.max(0, current + delta));
        }

        function getCurrentContactName() {
            // try chat header first, then the contact sidebar
            return ($('#chatHeaderName').text() || $('#sidebarName').text() || 'Contact').trim();
        }

        function formatDateHeader(dateStr) {
            const d = new Date(dateStr);
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            const time = formatTimeOnly(dateStr);
            return `${dd}/${mm}/${yyyy}`;
        }

        function formatTimeOnly(dateStr) {
            const d = new Date(dateStr);
            let h = d.getHours();
            const m = String(d.getMinutes()).padStart(2, '0');
            const a = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${m} ${a}`;
        }
    </script>




    <script>
        (function() {
            const root = document.documentElement;
            const messagesArea = document.getElementById('messagesArea');
            const composer = document.getElementById('chat-composer');
            const chatHeader = document.getElementById('chat-header');
            let resizeTimer;
            let lastHeight = window.innerHeight;

            function updateLayout() {
                const vv = window.visualViewport;
                if (!vv) {
                    return;
                }

                // Calculate keyboard height
                const kb = Math.max(0, window.innerHeight - (vv.height + vv.offsetTop));
                root.style.setProperty('--kb', kb + 'px');

                // Detect if keyboard is open (threshold for mobile browser UI changes)
                const isKeyboardOpen = kb > 100;

                if (isKeyboardOpen) {
                    document.body.classList.add('kb-open');

                    // Adjust messages area bottom padding to account for keyboard
                    if (messagesArea) {
                        const composerHeight = composer?.offsetHeight || 64;
                        messagesArea.style.paddingBottom = `${composerHeight + kb}px`;
                    }

                    // Keep messages scrolled to bottom
                    requestAnimationFrame(() => {
                        if (messagesArea) {
                            messagesArea.scrollTop = messagesArea.scrollHeight;
                        }
                    });
                } else {
                    document.body.classList.remove('kb-open');

                    // Reset to default padding
                    if (messagesArea) {
                        const composerHeight = composer?.offsetHeight || 64;
                        const headerHeight = chatHeader?.offsetHeight || 72;
                        messagesArea.style.paddingBottom = `${composerHeight + 16}px`;
                        messagesArea.style.paddingTop = `${headerHeight + 16}px`;
                    }
                }

                lastHeight = window.innerHeight;
            }

            function debouncedUpdate() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(updateLayout, 50);
            }

            // Initialize on load
            window.addEventListener('load', () => {
                setTimeout(updateLayout, 100);
            });

            // Handle orientation change
            window.addEventListener('orientationchange', () => {
                setTimeout(updateLayout, 300);
            });

            // Handle viewport resize (keyboard, browser UI)
            window.addEventListener('resize', debouncedUpdate);

            // Visual Viewport API for precise keyboard detection
            if (window.visualViewport) {
                visualViewport.addEventListener('resize', debouncedUpdate);
                visualViewport.addEventListener('scroll', updateLayout);
            }

            // Input focus handlers
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('focus', () => {
                    setTimeout(() => {
                        updateLayout();
                        // Scroll input into view
                        if (messagesArea) {
                            messagesArea.scrollTop = messagesArea.scrollHeight;
                        }
                    }, 350);
                });

                messageInput.addEventListener('blur', () => {
                    setTimeout(updateLayout, 150);
                });
            }

            // Prevent elastic scrolling on iOS
            //   document.addEventListener('touchmove', function(e) {
            //     if (e.target === document.body || e.target === document.documentElement) {
            //       e.preventDefault();
            //     }
            //   }, { passive: false });

            // ✅ new: sirf non-scroll areas me prevent, inputs ko allow
            document.addEventListener('touchmove', function(e) {
                const tag = (e.target.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea') return;

                const withinScrollable = e.target.closest(
                    '#messagesArea, #contactsList, #contactSidebar, #starredList, #chatSearchBarMobile');
                if (!withinScrollable) {
                    e.preventDefault();
                }
            }, {
                passive: false
            });


        })();
    </script>

    <script>
        // Additional initialization and mobile optimizations
        (function() {
            'use strict';

            // Prevent address bar from affecting layout
            function lockViewport() {
                const metaViewport = document.querySelector('meta[name="viewport"]');
                if (metaViewport) {
                    metaViewport.setAttribute('content',
                        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover, height=' +
                        window.innerHeight
                    );
                }
            }

            // Force initial layout calculation
            function initializeLayout() {
                const messagesArea = document.getElementById('messagesArea');
                const chatHeader = document.getElementById('chat-header');
                const composer = document.getElementById('chat-composer');

                if (messagesArea && chatHeader && composer) {
                    const headerHeight = chatHeader.offsetHeight || 72;
                    const composerHeight = composer.offsetHeight || 64;

                    messagesArea.style.paddingTop = `${headerHeight + 16}px`;
                    messagesArea.style.paddingBottom = `${composerHeight + 16}px`;
                }
            }

            // Handle Android Chrome bottom bar
            function handleAndroidChrome() {
                if (/Android/i.test(navigator.userAgent)) {
                    // Force layout recalculation on Android
                    window.addEventListener('resize', function() {
                        const vh = window.innerHeight * 0.01;
                        document.documentElement.style.setProperty('--vh', `${vh}px`);
                    });

                    // Initial setting
                    const vh = window.innerHeight * 0.01;
                    document.documentElement.style.setProperty('--vh', `${vh}px`);
                }
            }

            // Prevent pull-to-refresh
            let touchStartY = 0;
            document.addEventListener('touchstart', function(e) {
                touchStartY = e.touches[0].clientY;
            }, {
                passive: true
            });

            document.addEventListener('touchmove', function(e) {
                const tag = (e.target.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea') return; // allow typing gestures

                const touchY = e.touches[0].clientY;
                const touchDiff = touchY - touchStartY;
                const scrollableElement = e.target.closest(
                    '#messagesArea, #contactsList, #contactSidebar, #starredList, #chatSearchBarMobile');
                if (scrollableElement && scrollableElement.scrollTop === 0 && touchDiff > 0) {
                    e.preventDefault();
                }
            }, {
                passive: false
            });


            // Initialize everything
            document.addEventListener('DOMContentLoaded', function() {
                lockViewport();
                initializeLayout();
                handleAndroidChrome();

                // Re-initialize after a short delay to ensure all elements are rendered
                setTimeout(initializeLayout, 500);
            });

            // Handle page visibility changes
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    setTimeout(initializeLayout, 100);
                }
            });

            // iOS standalone mode optimizations
            if (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches) {
                document.documentElement.classList.add('standalone-mode');
            }

        })();
    </script>




    <script>
        (function() {
            let toggleLock = false;

            function safeToggle(fn) {
                if (toggleLock) return;
                toggleLock = true;
                try {
                    fn();
                } finally {
                    setTimeout(() => toggleLock = false, 220);
                }
            }

            function recalcPadding() {
                const header = document.getElementById('chat-header');
                const composer = document.getElementById('chat-composer');
                const messages = document.getElementById('messagesArea');
                if (!header || !composer || !messages) return;
                messages.style.paddingTop = ((header.offsetHeight || 72) + 16) + 'px';
                messages.style.paddingBottom = ((composer.offsetHeight || 64) + 16) + 'px';
            }

            function focusSearchInput() {
                const $inp = $('#chatSearchInputMobile');
                $inp.val('');
                // double-RAF to ensure element is visible before focus (iOS safe)
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        // IMPORTANT: no preventDefault on the triggering event
                        $inp.focus({
                            preventScroll: true
                        });
                        const el = $inp[0];
                        if (el && el.setSelectionRange) {
                            try {
                                el.setSelectionRange(el.value.length, el.value.length);
                            } catch (e) {}
                        }
                        // make sure it’s onscreen
                        el?.scrollIntoView({
                            block: 'center',
                            inline: 'nearest'
                        });
                        recalcPadding();
                    });
                });
            }

            function openMobileSearch() {
                $('#chatSearchBarMobile').removeClass('hidden');
                focusSearchInput();
            }

            function closeMobileSearch() {
                $('#chatSearchBarMobile').addClass('hidden');
                setTimeout(recalcPadding, 20);
            }

            // 🔁 SINGLE handler: pointerdown (fast + reliable)
            //   $(document).on('pointerdown', '#chatSearchBtn', function(e){
            //     // DO NOT call preventDefault here; it blocks keyboard on iOS
            //     e.stopPropagation();
            //     safeToggle(()=>{
            //       const hidden = $('#chatSearchBarMobile').hasClass('hidden');
            //       hidden ? openMobileSearch() : closeMobileSearch();
            //     });
            //   });

            $(document).off('pointerdown', '#chatSearchBtn'); // remove old
            $(document).on('pointerdown', '#chatSearchBtn', function(e) {
                e.stopPropagation();
                const isDesktop = window.matchMedia('(min-width: 768px)').matches;

                if (isDesktop) {
                    // Desktop inline search toggle
                    const $inp = $('#chatSearchInputDesktop');
                    const $close = $('#chatSearchCloseDesktop');
                    const $res = $('#chatSearchResultsDesktop');
                    const willOpen = $inp.hasClass('hidden');

                    if (willOpen) {
                        $('#chatSearchAreaDesktop').removeClass('hidden');
                        $inp.removeClass('hidden').val('').focus();
                        $close.removeClass('hidden');
                        $res.addClass('hidden').empty();
                    } else {
                        $inp.addClass('hidden').val('');
                        $close.addClass('hidden');
                        $res.addClass('hidden').empty();
                    }
                } else {
                    // Mobile full-width search
                    const hidden = $('#chatSearchBarMobile').hasClass('hidden');
                    if (hidden) {
                        $('#chatSearchBarMobile').removeClass('hidden');
                        requestAnimationFrame(() => {
                            const $m = $('#chatSearchInputMobile');
                            $m.val('').focus({
                                preventScroll: true
                            });
                            const el = $m[0];
                            try {
                                el && el.setSelectionRange(el.value.length, el.value.length);
                            } catch (_) {}
                        });
                    } else {
                        $('#chatSearchBarMobile').addClass('hidden');
                    }
                }
            });


            // Sidebar search button: always open (+ close contact info)
            $(document).on('pointerdown', '#chatSearchBtn2', function(e) {
                e.stopPropagation();
                safeToggle(() => {
                    try {
                        closeContactInfo && closeContactInfo();
                    } catch (_) {}
                    openMobileSearch();
                    setTimeout(() => $(window).trigger('resize'), 50);
                });
            });
        })();


        // Prevent button from stealing focus (keyboard open rahe)
        $(document).on('mousedown touchstart pointerdown', '#sendBtn', function(e) {
            e.preventDefault();
        });



        // show image inside the chat
        function showImagePreview(url) {
            document.getElementById('previewImage').src = url;
            document.getElementById('downloadImageBtn').href = url; // ✅ Enables downloading
            document.getElementById('imagePreviewModal').classList.remove('hidden');
        }

        function closeImagePreview() {
            document.getElementById('imagePreviewModal').classList.add('hidden');
        }
    </script>




</body>

</html>
