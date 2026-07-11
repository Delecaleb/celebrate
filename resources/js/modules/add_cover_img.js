document.addEventListener('DOMContentLoaded', () => {

    const input = document.getElementById('coverUpload');

    if (!input) return;

    input.addEventListener('change', async function () {

        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('cover_photo', file);
        formData.append('_token', window.CelebrationConfig.csrfToken);

        try {

            const response = await fetch(
                `/celebrant/${window.CelebrationConfig.celebrationId}/cover-photo`,
                {
                    method: 'POST',
                    body: formData
                }
            );

            const data = await response.json();

            if (data.success) {

                // simple + reliable UX
                window.location.reload();

            } else {
                alert(data.message || 'Upload failed');
            }

        } catch (error) {
            console.error(error);
            alert('Server error');
        }

    });

});