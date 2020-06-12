(function ($,frame,email) {

    $( '#email-form' ).on( 'submit', function () {
        $( '.spinner' ).css( 'visibility', 'visible' );

        if ( IsFrame.inFrame() && typeof parent.EmailStep != "undefined" ){
            parent.EmailStep.changesSaved = true;
            parent.EmailStep.newEmailId = email.email_id;
        }
    } );

    $( '#update_and_test' ).on( 'click', function () {
        var test = $( '#test-email' );

        var test_email = prompt( email.send_test_prompt, test.val() );

        if ( test_email ){
            test.attr( 'name', 'test_email' );
            test.val( test_email );
        }
    } );


})(jQuery,IsFrame,Email);