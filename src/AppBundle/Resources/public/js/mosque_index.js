$(".btn-refresh").click(function () {
    $.ajax({
        url: $(this).data('url'),
        success: function () {
            showModal("Demande de rafraîchissement de l'écran effectuée avec succès");
        },
        error: function () {
            showModal("Une erreur est survenue !");
        }
    });
});

$("#advanced-search-btn").click(function (e) {
    e.preventDefault();
    $("#advanced").toggleClass("hidden")
});

$("#country").change(function (e) {
    var self = $(this);
    $.ajax({
        url: self.data("remote").replace('-country-', self.val()),
        dataType: "json",
        success: function (data) {
            $("#city option").remove();
            $("#city").append('<option value="">Ville</option>');
            $.each(data, function (i, city) {
                $("#city").append('<option value="' + city + '">' + city + '</option>');
            })
        }
    });
});


$('.fa-map-marker-alt').click(function (e) {
    var $url = "https://maps.google.com/maps?daddr=";
    var $platform = navigator.platform;

    if (($platform.indexOf("iPhone") != -1) || ($platform.indexOf("iPad") != -1) || ($platform.indexOf("iPod") != -1)) {
        $url = "maps://maps.google.com/maps?daddr=";
    }

    window.open($url + $(this).data('gps') + "&amp;ll=");
});

$(".linkSelector").change(function (e) {
    let link = $(this).parent().find('.link');
    link.addClass('hidden');
    if ($(this).val()) {
        var selected = $(this).find(':selected');
        link.attr('href', selected.data('link'))
        link.text(selected.data('link'));
        link.removeClass('hidden');
    }
});

setTimeout(function () {
    if (getLocalTTL("photo-reminder-modal") === null) {
        $("#photo-reminder-modal").modal({
            backdrop: 'static',
            keyboard: false
        });
        setLocalTTL("photo-reminder-modal", "", 86400000)
    }
}, 1000);