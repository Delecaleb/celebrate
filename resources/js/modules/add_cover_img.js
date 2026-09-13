document.addEventListener('DOMContentLoaded', () => {

    const input = document.getElementById('coverUpload');

    if (!input) return;

    input.addEventListener('change', async function () {

        if (this.files.length === 0) return;

        // Check maximum files limit
        if (this.files.length > 4) {
            alert('You can only select up to 4 images.');
            this.value = ''; // clear selection
            return;
        }

        // Refuse an oversized photo before uploading anything: four large
        // files are a long wait just to be told no. The limit comes from the
        // server, so this can never disagree with the validation rule.
        const maxBytes = window.CelebrationConfig?.imageMaxBytes ?? 10 * 1024 * 1024;
        const maxLabel = window.CelebrationConfig?.imageMaxLabel ?? '10MB';
        const tooBig   = Array.from(this.files).find((file) => file.size > maxBytes);

        if (tooBig) {
            alert(`"${tooBig.name}" is too large. Each photo must be ${maxLabel} or smaller.`);
            this.value = '';
            return;
        }

        const coverContainer = document.querySelector('.celebration-cover');
        
        // Show loader overlay
        let loader = document.createElement('div');
        loader.className = 'absolute inset-0 bg-black/60 backdrop-blur-sm z-40 flex flex-col items-center justify-center text-white text-sm font-semibold rounded-3xl';
        loader.innerHTML = `
            <svg class="animate-spin h-8 w-8 text-white mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Uploading images...</span>
        `;
        if (coverContainer) {
            coverContainer.appendChild(loader);
        }

        const formData = new FormData();
        
        if (this.files.length > 1) {
            for (let i = 0; i < this.files.length; i++) {
                formData.append('cover_photos[]', this.files[i]);
            }
        } else {
            formData.append('cover_photo', this.files[0]);
        }

        formData.append('_token', window.CelebrationConfig.csrfToken);

        try {

            // Accept: application/json is what makes a refused upload come back
            // as a readable 422 message. Without it Laravel redirects, the HTML
            // fails to parse, and all anyone saw was "Server error".
            const response = await fetch(
                `/celebrant/${window.CelebrationConfig.celebrationId}/cover-photo`,
                {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                }
            );

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                if (loader) loader.remove();
                alert(data.message || 'Upload failed');
                this.value = '';
            }

        } catch (error) {
            if (loader) loader.remove();
            console.error(error);
            alert('Server error');
            this.value = '';
        }

    });

});