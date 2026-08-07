<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                        <h3 class="text-lg font-medium">Lista de Usuarios</h3>
                        @if(!Auth::user()->isAdminView())
                        <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Nuevo Usuario
                        </a>
                        @endif
                    </div>
                    
                    <!-- Search & Filter Form -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <form id="filter-form" action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4" novalidate>
                            <!-- Search Input -->
                            <div class="col-span-1 md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar (Nombre, Email)</label>
                                <input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Buscar...">
                            </div>

                            <!-- Role Filter -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol</label>
                                <select name="role" id="role" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="admin_view" {{ request('role') == 'admin_view' ? 'selected' : '' }}>Admin (Solo Lectura)</option>
                                    <option value="director" {{ request('role') == 'director' ? 'selected' : '' }}>Director</option>
                                    <option value="control_obra" {{ request('role') == 'control_obra' ? 'selected' : '' }}>Control de Obra</option>
                                    <option value="director_ejecutivo" {{ request('role') == 'director_ejecutivo' ? 'selected' : '' }}>Director Ejecutivo</option>
                                    <option value="accountant" {{ request('role') == 'accountant' ? 'selected' : '' }}>CXP Revisador</option>
                                    <option value="direccion" {{ request('role') == 'direccion' ? 'selected' : '' }}>Dirección General</option>
                                    <option value="tesoreria" {{ request('role') == 'tesoreria' ? 'selected' : '' }}>CXP Pagador</option>
                                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Usuario</option>
                                </select>
                             </div>

                            <!-- Status Filter -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estatus</label>
                                <select name="status" id="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Todos</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos (Registro Completo)</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes de primer acceso</option>
                                    <option value="disabled" {{ request('status') == 'disabled' ? 'selected' : '' }}>Deshabilitados</option>
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="col-span-1 flex justify-end items-end space-x-2">
                                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Limpiar
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 h-10">
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div id="results-container">
                        <div class="overflow-x-auto relative shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-black text-gray-500 dark:text-gray-300 uppercase tracking-widest">Usuario</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Correo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                                    @if(Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'direccion'))
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                    @endif

                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($users as $user)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-all">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('users.show', $user->id) }}" class="flex items-center group">
                                        <div>
                                            <div class="text-sm font-black text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors underline decoration-dotted decoration-indigo-200 underline-offset-4">{{ $user->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-bold">Ver Panel Personal &rarr;</div>
                                        </div>
                                    </a>
                                </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest {{ $user->role === 'admin' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $user->profile?->display_name ?: $user->role_name }}
                                        </span>
                                    </td>
                                    @if(Auth::user()->hasRole('admin', 'admin_view', 'director_ejecutivo', 'direccion'))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($user->status === 'disabled')<span class="text-red-600 font-bold">Deshabilitado</span>@elseif($user->status === 'pending')<span class="text-amber-600 font-bold">Pendiente de primer acceso</span>@else<span class="text-emerald-600 font-bold">Activo</span>@endif
                                    </td>
                                    @endif

                                     <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        @if(!Auth::user()->isAdminView())
                                            @if($user->invitation_token && !$user->isBlocked())
                                                <form action="{{ route('users.resend_invitation', $user->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-600" title="Reenviar Invitación">✉</button>
                                                </form>
                                                <button type="button" onclick="copyInvitationLink('{{ route('invitation.accept', $user->invitation_token) }}')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-600" title="Copiar Enlace de Invitación">↗</button>
                                            @endif
                                        <a href="{{ route('users.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600">Editar</a>
                                        
                                        @if($user->id !== auth()->id())
                                        <form id="block-user-{{ $user->id }}" action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline">
    @csrf
    @method('DELETE')
    <input type="hidden" name="reimbursement_action" value="keep">
    <input type="hidden" name="reimbursement_transfer_to_user_id" value="">
    <input type="hidden" name="approval_action" value="remove">
    <input type="hidden" name="approval_reassign_to_user_id" value="">
    <input type="hidden" name="pending_approval_action" value="next">
    <input type="hidden" name="transfer_to_user_id" value="">
    @if(!$user->isBlocked())
    <a href="{{ route('users.deactivation', $user) }}" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 ml-2">Inhabilitar</a>
    @else
    <span class="text-gray-400 italic ml-2">Ya inhabilitado</span>
    @endif
</form>
                                        @endif
                                        @else
                                        <span class="text-gray-400 italic">Solo lectura</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-8 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 sm:rounded-b-lg">
                        {{ $users->links() }}
                    </div>
                    </div> <!-- End results-container -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const container = document.getElementById('results-container');
            
            // Function to handle fetching and updating
            function fetchResults(url) {
                // simple opacity fade for feedback
                container.style.opacity = '0.5';
                
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('results-container').innerHTML;
                    container.innerHTML = newContent;
                    container.style.opacity = '1';
                    
                    // Update URL without reload
                    window.history.pushState({}, '', url);
                    
                    // Re-attach pagination listeners
                    attachPaginationListeners();
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.style.opacity = '1';
                });
            }
            
            // Handle Form Submit (Manual Filter)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFilter();
            });

            function submitFilter() {
                const url = new URL(form.action);
                const params = new URLSearchParams(new FormData(form));
                url.search = params.toString();
                fetchResults(url);
            }

            // Real-time Search Logic with Debounce
            let debounceTimer;
            
            // Listen to inputs
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        submitFilter();
                    }, 500); // 500ms delay
                });
            });

            // Listen to selects (immediate trigger)
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    submitFilter();
                });
            });
            
            // Handle Pagination Clicks
            function attachPaginationListeners() {
                const links = container.querySelectorAll('a.page-link, .pagination a'); // Adapt selector to Laravel's pagination classes
                links.forEach(link => {
                     link.addEventListener('click', function(e) {
                         e.preventDefault();
                         fetchResults(this.href);
                     });
                });
            }
            
            // Initial attach
            attachPaginationListeners();
        });

        function copyInvitationLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Enlace Copiado',
                    text: 'El enlace de invitación ha sido copiado al portapapeles.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }

        @php
            $fixedFundTransferCandidateOptions = $fixedFundTransferCandidates->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'profile' => $candidate->profile?->display_name ?: $candidate->role_name,
                ];
            })->values();
        @endphp
        const fixedFundTransferCandidates = @js($fixedFundTransferCandidateOptions);
        const activeUserCandidates = @js($activeUserCandidates->map(fn ($candidate) => [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'profile' => $candidate->profile?->display_name ?: $candidate->role_name,
        ])->values());

        async function confirmUserDeactivation(userId, userName, activeFundCount, activeReimbursementCount, approvalStepCount) {
            const form = document.getElementById('block-user-' + userId);
            if (!form) return;

            const candidates = activeUserCandidates.filter(candidate => Number(candidate.id) !== Number(userId));
            const options = candidates.map(candidate => '<option value="' + candidate.id + '">' + candidate.name + ' — ' + candidate.profile + '</option>').join('');
            let html = '<p class="text-sm text-left mb-3">La cuenta quedará inhabilitada y conservará su histórico.</p>';

            if (activeReimbursementCount) {
                html += '<label class="block text-left text-xs font-bold mt-3">Reembolsos propios en proceso (' + activeReimbursementCount + ')</label>';
                html += '<select id="reimbursement-action" class="swal2-select" style="display:block;width:100%;margin:.4rem 0"><option value="keep">Conservarlos con este usuario</option><option value="transfer">Transferirlos a otra persona</option></select>';
                html += '<select id="reimbursement-target" class="swal2-select" style="display:block;width:100%;margin:.4rem 0" disabled><option value="">Selecciona responsable...</option>' + options + '</select>';
            }

            if (approvalStepCount) {
                html += '<div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 text-left"><p class="text-xs font-black uppercase tracking-wide text-indigo-700">Flujo de aprobación</p>';
                html += '<label class="block text-xs font-bold mt-2 text-gray-700">Qué hacer con el paso</label>';
                html += '<select id="approval-action" class="swal2-select" style="display:block;width:100%;margin:.4rem 0"><option value="reassign">Sustituir al aprobador por otra persona</option><option value="remove">Eliminar este paso de los centros de costos</option></select>';
                html += '<select id="approval-target" class="swal2-select" style="display:block;width:100%;margin:.4rem 0"><option value="">Selecciona sustituto...</option>' + options + '</select>';
                html += '<label class="block text-xs font-bold mt-3 text-gray-700">Reembolsos detenidos en este paso</label>';
                html += '<select id="pending-action" class="swal2-select" style="display:block;width:100%;margin:.4rem 0"><option value="keep">Conservarlos aquí para que los revise el sustituto</option><option value="next">Pasarlos al siguiente paso</option><option value="previous">Regresarlos al paso anterior</option></select>';
                html += '<p class="text-[11px] leading-4 text-indigo-700">Conservarlos mantiene el paso y cambia únicamente a la persona responsable.</p></div>';
            }

            if (activeFundCount) {
                html += '<label class="block text-left text-xs font-bold mt-3">Fondos fijos (' + activeFundCount + ')</label><select id="fund-target" class="swal2-select" style="display:block;width:100%;margin:.4rem 0"><option value="">Selecciona responsable...</option>' + options + '</select>';
            }

            const result = await Swal.fire({
                icon: 'warning',
                title: 'Inhabilitar a ' + userName,
                html: html,
                width: 620,
                showCancelButton: true,
                confirmButtonText: 'INHABILITAR CUENTA',
                cancelButtonText: 'CANCELAR',
                confirmButtonColor: '#dc2626',
                didOpen: () => {
                    const toggle = (source, target, value) => {
                        if (!source || !target) return;
                        source.addEventListener('change', () => target.disabled = source.value !== value);
                    };
                    toggle(document.getElementById('reimbursement-action'), document.getElementById('reimbursement-target'), 'transfer');
                    const approvalAction = document.getElementById('approval-action');
                    const approvalTarget = document.getElementById('approval-target');
                    const pendingAction = document.getElementById('pending-action');
                    if (approvalAction && approvalTarget && pendingAction) {
                        const syncApprovalOptions = () => {
                            approvalTarget.disabled = approvalAction.value !== 'reassign';
                            const keepOption = pendingAction.querySelector('option[value="keep"]');
                            if (keepOption) keepOption.hidden = approvalAction.value !== 'reassign';
                            if (approvalAction.value === 'remove' && pendingAction.value === 'keep') pendingAction.value = 'next';
                        };
                        approvalAction.addEventListener('change', syncApprovalOptions);
                        syncApprovalOptions();
                    }
                },
                preConfirm: () => {
                    const reimbursementAction = document.getElementById('reimbursement-action')?.value || 'keep';
                    const reimbursementTarget = document.getElementById('reimbursement-target')?.value || '';
                    const approvalAction = document.getElementById('approval-action')?.value || 'remove';
                    const approvalTarget = document.getElementById('approval-target')?.value || '';
                    const fundTarget = document.getElementById('fund-target')?.value || '';
                    if (reimbursementAction === 'transfer' && !reimbursementTarget) return Swal.showValidationMessage('Selecciona quién recibirá los reembolsos.');
                    if (approvalAction === 'reassign' && !approvalTarget) return Swal.showValidationMessage('Selecciona quién sustituirá el paso de aprobación.');
                    if (approvalAction === 'remove' && document.getElementById('pending-action')?.value === 'keep') return Swal.showValidationMessage('Al eliminar el paso debes elegir el paso anterior o el siguiente.');
                    if (activeFundCount && !fundTarget) return Swal.showValidationMessage('Selecciona quién recibirá los fondos fijos.');
                    return { reimbursementAction, reimbursementTarget, approvalAction, approvalTarget, pendingAction: document.getElementById('pending-action')?.value || 'next', fundTarget };
                }
            });

            if (!result.isConfirmed) return;
            form.querySelector('[name="reimbursement_action"]').value = result.value.reimbursementAction;
            form.querySelector('[name="reimbursement_transfer_to_user_id"]').value = result.value.reimbursementTarget;
            form.querySelector('[name="approval_action"]').value = result.value.approvalAction;
            form.querySelector('[name="approval_reassign_to_user_id"]').value = result.value.approvalTarget;
            form.querySelector('[name="pending_approval_action"]').value = result.value.pendingAction;
            form.querySelector('[name="transfer_to_user_id"]').value = result.value.fundTarget;
            form.submit();
        }
    </script>
</x-app-layout>
