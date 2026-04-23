<?php
// admin/includes/modal.php
// Custom themed modal component — replaces browser alert() / confirm()
// Include this ONCE per page, right before </body> (or inside footer.php).
// Then use from JS: xModal.alert(...), xModal.confirm(...), xModal.toast(...)
?>

<!-- ================= Custom Modal (XSpec themed) ================= -->
<div id="xModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4">
    <!-- Backdrop -->
    <div id="xModalBackdrop"
         class="absolute inset-0 bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <!-- Dialog -->
    <div id="xModalDialog"
         class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden
                scale-95 opacity-0 transition-all duration-200 border border-gray-100">

        <!-- Accent bar -->
        <div id="xModalAccent" class="h-1.5 w-full bg-gradient-to-r from-primary to-primary-dark"></div>

        <div class="p-7">
            <!-- Icon -->
            <div id="xModalIconWrap"
                 class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-5
                        ring-8 ring-red-50/50">
                <i id="xModalIcon" class="fas fa-exclamation-circle text-3xl text-primary"></i>
            </div>

            <!-- Title -->
            <h3 id="xModalTitle" class="text-xl font-bold text-gray-800 text-center mb-2">Confirm</h3>

            <!-- Message -->
            <p id="xModalMessage" class="text-gray-600 text-center mb-6 leading-relaxed">Are you sure?</p>

            <!-- Buttons -->
            <div id="xModalButtons" class="flex gap-3">
                <button id="xModalCancel" type="button"
                        class="flex-1 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700
                               rounded-xl font-semibold transition-colors">
                    Cancel
                </button>
                <button id="xModalConfirm" type="button"
                        class="flex-1 px-5 py-3 bg-primary hover:bg-primary-dark text-white
                               rounded-xl font-semibold transition-colors shadow-md
                               hover:shadow-lg hover:shadow-red-300/40">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div id="xToastContainer" class="fixed top-6 right-6 z-[10000] flex flex-col gap-3 pointer-events-none"></div>

