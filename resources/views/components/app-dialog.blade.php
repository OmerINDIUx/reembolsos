<script>
    (function () {
        const dialogClasses = {
            popup: 'rounded-[1.5rem] border-none shadow-2xl dark:bg-gray-800',
            confirmButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest',
            cancelButton: 'rounded-xl px-8 py-3 font-black text-xs uppercase tracking-widest',
        };

        const typePresets = {
            danger: { icon: 'warning', color: '#ef4444', titleClass: 'text-red-600' },
            warning: { icon: 'warning', color: '#f59e0b', titleClass: 'text-amber-600' },
            question: { icon: 'question', color: '#4f46e5', titleClass: 'text-indigo-600' },
            info: { icon: 'info', color: '#4f46e5', titleClass: 'text-indigo-600' },
        };

        window.AppDialog = {
            confirm(options = {}) {
                const preset = typePresets[options.type] || typePresets.warning;
                const titleText = options.title || '¿Estás seguro?';
                const titleClass = options.titleClass || preset.titleClass;

                return Swal.fire({
                    title: `<span class="text-xl font-black uppercase tracking-tight ${titleClass}">${titleText}</span>`,
                    html: options.message
                        ? `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">${options.message}</p>`
                        : '',
                    icon: options.icon || preset.icon,
                    showCancelButton: true,
                    confirmButtonText: options.confirmText || 'SÍ, CONFIRMAR',
                    cancelButtonText: options.cancelText || 'CANCELAR',
                    confirmButtonColor: options.confirmColor || preset.color,
                    cancelButtonColor: '#9ca3af',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: dialogClasses,
                });
            },

            alert(options = {}) {
                const preset = typePresets[options.type] || typePresets.info;
                const titleText = options.title || 'Atención';
                const titleClass = options.titleClass || preset.titleClass;

                return Swal.fire({
                    title: `<span class="text-xl font-black uppercase tracking-tight ${titleClass}">${titleText}</span>`,
                    html: options.message
                        ? `<p class="text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">${options.message}</p>`
                        : (options.html || ''),
                    icon: options.icon || preset.icon,
                    confirmButtonText: options.confirmText || 'ENTENDIDO',
                    confirmButtonColor: options.confirmColor || preset.color,
                    customClass: dialogClasses,
                });
            },
        };

        window.AppConfirm = (options) => window.AppDialog.confirm(options);
        window.AppAlert = (options) => window.AppDialog.alert(options);

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-confirm]');
            if (!form) return;

            if (form.dataset.confirmConfirmed === '1') {
                delete form.dataset.confirmConfirmed;
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            AppDialog.confirm({
                type: form.dataset.confirmType || 'danger',
                title: form.dataset.confirmTitle || '¿Confirmar acción?',
                message: form.dataset.confirm,
                confirmText: form.dataset.confirmBtn || 'SÍ, CONFIRMAR',
                cancelText: form.dataset.cancelBtn || 'CANCELAR',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        }, true);
    })();
</script>
