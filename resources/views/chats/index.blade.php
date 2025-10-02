<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chat Application — WhatsApp-like</title>

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
        /* === WhatsApp-like Light Theme === */
        body {
            background: #f5f7fb;
            color: #111b21;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .scrollbar-hide {
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
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
            border: 1px solid #e6e6e6;
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
            filter: drop-shadow(-1px 0 0 #e6e6e6);
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

        /* Mobile view state */
        @media (max-width: 767px) {
            #contactsPane {
                display: block;
            }

            #chatPane {
                display: none;
            }

            #app.is-chat-open #contactsPane {
                display: none;
            }

            #app.is-chat-open #chatPane {
                display: flex;
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
            /* Adjust to position relative to the button */
            left: 50;
            background-color: #ffffff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
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
                                <a href="{{ route('admin.logout') }}">
                                    <li class="hover:bg-gray-200 rounded-lg"><i class="fa fa-sign-out "></i> Log out</li>
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
                        data-last-message="{{ $conversation->last_message_body ?? '' }}"
                        data-last-time="{{ $conversation->last_message }}">
                        <div class="flex items-start space-x-3">
                            <img src="{{ asset('assets/images/profile.png') }}" alt="{{ $conversation->contact }}"
                                class="w-12 h-12 rounded-full object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between ">
                                    <h4 class="wa-text-primary truncate flex-1">{{ $conversation->contact }}</h4>
                                    <span class="text-xs wa-text-secondary ml-2 whitespace-nowrap">
                                        {{ Carbon\Carbon::parse($conversation->last_message)->format('g:i A') }}
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
                                <li class="px-4 py-2  hover:bg-gray-100 cursor-pointer text-md  delete-chat-btn wa-text-primary rounded-lg">
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
        <main id="chatPane" class="flex-1 flex flex-col md:flex">
            <!-- Chat Header -->
            <div class="chat-header hidden  py-4 shadow-lg">
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
                            class="w-10 h-10 rounded-full object-cover flex-shrink-0" alt="Avatar" />
                        <div class="flex-1 min-w-0">
                            <h3 id="chatHeaderName" class="font-semibold wa-text-primary truncate">Chat</h3>
                            <p class="text-xs wa-text-secondary">online</p>
                            <input type="hidden" id="chat-sid" />
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button class="p-2 rounded-full wa-hover" title="Search">
                            <svg class="w-5 h-5 wa-text-secondary" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                        <button class="p-2 rounded-full wa-hover" title="Menu">
                            <svg class="w-5 h-5 wa-text-secondary" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4zm0 7a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div id="messagesArea"
                class="flex-1 overflow-y-auto p-4 md:p-6 space-y-3 scrollbar-hide custom-scrollbar chat-bg-pattern">
            </div>

            <!-- Composer -->
            <div class="chat-input hidden  p-3" style="background: #F5F1EB;">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        function scrollToBottom() {
            var messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
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
                $contact.find('.last-message-preview').text(data.message.substring(0, 40) + (data.message.length >
                    40 ? '...' : ''));
                $contact.find('span.unread-badge').css('display', 'block');
                $contact.find('span.unread-badge').text(function(i, oldText) {
                    return oldText === '' ? '1' : (parseInt(oldText) + 1).toString();
                }).removeClass('hidden');
                $contact.prependTo('#contactsList');
            }

            var contactImg = $('#user_profile').val();
            var sid = $('#chat-sid').val();
            var currenttime = new Date().toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit'
            });

            // Check if the received message is for the current conversation
            if (data.sid !== sid) return;

            var messageHtml = '';
            if (data.sender === 'user') {
                messageHtml = `
            <div class="flex items-end space-x-2">
                <img src="${contactImg}" alt="Contact" class="w-8 h-8 rounded-full flex-shrink-0" />
                <div class="relative wa-bg-message-in rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-in">
                    <p class="wa-text-primary text-sm break-words">${data.message}</p>
                    <p class="text-xs wa-text-secondary text-right mt-1">${currenttime}</p>
                </div>
            </div>`;
            } else {
                messageHtml = `
            <div class="flex items-end justify-end space-x-2">
                <div class="relative wa-bg-message-out rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-out">
                    <p class="wa-text-primary text-sm break-words whitespace-pre-wrap">${data.message}</p>
                    <p class="text-xs wa-text-secondary text-right mt-1">
                        ${currenttime}
                        <svg class="w-4 h-4 inline-block ml-1" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/></svg>
                    </p>
                </div>
            </div>`;
            }

            // Append the new message to the messages area
            $('#messagesArea').append(messageHtml);

            // Update last message in contact list
            updateLastMessage(data.sid, data.message);

            // Move the contact-item whose sid matches the received message's sid to the top of the contact list


            // Scroll to the bottom of the chat window
            scrollToBottom();
        });


        function updateLastMessage(sid, message) {
            const $contact = $(`.contact-item[sid="${sid}"]`);
            if ($contact.length) {
                $contact.find('.last-message-preview').text(message.substring(0, 40) + (message.length > 40 ? '...' : ''));
                $contact.attr('data-last-message', message);
                // Move to top of list
                $contact.prependTo('#contactsList');
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
                const term = $(this).val().toLowerCase();
                $('.contact-item').each(function() {
                    const name = $(this).data('name').toLowerCase();
                    const lastMsg = $(this).data('last-message').toLowerCase();
                    $(this).toggle(name.includes(term) || lastMsg.includes(term));
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

                $('.contact-item').removeClass('active');
                $(this).addClass('active');
                $(this).find('span.unread-badge').css('display', 'none');
                const contactName = $(this).data('name');
                const contactImg = $(this).find('img').attr('src');
                const sid = $(this).attr('sid');
                $('#chat-sid').val(sid);

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
                        $('.chat-header, .chat-input').removeClass('hidden');
                        const $messages = $('#messagesArea');
                        $messages.empty();
                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(function(message) {
                                let html = '';
                                if (message.author === 'system') {
                                    html = `
                                        <div class="flex items-end justify-end space-x-2">
                                            <div class="relative wa-bg-message-out rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-out">
                                                <p class="wa-text-primary text-sm break-words whitespace-pre-wrap">${message.body}</p>
                                                <p class="text-xs wa-text-secondary text-right mt-1">
                                                    ${formatDate(message.date_created)}
                                                    <svg class="w-4 h-4 inline-block ml-1" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/></svg>
                                                </p>
                                            </div>
                                        </div>`;
                                } else {
                                    html = `
                                        <div class="flex items-end space-x-2">
                                            <img src="${contactImg}" alt="${contactName}" class="w-8 h-8 rounded-full flex-shrink-0" />
                                            <div class="relative wa-bg-message-in rounded-lg px-3 py-2 max-w-md shadow-sm message-tail-in">
                                                <p class="wa-text-primary text-sm break-words">${message.body}</p>
                                                <p class="text-xs wa-text-secondary text-right mt-1">${formatDate(message.date_created)}</p>
                                            </div>
                                        </div>`;
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
                            <p class="wa-text-primary text-sm break-words">${message}</p>
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
            }

            $('#messageInput').on('keypress', function(e) {
                if (e.which === 13) sendCurrentMessage();
            });
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
        });
    </script>
</body>

</html>
