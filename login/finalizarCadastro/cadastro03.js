const profilePicInput = document.getElementById('profilePic');
const imagePreview = document.getElementById('imagePreview');
const previewContainer = document.getElementById('previewContainer');
const placeholderText = document.getElementById('placeholderText');
profilePicInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
            placeholderText.style.display = 'none';
            previewContainer.style.borderStyle = 'solid';
        }
        reader.readAsDataURL(file);
    } else {
        imagePreview.src = '';
        imagePreview.style.display = 'none';
        placeholderText.style.display = 'block';
        previewContainer.style.borderStyle = 'dashed';
    }
});