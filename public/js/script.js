( function ( $ ) {
    $( window ).load( function () {
        "use strict";

        var ratingField = $( '#rate-it' );

        if ( ratingField.hasClass( 'require-yes' ) ) { // This will be moved over only submit button.
            var container    = $( '#stars-rating-review' );
            var parentForm   = ratingField.closest( 'form' );
            var submitButton = parentForm.find( 'input[type="submit"]' );

            ratingField.barrating( {
                theme         : 'fontawesome-stars',
                initialRating : 5,
                onSelect      : function ( value, text, event ) {

                    if ( event !== null ) {

                        submitButton.addClass( 'reviewed' );

                        if ( ratingField.hasClass( 'negative-alert-enable' ) && value <= ratingField.data( 'threshold' ) ) {
                            submitButton.addClass( 'lower-rating' );
                        } else {
                            submitButton.removeClass( 'lower-rating' );
                        }
                    }
                }
            } );

            parentForm.on( 'submit', function () {
                if ( ! submitButton.hasClass( 'reviewed' ) && container.is( ':visible' ) ) {
                    alert( 'Please select a rating.' )
                    return false;
                }

                if ( submitButton.hasClass( 'lower-rating' ) ) {
                    openPopup();
                    return false;
                }
            } );

            // Show the popup and overlay
            function openPopup() {
                $( ".low-rating-alert-overlay, .low-rating-alert-wrap" ).fadeIn();
            }

            // Close the popup and overlay
            function closePopup() {
                $( ".low-rating-alert-overlay, .low-rating-alert-wrap" ).fadeOut();
            }

            $( "#post-rating" ).on( "click", function () {
                submitButton.removeClass( 'lower-rating' );
                submitButton.trigger( 'click' );
            } );

            $( ".low-rating-alert-overlay" ).on( "click", function () {
                closePopup();
            } );

            $( "#contact-before-rating" ).on( "click", function () {
                console.log( "User clicked contact" );
            } );
        } else {
            ratingField.barrating( {
                theme         : 'fontawesome-stars',
                initialRating : 5
            } );

        }

        // TODO: 3 is selected for the low rating alert.
        // TODO: rating is being posted above 3, do nothing
        // TODO: rating is being posted lower or = 3 then alert
        // TODO: user click on contact us will go to the contact page with given link
        // TODO: user click on post review, review will be posted
        // TODO: user click on the overlay or close button then nothing happens
        // TODO: display rating alert markup only if value is given to alert
        // TODO: improve ids and classes of new markup
        // TODO: why comment fields are so thin
        // TODO: please select a rating should also be a nice popup, can be added in next version
        // TODO: buy me a coffee should be by the buy me coffee service

    } );
} )( jQuery );