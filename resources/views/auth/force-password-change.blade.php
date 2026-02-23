<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center px-4 bg-gray-50 dark:bg-gray-900">
        <div class="w-full max-w-md">
            <!-- Branding/Logo -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl shadow-xl shadow-indigo-500/20 mb-6 text-white text-3xl font-black">
                    R
                </div>
                <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                    Seguridad Obligatoria
                </h2>
                <p class="mt-3 text-gray-500 dark:text-gray-400 font-medium">
                    Por favor, actualiza tu contraseña genérica para continuar.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-[2rem] p-8 md:p-10 border border-gray-100 dark:border-gray-700">
                <form method="POST" action="{{ route('password.force_change.store') }}">
                    @csrf

                    <!-- New Password -->
                    <div class="mb-6">
                        <label for="password" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nueva Contraseña Personal</label>
                        <input id="password" name="password" type="password" required 
                               class="w-full bg-gray-50 dark:bg-gray-900 border-none rounded-2xl px-5 py-4 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                               placeholder="Mínimo 8 caracteres">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-8">
                        <label for="password_confirmation" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Confirmar Nueva Contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required 
                               class="w-full bg-gray-50 dark:bg-gray-900 border-none rounded-2xl px-5 py-4 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all font-medium"
                               placeholder="Repite tu nueva contraseña">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-widest py-5 rounded-2xl shadow-xl shadow-indigo-500/30 transition-all active:scale-[0.98]">
                            ACTUALIZAR Y ENTRAR AL SISTEMA
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-700 text-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors uppercase tracking-widest">
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="mt-10 text-center text-xs text-gray-400 font-medium">
                © {{ date('Y') }} Sistema de Reembolsos. Priorizamos tu seguridad.
            </p>
        </div>
    </div>
</x-guest-layout>
