// Modern Image Upload Handler
(function() {
    function initImageUpload(uploadAreaId, inputId, previewId, previewImgId, removeBtnId) {
        const uploadArea = document.getElementById(uploadAreaId);
        const fileInput = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const previewImg = document.getElementById(previewImgId);
        const removeBtn = document.getElementById(removeBtnId);

        if (!uploadArea || !fileInput || !preview || !previewImg || !removeBtn) {
            return;
        }

        // Click to upload
        uploadArea.addEventListener('click', (e) => {
            if (e.target !== removeBtn && !removeBtn.contains(e.target)) {
                fileInput.click();
            }
        });

        // File selected
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                handleFile(file);
            }
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                fileInput.files = e.dataTransfer.files;
                handleFile(file);
            } else {
                alert('Please drop an image file');
            }
        });

        // Remove image
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.value = '';
            preview.style.display = 'none';
            uploadArea.querySelector('.image-upload-content').style.display = 'flex';
        });

        function handleFile(file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }

            // Validate file type
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/)) {
                alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.style.display = 'flex';
                uploadArea.querySelector('.image-upload-content').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    // Initialize for room edit
    if (document.getElementById('room-image-upload')) {
        initImageUpload(
            'room-image-upload',
            'room-image-input',
            'room-image-preview',
            'room-image-preview-img',
            'room-image-remove'
        );
    }

    // Initialize for room type edit
    if (document.getElementById('room-type-image-upload')) {
        initImageUpload(
            'room-type-image-upload',
            'room-type-image-input',
            'room-type-image-preview',
            'room-type-image-preview-img',
            'room-type-image-remove'
        );
    }
})();