<script>
(function () {
    const modal    = document.getElementById('xModal');
    const backdrop = document.getElementById('xModalBackdrop');
    const dialog   = document.getElementById('xModalDialog');
    const accent   = document.getElementById('xModalAccent');
    const iconWrap = document.getElementById('xModalIconWrap');
    const iconEl   = document.getElementById('xModalIcon');
    const titleEl  = document.getElementById('xModalTitle');
    const msgEl    = document.getElementById('xModalMessage');
    const btnsEl   = document.getElementById('xModalButtons');
    const btnCnl   = document.getElementById('xModalCancel');
    const btnOk    = document.getElementById('xModalConfirm');

    const THEMES = {
        confirm: {
            accent: 'bg-gradient-to-r from-primary to-primary-dark',
            ring:   'bg-red-50 ring-red-50/50',
            icon:   'fas fa-question-circle text-primary',
            okBg:   'bg-primary hover:bg-primary-dark hover:shadow-red-300/40'
        },
        danger: {
            accent: 'bg-gradient-to-r from-red-500 to-red-700',
            ring:   'bg-red-50 ring-red-50/50',
            icon:   'fas fa-exclamation-triangle text-red-600',
            okBg:   'bg-red-600 hover:bg-red-700 hover:shadow-red-300/40'
        },
        success: {
            accent: 'bg-gradient-to-r from-green-500 to-emerald-600',
            ring:   'bg-green-50 ring-green-50/50',
            icon:   'fas fa-check-circle text-green-600',
            okBg:   'bg-green-600 hover:bg-green-700 hover:shadow-green-300/40'
        },
        info: {
            accent: 'bg-gradient-to-r from-blue-500 to-indigo-600',
            ring:   'bg-blue-50 ring-blue-50/50',
            icon:   'fas fa-info-circle text-blue-600',
            okBg:   'bg-blue-600 hover:bg-blue-700 hover:shadow-blue-300/40'
        },
        warning: {
            accent: 'bg-gradient-to-r from-amber-500 to-orange-600',
            ring:   'bg-amber-50 ring-amber-50/50',
            icon:   'fas fa-exclamation-circle text-amber-600',
            okBg:   'bg-amber-600 hover:bg-amber-700 hover:shadow-amber-300/40'
        }
    };

    let pendingResolve = null;

    function applyTheme(type) {
        const t = THEMES[type] || THEMES.confirm;
        accent.className   = 'h-1.5 w-full ' + t.accent;
        iconWrap.className = 'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-5 ring-8 ' + t.ring;
        iconEl.className   = t.icon + ' text-3xl';
        btnOk.className    = 'flex-1 px-5 py-3 text-white rounded-xl font-semibold transition-colors shadow-md hover:shadow-lg ' + t.okBg;
    }

    function open() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // Next frame so transition kicks in
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            dialog.classList.remove('scale-95', 'opacity-0');
            dialog.classList.add('scale-100', 'opacity-100');
        });
    }

    function close(result) {
        backdrop.classList.add('opacity-0');
        backdrop.classList.remove('opacity-100');
        dialog.classList.add('scale-95', 'opacity-0');
        dialog.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (pendingResolve) {
                const r = pendingResolve;
                pendingResolve = null;
                r(result);
            }
        }, 200);
    }

    btnOk.addEventListener('click',   () => close(true));
    btnCnl.addEventListener('click',  () => close(false));
    backdrop.addEventListener('click', () => close(false));
    document.addEventListener('keydown', (e) => {
        if (modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') close(false);
        if (e.key === 'Enter')  close(true);
    });

    function show(opts) {
        return new Promise((resolve) => {
            pendingResolve = resolve;
            applyTheme(opts.type || 'confirm');
            titleEl.textContent  = opts.title   || 'Confirm';
            msgEl.textContent    = opts.message || '';
            btnOk.textContent    = opts.okText  || 'Confirm';
            btnCnl.textContent   = opts.cancelText || 'Cancel';
            btnCnl.style.display = opts.hideCancel ? 'none' : '';
            open();
        });
    }

    // ============ Toast ============
    const toastContainer = document.getElementById('xToastContainer');
    const TOAST_THEMES = {
        success: { bg: 'bg-green-600',  icon: 'fa-circle-check' },
        error:   { bg: 'bg-red-600',    icon: 'fa-circle-xmark' },
        warning: { bg: 'bg-amber-500',  icon: 'fa-triangle-exclamation' },
        info:    { bg: 'bg-blue-600',   icon: 'fa-circle-info' }
    };

    function toast(message, type = 'success', duration = 3000) {
        const t = TOAST_THEMES[type] || TOAST_THEMES.success;
        const el = document.createElement('div');
        el.className = `pointer-events-auto ${t.bg} text-white px-5 py-4 rounded-xl shadow-2xl
                        flex items-center gap-3 min-w-[280px] max-w-md
                        translate-x-[120%] opacity-0 transition-all duration-300 ease-out`;
        el.innerHTML = `
            <i class="fas ${t.icon} text-xl"></i>
            <p class="font-semibold flex-1">${message.replace(/</g, '&lt;')}</p>
            <button class="hover:opacity-70 transition-opacity"><i class="fas fa-xmark"></i></button>
        `;
        toastContainer.appendChild(el);
        requestAnimationFrame(() => {
            el.classList.remove('translate-x-[120%]', 'opacity-0');
        });
        const dismiss = () => {
            el.classList.add('translate-x-[120%]', 'opacity-0');
            setTimeout(() => el.remove(), 300);
        };
        el.querySelector('button').addEventListener('click', dismiss);
        if (duration > 0) setTimeout(dismiss, duration);
    }

    // ============ Public API ============
    window.xModal = {
        confirm: (opts) => show({
            type: 'confirm',
            title: opts.title || 'Confirmation',
            message: opts.message || 'Are you sure?',
            okText: opts.okText || 'Yes',
            cancelText: opts.cancelText || 'Cancel'
        }),
        danger: (opts) => show({
            type: 'danger',
            title: opts.title || 'Delete?',
            message: opts.message || 'This action cannot be undone.',
            okText: opts.okText || 'Delete',
            cancelText: opts.cancelText || 'Cancel'
        }),
        alert: (opts) => show({
            type: opts.type || 'info',
            title: opts.title || 'Notice',
            message: opts.message || '',
            okText: opts.okText || 'OK',
            hideCancel: true
        }),
        success: (message, title) => show({
            type: 'success',
            title: title || 'Success',
            message, okText: 'OK', hideCancel: true
        }),
        error: (message, title) => show({
            type: 'danger',
            title: title || 'Error',
            message, okText: 'OK', hideCancel: true
        }),
        toast
    };
})();
</script>
