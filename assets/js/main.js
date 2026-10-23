document.addEventListener('DOMContentLoaded', function () {
    // Confirm dialogs for forms that have data-confirm="message"
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Stop double submissions on forms marked data-once
    document.querySelectorAll('form[data-once]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) { return; }
            setTimeout(function () {
                form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                    b.disabled = true;
                    b.textContent = 'Please wait...';
                });
            }, 0);
        });
    });

    // Mobile nav toggle
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // Photo preview + size check on report / edit forms
    var imageInput = document.getElementById('image');
    var preview = document.getElementById('imagePreview');
    if (imageInput && preview) {
        imageInput.addEventListener('change', function () {
            var file = imageInput.files && imageInput.files[0];
            if (!file) {
                preview.hidden = true;
                return;
            }
            if (file.size > 3 * 1024 * 1024) {
                window.alert('That image is larger than 3 MB. Please choose a smaller one.');
                imageInput.value = '';
                preview.hidden = true;
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }
});