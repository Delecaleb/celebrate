function handleImagePreview(fileInput, previewContainer, previewImage, removeButton) {
    
    // 1. Listen for when a user selects a file
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            // When the file is finished loading, update the image src and show the container
            reader.addEventListener('load', function() {
                previewImage.setAttribute('src', this.result);
                previewContainer.classList.remove('hidden'); // Assuming you use 'hidden' to hide it initially
            });
            
            reader.readAsDataURL(file);
        }
    });

    // 2. Listen for when the user clicks the remove button
    removeButton.addEventListener('click', function() {
        fileInput.value = ''; // Clear the file input
        previewImage.setAttribute('src', ''); // Clear image source
        previewContainer.classList.add('hidden'); // Hide the container again
    });
}

// How you would call it:
handleImagePreview(
    document.getElementById('image'),
    document.getElementById('image-preview'),
    document.querySelector('#image-preview img'),
    document.querySelector('#image-preview .remove-image')
);