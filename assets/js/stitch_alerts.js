/**
 * StitchAlerts - Custom SweetAlert2 Wrapper for KKYF TAMS
 * Matches the "Stitch" Design System
 */

const StitchAlert = {
    // Base Configuration matching Design System
    baseConfig: {
        confirmButtonColor: '#00BD06', // Primary Green
        cancelButtonColor: '#f1f5f9', // Slate-100 (will style text via custom class if needed, or stick to simple)
        buttonsStyling: true,
        customClass: {
            popup: 'rounded-2xl shadow-2xl font-sans', // Rounded cards, heavy shadow
            title: 'text-xl font-bold text-slate-800',
            htmlContainer: 'text-slate-600',
            confirmButton: 'bg-[#00BD06] text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-green-500/20 hover:bg-[#009605] transition-all mx-2',
            cancelButton: 'bg-slate-100 text-slate-600 font-bold py-3 px-6 rounded-xl hover:bg-slate-200 transition-all mx-2'
        }
    },

    async showSuccess(title, message) {
        return Swal.fire({
            ...this.baseConfig,
            icon: 'success',
            title: title,
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    },

    async showError(title, message) {
        return Swal.fire({
            ...this.baseConfig,
            icon: 'error',
            title: title || 'Oops...',
            text: message || 'Something went wrong!',
            confirmButtonText: 'Okay'
        });
    },

    async confirmAction(title, message, confirmText = 'Yes, do it!', isDestructive = false) {
        const config = { ...this.baseConfig };

        if (isDestructive) {
            config.customClass = {
                ...config.customClass,
                confirmButton: 'bg-red-500 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-red-500/20 hover:bg-red-600 transition-all mx-2'
            };
        }

        const result = await Swal.fire({
            ...config,
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel',
            reverseButtons: true
        });

        return result.isConfirmed;
    }
};
