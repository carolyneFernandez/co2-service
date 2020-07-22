$(document).ready(function () {


    $('.enseigne-field').on('change', function () {
        const $optionSelected = $('option:selected', $(this));
        const adresse = $optionSelected.data('adresse');
        console.log(adresse);
        const $fieldAdresse = $(this).closest('.div-adresse').find('.adresse-field');
        if ($fieldAdresse && $fieldAdresse.data('complete-auto') === 1) {
            $fieldAdresse.html(adresse);
        }
    });

    $('.enseigne-field').change();
});

