// Auto-submit the filter form when any dropdown changes
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.filter-select')
        .forEach(function (el) {
            el.addEventListener('change', function () {
                el.form.submit();
            });
        });
});
