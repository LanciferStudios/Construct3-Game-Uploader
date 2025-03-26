document.addEventListener('DOMContentLoaded', function() {
    var buttons = document.querySelectorAll('.c3gu-fullscreen-btn');
    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            var iframeId = button.getAttribute('data-iframe-id');
            var iframe = document.getElementById(iframeId);
            
            if (iframe) {
                // Request fullscreen for the iframe
                if (iframe.requestFullscreen) {
                    iframe.requestFullscreen();
                } else if (iframe.mozRequestFullScreen) { // Firefox
                    iframe.mozRequestFullScreen();
                } else if (iframe.webkitRequestFullscreen) { // Chrome, Safari, Opera
                    iframe.webkitRequestFullscreen();
                } else if (iframe.msRequestFullscreen) { // IE/Edge
                    iframe.msRequestFullscreen();
                }
            }
        });
    });
});