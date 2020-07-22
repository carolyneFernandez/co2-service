import flatpickr from "flatpickr";

const French = require("flatpickr/dist/l10n/fr.js").default.fr;
// or import { Russian } from "flatpickr/dist/l10n/ru.js"
flatpickr.localize(French);

export const optionsDateTime = {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    altInput: true,
    altInputClass: "form-control",
    altFormat: "d/m/Y \\à H\\hi",
    readonly: true,
    time_24hr: true,
    // weekNumbers: true,
    disableMobile: false,
    allowInput: true,
    // clearBtn: true,
    timeZone: 'Europe/Paris',

    utc: true,
    onReady: function (dateObj, dateStr, instance) {
        var $cal = $(instance.calendarContainer);
        if ($cal.find('.flatpickr-clear').length < 1) {
            $cal.append('<div class="flatpickr-clear btn btn-outline-primary btn-sm">Supprimer</div>');
            $cal.find('.flatpickr-clear').on('click', function () {
                instance.clear();
                instance.close();
            });
        }
    },


};

export const optionsDate = {
    enableTime: false,
    dateFormat: "Y-m-d",
    altInput: true,
    altInputClass: "form-control",
    altFormat: "d/m/Y",
    // weekNumbers: true,
    disableMobile: false,

    readonly: true,
    time_24hr: true,

    // clearBtn: true,

    onReady: function (dateObj, dateStr, instance) {
        var $cal = $(instance.calendarContainer);
        if ($cal.find('.flatpickr-clear').length < 1) {
            $cal.append('<div class="flatpickr-clear btn btn-outline-primary btn-sm">Supprimer</div>');
            $cal.find('.flatpickr-clear').on('click', function () {
                instance.clear();
                instance.close();
            });
        }
    },

    allowInput: true,
    timeZone: 'Europe/Paris',
    utc: true,


};

export const optionsTime = {
    noCalendar: true,
    enableTime: true,
    dateFormat: "H:i",
    // clearBtn: true,
    altInput: true,
    altInputClass: "form-control",
    altFormat: "H\\hi",
    readonly: true,
    time_24hr: true,
    allowInput: true,
    disableMobile: false,
    utc: true,
    timeZone: 'Europe/Paris',

    onReady: function (dateObj, dateStr, instance) {
        var $cal = $(instance.calendarContainer);
        if ($cal.find('.flatpickr-clear').length < 1) {
            $cal.append('<div class="flatpickr-clear btn btn-outline-primary btn-sm">Supprimer</div>');
            $cal.find('.flatpickr-clear').on('click', function () {
                instance.clear();
                instance.close();
            });
        }
    },
    locale: {}

};

$(document).ready(function () {


    setFlatpickr();


    $('.flatpickr-datetime, .flatpickr-date, .flatpickr-time').on('change', function () {
        let now = $(this).val();
        let options = {};

        let $debut = $($(this).data('datedebut'));
        let $fin = $($(this).data('datefin'));

        if ($debut.length > 0) {
            const val = $debut.val();
            if ($debut.hasClass('flatpickr-datetime')) {
                options = $.extend({}, optionsDateTime);
            } else if ($debut.hasClass('flatpickr-date')) {
                options = $.extend({}, optionsDate);
            } else {
                options = $.extend({}, optionsTime);
            }

            if ($debut.data('max_date') != null) {
                if ($debut.data('max_date') < now) {
                    now = $debut.data('max_date');
                }
            }
            if ($debut.data('min_date') != null) {
                options.minDate = $fin.data('min_date');

            }

            options.maxDate = now;
            options.defaultDate = [val];
            options.disableMobile = false;

            $debut.flatpickr(options);

        }
        if ($fin.length > 0) {
            const val = $fin.val();


            if ($fin.hasClass('flatpickr-datetime')) {
                options = $.extend({}, optionsDateTime);
            } else if ($fin.hasClass('flatpickr-date')) {
                options = $.extend({}, optionsDate);
            } else {
                options = $.extend({}, optionsTime);
            }

            if ($fin.data('min_date') != null) {
                if ($fin.data('min_date') > now) {
                    now = $fin.data('min_date');
                }
            }
            if ($fin.data('max_date') != null) {
                options.maxDate = $fin.data('max_date');
            }

            options.minDate = now;
            options.date = val;
            options.defaultDate = [val];
            options.disableMobile = false;


            $fin.flatpickr(options);

        }

    });


    $('.flatpickr-datetime').each(function () {
        const date = $(this).val();
        let options = $.extend({}, optionsDateTime);
        let change = false;
        if ($(this).data('min_date') != null) {
            change = true;
            const minDate = new Date($(this).data('min_date'));
            options.minDate = minDate;
        }
        if ($(this).data('max_date') != null) {
            change = true;
            const maxDate = new Date($(this).data('max_date'));
            options.maxDate = maxDate;
        }
        options.defaultDate = [date];
        options.disableMobile = false;

        if (change) $(this).flatpickr(options);

    });

    $('.flatpickr-date').each(function () {
        const date = $(this).val();

        let options = $.extend({}, optionsDate);
        let change = false;
        if ($(this).data('min_date') != null) {
            change = true;
            const minDate = new Date($(this).data('min_date'));
            options.minDate = minDate;
        }
        if ($(this).data('max_date') != null) {
            change = true;
            const maxDate = new Date($(this).data('max_date'));
            options.maxDate = maxDate;
        }
        options.defaultDate = [date];
        options.disableMobile = false;


        if (change) $(this).flatpickr(options);
    });

    $('.flatpickr-time').each(function () {
        const date = $(this).val();

        let options = $.extend({}, optionsTime);
        let change = false;
        if ($(this).data('min_date') != null) {
            change = true;
            const minDate = new Date($(this).data('min_date'));
            options.minDate = minDate;
        }
        if ($(this).data('max_date') != null) {
            change = true;
            const maxDate = new Date($(this).data('max_date'));
            options.maxDate = maxDate;
        }
        options.defaultDate = [date];

        if (change) $(this).flatpickr(options);
    });


});

export function setFlatpickr() {
    flatpickr(".flatpickr-datetime", optionsDateTime);


    flatpickr(".flatpickr-date", optionsDate);


    flatpickr(".flatpickr-time", optionsTime);
}