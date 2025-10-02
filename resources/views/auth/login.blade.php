<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FlettonsChat – Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
  <style>
    html,
    body {
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
    }
  </style>
</head>

<body class="min-h-screen bg-white flex items-center justify-center px-4">
  <!-- Card -->
  <div
    class="w-full max-w-[520px] min-h-[600px]  rounded-xl bg-[#1A202C] px-6 sm:px-10 py-10 shadow-xl flex flex-col justify-center">
    <!-- Brand -->
    <div class="mb-8 text-center">
      <h1
        class="text-4xl sm:text-5xl md:text-[65px] font-bold leading-none tracking-tight select-none">
        <span class="text-white">Flettons</span><span class="text-[#C1EC4A]">Chat</span>
      </h1>
    </div>

    <!-- Form -->
    <form class="space-y-5" action="{{ route('admin.login') }}" method="POST">
      @csrf
      <div>
        <input type="text" placeholder="Username or email" name="email"
          class="w-full h-[65px] rounded-lg bg-white text-[#111827] placeholder-[#111827] px-4 text-lg outline-none" />
      </div>
      <div>
        <input type="password" placeholder="Password" name="password"
          class="w-full h-[65px] rounded-lg bg-white text-[#111827] placeholder-[#111827] px-4 text-lg outline-none" />
      </div>
      <button type="submit"
        class="w-full h-[65px] rounded-lg bg-[#C1EC4A] text-[#1A202C] font-extrabold tracking-wide uppercase shadow-md active:translate-y-[1px] text-lg sm:text-xl md:text-2xl transition">
        Log In
      </button>
    </form>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>

</html>
