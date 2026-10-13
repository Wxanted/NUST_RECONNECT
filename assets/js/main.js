document.addEventListener('DOMContentLoaded', function () {
    // Confirm dialogs for forms that have data-confirm="message"
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
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

    // Report form: proof question is required for "found" items
    var typeInputs = document.querySelectorAll('input[name="type"][data-proof-toggle]');
    var proofNote = document.getElementById('proofNote');
    if (typeInputs.length && proofNote) {
        var update = function () {
            var checked = document.querySelector('input[name="type"]:checked');
            proofNote.hidden = !(checked && checked.value === 'found');
        };
        typeInputs.forEach(function (r) { r.addEventListener('change', update); });
        update();
    }
});
