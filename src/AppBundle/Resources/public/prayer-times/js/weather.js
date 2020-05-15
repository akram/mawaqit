/**
 * handle weather
 */
var weather = {
    /**
     * get and display weather
     */
    getWeather: function () {
        $weatherEl = $("#weather");
        $weatherEl.addClass("hidden");
        $.ajax({
            url: $weatherEl.data("remote"),
            headers: {'t': $(".main").data("t")},
            success: function (resp) {
                if (resp && "temperature" in resp) {
                    $weatherEl.removeAttr("class");
                    $weatherEl.addClass(resp.feeling);
                    var icon = resp.icon;
                    var now = new Date();
                    var shuruq = prayer.getCurrentDateForPrayerTime(prayer.getChouroukTime());
                    var maghrib = prayer.getCurrentDateForPrayerTime(prayer.getTimeByIndex(3));
                    if (now.getTime() > shuruq.getTime() && now.getTime() < maghrib.getTime()) {
                        icon = "day-" + icon;
                    } else {
                        // fix night sunny
                        if (icon = 'sunny') {
                            icon = 'clear';
                        }
                        icon = "night-" + icon;
                    }

                    $weatherEl.find("i").attr('class', 'wi wi-' + icon);
                    $weatherEl.find("span").text(resp.temperature);
                }
            }
        });
    },
    initUpdateWeather: function () {
        if (!isLocal && prayer.confData.temperatureEnabled === true) {
            weather.getWeather();
            setInterval(function () {
                weather.getWeather();
            }, prayer.oneMinute * 60);
        }
    }
};
