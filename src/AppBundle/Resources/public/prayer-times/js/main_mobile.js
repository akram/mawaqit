/* global prayer */

prayer.hideSpinner =  function () {
    $("#spinner").fadeOut(0, function () {
        $(".main").fadeIn(500);
    });
};

if(!prayer.confData.iqamaEnabled){
    $(".wait").css("visibility","hidden");
}

prayer.setTime();
prayer.setTimeInterval();
prayer.loadData();
prayer.setDate();
prayer.setTimes();
prayer.nextPrayerCountdown();
prayer.setWaitings();
prayer.initNextTimeHilight();
prayer.adhan.initFlash();
prayer.iqama.initFlash();
prayer.initCronHandlingTimes();
prayer.setSpecialTimes();
prayer.showSpecialTimes();
prayer.hideSpinner();
