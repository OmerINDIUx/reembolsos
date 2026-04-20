<?php
$file = 'resources/views/reimbursements/index.blade.php';
$content = file_get_contents($file);

$find = <<<EOT
                                                    @if(\$r->status === 'pendiente' && \$r->currentStep) 
                                                        En: {{ \$r->currentStep->user->name ?? 'Por asignar' }}
EOT;

$replace = <<<EOT
                                                    @if(\$r->status === 'pendiente' && \$r->currentStep) 
                                                        En: {{ \$r->currentStep->user->name ?? 'Por asignar' }}
                                                        @php
                                                            \$isSubstituteApproval = false;
                                                            if (\$r->currentStep->user_id !== Auth::id()) {
                                                                \$isSubstituteApproval = Auth::user()->substitutingFor()->where('original_user_id', \$r->currentStep->user_id)->exists();
                                                            }
                                                        @endphp
                                                        @if(\$isSubstituteApproval)
                                                            <span class="block mt-1 text-indigo-500 font-bold bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800 text-[9px] w-fit">Sustituyendo a {{ \$r->currentStep->user->name }}</span>
                                                        @endif
EOT;

$content = str_replace($find, $replace, $content);
file_put_contents($file, $content);
echo "Blade badge added.\n";
