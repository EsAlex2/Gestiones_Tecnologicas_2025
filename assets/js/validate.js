
    $(document).ready(function() {
        $('input[name="first_name"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    })

    