/* global prayer */

/**
 * get and display a random hadith from server
 * It will be shwon every 5 min, except in prayer moment
 */
var randomHadith = {
    isVisible: false,
    init: function () {
        if (prayer.confData.randomHadithEnabled) {
            setInterval(function () {
                randomHadith.get();
            }, 4 * prayer.oneMinute);
        }
    },
    /**
     * check if hadith displaying is allowed, 2 conditions
     * 1- not prayer moment
     * 2- not disabling hadith moment
     * @returns {boolean}
     */
    isAllowed: function () {
        if (prayer.isPrayingMoment()) {
            return false;
        }

        if (prayer.isJumuaMoment()) {
            return false;
        }

        if (/^\d\-\d$/.test(prayer.confData.randomHadithIntervalDisabling)) {
            var prayers = prayer.confData.randomHadithIntervalDisabling.split("-");
            var prayer1 = prayer.getTimeByIndex(parseInt(prayers[0]));
            var prayer2 = prayer.getTimeByIndex(parseInt(prayers[1]));

            var prayer1DateTime = prayer.getCurrentDateForPrayerTime(prayer1);
            var prayer2DateTime = prayer.getCurrentDateForPrayerTime(prayer2);
            var date = new Date();
            if (date > prayer1DateTime && date < prayer2DateTime) {
                return false;
            }
        }

        return true;
    },
    get: function () {
        // start hadith after 5 seconds to bypass a display bug
        setTimeout(function () {
            if (!randomHadith.isAllowed()) {
                return;
            }

            if ($(".main").is(":visible") && !messageInfoSlider.messageInfoIsShowing) {

                let $randomHadithEl = $(".random-hadith");
                let hadith = getLocalTTL("hadith");

                if (hadith) {
                    $(".random-hadith .text div").text(hadith);
                    randomHadith.show(randomHadith.setFontSize);
                }

                if (!hadith) {
                    $.ajax({
                        url: $randomHadithEl.data("remote"),
                        headers: {'t': $(".main").data("t")},
                        success: function (resp) {
                            if (resp.text) {
                                hadith = resp.text;
                                $(".random-hadith .text div").text(hadith);
                                // put hadith in cache during 1 hour
                                setLocalTTL("hadith", hadith, 3600000)
                                randomHadith.show(randomHadith.setFontSize);
                            }
                        },
                        error: function () {
                            if ($(".random-hadith .text div").text() !== "") {
                                randomHadith.show();
                            }
                        }
                    });
                }
            }
        }, 5000);
    },
    show: function (callback) {
        prayer.nextPrayerCountdown();
        randomHadith.isVisible = true;
        $(".top-content").fadeOut(0, function () {
            $("footer").hide();
            $(".random-hadith").fadeIn(500);
            if (typeof callback !== 'undefined') {
                callback();
            }
        });

        setTimeout(function () {
            randomHadith.hide();
        }, prayer.oneSecond * 90);

    },
    hide: function () {
        randomHadith.isVisible = false;
        $(".random-hadith").fadeOut(0, function () {
            $("footer").show();
            $(".top-content").fadeIn(500);
        });
    },
    setFontSize: function () {
        var $textContainer = $('.random-hadith .text');
        var $textContainerDiv = $('.random-hadith .text div');
        var defaultSize = 10;
        $textContainerDiv.css('font-size', defaultSize + 'vh');
        while ($textContainerDiv.height() > $textContainer.height() - 30) {
            defaultSize = defaultSize - 0.2;
            $textContainerDiv.css('font-size', (defaultSize) + 'vh');
        }
    }
};
