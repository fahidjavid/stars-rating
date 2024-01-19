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

        } else {
            ratingField.barrating( {
                theme         : 'fontawesome-stars',
                initialRating : 5
            } );

        }

    } );
} )( jQuery );