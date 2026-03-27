<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema de Reembolsos') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Tom Select -->
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @stack('scripts')

        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                },
                customClass: {
                    popup: 'rounded-2xl shadow-2xl border-none font-sans'
                }
            });

            @if(session('success'))
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif

            @if(session('error'))
                Swal.fire({
                    title: '<span class="text-xl font-black uppercase tracking-tight text-red-600">Atención</span>',
                    html: '<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">{{ session('error') }}</p>',
                    icon: 'error',
                    confirmButtonText: 'ENTENDIDO',
                    confirmButtonColor: '#ef4444',
                    customClass: {
                        popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
                        confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest'
                    }
                });
            @endif

            @if(session('warning'))
                Toast.fire({ icon: 'warning', title: '{{ session('warning') }}' });
            @endif

            // Global Beta Notice (Triggered from Dashboard/Login)
            document.addEventListener('DOMContentLoaded', function() {
                @if(session('show_beta_modal') || (request()->routeIs('panel') && !session()->has('beta_notice_displayed_once_this_load')))
                    Swal.fire({
                        title: '<div class="flex flex-col items-center"><div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mb-4 text-3xl">🚀</div><span class="text-xl font-black uppercase tracking-tighter text-gray-900 dark:text-white text-center">¡Gracias por ser parte de la Beta!</span></div>',
                        html: `
                            <div class="text-center">
                                <p class="text-sm font-bold text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
                                    Queremos informarte que el periodo de pruebas ha concluido con éxito. 
                                    <br><br>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-black">IMPORTANTE:</span> Por el momento <span class="underline decoration-indigo-500 decoration-2 underline-offset-4">no se podrán subir nuevos reembolsos</span>. El sistema permanecerá activo únicamente para aprobar o rechazar los registros existentes.
                                </p>
                                <div class="bg-indigo-50 dark:bg-indigo-900/40 p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800 mb-2">
                                    <p class="text-indigo-900 dark:text-indigo-100 font-black text-lg uppercase tracking-widest">Nos vemos en la V1</p>
                                    <p class="text-indigo-600 dark:text-indigo-400 font-black text-2xl">15 de Abril</p>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'ENTENDIDO',
                        confirmButtonColor: '#4f46e5',
                        padding: '2.5rem',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        customClass: {
                            popup: 'rounded-[2.5rem] shadow-2xl border-none',
                            confirmButton: 'rounded-xl px-12 py-4 font-black text-xs uppercase tracking-[0.2em] shadow-lg hover:shadow-indigo-500/30 transition-all'
                        }
                    });
                    @php session()->flash('beta_notice_displayed_once_this_load', true); @endphp
                @endif
            });
        </script>
    </body>
</html>
