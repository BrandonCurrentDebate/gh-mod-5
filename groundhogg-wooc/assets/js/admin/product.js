var ProductStep = {};

(function ($,step) {
    $.extend( step, {

        step: null,

        init: function(){

            var self = this;

            $( document ).on( 'change', '.condition-select', function ( e ) {
                var $condition = $(this);

                self.step = $condition.closest('.postbox' );
                self.step.find( '.condition' ).addClass( 'hidden' );

                if ( $condition.val() !== 'any' ){
                    self.step.find( '.' + $condition.val() + '_select' ).removeClass( 'hidden' );
                }

            } );

        },
    } );

    $( function () {
        step.init()
    } );

})( jQuery, ProductStep );