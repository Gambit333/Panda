<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>PANDA NEW ERA</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles (Tailwind CSS) -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="font-sans antialiased bg-[#0a0a0a] text-white selection:bg-purple-500 selection:text-white">
        
        <div class="min-h-screen flex flex-col relative overflow-hidden">
            <!-- Efecto de luz morada de fondo (Minimalista) -->
            <div class="absolute top-[-20%] left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-purple-900/20 blur-[120px] rounded-full pointer-events-none z-0"></div>

            <!-- Navegación Superior -->
            <header class="w-full px-6 py-6 flex items-center justify-between z-10 max-w-7xl mx-auto">
                <!-- Logo -->
                <div class="text-2xl font-bold tracking-widest text-purple-500 flex items-center gap-2">
                    <!-- Icono minimalista opcional -->
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    <span>PANDA<span class="text-white">NEW ERA</span></span>
                </div>

                <!-- Menú de Login / Registro -->
                @if (Route::has('login'))
                    <nav class="flex items-center space-x-6">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-gray-300 hover:text-purple-400 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-300 hover:text-purple-400 transition-colors">Iniciar Sesión</a>
                            
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-medium px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-full transition-all shadow-[0_0_15px_rgba(147,51,234,0.3)] hover:shadow-[0_0_25px_rgba(147,51,234,0.5)]">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <!-- Contenido Principal -->
            <main class="flex-1 flex flex-col items-center justify-center z-10 px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-extrabold mb-6 tracking-tight">
                    Bienvenido a la <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-purple-600">Nueva Era</span>
                </h1>
                
                <p class="text-lg md:text-xl text-gray-400 max-w-2xl mb-12 font-light">
                    Un espacio minimalista y oscuro diseñado para la elegancia y el rendimiento. Prepárate para descubrir lo que PANDA tiene para ofrecerte.
                </p>

                <!-- Botones de Acción -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#" class="px-8 py-3.5 rounded-full bg-white text-black font-semibold hover:bg-gray-200 transition-colors">
                        Comenzar Ahora
                    </a>
                    <a href="#" class="px-8 py-3.5 rounded-full border border-purple-500/50 text-purple-400 hover:bg-purple-500/10 transition-colors">
                        Saber más
                    </a>
                </div>
            </main>

            <!-- Pie de página -->
            <footer class="w-full py-8 text-center text-sm text-gray-500 z-10">
                &copy; {{ date('Y') }} PANDA NEW ERA. Todos los derechos reservados.
            </footer>
        </div>

    </body>
</html>