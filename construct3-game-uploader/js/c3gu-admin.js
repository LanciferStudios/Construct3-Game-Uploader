// js/c3gu-admin.js
document.addEventListener('DOMContentLoaded', function() {
    const orientationSelect = document.getElementById('orientation');
    const customSizeRows = document.querySelectorAll('.custom-size-row');

    function toggleCustomFields() {
        const isCustom = orientationSelect.value === 'custom';
        customSizeRows.forEach(row => row.style.display = isCustom ? '' : 'none');
    }

    if (orientationSelect) {
        orientationSelect.addEventListener('change', toggleCustomFields);
        toggleCustomFields(); // Initial check
    }
});