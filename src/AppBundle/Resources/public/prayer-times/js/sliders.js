/**
 * Douaa slider class
 * @type {Object}
 */
var douaaSlider = {
    oneDouaaShowingTime: 20000,
    /**
     * it saves html (ul,li)
     * @type String
     */
    sliderHtmlContent: '',
    /**
     *  init douaa after prayer slider
     */
    init: function () {
        // if mobile device ignore douaa slider
        if (isMobile) {
            return;
        }

        var screenWidth = $(window).width();
        var ul = $('.adhkar-after-prayer ul');
        var li = ul.children('li');

        li.width(screenWidth);
        var slideCount = li.length;
        var sliderUlWidth = slideCount * screenWidth;
        ul.css({width: sliderUlWidth});
        //save html slider
        this.sliderHtmlContent = $('.adhkar-after-prayer').html();
    },
    /**
     * If enabled show douaa after prayer for 5 minutes
     * @param {Number} currentTimeIndex
     */
    show: function (currentTimeIndex) {
        // if jumua and mosque type
        if (prayer.isJumua(currentTimeIndex) && prayer.isMosque) {
            return;
        }

        if (prayer.confData.duaAfterPrayerEnabled) {
            $("#black-screen, .main").hide();
            $(".adhkar-after-prayer").show();
            douaaSlider.setFontSize();

            var douaaInterval = setInterval(function () {
                douaaSlider.moveRight();
            }, douaaSlider.oneDouaaShowingTime);

            setTimeout(function () {
                clearInterval(douaaInterval);
                $(".adhkar-after-prayer").fadeOut(0, function () {
                    $(".main").fadeIn(500);
                });

                // show messages if exist after 10 sec after duaa
                setTimeout(function () {
                    messageInfoSlider.run();
                }, 10 * prayer.oneSecond);

            }, douaaSlider.getTimeForShow());
        } else {
            $("#black-screen").fadeOut(0, function () {
                $(".main").fadeIn(500);
            });
            setTimeout(function () {
                // no douaa, show messages if exist after 2 min after prayer
                messageInfoSlider.run();
            }, 2 * prayer.oneMinute);
        }
    },
    /**
     * Number of seconds to show all douaa
     * @returns {Number}
     */
    getTimeForShow: function () {
        return ($('.adhkar-after-prayer li').length * douaaSlider.oneDouaaShowingTime);
    },

    moveRight: function () {
        var screenWidth = $(window).width();
        var ul = $('.adhkar-after-prayer ul');
        ul.not(':animated').prepend($('li:last-child', ul))
            .css({left: -screenWidth})
            .animate({left: 0}, 200);
    },
    setFontSize: function () {
        $('.duaa li > div').each(function (i, slide) {
            fixFontSize(slide);
        });
    }
};

/**
 * Messages slider class
 * @type {Object}
 */
var messageInfoSlider = {
    messageInfoIsShowing: false,
    /**
     * it saves html (ul,li)
     * @type String
     */
    sliderHtmlContent: '',
    /**
     * Cron handling message info showing
     * The messages will be shown
     *  - 5 before every adhan
     *  - 5 before jumu`a
     *  - At defined time
     */
    initCronMessageInfo: function () {
        setInterval(function () {
            if (prayer.isPrayingMoment()) {
                return false;
            }

            if (messageInfoSlider.messageInfoIsShowing === false) {
                messageInfoSlider.run();
            }
        }, prayer.oneMinute * 9);
    },
    /**
     *  run message slider
     */
    run: function () {
        if (!$(".sub-main").is(":visible")) {
            return;
        }

        if (randomHadith.isVisible) {
            return;
        }

        var nbSlides = $('.message-slider li').length;

        if (nbSlides === 0) {
            return;
        }

        var screenWidth = $(window).width();

        $('.message-slider li').width(screenWidth);

        var sliderUlWidth = nbSlides * screenWidth;
        $('.message-slider ul').css({width: sliderUlWidth});

        //save html slider
        messageInfoSlider.sliderHtmlContent = $('.message-slider .messageContent').html();

        $(".sub-main").hide();
        $(".message-slider").show();
        messageInfoSlider.setFontSize();
        messageInfoSlider.messageInfoIsShowing = true;

        var interval = setInterval(function () {
            messageInfoSlider.moveRight();
        }, prayer.confData.timeToDisplayMessage * 1000);

        setTimeout(function () {
            clearInterval(interval);
            $(".message-slider").fadeOut(0, function () {
                $(".sub-main").fadeIn(500);
            });
            messageInfoSlider.messageInfoIsShowing = false;

            // sort message
            $(".message-slider li").sort(function (a, b) {
                return ($(a).data('position')) < ($(b).data('position')) ? -1 : 1;
            }).appendTo('.message-slider ul');

        }, (nbSlides * prayer.confData.timeToDisplayMessage * 1000) - 1000);
    },

    moveRight: function () {
        var screenWidth = $(window).width();
        $('.message-slider ul').animate({
            left: -screenWidth
        }, 200, function () {
            $('.message-slider li:first-child').appendTo('.message-slider ul');
            $('.message-slider ul').css('left', '');
        });
    },
    setFontSize: function () {
        $('.message-slider .text > div').each(function (i, slide) {
            fixFontSize(slide);
        });
    }
};