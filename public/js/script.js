(function ($) {
    $(window).load(function () {
        "use strict";

        var ratingField = $('#rate-it');

        if (ratingField.hasClass('require-yes')) { // This will be moved over only submit button.
            var container = $('#stars-rating-review');
            var parentForm = ratingField.closest('form');
            var submitButton = parentForm.find('input[type="submit"]');

            ratingField.barrating({
                theme: 'fontawesome-stars',
                initialRating: 5,
                onSelect: function (value, text, event) {

                    console.log(value); // TODO: remove this line.

                    if (event !== null) {

                        submitButton.addClass('reviewed');

                        if(value < 3){
                            submitButton.addClass('lower-rating');
                        } else {
                            submitButton.removeClass('lower-rating');
                        }
                    }
                }
            });

            parentForm.on('submit', function () {
                if (!submitButton.hasClass('reviewed') && container.is(':visible')) {
                    alert('Please select a rating.')
                    return false;
                }

                if(submitButton.hasClass('lower-rating')){
                    alert('we’re sorry you’ve had a bad experience. Before you post your review, feel free to contact us so we can help resolve your issue!');
                    submitButton.removeClass('lower-rating');
                    return false;
                }
            });
        } else {
            ratingField.barrating({
                theme: 'fontawesome-stars',
                initialRating: 5
            });

        }


        // Show the popup and overlay
        function openPopup() {
            $(".overlay, .popup").fadeIn();
        }

        // Close the popup and overlay
        function closePopup() {
            $(".overlay, .popup").fadeOut();
        }

        // Trigger the popup on button click
        // $("#openPopupBtn").on("click", function () {
        // });
        openPopup();

        // Close the popup on "No" button click
        $("#noBtn").on("click", function () {
            closePopup();
            // Do something when "No" is clicked
            console.log("User clicked No");
        });

        // Close the popup on overlay click
        $(".overlay").on("click", function () {
            closePopup();
        });

        // Trigger some action on "Yes" button click
        $("#yesBtn").on("click", function () {
            // Do something when "Yes" is clicked
            console.log("User clicked Yes");
        });

    });
})(jQuery);