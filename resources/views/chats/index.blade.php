<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

</head>

<body class="bg-gray-900 text-white h-screen overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-80 bg-gray-800 border-r border-gray-700 flex flex-col">
            <!-- User Profile Header -->
            <div class="p-4 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face"
                            alt="Mike Ross" class="w-10 h-10 rounded-full">
                        <div
                            class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-gray-800">
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-white">Flettons</h3>
                        <div class="flex items-center text-gray-400 text-sm">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                            <span>▼</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="p-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search contacts..."
                        class="w-full bg-gray-700 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Contacts List -->
            <div class="flex-1 overflow-y-auto scrollbar-hide" id="contactsList">

                @foreach ($conversations as $conversation)
                    <div class="contact-item p-4 cursor-pointer " sid="{{ $conversation['sid'] }}"
                        data-name="{{ $conversation['friendly_name'] }}">
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('assets/images/profile.png') }}" alt="Harvey Specter"
                                class="w-10 h-10 rounded-full">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-semibold text-white truncate">{{ $conversation['friendly_name'] }}
                                    </h4>
                                    <span class="text-xs text-gray-400">2:30 PM</span>
                                </div>
                                {{-- <p class="text-sm text-gray-400 truncate">Wrong. You take the gun, or you pull...</p> --}}
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        <input type="hidden" name="" id="user_profile" value="{{ asset('assets/images/profile.png') }}">
        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col bg-gray-900">
            <!-- Chat Header -->
            <div class="bg-gray-800 border-b border-gray-700 p-4 chat-header hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=40&h=40&fit=crop&crop=face"
                            alt="Harvey Specter" class="w-10 h-10 rounded-full">
                        <div>
                            <h3 class="font-semibold text-white" id="chatHeaderName">Harvey Specter</h3>
                            {{-- <p class="text-sm text-green-400">Online</p> --}}
                            <input type="hidden" name="" id="chat-sid">
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </button>
                        <button class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <button class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-hide" id="messagesArea">

            </div>

            <!-- Message Input -->
            <div class="bg-gray-800 border-t border-gray-700 p-4 chat-input hidden">
                <div class="flex items-center space-x-3">
                    <button class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>
                    <div class="flex-1 relative">
                        <input type="text" placeholder="Write your message..."
                            class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                        <button
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function scrollToBottom() {
            var messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }


        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('1643ce51c4a3d4c535e9', {
            cluster: 'ap2'
        });

        // Subscribe to the 'chat' channel
        var channel = pusher.subscribe('chat');

        // Bind to the 'my-event' event that is broadcasted from Laravel
        channel.bind('my-event', function(data) {
            console.log('Received data:', data);
            console.log('sid:', data.sid);

            var contactImg = $('#user_profile').val();
            var sid = $('#chat-sid').val();
            var currenttime = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

            if (data.sid !== sid) {
                return; // Ignore messages not for the current chat
            }

            // Check if the message is from the system or user
            if (data.sender === 'user') {
                var messageHtml = `
                                 <div class="flex space-x-3">
                                    <img src="${contactImg}"
                                        alt="Harvey" class="w-8 h-8 rounded-full">
                                    <div class="flex-1">
                                        <div class="bg-gray-700 rounded-lg p-3 max-w-md">
                                            <p class="text-white">${data.message}</p>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">${currenttime }</p>
                                    </div>
                                </div>
                            `;
            } else {
                var messageHtml = `
                            <div class="flex space-x-3 justify-end">
                                <div class="flex-1 text-right">
                                    <div class="bg-blue-600 rounded-lg p-3 max-w-md inline-block">
                                        <p class="text-white">${data.message}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">${currenttime}</p>
                                </div>
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
                                    alt="Mike" class="w-8 h-8 rounded-full">
                            </div>
                        `;
            }
            $('#messagesArea').append(messageHtml);
            scrollToBottom();
        });
    </script>


    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();

                $('.contact-item').each(function() {
                    const name = $(this).data('name').toLowerCase();
                    const preview = $(this).data('preview').toLowerCase();

                    if (name.includes(searchTerm) || preview.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // If search is empty, show all contacts
                if (searchTerm === '') {
                    $('.contact-item').show();
                }
            });

            // Contact selection
            $('.contact-item').on('click', function() {
                // Remove active class from all contacts
                $('.contact-item').removeClass('active');
                $('.chat-header').removeClass('hidden');
                $('.chat-input').removeClass('hidden');

                // Add active class to clicked contact
                $(this).addClass('active');

                // Update chat header with selected contact
                const contactName = $(this).data('name');
                const contactImg = $(this).find('img').attr('src');
                const sid = $(this).attr('sid');

                $('#chat-sid').val(sid);

                $('#chatHeaderName').text(contactName);
                $('.flex-1 .bg-gray-800 img').attr('src', contactImg);
                $('.flex-1 .bg-gray-800 img').attr('alt', contactName);

                $.ajax({
                    url: "{{ url('chat/messages') }}" + '/' + $(this).attr('sid'),
                    method: 'GET',
                    success: function(data) {
                        $('#messagesArea').empty(); // Clear previous messages
                        if (data.messages && data.messages.length > 0) {
                            data.messages.forEach(function(message) {
                                // Determine sender and style accordingly
                                if (message.author == 'system') {
                                    var messageHtml = `
                            <div class="flex space-x-3 justify-end">
                                <div class="flex-1 text-right">
                                    <div class="bg-blue-600 rounded-lg p-3 max-w-md inline-block">
                                        <p class="text-white">${message.body}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">${formatDate(message.date_created)}</p>
                                </div>
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
                                    alt="Mike" class="w-8 h-8 rounded-full">
                            </div>
                        `;
                                } else {
                                    var messageHtml = `
                             <div class="flex space-x-3">
                                <img src="${contactImg}"
                                    alt="Harvey" class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <div class="bg-gray-700 rounded-lg p-3 max-w-md">
                                        <p class="text-white">${message.body}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">${formatDate(message.date_created)}</p>
                                </div>
                            </div>
                        `;
                                }
                                $('#messagesArea').append(
                                    messageHtml); // Append message to chat area
                            });
                        } else {
                            $('#messagesArea').append(
                                '<p>No messages found.</p>'); // If no messages exist
                        }

                        scrollToBottom();
                    },
                    error: function() {
                        alert('Error fetching messages!');
                    }
                });
            });

            // Helper function to format date with AM/PM
            function formatDate(dateStr) {
                const date = new Date(dateStr);
                let hours = date.getHours();
                const minutes = date.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';

                // Convert hours to 12-hour format
                hours = hours % 12;
                hours = hours ? hours : 12; // '0' hour should be '12'

                return `${hours}:${minutes} ${ampm}`;
            }



            // Clear search on escape key
            $('#searchInput').on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $(this).val('');
                    $('.contact-item').show();
                }
            });

            // Message input functionality
            $('.bg-gray-800:last-child input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    const message = $(this).val().trim();
                    var currenttime = new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    if (message) {
                        // Add message to chat (placeholder functionality)
                        const messageHtml = `
                            <div class="flex space-x-3 justify-end">
                                <div class="flex-1 text-right">
                                    <div class="bg-blue-600 rounded-lg p-3 max-w-md inline-block">
                                        <p class="text-white">${message}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">${currenttime}</p>
                                </div>
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
                                     alt="Mike" class="w-8 h-8 rounded-full">
                            </div>
                        `;

                        if ($('#messagesArea .flex.items-center.justify-center').length > 0) {
                            $('#messagesArea').html('');
                        }

                        $.ajax({
                            url: "{{ url('send-message') }}",
                            method: 'POST',
                            data: {
                                message: message,
                                sid: $('#chat-sid').val(),
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                console.log(response);
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });

                        $('#messagesArea').append(messageHtml);
                        $('#messagesArea').scrollTop($('#messagesArea')[0].scrollHeight);
                        $(this).val('');
                    }
                }
            });

            // Send button functionality
            $('.bg-gray-800:last-child button:last-child').on('click', function() {
                const messageInput = $('.bg-gray-800:last-child input');
                const message = messageInput.val().trim();
                var currenttime = new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                if (message) {
                    const messageHtml = `
                        <div class="flex space-x-3 justify-end">
                            <div class="flex-1 text-right">
                                <div class="bg-blue-600 rounded-lg p-3 max-w-md inline-block">
                                    <p class="text-white">${message}</p>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">${currenttime}</p>
                            </div>
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
                                 alt="Mike" class="w-8 h-8 rounded-full">
                        </div>
                    `;

                    if ($('#messagesArea .flex.items-center.justify-center').length > 0) {
                        $('#messagesArea').html('');
                    }

                    $('#messagesArea').append(messageHtml);
                    $('#messagesArea').scrollTop($('#messagesArea')[0].scrollHeight);
                    messageInput.val('');
                }
            });

            // Auto-scroll to bottom of messages
            $('#messagesArea').scrollTop($('#messagesArea')[0].scrollHeight);

            // Add some hover effects for better UX
            $('.contact-item').hover(
                function() {
                    if (!$(this).hasClass('active')) {
                        $(this).css('background-color', 'rgba(55, 65, 81, 0.5)');
                    }
                },
                function() {
                    if (!$(this).hasClass('active')) {
                        $(this).css('background-color', '');
                    }
                }
            );

            // Simulate typing indicator (optional enhancement)
            function showTypingIndicator(contactName) {
                const typingHtml = `
                    <div class="flex space-x-3 typing-indicator">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=32&h=32&fit=crop&crop=face"
                             alt="${contactName}" class="w-8 h-8 rounded-full">
                        <div class="flex-1">
                            <div class="bg-gray-700 rounded-lg p-3 max-w-md">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Typing...</p>
                        </div>
                    </div>
                `;

                $('#messagesArea').append(typingHtml);
                $('#messagesArea').scrollTop($('#messagesArea')[0].scrollHeight);

                // Remove typing indicator after 2 seconds
                setTimeout(function() {
                    $('.typing-indicator').remove();
                }, 2000);
            }

            // Example: Show typing indicator when Harvey is selected and user sends a message
            $('.bg-gray-800:last-child input, .bg-gray-800:last-child button:last-child').on('click keypress',
                function(e) {
                    if ((e.type === 'click' || e.which === 13) && $('#chatHeaderName').text() ===
                        'Harvey Specter') {
                        setTimeout(function() {
                            showTypingIndicator('Harvey Specter');
                        }, 1000);
                    }
                });
        });
    </script>
</body>

</html>
