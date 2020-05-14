$(".iframe-button").click(function () {
    let iframeW = '<iframe src="' + $(this).data('urlw') + '" frameborder="0" scrolling="no" class="widget"></iframe>'
    let iframeM = '<iframe src="' + $(this).data('urlm') + '" frameborder="0" scrolling="no" class="mobile"></iframe>'
    $('#iframe-modal #iframe-w-text').text(iframeW);
    $('#iframe-modal #iframe-w-html').html(iframeW);
    $('#iframe-modal #iframe-m-text').text(iframeM);
    $('#iframe-modal #iframe-m-html').html(iframeM);
    $('#iframe-modal').modal();
});