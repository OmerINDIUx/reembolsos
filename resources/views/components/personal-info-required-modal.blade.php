@auth
    @php
        $currentUser = Auth::user();
        $snoozedUntil = session('personal_info_remind_later_until');
        $isSnoozed = false;

        if ($snoozedUntil && !$errors->any()) {
            try {
                $isSnoozed = now()->lt(\Carbon\Carbon::parse($snoozedUntil));
            } catch (\Throwable $e) {
                $isSnoozed = false;
            }
        }

        $mustCompletePersonalInfo = ($currentUser?->needsPersonalReimbursementInfo() ?? false) && !$isSnoozed;
        $missingPersonalInfo = [
            'Nombre completo' => blank($currentUser?->name) || count(preg_split('/\s+/u', trim((string) $currentUser?->name), -1, PREG_SPLIT_NO_EMPTY)) < 3,
            'RFC' => blank($currentUser?->rfc),
            'Banco' => blank($currentUser?->bank_name),
            'CLABE' => blank($currentUser?->clabe),
            'Confirmación' => $currentUser?->personal_info_confirmed_at === null,
        ];
    @endphp

    @if($mustCompletePersonalInfo)
        <div
            x-data="{ open: true }"
            x-show="open"
            x-cloak
            style="position: fixed; inset: 0; z-index: 9999; width: 100vw; height: 100vh; background: rgba(15, 23, 42, .58); backdrop-filter: blur(6px);"
        >
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: min(760px, calc(100vw - 32px)); max-height: calc(100vh - 32px); overflow-y: auto; border-radius: 28px; background: #ffffff; box-shadow: 0 30px 80px rgba(15, 23, 42, .35); border: 1px solid rgba(226, 232, 240, .9);">
                <div style="padding: 28px 30px 22px; background: linear-gradient(135deg, #111827 0%, #1d4ed8 55%, #0f766e 100%); color: #ffffff;">
                    <div style="display: flex; align-items: flex-start; gap: 16px;">
                        <div style="width: 46px; height: 46px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, .16); flex: none;">
                            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3Zm0 2c-2.761 0-5 1.343-5 3v1h10v-1c0-1.657-2.239-3-5-3Z"/>
                            </svg>
                        </div>
                        <div>
                            <p style="margin: 0 0 6px; font-size: 11px; font-weight: 900; letter-spacing: .18em; text-transform: uppercase; color: rgba(255,255,255,.72);">Información personal requerida</p>
                            <h2 style="margin: 0; font-size: 26px; line-height: 1.1; font-weight: 900;">Confirma tus datos para reembolsos</h2>
                            <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.55; color: rgba(255,255,255,.84);">
                                Tu nombre completo, RFC y cuenta bancaria son datos personales requeridos para poder procesar tus reembolsos y pagos correctamente.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="padding: 22px 30px 30px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px;">
                        @foreach($missingPersonalInfo as $label => $isMissing)
                            <span style="display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 800; background: {{ $isMissing ? '#fff7ed' : '#ecfdf5' }}; color: {{ $isMissing ? '#c2410c' : '#047857' }}; border: 1px solid {{ $isMissing ? '#fed7aa' : '#a7f3d0' }};">
                                {{ $isMissing ? 'Pendiente' : 'Listo' }} · {{ $label }}
                            </span>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" style="display: grid; gap: 16px;">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="required_name" style="display:block; margin-bottom: 6px; color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.14em;">Nombre completo con dos apellidos</label>
                            <input id="required_name" name="name" type="text" value="{{ old('name', $currentUser->name) }}" required autocomplete="name" placeholder="Nombre Apellido Paterno Apellido Materno" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; font-size:15px; text-transform:uppercase; box-shadow: 0 1px 2px rgba(15,23,42,.06);">
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                            <div>
                                <label for="required_rfc" style="display:block; margin-bottom: 6px; color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.14em;">RFC</label>
                                <input id="required_rfc" name="rfc" type="text" value="{{ old('rfc', $currentUser->rfc) }}" required maxlength="13" minlength="12" placeholder="ABCD000000XXX" oninput="this.value = this.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g, '')" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; font-size:15px; text-transform:uppercase; box-shadow: 0 1px 2px rgba(15,23,42,.06);">
                                <x-input-error class="mt-2" :messages="$errors->get('rfc')" />
                            </div>

                            <div>
                                <label for="required_bank_name" style="display:block; margin-bottom: 6px; color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.14em;">Institución bancaria</label>
                                <input id="required_bank_name" name="bank_name" type="text" value="{{ old('bank_name', $currentUser->bank_name) }}" required placeholder="Ej. BBVA, Santander..." style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; font-size:15px; text-transform:uppercase; box-shadow: 0 1px 2px rgba(15,23,42,.06);">
                                <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
                            </div>
                        </div>

                        <div>
                            <label for="required_clabe" style="display:block; margin-bottom: 6px; color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.14em;">Cuenta CLABE (18 dígitos)</label>
                            <input id="required_clabe" name="clabe" type="text" value="{{ old('clabe', $currentUser->clabe) }}" required maxlength="18" minlength="18" placeholder="000000000000000000" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="width:100%; border:1px solid #cbd5e1; border-radius:14px; padding:12px 14px; font-size:15px; box-shadow: 0 1px 2px rgba(15,23,42,.06);">
                            <x-input-error class="mt-2" :messages="$errors->get('clabe')" />
                        </div>

                        <label style="display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-radius:18px; background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:13px; line-height:1.5;">
                            <input type="checkbox" name="personal_info_confirmed" value="1" required style="margin-top:3px; width:18px; height:18px;">
                            <span>Confirmo que mi nombre completo, RFC y datos bancarios son correctos. Entiendo que esta información personal es requerida para recibir mis reembolsos.</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('personal_info_confirmed')" />

                        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding-top:4px;">
                            <button type="submit" form="personal-info-remind-later-form" style="border:0; background:transparent; color:#64748b; font-size:12px; font-weight:900; text-transform:uppercase; letter-spacing:.12em; cursor:pointer;">
                                Recordar más tarde
                            </button>

                            <button type="submit" style="border:0; border-radius:16px; background:#0f172a; color:#fff; padding:13px 22px; font-size:12px; font-weight:900; text-transform:uppercase; letter-spacing:.12em; box-shadow: 0 12px 28px rgba(15,23,42,.24); cursor:pointer;">
                                Guardar y continuar
                            </button>
                        </div>
                    </form>

                    <form id="personal-info-remind-later-form" method="POST" action="{{ route('profile.personal_info.remind_later') }}" style="display:none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    @endif
@endauth
