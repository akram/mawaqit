$(window).load(function () {
    $('.flexslider').flexslider({
        slideshowSpeed: 4000,
        pauseOnHover: true,
        animation: "slide",
        animationSpeed: 1000,
        directionNav: false
    });
});

$("#find-mosque").autocomplete({
    source: function (request, response) {
        let input = $("#find-mosque");
        $.ajax({
            url: input.data("remote"),
            headers: {'Api-Access-Token': input.data("apiAccessToken")},
            data: {
                word: request.term
            },
            success: function (data) {
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        window.open('https://mawaqit.net/' + lang + '/' + ui.item.slug, '_blank');
    }
});


function isInView(elem) {
    return $(elem).offset().top - $(window).scrollTop() < $(elem).height();
}

$(window).scroll(function () {
    if (isInView('.odometer-parent'))
        setTimeout(function () {
            $(".odometer").each(function (i, elem) {
                $(elem).text($(elem).data("count"));
            });
        }, 1000);
});