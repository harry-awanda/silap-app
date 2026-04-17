$(document).ready(function () {
    $(".select2").each(function () {
        const $el = $(this);

        // Bungkus seperti forms-selects.js
        if (!$el.parent().hasClass("position-relative")) {
            $el.wrap('<div class="position-relative"></div>');
        }

        $el.select2({
            placeholder: function () {
                return $el.data("placeholder") || "Pilih opsi";
            },
            allowClear: true,
            width: "100%",
            dropdownParent: $el.parent(), // ini kunci anti overflow
        });
    });
});
