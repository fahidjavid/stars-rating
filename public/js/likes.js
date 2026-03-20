( function ( $ ) {
    $( function () {
        "use strict";

        /**
         * Show a transient feedback message inside the wrap.
         *
         * @param {jQuery} $wrap  The .sr-likes-wrap element.
         * @param {string} msg    Message text.
         * @param {string} type   'success' | 'error'
         */
        function showFeedback( $wrap, msg, type ) {
            var $fb = $wrap.find( '.sr-likes-feedback' );
            $fb.removeClass( 'sr-fb-success sr-fb-error' )
               .addClass( 'sr-fb-' + type )
               .text( msg )
               .stop( true )
               .fadeIn( 200 );

            clearTimeout( $fb.data( 'timer' ) );
            $fb.data( 'timer', setTimeout( function () {
                $fb.fadeOut( 300 );
            }, 3000 ) );
        }

        $( document ).on( 'click', '.sr-vote-btn', function () {

            var $btn  = $( this );
            var $wrap = $btn.closest( '.sr-likes-wrap' );

            // Prevent double-clicks during the AJAX request.
            if ( $wrap.hasClass( 'sr-loading' ) ) {
                return;
            }

            // Prompt login if guests are not allowed.
            if ( 'true' === $wrap.data( 'login-required' ) ) {
                var loginMsg = ( typeof srLikesVars !== 'undefined' && srLikesVars.mustLoginMsg )
                    ? srLikesVars.mustLoginMsg
                    : 'You must be logged in to vote.';

                if ( typeof srLikesVars !== 'undefined' && srLikesVars.loginUrl ) {
                    showFeedback( $wrap, loginMsg, 'error' );
                } else {
                    showFeedback( $wrap, loginMsg, 'error' );
                }
                return;
            }

            var postId = $wrap.data( 'post-id' );
            var nonce  = $wrap.data( 'nonce' );
            var vote   = $btn.data( 'vote' );
            var ajaxUrl = ( typeof srLikesVars !== 'undefined' )
                ? srLikesVars.ajaxUrl
                : '';

            if ( ! ajaxUrl ) {
                return;
            }

            $wrap.addClass( 'sr-loading' );

            $.post(
                ajaxUrl,
                {
                    action  : 'sr_vote',
                    post_id : postId,
                    vote    : vote,
                    nonce   : nonce
                },
                function ( response ) {
                    $wrap.removeClass( 'sr-loading' );

                    if ( ! response.success ) {
                        var errMsg = ( response.data && response.data.message )
                            ? response.data.message
                            : 'Something went wrong. Please try again.';
                        showFeedback( $wrap, errMsg, 'error' );
                        return;
                    }

                    var data        = response.data;
                    var $likeBtn    = $wrap.find( '.sr-like-btn' );
                    var $dislikeBtn = $wrap.find( '.sr-dislike-btn' );

                    // Update counts.
                    $likeBtn.find( '.sr-vote-count' ).text( data.likes );
                    $dislikeBtn.find( '.sr-vote-count' ).text( data.dislikes );

                    // Toggle voted state and aria-pressed.
                    $likeBtn
                        .toggleClass( 'sr-voted', data.user_vote === 'like' )
                        .attr( 'aria-pressed', data.user_vote === 'like' ? 'true' : 'false' );

                    $dislikeBtn
                        .toggleClass( 'sr-voted', data.user_vote === 'dislike' )
                        .attr( 'aria-pressed', data.user_vote === 'dislike' ? 'true' : 'false' );

                    // Brief confirmation feedback.
                    if ( data.user_vote === 'like' ) {
                        showFeedback( $wrap, srLikesVars.thanksMsg || 'Thanks for the feedback!', 'success' );
                    } else if ( data.user_vote === 'dislike' ) {
                        showFeedback( $wrap, srLikesVars.thanksMsg || 'Thanks for the feedback!', 'success' );
                    }
                }
            ).fail( function () {
                $wrap.removeClass( 'sr-loading' );
                showFeedback( $wrap, 'Something went wrong. Please try again.', 'error' );
            } );

        } );

    } );
} )( jQuery );
