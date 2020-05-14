/**
 * Messages slider class
 * @type {Object}
 */
var messageInfoSlider = {
    slider: $("#slider"),
    nbOfSlides: null,
    interval: null,
    /**
     *  run message slider
     */
    run: function () {
        var screenWidth = $(window).width();
        this.nbSlides = $('#slider li').length;
        $('#slider li').width(screenWidth);

        setTimeout(function () {
            messageInfoSlider.setFontSize();
        }, 300);

        var sliderUlWidth = this.nbSlides * screenWidth;
        $('#slider ul').css({width: sliderUlWidth});

        this.updateInterval(timeToDisplayMessage * 1000);
    },

    updateInterval: function (interval) {
        clearInterval(messageInfoSlider.interval);
        messageInfoSlider.interval = setInterval(function () {
            if (messageInfoSlider.nbSlides > 1) {
                messageInfoSlider.moveRight();
            }
        }, interval);
    },
    /**
     * Get message from server
     */
    moveRight: function () {
        let screenWidth = $(window).width();
        $('#slider ul').animate({
            left: -screenWidth
        }, 200, function () {
            $('#slider li:first-child').appendTo('#slider ul');
            $('#slider ul').css('left', '');
        });

        this.handleVideo()
    },
    handleVideo: function (slide) {
        // handle video
        $('#slider li').each(function (i, li) {
            if (messageInfoSlider.isYoutubeSlide(li)) {
                let player = $(li).find("iframe").get(0);
                messageInfoSlider.controleYoutube(player, "stop")
                messageInfoSlider.updateInterval(timeToDisplayMessage * 1000);
                if (messageInfoSlider.isVisibleSlide(li)) {
                    messageInfoSlider.updateInterval($(player).data("duration") * 1000);
                    messageInfoSlider.controleYoutube(player, "play")
                }
            }
        });
    },
    isYoutubeSlide: function (slide) {
        return $(slide).find("iframe").length === 1;

    },
    isVisibleSlide: function (slide) {
        return $(slide).offset().left === $(window).width();

    },
    postMessageToYoutubePlayer: function (player, command) {
        player.contentWindow.postMessage(JSON.stringify(command), "*");
    },
    controleYoutube: function (player, control) {
        if (player === undefined) {
            return;
        }
        switch (control) {
            case "play":
                this.postMessageToYoutubePlayer(player, {
                    event: "command",
                    func: "playVideo"
                });
                this.postMessageToYoutubePlayer(player, {
                    event: "command",
                    func: "seekTo",
                    args: [0, true]
                });
                break;
            case "stop":
                this.postMessageToYoutubePlayer(player, {
                    "event": "command",
                    "func": "stopVideo"
                });
                break;
        }
    },
    setFontSize: function () {
        $('#slider .text > div').each(function (i, slide) {
            fixFontSize(slide, 100);
        });
    }
};

messageInfoSlider.run();

// reload if updated
setInterval(function () {
    $.ajax({
        url: $("#slider").data("remote") + "?lastUpdatedDate=" + lastUpdated,
        success: function (resp) {
            if (resp.hasBeenUpdated === true) {
                reloadIfConnected();
            }
        }
    });
}, 3 * 60000);

// reload at 2 o'clock
setInterval(function () {
    let date = new Date();
    if (date.getHours() === 2) {
        reloadIfConnected();
    }
}, 3600000);

setInterval(function () {
    let date = dateTime.getCurrentDate(locale, "short", "2-digit", "2-digit", "2-digit").firstCapitalize();
    $(".date").text(date);
    $(".time").text(dateTime.getCurrentTime());
}, 1000);
