<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Panda Multiverse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#09090b] text-zinc-100 font-sans min-h-screen flex items-center justify-center p-4 selection:bg-purple-500 selection:text-white">

    <!-- Efecto de luces al fondo -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-purple-900/30 rounded-full blur-[128px]"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-950/40 rounded-full blur-[128px]"></div>
    </div>

    <!-- Tarjeta Centrada -->
    <div class="w-full max-w-md bg-zinc-900/80 border border-purple-900/30 backdrop-blur-xl rounded-3xl p-8 shadow-2xl shadow-purple-950/40 relative z-10">
        
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-950 to-zinc-800 border border-purple-500/40 flex items-center justify-center mb-3 shadow-lg shadow-purple-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Iniciar sesión</h1>
            <p class="text-xs text-zinc-400 mt-1">Ingresa el email registrado de tu cuenta para continuar.</p>
        </div>

        <form method="POST" action="{{ route('login.submit-email') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-xs font-medium text-zinc-300 mb-1.5">Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="tu@correo.com"
                        class="w-full pl-12 pr-4 py-3 bg-zinc-950/60 border border-purple-900/40 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all text-sm">
                </div>
                @error('email')
                    <p class="text-rose-400 text-xs mt-2 pl-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full py-3.5 px-4 bg-purple-600 hover:bg-purple-500 text-white font-medium text-sm rounded-xl transition-all duration-200 shadow-lg shadow-purple-950/50 hover:shadow-purple-600/30 active:scale-[0.99]">
                Continuar
            </button>
        </form>

    </div>

</body>
</html>