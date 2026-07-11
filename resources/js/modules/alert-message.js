function showAlert(message, type = 'success') {
    
    const typeStyles = {
        success: 'bg-emerald-500',
        error:   'bg-rose-500',
        warning: 'bg-amber-500',
        info:    'bg-sky-500'
    };
    const bgClass = typeStyles[type] || typeStyles.info;
    // 1. Check if the alert container container already exists, if not, create it
    let alertWrapper = document.getElementById('global-alert-wrapper');
    if (!alertWrapper) {
        alertWrapper = document.createElement('div');
        alertWrapper.id = 'global-alert-wrapper';
        // This container sits fixed at the top right, managing the vertical flow of alerts
        alertWrapper.className = 'fixed top-14 right-4 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(alertWrapper);
    }

    // 2. Create the individual alert element
    const alertContainer = document.createElement('div');
    
    // Note: Removed 'fixed top-14 right-4' and replaced with 'pointer-events-auto' 
    // so users can click through the empty spaces of the wrapper container.
    alertContainer.className = `pointer-events-auto max-w-sm min-w-[200px] px-4 py-3 rounded shadow-xl text-white font-medium transform transition-all duration-300 opacity-0 translate-y-[-10px] ${bgClass}`;
    
    alertContainer.textContent = message;
    
    // 3. Append the alert to our wrapper instead of document.body
    alertWrapper.appendChild(alertContainer);

    // 4. Trigger entry animation
    requestAnimationFrame(() => {
        alertContainer.classList.remove('opacity-0', 'translate-y-[-10px]');
    });

    // 5. Smoothly fade out and remove after 4 seconds
    setTimeout(() => {
        alertContainer.classList.add('opacity-0', 'translate-y-[-10px]');
        
        alertContainer.addEventListener('transitionend', () => {
            alertContainer.remove();
            
            // Clean up the wrapper from the DOM if it's completely empty
            if (alertWrapper.children.length === 0) {
                alertWrapper.remove();
            }
        });
    }, 4000);
}

window.showAlert = showAlert;
