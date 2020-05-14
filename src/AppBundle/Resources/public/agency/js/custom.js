$(".copyright-year").text((new Date()).getFullYear());

$("#whatsapp-select").bind("change keyup", function (event) {
    if(!$(this).val()){
        return;
    }

    $.ajax({
        url: $(this).data("remote"),
        headers: {'Api-Access-Token': $(this).data("token")},
        data: {
            country: $(this).val()
        },
        success: function (data) {
            window.location.href = data.web.whatsapp;
        }
    });
});