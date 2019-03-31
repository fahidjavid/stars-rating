(function ($) {
    $(window).load(function () {
        "use strict";

        var ratingField = $('#rate-it');

        if (ratingField.hasClass('require-yes')) {
            var parentForm = ratingField.closest('form');
            var submitButton = parentForm.find('input[type="submit"]');
            ratingField.barrating({
                theme: 'fontawesome-stars',
                initialRating: 5,
                onSelect: function (value, text, event) {
                    if (event !== null && event.type === 'click') {
                        console.log("You can go ahead");
                        submitButton.addClass('reviewed');
                    }
                }
            });

            parentForm.on('submit', function () {
                if (!submitButton.hasClass('reviewed')) {
                    alert('Please select a rating.')
                    return false;
                }
            });
        } else {
            ratingField.barrating({
                theme: 'fontawesome-stars',
                initialRating: 5
            });

        }

    });
})(jQuery);