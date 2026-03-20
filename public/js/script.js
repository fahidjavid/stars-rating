( function ( $ ) {
    $( function () {
        "use strict";

        var ratingField = $( '#rate-it' );

        if ( ratingField.hasClass( 'require-yes' ) ) {
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
                    showRatingError( container );
                    return false;
                }

                if ( submitButton.hasClass( 'lower-rating' ) ) {
                    openPopup();
                    return false;
                }
            } );

            // Show an inline error below the rating widget
            function showRatingError( container ) {
                var errorId  = 'sr-rating-error-msg';
                var errorMsg = ( typeof srRatingVars !== 'undefined' )
                    ? srRatingVars.requireMsg
                    : 'Please select a star rating before submitting your review.';

                if ( ! $( '#' + errorId ).length ) {
                    $( '<p id="' + errorId + '" class="sr-rating-error" role="alert"></p>' )
                        .text( errorMsg )
                        .insertAfter( container );
                }

                $( '#' + errorId ).stop( true ).fadeIn();
            }

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
