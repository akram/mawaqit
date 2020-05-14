$('#top-buttons .glyphicon-resize-full').click(function () {
    var self = $(this);
    if (self.hasClass('glyphicon-resize-full')) {
        fullscreen();
    } else if (self.hasClass('glyphicon-resize-small')) {
        exitFullscreen();
    }
    fullScreenHandler();
});

function fullScreenHandler() {
    var $fullScreenButton = $('#top-buttons .glyphicon-resize-full');
    $fullScreenButton.removeClass('glyphicon-resize-full');
    $fullScreenButton.removeClass('glyphicon-resize-small');
    if (screen.height === window.innerHeight) {
        $fullScreenButton.addClass('glyphicon-resize-small');
    } else {
        $fullScreenButton.addClass('glyphicon-resize-full');
    }
}

fullScreenHandler();

$(window).on('resize', function () {
    fullScreenHandler();
});