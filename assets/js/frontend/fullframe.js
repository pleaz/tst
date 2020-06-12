(function ($) {

    function initAllFrames() {
        var iFrames = $('iframe');

        var offset = 0;

        function iResize() {
            for (var i = 0, j = iFrames.length; i < j; i++) {
                // console.log($(iFrames[i].contentWindow.document.body.offsetHeight));
                // iFrames[i].style.height = $(iFrames[i].contentWindow.document.body.offsetHeight) ;

                //replaced function with assignment in case it fix it..
                iFrames[i].style.height = $(iFrames[i].contentWindow.document.body.offsetHeight)+"px";
                // iFrames[i].height($(iFrames[i].contentWindow.document).height());
            }
        }

        if ($.browser.safari || $.browser.opera) {

            iFrames.load(function () {
                setTimeout(iResize, 0);
            });

            for (var i = 0, j = iFrames.length; i < j; i++) {
                var iSource = iFrames[i].src;
                iFrames[i].src = '';
                iFrames[i].src = iSource;
            }

        } else {
            iFrames.load(function () {
                $(this).height($(this.contentWindow.document).height());
            });
        }

        // console.log( iFrames );
    }

    function addEvent(event, callback) {
        if (!window.addEventListener) { // This listener will not be valid in < IE9
            window.attachEvent("on" + event, callback);
        } else { // For all other browsers other than < IE9
            window.addEventListener(event, callback, false);
        }
    }

    function resizeAllFrames() {
        var iFrames = $('iframe');
        for (var i = 0; i < iFrames.length; i++) {
            var ifrm = iFrames[i];
            var $ifrm = $(ifrm);
            $ifrm.attr('id', 'frame-' + (i + 1));
            var height = ifrm.contentWindow.postMessage({action: 'getFrameSize', id: $ifrm.attr('id')}, "*");
        }
    }

    function receiveMessage(event) {
        // console.log( event.data );
        resizeFrame(event.data);
    }

    function resizeFrame(data) {
        if (data.height) {
            var f = $('#' + data.id);
            if (f) {
                f.height(data.height);
                f.width(data.width);
            }
        }
    }

    addEvent('message', receiveMessage);
    addEvent('resize', resizeAllFrames);
    addEvent('load', resizeAllFrames);

    $(initAllFrames);

})(jQuery);