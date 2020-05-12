/* global dateTime */
/* global douaaSlider */
/* global lang */
/* global confData */

/**
 * Class handling prayers
 * @author ibrahim.zehhaf@gmail.com
 * @type {object}
 */

var prayer = {
    /**
     * prayer times
     * @type Array
     */
    times: [],
    /**
     * One minute in milliseconds
     * @type Integer
     */
    oneMinute: 60000,
    /**
     * One second in milliseconds
     * @type Integer
     */
    oneSecond: 1000,
    /**
     * @type Json
     */
    isMosque: isMosque,
    confData: confData,
    /**
     * load all data
     */
    loadData: function () {
        this.loadTimes();

        // if current time > ichaa time + nextPrayerHilightWait minutes we load tomorrow times
        var date = new Date();
        if (date.getHours() !== 0) {
            var ichaaDateTime = this.getCurrentDateForPrayerTime(this.getIshaTime());
            if (ichaaDateTime.getHours() !== 0) {
                let ishaPrayerWaiting = prayer.getWaitingByIndex(4) + 5;
                ichaaDateTime.setMinutes(ichaaDateTime.getMinutes() + ishaPrayerWaiting);
                if (date > ichaaDateTime) {
                    this.loadTimes(true);
                }
            }
        }
    },
    /**
     * check for update every 2 minute
     */
    initUpdateConfData: function () {
        if (isLocal) {
            return;
        }

        let frequency = 5;
        if(!isMosque)
        {
            frequency = 10;
        }

        var remote = $(".main").data("remote");
        setInterval(function () {
            $.ajax({
                url: remote + "?lastUpdatedDate=" + lastUpdated,
                success: function (resp) {
                    if (resp.hasBeenUpdated === true) {
                        // check if screen page is ok (http status = 200 )
                        $.ajax({
                            url: location.href,
                            method: 'HEAD',
                            success: function (resp) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        }, prayer.oneMinute * frequency);
    },
    /**
     * load prayer times
     * if sourceCalcul = csv we load from csv file
     * else we load from PrayTimes() function
     * @param {boolean} tomorrow if true we load tomorrow time, otherxise we load today times
     */
    loadTimes: function (tomorrow) {
        this.loadTimesFromCalendar(tomorrow);
    },
    /**
     * @param {boolean} tomorrow
     * @returns {Array}
     */
    loadTimesFromCalendar: function (tomorrow) {

        var month = dateTime.getCurrentMonth();
        var day = dateTime.getCurrentDay();
        if (typeof tomorrow === 'boolean' && tomorrow === true) {
            month = dateTime.getTomorrowMonth();
            day = dateTime.getTomorrowDay();
        }
        this.times = prayer.confData.calendar[month][day];

    },
    /**
     * get today prayer times, array of only five prayer times
     * @param {boolean} tomorrow
     * @returns {Array}
     */
    getTimes: function () {
        return [this.times[0], this.times[2], this.times[3], this.times[4], this.times[5]];
    },
    getTimeByIndex: function (index) {
        return this.getTimes()[index];
    },
    getWaitingByIndex: function (index) {
        var waiting = this.getWaitingTimes()[index];
        // if waiting fixed to < 2 min we adjust it to 2 min for adhan and dua`a
        if (waiting <= 2) {
            waiting = 2;
            if (!prayer.isMosque){
                waiting = 4;
            }
        }
        return waiting;
    },
    /**
     * get prayer waiting taimes
     * @returns {Array}
     */
    waitings: [],
    getWaitingTimes: function () {

        if (prayer.waitings.length !== 0) {
            return prayer.waitings;
        }

        prayer.waitings = this.confData.iqama;
        var prayerTimes = prayer.getTimes();

        var prayerTime, iqamaTime, month, day, fixedIqama;
        $.each(prayer.waitings, function (i, wait) {
            fixedIqama = null;
            prayerTime = prayer.getCurrentDateForPrayerTime(prayerTimes[i]);

            month = dateTime.getCurrentMonth();
            day = dateTime.getCurrentDay();

            if (prayer.confData.fixedIqama[i] !== "") {
                fixedIqama = prayer.confData.fixedIqama[i];
                iqamaTime = prayer.getCurrentDateForPrayerTime(fixedIqama);
                if (iqamaTime.getTime() > prayerTime.getTime()) {
                    prayer.waitings[i] = Math.floor((Math.abs(iqamaTime - prayerTime) / 1000) / 60);
                }
            }
        });

        var ishaDate = this.getCurrentDateForPrayerTime(this.getIshaTime());
        if (this.confData.maximumIshaTimeForNoWaiting != null && this.confData.maximumIshaTimeForNoWaiting.matchTime()) {
            var maximumIshaTimeForNoWaitingDate = this.getCurrentDateForPrayerTime(this.confData.maximumIshaTimeForNoWaiting);
            if (ishaDate.getHours() === 0 || ishaDate >= maximumIshaTimeForNoWaitingDate) {
                prayer.waitings[4] = 0;
            }
        }
        return prayer.waitings;
    },
    /**
     * handle next prayer countdown
     */
    nextPrayerCountdown: function () {
        $(".next-prayer").show();
        var prayerDateTime, pattern;
        var date = new Date();
        // by default we countdwon the next day fajr
        var tomorrowFajrDate = prayer.getCurrentDateForPrayerTime(prayer.getTimeByIndex(0));
        tomorrowFajrDate.setDate(tomorrowFajrDate.getDate() + 1);
        var countDownDate = tomorrowFajrDate;

        $.each(prayer.getTimes(), function (index, time) {
            // handle jumua
            if (prayer.isJumua(index)) {
                time = prayer.getJumuaTime();
            }

            prayerDateTime = prayer.getCurrentDateForPrayerTime(time);

            if (prayerDateTime.getHours() !== 0 && date < prayerDateTime) {
                countDownDate = prayerDateTime;
                return false;
            }
        });

        $(".next-prayer .countdown").countdown(countDownDate, function (event) {
            pattern = '%H:%M';
            if (event.offset.hours === 0 && event.offset.minutes === 0) {
                pattern = '%H:%M:%S'
                if (event.offset.seconds === 0) {
                    $(".next-prayer").hide();
                    return;
                }
            }
            $(this).text(event.strftime(pattern));
        });
    },
    formatTime: function (time) {
        if (time) {
            var timeDisplayFormat = prayer.confData.timeDisplayFormat;
            return dateTime.formatTime(time, timeDisplayFormat);
        }
    },
    /**
     * get current date object for given prayer time
     * @param {String} time
     * @returns {Date}
     */
    getCurrentDateForPrayerTime: function (time) {
        var date = new Date();
        time = time.split(':');
        date.setHours(time[0]);
        date.setMinutes(time[1]);
        date.setSeconds(0);
        return date;
    },
    /**
     * get Ichaa time, if ichaa is <= then 19:50 then return 19:50
     * @returns {String}
     */
    getIshaTime: function () {
        return this.getTimes()[4];
    },
    /**
     * get chourouk time
     * @returns {String}
     */
    getChouroukTime: function () {
        return this.times[1];
    },
    /**
     * Get the imsak time calculated by soustraction of imsakNbMinBeforeFajr from sobh time
     * @returns {String}
     */
    getImsak: function () {
        var fajr = this.getTimeByIndex(0);
        var fajrDateTime = this.getCurrentDateForPrayerTime(fajr);
        var imsakDateTime = fajrDateTime.setMinutes(fajrDateTime.getMinutes() - this.confData.imsakNbMinBeforeFajr);
        imsakDateTime = new Date(imsakDateTime);
        return addZero(imsakDateTime.getHours()) + ':' + addZero(imsakDateTime.getMinutes());
    },
    /**
     * init the cron that change prayer times by day
     * at midnight we change prayer times for the day
     * we check every minute
     */
    initCronHandlingTimes: function () {
        var date;
        setInterval(function () {
            date = new Date();
            if (date.getHours() === 0 && date.getMinutes() === 0) {
                prayer.setDate();
                prayer.loadTimes();
                prayer.setTimes();
                prayer.initNextTimeHilight();
                prayer.setSpecialTimes();
            }
            prayer.showSpecialTimes();
        }, prayer.oneMinute);
    },
    /**
     * Reload page every day at 2 o'clock to prevent any graphical bug
     * Reload only if internet connection available
     */
    initCronReloadPage: function () {
        setInterval(function () {
            var date = new Date();
            if (date.getHours() === 2) {
                reloadIfConnected();
            }
        }, 60 * prayer.oneMinute);
    },
    /**
     * Check every second if iqama time is ok
     * if ok we show iqama flashing for 30 sec
     */
    iqama: {
        isFlashing: false,
        initFlash: function () {
            if (!prayer.confData.iqamaEnabled) {
                return;
            }
            setInterval(function () {
                if (!prayer.iqama.isFlashing) {
                    var currentDateForPrayerTime, diffTimeInMiniute, currentPrayerWaitingTime, date;
                    $(prayer.getTimes()).each(function (currentPrayerIndex, time) {

                        // if jumua and mosque type we don't flash iqama
                        if (prayer.isJumua(currentPrayerIndex) && prayer.isMosque) {
                            return;
                        }

                        date = new Date();
                        currentDateForPrayerTime = prayer.getCurrentDateForPrayerTime(time);

                        if (date.getHours() === 0 && currentDateForPrayerTime.getHours() === 23) {
                            currentDateForPrayerTime.setDate(currentDateForPrayerTime.getDate() - 1);
                        }

                        diffTimeInMiniute = Math.floor((date - currentDateForPrayerTime) / prayer.oneMinute);
                        currentPrayerWaitingTime = prayer.getWaitingByIndex(currentPrayerIndex);
                        if (diffTimeInMiniute === currentPrayerWaitingTime) {
                            prayer.iqama.isFlashing = true;
                            // iqama flashing
                            prayer.iqama.flash(currentPrayerIndex);
                        }
                    });
                }
            }, prayer.oneSecond);
        },
        /**
         * flash iqama for 30 sec
         * @param {Number} currentPrayerIndex
         */
        flash: function (currentPrayerIndex) {

            if (prayer.confData.iqamaBip === true) {
                prayer.playSound();
            }

            $(".main-iqama-countdown").addClass("hidden");
            $(".top-content .content").removeClass("hidden");

            prayer.switchLayer('main', 'iqama');

            // flash
            var iqamaFlashInterval = setInterval(function () {
                $(".iqama .image").toggleClass("hidden");
            }, prayer.oneSecond);

            // stop iqama flashing after defined time
            setTimeout(function () {
                prayer.iqama.stopFlashing(iqamaFlashInterval);
            }, prayer.confData.iqamaDisplayTime * prayer.oneSecond);

            // reset flag iqamaIsFlashing after one minute
            setTimeout(function () {
                prayer.iqama.isFlashing = false;
            }, prayer.oneMinute);

            // init douaa after prayer timeout
            setTimeout(function () {
                douaaSlider.show(currentPrayerIndex);
                flashMessage.show();
            }, prayer.confData.duaAfterPrayerShowTimes[currentPrayerIndex] * prayer.oneMinute);
        },
        stopFlashing: function (iqamaFlashInterval) {
            $(".iqama").fadeOut(0, function () {
                if (prayer.confData.blackScreenWhenPraying && !isMobile) {
                    $("#black-screen").fadeIn(500);
                } else {
                    $(".main").fadeIn(500);
                }
            });
            clearInterval(iqamaFlashInterval);
        },
        /**
         * Set iqama countdonwn
         * @param {Number} currentPrayerIndex
         */
        countdown: function (currentPrayerIndex) {
            var time = prayer.getTimeByIndex(currentPrayerIndex);
            var currentElem = $(".wait").eq(currentPrayerIndex);
            var waitingCentent = currentElem.html();
            var prayerTimeDate = prayer.getCurrentDateForPrayerTime(time);
            var prayerTimePlusWaiting = prayerTimeDate.setMinutes(prayerTimeDate.getMinutes() + prayer.getWaitingByIndex(currentPrayerIndex));
            var countdown;
            $(currentElem).countdown(prayerTimePlusWaiting, function (event) {
                countdown = event.strftime('%M:%S');
                if (prayer.getWaitingByIndex(currentPrayerIndex) > 60) {
                    countdown = event.strftime('%H:%M:%S');
                }

                $('.main-iqama-countdown .countdown,' + ".mobile .wait._" + currentPrayerIndex).text(countdown);
                if (prayer.confData.iqamaFullScreenCountdown === false) {
                    $(this).text(countdown);
                }
            }).on('finish.countdown', function () {
                $(currentElem).html(waitingCentent);
            });
        }
    },
    /**
     * Check every minute if athan time is ok
     * if adhan time is ok we flash time
     * after 2 minutes we stop flashing and show adhan douaa
     */
    adhan: {
        isFlashing: false,
        hasNotified: false,
        initFlash: function () {
            setInterval(function () {
                if (!prayer.adhan.isFlashing) {
                    var currentTime = dateTime.getCurrentTime();
                    $(prayer.getTimes()).each(function (currentPrayerIndex, time) {
                        if (time === currentTime) {
                            // if jumua and mosque type we don't flash adhan
                            if (prayer.isJumua(currentPrayerIndex) && prayer.isMosque) {
                                return;
                            }
                            prayer.adhan.isFlashing = true;
                            prayer.adhan.flash(currentPrayerIndex);
                        }
                    });
                }
            }, prayer.oneSecond);
        },
        /**
         * Flash adhan, play sound if enabled
         * @param {Number} currentPrayerIndex
         */
        flash: function (currentPrayerIndex) {
            if (prayer.confData.azanVoiceEnabled === true) {
                var file = "adhan-maquah.mp3";
                if (currentPrayerIndex === 0) {
                    var file = "adhan-maquah-fajr.mp3";
                }
                prayer.playSound(file);
            } else if (prayer.confData.azanBip === true) {
                prayer.playSound();
            }

            // init next hilight timeout
            prayer.setNextTimeHilight(currentPrayerIndex);

            // iqama countdown
            if (prayer.confData.iqamaEnabled) {
                prayer.iqama.countdown(currentPrayerIndex);
            }

            $(".top-content .content").addClass("hidden");

            flashMessage.hide();

            var adhanFlashInterval = setInterval(function () {
                $(".top-content .adhan-flash").toggleClass("hidden");
                $(".mobile .prayers .adhan").eq(currentPrayerIndex).toggleClass("hidden");
                $(".mobile .prayers .time").eq(currentPrayerIndex).toggleClass("hidden");
            }, prayer.oneSecond);

            setTimeout(function () {
                prayer.adhan.stopFlashing(adhanFlashInterval, currentPrayerIndex);
            }, prayer.getAdhanFlashingTime(currentPrayerIndex));
        },
        stopFlashing: function (adhanFlashInterval, currentPrayerIndex) {
            clearInterval(adhanFlashInterval);
            prayer.adhan.isFlashing = false;
            $(".top-content .adhan-flash").addClass("hidden");

            if (prayer.confData.iqamaEnabled && prayer.confData.iqamaFullScreenCountdown === true) {
                $(".main-iqama-countdown").removeClass("hidden");
            } else {
                $(".top-content .content").removeClass("hidden");
            }

            $(".mobile .prayers .adhan").eq(currentPrayerIndex).addClass("hidden");
            $(".mobile .prayers .time").eq(currentPrayerIndex).removeClass("hidden");

            prayer.duaAfterAdhan.handle(currentPrayerIndex);
        }
    },

    /**
     * cron for fajr waking up
     * @returns {undefined}
     */
    fajrWakeAdhanIsPlaying: false,
    initWakupFajr: function () {
        setInterval(function () {
            if (!prayer.isMosque && prayer.fajrWakeAdhanIsPlaying === false && parseInt(prayer.confData.wakeForFajrTime) > 0) {
                var date = new Date();
                var fajrTime = prayer.getTimeByIndex(0);
                var diffTimeInMiniute = Math.floor((date - prayer.getCurrentDateForPrayerTime(fajrTime)) / prayer.oneMinute);
                if (diffTimeInMiniute === -parseInt(prayer.confData.wakeForFajrTime)) {
                    var $contentEl = $(".top-content .content");
                    var $alarmFlashEl = $(".alarm-flash");

                    prayer.fajrWakeAdhanIsPlaying = true;
                    // play adhan sound
                    prayer.playSound(prayer.confData.wakeAzanVoice + ".mp3");
                    $contentEl.addClass("hidden");

                    // flash every one seconde
                    var interval = setInterval(function () {
                        $alarmFlashEl.toggleClass("hidden");
                    }, prayer.oneSecond);

                    // timeout to stop flashing
                    setTimeout(function () {
                        prayer.fajrWakeAdhanIsPlaying = false;
                        $contentEl.removeClass("hidden");
                        $alarmFlashEl.addClass("hidden");
                        clearInterval(interval);
                    }, 200 * prayer.oneSecond);
                }
            }
        }, prayer.oneMinute);
    },
    jumuaHandler: {
        /**
         * init cron
         */
        init: function () {
            if (prayer.getJumuaTime()) {
                setInterval(function () {
                    var date = new Date();
                    if (date.getDay() === 5) {
                        var currentTime = dateTime.getCurrentTime();
                        // show reminder
                        if (currentTime === prayer.getJumuaTime()) {

                            // hilight asr
                            prayer.setNextTimeHilight(1);

                            if (prayer.confData.jumuaDhikrReminderEnabled === true) {
                                prayer.jumuaHandler.showReminder();
                                setTimeout(function () {
                                    prayer.jumuaHandler.hideReminder();
                                }, prayer.confData.jumuaTimeout * prayer.oneMinute);
                            } else if (prayer.confData.jumuaBlackScreenEnabled === true) {
                                prayer.jumuaHandler.showBlackScreen();
                                setTimeout(function () {
                                    prayer.jumuaHandler.hideBlackScreen();
                                }, prayer.confData.jumuaTimeout * prayer.oneMinute);
                            }
                        }
                    }
                }, prayer.oneMinute);
            }
        },
        showReminder: function () {
            fixFontSize('.jumua-dhikr-reminder');
            $(".main").fadeOut(0, function () {
                $(".jumua-dhikr-reminder").fadeIn(500);
            });
        },
        hideReminder: function () {
            $(".jumua-dhikr-reminder").fadeOut(0, function () {
                $(".main").fadeIn(500, function () {
                    messageInfoSlider.run();
                });
            });
        },
        showBlackScreen: function () {
            $(".main").fadeOut(0, function () {
                $("#black-screen").fadeIn(500);
            });
        },
        hideBlackScreen: function () {
            $("#black-screen").fadeOut(0, function () {
                $(".main").fadeIn(500, function () {
                    messageInfoSlider.run();
                });
            });
        }
    },
    /**
     *  timeout for stopping time flashing
     *  @param {integer} currentPrayerIndex
     */
    getAdhanFlashingTime: function (currentPrayerIndex) {

        // if short waiting
        if (prayer.getWaitingByIndex(currentPrayerIndex) <= 2) {
            return prayer.oneSecond * 90;
        }

        // if azan enablded
        if (!prayer.isMosque && prayer.confData.azanVoiceEnabled === true) {
            // if fajr
            if (currentPrayerIndex === 0) {
                return prayer.oneSecond * 250;
            }
            return prayer.oneSecond * 200;
        }

        // otherwise
        return prayer.oneSecond * 150;
    },
    /**
     * @param layerToHide the element class to hide
     * @param layerToShow the element class to show
     */
    switchLayer: function (layerToHide, layerToShow, correctFontSize) {
        if (correctFontSize === true) {
            fixFontSize('.' + layerToShow);
        }
        $('.' + layerToHide).fadeOut(0, function () {
            $('.' + layerToShow).fadeIn(500);
        });
    },
    /**
     * Play a bip
     */
    playSound: function (file) {
        if (typeof file === "undefined") {
            file = "bip.mp3";
        }

        var audio = new Audio('/static/mp3/' + file);
        audio.play();
    },
    /**
     * search and set the next prayer time hilight
     */
    initNextTimeHilight: function () {
        var date = new Date();
        var prayerDateTime;
        // sobh is default
        prayer.hilightByIndex(0);
        var times = this.getTimes();
        $.each(times, function (index, time) {
            prayerDateTime = prayer.getCurrentDateForPrayerTime(time);
            let prayerWaiting = prayer.getWaitingByIndex(index) + 5;
            prayerDateTime.setMinutes(prayerDateTime.getMinutes() + prayerWaiting);
            if (prayerDateTime.getHours() !== 0 && date > prayerDateTime) {
                index++;
                if (index === 5) {
                    index = 0;
                }
                prayer.hilightByIndex(index);
            }
        });
    },
    /**
     * hilight prayer by index
     * @param {Number} prayerIndex
     */
    hilightByIndex: function (prayerIndex) {
        $(".prayer-hilighted").removeClass("prayer-hilighted");

        // if joumouaa we hilight joumouaa time
        if (prayer.isJumua(prayerIndex)) {
            $(".joumouaa").addClass("prayer-hilighted");
            if (prayer.isMosque) {
                return;
            }
        }

        $(".prayers > div").eq(prayerIndex).addClass("prayer-hilighted");
    },
    /**
     * at iqama we hilight the next prayer time
     * @param {int} currentTimeIndex
     */
    setNextTimeHilight: function (currentTimeIndex) {
        var nextTimeIndex = currentTimeIndex + 1;
        // if icha is the current prayer
        if (nextTimeIndex === 5) {
            nextTimeIndex = 0;
        }

        let prayerWaiting = prayer.getWaitingByIndex(currentTimeIndex) + 5;

        setTimeout(function () {
            prayer.hilightByIndex(nextTimeIndex);
            prayer.nextPrayerCountdown();
            // if ichaa we load tomorrow times after iqama
            var date = new Date();
            if (nextTimeIndex === 0 && date.getHours() !== 0) {
                prayer.loadTimes(true);
                prayer.setTimes(true);
            }
        }, prayerWaiting * prayer.oneMinute);
    },
    duaAfterAdhan: {
        showAdhanDua: function () {
            if (!prayer.isMosque && prayer.confData.azanVoiceEnabled === true) {
                prayer.playSound("duaa-after-adhan.mp3");
            }

            prayer.switchLayer('main', 'adhan', true);
        },
        hideAdhanDua: function () {
            prayer.switchLayer('adhan', 'main');
        },
        showHadith: function () {
            prayer.switchLayer('main', 'douaa-between-adhan-iqama', true);
        },
        hideHadith: function () {
            prayer.switchLayer('douaa-between-adhan-iqama', 'main');
        },
        /**
         * @param integer currentPrayerIndex
         * show douaa after adhan flash finish
         * show douaa for configured time
         * show hadith to remeber importance of douaa between adhan and iqama
         */
        handle: function (currentPrayerIndex) {
            if (prayer.confData.duaAfterAzanEnabled === true) {
                var iqamaWaiting = prayer.getWaitingByIndex(currentPrayerIndex);
                prayer.duaAfterAdhan.showAdhanDua();
                setTimeout(function () {
                    prayer.duaAfterAdhan.hideAdhanDua();
                    if (iqamaWaiting > 2) {
                        // show hadith between adhan and iqama
                        setTimeout(function () {
                            prayer.duaAfterAdhan.showHadith();
                            setTimeout(function () {
                                prayer.duaAfterAdhan.hideHadith();
                            }, 30 * prayer.oneSecond);
                        }, 10 * prayer.oneSecond);
                    }
                }, (iqamaWaiting > 2 ? 30 : 20) * prayer.oneSecond);
            }
        }
    },
    /**
     * set time every second
     */
    setTime: function () {
        var time, timeWithoutSec;
        var timeEl = $(".currentTime");
        var timeShortEl = $(".currentTimeShort");
        time = dateTime.getCurrentTime(true);
        timeWithoutSec = prayer.formatTime(dateTime.getCurrentTime());
        timeEl.html(prayer.formatTime(time));
        timeShortEl.html(timeWithoutSec);
    },
    setTimeInterval: function () {
        setInterval(function () {
            prayer.setTime();
        }, prayer.oneSecond);
    },
    /**
     * set date
     */
    setDate: function () {
        $(".gregorianDate").text(dateTime.getCurrentDate(locale));
        this.setCurrentHijriDate();
    },
    /**
     * set hijri date from hijriDate.js
     */
    setCurrentHijriDate: function () {
        if (prayer.confData.hijriDateEnabled === true) {
            var hijriAdjustment = prayer.confData.hijriAdjustment;
            $(".hijriDate span").text(writeIslamicDate(hijriAdjustment, lang));
            var hijriDate = kuwaiticalendar(hijriAdjustment);
            $(".hijriDate span").removeClass("white-days");
            $(".hijriDate img").addClass("hidden");
            if ($.inArray(hijriDate[5], [13, 14, 15]) !== -1) {
                $(".hijriDate span").addClass("white-days");
                $(".hijriDate img").removeClass("hidden");
            }
        }
    },
    /**
     * get jumu`a time
     * @returns {String}
     */
    getJumuaTime: function () {
        return this.confData.jumua;
    },
    /**
     * if current time is joumouaa
     * @param {int} currentPrayerIndex
     * @returns {boolean}
     */
    isJumua: function (currentPrayerIndex) {
        var date = new Date();
        return prayer.getJumuaTime() && date.getDay() === 5 && currentPrayerIndex === 1;
    },
    /**
     * check if jumua moment
     * @return {boolean}
     */
    isJumuaMoment: function () {
        var date = new Date();

        if (!prayer.getJumuaTime()) {
            return false;
        }

        if (date.getDay() !== 5) {
            return false;
        }

        var beginDateTime = prayer.getCurrentDateForPrayerTime(prayer.getJumuaTime());
        var beginTime = beginDateTime.getTime();
        var endTime = new Date(beginTime);
        endTime.setMinutes(endTime.getMinutes() + prayer.confData.jumuaTimeout);

        if (date.getTime() < beginTime || date.getTime() > endTime) {
            return false;
        }

        return true;
    },
    /**
     * handle custom time display
     */
    showSpecialTimes: function () {

        $(".custom-time").hide();
        // if aid time enabled we set/show it
        if (this.confData.aidPrayerTime && this.aidIsCommingSoon()) {
            $(".aid").addClass("prayer-hilighted");
            $(".aid").show();
            return;
        }

        // if imsak disabled => show shuruq
        if (parseInt(this.confData.imsakNbMinBeforeFajr) === 0) {
            $(".chourouk").show();
            return;
        }

        // if imsak time enabled we show it between chourouk + 1 hour and sobh
        if (parseInt(this.confData.imsakNbMinBeforeFajr) !== 0) {
            var date = new Date();
            var midnight = new Date();
            midnight.setHours(0);
            midnight.setMinutes(0);
            midnight.setSeconds(0);
            var sobhDate = prayer.getCurrentDateForPrayerTime(prayer.getTimeByIndex(0));
            // if time betwwen midnight and sobh => show imsak
            if (date < sobhDate && date > midnight) {
                $(".imsak").show();
                return;
            }

            var chouroukDate = prayer.getCurrentDateForPrayerTime(prayer.getChouroukTime());
            chouroukDate = chouroukDate.setHours(chouroukDate.getHours() + 1);
            // if time > chourouk + 1 hour => show imsak
            if (date.getTime() > chouroukDate) {
                $(".imsak").show();
                if (this.isRamadan()) {
                    $(".imsak-id").addClass('important');
                }
                return;
            }
        }

        $(".chourouk").show();
    },
    setSpecialTimes: function () {
        // jumua
        $(".joumouaa-id").html(prayer.formatTime(this.getJumuaTime()));
        $(".joumouaa2-id").html(prayer.formatTime(prayer.confData.jumua2));

        // if aid time enabled we set/show it
        $(".aid-id").html(prayer.formatTime(this.confData.aidPrayerTime));

        // set chourouk time
        $(".chourouk-id").html(prayer.formatTime(this.getChouroukTime()));

        // if imsak time enabled we show it between chourouk + 1 hour and sobh
        $(".imsak-id").html(prayer.formatTime(this.getImsak()));
    },
    /**
     * set all prayer times
     * @param {Boolean} tomorrow
     */
    setTimes: function (tomorrow) {
        var times = this.getTimes(tomorrow);
        $.each(times, function (i, time) {
            $('.prayers .time').eq(i).html(prayer.formatTime(time))
        });
    },
    /**
     * set wating times
     */
    setWaitings: function () {
        if (!prayer.confData.iqamaEnabled) {
            return;
        }

        var prayerTime, iqamaTime, fixedIqama, prayerTimes, month, day;
        $.each(prayer.getWaitingTimes(), function (i, wait) {
            fixedIqama = null;
            wait = wait + "'";
            prayerTimes = prayer.getTimes();
            prayerTime = prayer.getCurrentDateForPrayerTime(prayerTimes[i]);

            month = dateTime.getCurrentMonth();
            day = dateTime.getCurrentDay();

            if (prayer.confData.fixedIqama[i] !== "") {
                fixedIqama = prayer.confData.fixedIqama[i];
                iqamaTime = prayer.getCurrentDateForPrayerTime(fixedIqama);
                if (iqamaTime.getTime() > prayerTime.getTime()) {
                    wait = prayer.formatTime(fixedIqama);
                    if (!isMobile) {
                        $(".prayers .wait, .prayers .time").css({"font-size": "3.5vw"});
                    }
                }
            }

            $('.prayers .wait').eq(i).html(wait);
        });

        var ishaDate = this.getCurrentDateForPrayerTime(this.getIshaTime());
        if (this.confData.maximumIshaTimeForNoWaiting != null && this.confData.maximumIshaTimeForNoWaiting.matchTime()) {
            var maximumIshaTimeForNoWaitingDate = this.getCurrentDateForPrayerTime(this.confData.maximumIshaTimeForNoWaiting);
            if (ishaDate.getHours() === 0 || ishaDate >= maximumIshaTimeForNoWaitingDate) {
                $('.prayers .wait').eq(4).html("0'");
            }
        }

    },
    hideSpinner: function () {
        setTimeout(function () {
            $("#spinner").fadeOut(0, function () {
                $(".main").fadeIn(100);
            });
        }, 1500);
    },
    /**
     * Init events
     */
    initEvents: function () {
        $(".version").click(function () {
            prayer.test();
        });
    },
    /**
     * Check if we are in praying moment (10 min before afhan and and 19 min after iqamah)
     */
    isPrayingMoment: function () {
        let isPrayingMoment = false;
        let date = new Date();
        let beginDateTime, endDateTime, prayerDateTime;

        if(prayer.isJumuaMoment())
        {
            return true;
        }

        $(prayer.getTimes()).each(function (i, time) {
            prayerDateTime = prayer.getCurrentDateForPrayerTime(time);
            beginDateTime = new Date(prayerDateTime.getTime());
            endDateTime = new Date(prayerDateTime.getTime());
            beginDateTime.setMinutes(beginDateTime.getMinutes() - 10);
            endDateTime.setMinutes(endDateTime.getMinutes() + prayer.getWaitingByIndex(i) + 11);

            if (date > beginDateTime && date < endDateTime) {
                isPrayingMoment = true;
                return false;
            }
        });

        return isPrayingMoment;
    },
    /**
     * Set QR code
     */
    setQRCode: function () {
        var qrCodeElm = $("#qrcode")
        qrCodeElm.qrcode({
            background: '#ffffff',
            size: 100,
            text: qrCodeElm.data("url")
        });
    },
    isRamadan: function () {
        var hijriDateInfo = kuwaiticalendar(prayer.confData.hijriAdjustment);
        if (hijriDateInfo[6] === 8) {
            return true;
        }
        return false;
    },
    /**
     * Return true if aid is comming soon, 3 days before aid
     * @returns {boolean}
     */
    aidIsCommingSoon: function () {
        var hijriDateInfo = kuwaiticalendar(prayer.confData.hijriAdjustment);
        // if aid al-fitr
        if (hijriDateInfo[6] === 8 && hijriDateInfo[5] >= 27 && hijriDateInfo[5] <= 30) {
            return true;
        }
        if (hijriDateInfo[6] === 9 && hijriDateInfo[5] === 1) {
            return true;
        }

        // if aid al-adha
        if (hijriDateInfo[6] === 11 && hijriDateInfo[5] >= 7 && hijriDateInfo[5] <= 10) {
            return true;
        }
        return false;
    }
};