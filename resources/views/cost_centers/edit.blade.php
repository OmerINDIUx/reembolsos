<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Centro de Costos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('cost_centers.update', $costCenter->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nombre del Centro de Costos *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $costCenter->name) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3 uppercase" required>
                            </div>
                            <div>
                                <label for="company_id" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Empresa *</label>
                                <select name="company_id" id="company_id" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3" required>
                                    <option value="">Seleccione Empresa...</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $costCenter->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }} - {{ $company->account }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @php
                            $fundRows = old('fixed_funds', $costCenter->fixedFunds->where('is_active', true)->map(fn($f) => [
                                'id' => $f->id,
                                'user_id' => (string) $f->user_id,
                                'name' => $f->name,
                                'budget' => $f->budget,
                                'active_reimbursements' => $activeFixedFundReimbursementCounts[$f->id] ?? 0,
                            ])->values()->all());
                            $fundTransferRows = old('fund_transfers', []);
                            $fundUserOptions = $users
                                ->reject(fn($u) => $u->hasRole('tesoreria'))
                                ->map(fn($u) => ['id' => (string) $u->id, 'name' => $u->name, 'role' => $u->role_name])
                                ->values();
                        @endphp
                        <div
                            x-data="{
                                funds: @js($fundRows),
                                transfers: @js($fundTransferRows),
                                users: @js($fundUserOptions),
                                addFund() {
                                    this.funds.push({ id: null, user_id: '', name: 'Fondo fijo', budget: '', active_reimbursements: 0 });
                                },
                                userName(userId) {
                                    return this.users.find((user) => String(user.id) === String(userId))?.name || 'Sin responsable';
                                },
                                money(value) {
                                    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
                                },
                                totalBudget() {
                                    return this.funds.reduce((total, fund) => total + Number(fund.budget || 0), 0);
                                },
                                receiverOptions(removingIndex) {
                                    return this.funds
                                        .filter((fund, index) => index !== removingIndex && fund.user_id)
                                        .reduce((options, fund) => {
                                            options[fund.user_id] = `${this.userName(fund.user_id)} - ${fund.name || 'Fondo fijo'}`;
                                            return options;
                                        }, {});
                                },
                                async removeFund(index) {
                                    const fund = this.funds[index];
                                    if (fund?.id && Number(fund.active_reimbursements || 0) > 0) {
                                        const inputOptions = this.receiverOptions(index);

                                        if (Object.keys(inputOptions).length === 0) {
                                            await Swal.fire({
                                                icon: 'warning',
                                                title: 'Agrega un receptor',
                                                text: 'Este fondo tiene reembolsos activos. Agrega otro fondo responsable antes de eliminarlo.',
                                                confirmButtonText: 'Entendido',
                                                confirmButtonColor: '#059669',
                                                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl px-6 py-3 font-black text-xs uppercase tracking-widest' }
                                            });
                                            return;
                                        }

                                        const result = await Swal.fire({
                                            icon: 'question',
                                            title: 'Transferir reembolsos activos',
                                            text: `${fund.name} tiene ${fund.active_reimbursements} reembolso(s) activo(s). Elige quién los recibirá antes de quitar este fondo.`,
                                            input: 'select',
                                            inputOptions,
                                            inputPlaceholder: 'Selecciona el nuevo responsable',
                                            showCancelButton: true,
                                            confirmButtonText: 'Transferir y quitar',
                                            cancelButtonText: 'Cancelar',
                                            confirmButtonColor: '#059669',
                                            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl px-6 py-3 font-black text-xs uppercase tracking-widest', cancelButton: 'rounded-xl px-6 py-3 font-bold text-xs uppercase tracking-widest' },
                                            inputValidator: (value) => !value ? 'Selecciona el nuevo responsable.' : undefined
                                        });

                                        if (!result.isConfirmed) {
                                            return;
                                        }

                                        this.transfers = this.transfers.filter((transfer) => String(transfer.fund_id) !== String(fund.id));
                                        this.transfers.push({ fund_id: fund.id, transfer_to_user_id: result.value });
                                    }

                                    this.funds.splice(index, 1);
                                }
                            }"
                            class="mb-8 mt-12 rounded-[2rem] border border-indigo-100 bg-indigo-50/30 p-6 dark:border-indigo-800 dark:bg-indigo-900/10"
                        >
                            <template x-for="(transfer, index) in transfers" :key="`transfer-${transfer.fund_id}`">
                                <div>
                                    <input type="hidden" :name="`fund_transfers[${index}][fund_id]`" :value="transfer.fund_id">
                                    <input type="hidden" :name="`fund_transfers[${index}][transfer_to_user_id]`" :value="transfer.transfer_to_user_id">
                                </div>
                            </template>

                            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 class="text-md font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Fondos fijos asignados</h3>
                                    <p class="mt-1 text-[10px] font-bold uppercase text-gray-500">Administra responsables, presupuestos y transferencias activas.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="rounded-xl bg-white px-4 py-2 text-right shadow-sm ring-1 ring-indigo-100 dark:bg-gray-900 dark:ring-indigo-900">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-300">Presupuesto total</p>
                                        <p class="text-sm font-black text-gray-900 dark:text-white" x-text="money(totalBudget())"></p>
                                    </div>
                                    <button type="button" @click="addFund()" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition-colors hover:bg-indigo-700">
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Añadir fondo
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(fund, index) in funds" :key="fund.id || `new-${index}`">
                                    <div class="group flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-3 transition-all hover:border-indigo-200 dark:border-gray-700 dark:bg-gray-900/70 md:flex-row md:items-center">
                                        <input type="hidden" :name="`fixed_funds[${index}][id]`" :value="fund.id || ''">
                                        <div class="flex-none flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-black text-white" x-text="index + 1"></div>

                                        <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-[1.15fr_1fr_180px]">
                                            <select :name="`fixed_funds[${index}][user_id]`" x-model="fund.user_id" class="block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 sm:text-sm" required>
                                                <option value="">Responsable...</option>
                                                @foreach($users as $u)
                                                    @if(!$u->hasRole('tesoreria'))
                                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role_name }})</option>
                                                    @endif
                                                @endforeach
                                            </select>

                                            <input :name="`fixed_funds[${index}][name]`" x-model="fund.name" class="block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 sm:text-sm" placeholder="Nombre del fondo" required>

                                            <input type="number" step="0.01" min="0" :name="`fixed_funds[${index}][budget]`" x-model="fund.budget" class="block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 sm:text-sm" placeholder="Presupuesto" required>
                                        </div>

                                        <div class="flex flex-none items-center justify-end gap-2">
                                            <span x-show="Number(fund.active_reimbursements || 0) > 0" class="rounded-lg bg-amber-50 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900">
                                                <span x-text="fund.active_reimbursements"></span> activos
                                            </span>
                                            <button type="button" @click="removeFund(index)" class="p-2 text-gray-400 transition-colors hover:text-red-500" title="Eliminar fondo">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="funds.length === 0" class="text-center py-8 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-3xl">
                                    <p class="text-[10px] font-black uppercase text-gray-300 tracking-[0.2em]">No hay fondos fijos activos asignados</p>
                                    <button type="button" @click="addFund()" class="mt-4 rounded-lg bg-indigo-100 px-4 py-2 text-xs font-bold text-indigo-700 transition-colors hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300">Añadir fondo</button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="menfis_email" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Correo de Menfis (Opcional)</label>
                                <input type="email" name="menfis_email" id="menfis_email" value="{{ old('menfis_email', $costCenter->menfis_email) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-bold py-3" placeholder="correo@ejemplo.com">
                            </div>
                            <div>
                                <label for="description" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Descripción (Opcional)</label>
                                <input type="text" name="description" id="description" value="{{ old('description', $costCenter->description) }}" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all font-medium py-3">
                            </div>
                        </div>

                        <!-- Dynamic Steps with Alpine.js -->
                        <div x-data="{ 
                            steps: {{ $costCenter->approvalSteps->map(fn($s) => ['id' => $s->id, 'user_id' => $s->user_id, 'name' => $s->name])->toJson() }},
                            addStep() {
                                this.steps.push({ id: null, user_id: '', name: 'Aprobador N' + (this.steps.length + 1) });
                            },
                            removeStep(index) {
                                this.steps.splice(index, 1);
                            }
                        }" class="mb-8 mt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-md font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Flujo de Aprobación Personalizado</h3>
                                <button type="button" @click="addStep()" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-bold hover:bg-indigo-200 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Añadir Nivel
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(step, index) in steps" :key="index">
                                    <div class="group flex items-center gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700 transition-all hover:border-indigo-200">
                                        <div class="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-black text-xs" x-text="index + 1"></div>
                                        <input type="hidden" :name="'steps['+index+'][id]'" :value="step.id ?? ''">
                                         
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <input type="text" :name="'steps['+index+'][name]'" x-model="step.name" placeholder="Nombre del Nivel (ej: Director)" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            </div>
                                            <div>
                                                <select :name="'steps['+index+'][user_id]'" x-model="step.user_id" class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                    <option value="">Seleccione Usuario...</option>
                                                    @foreach($users as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role_name }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <button type="button" @click="removeStep(index)" class="flex-none p-2 text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Authorized Requestors with Alpine.js -->
                        @php
                            $authorizedUsers = $costCenter->authorizedUsers->map(fn($u) => [
                                'user_id' => $u->id,
                                'can_do_special' => (bool)$u->pivot->can_do_special
                            ]);
                        @endphp
                        <div x-data="{ 
                            users: {{ $authorizedUsers->toJson() }},
                            addUser() {
                                this.users.push({ user_id: '', can_do_special: false });
                            },
                            removeUser(index) {
                                this.users.splice(index, 1);
                            }
                        }" class="mb-8 mt-12 bg-indigo-50/30 dark:bg-indigo-900/10 p-6 rounded-[2rem] border border-indigo-100 dark:border-indigo-800">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-md font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Usuarios Autorizados</h3>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">Solo los usuarios marcados aquí pueden registrar Fondo Fijo, Comida y Viajes en este centro de costos.</p>
                                </div>
                                <button type="button" @click="addUser()" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Añadir Usuario
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(user, index) in users" :key="index">
                                    <div class="group flex items-center gap-4 bg-white dark:bg-gray-900 p-3 rounded-2xl border border-gray-100 dark:border-gray-700 transition-all hover:border-indigo-300">
                                        <div class="flex-1">
                                            <select :name="'allowed_users['+index+'][user_id]'" x-model="user.user_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold" required>
                                                <option value="">Seleccione Usuario...</option>
                                                @foreach($users as $u)
                                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role_name }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                            <input type="checkbox" :name="'allowed_users['+index+'][can_do_special]'" x-model="user.can_do_special" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Puede crear Fondo fijo / Comida / Viaje</label>
                                        </div>

                                        <button type="button" @click="removeUser(index)" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </template>

                                <div x-show="users.length === 0" class="text-center py-8 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-3xl">
                                    <p class="text-[10px] font-black uppercase text-gray-300 tracking-[0.2em]">No hay usuarios autorizados asignados</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-12 pt-8 border-t border-gray-100 dark:border-gray-800">
                            <a href="{{ route('cost_centers.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 mr-6 transition-colors">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-800 dark:hover:bg-gray-100 transition-all shadow-xl shadow-gray-900/20 dark:shadow-white/10">
                                Actualizar Centro de Costo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
