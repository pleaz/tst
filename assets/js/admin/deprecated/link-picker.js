var wpghLinkPicker;

(function( $ ) {
    $.fn.linkPicker = function() {

        this.each(function() {
            $(this).click(function() {
                wpActiveEditor = true; //we need to override this var as the link dialogue is expecting an actual wp_editor instance
                wpLink.active = $(this).attr('data-target');
                wpLink.open(wpLink.active); //open the link popup

                var active = $( '#' + wpLink.active);

                if ( active.prop('tagName') !== 'TEXTAREA' ) {

                    $('#wp-link-url').val( active.val() );

                }

                return false;
            });
        });

        return this;

    };

    wpghLinkPicker = {

        init: function () {
            $( '.wp-link-form' ).css( 'display', 'none' );
            $( '.link-target' ).css( 'display', 'none' );

            var $body = $('body');
            $body.on('click', '#wp-link-submit', function(event) {
                var linkAtts = wpLink.getAttrs();//the superlinks attributes (href, target) are stored in an object, which can be access via  wpLink.getAttrs()
                var active = $( '#' + wpLink.active );

                if ( active.prop('tagName') === 'TEXTAREA' ) {
                    active.val( active.val() + linkAtts.href + '\n' );//get the href attribute and add to a textfield, or use as you see fit
                } else {
                    active.val(linkAtts.href);//get the href attribute and add to a textfield, or use as you see fit
                }

                wpLink.textarea = $('body'); //to close the link dialogue, it is again expecting an wp_editor instance, so you need to give it something to set focus back to. In this case, I'm using body, but the textfield with the URL would be fine
                wpLink.close();//close the dialogue
                // trap any events
                event.preventDefault ? event.preventDefault() : event.returnValue = false;
                event.stopPropagation();
                return false;
            });
            $body.on('click', '#wp-link-cancel, #wp-link-close', function(event) {
                wpLink.textarea = $('body');
                wpLink.close();
                event.preventDefault ? event.preventDefault() : event.returnValue = false;
                event.stopPropagation();
                return false;
            });
        }
    };
    
    $( function () {
        wpghLinkPicker.init();
    });
}( jQuery ));