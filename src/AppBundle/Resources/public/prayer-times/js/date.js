/* global prayer, getConfFromLocalStorage */

/**
 * class handling date and time
 * @type object
 */
var dateTime = {
    getCurrentMinute: function () {
        var date = new Date();
        return addZero(date.getMinutes());
    },
    getCurrentHour: function () {
        var date = new Date();
        return addZero(date.getHours());
    },
    /**
     * get day of month ex: 0, 1 ... 30
     * 0 is the first day
     */
    getCurrentDay: function () {
        var date = new Date();
        return date.getDate();
    },
    /**
     * get tomorrow day ex: 0, 1 ... 30
     * 0 is the first day
     */
    getTomorrowDay: function () {
        var date = this.tomorrow();
        return date.getDate();
    },
    /**
     * get current month numbre 0, 1, 2 ...
     */
    getCurrentMonth: function () {
        var date = new Date();
        return date.getMonth();
    },
    /**
     * get current month numbre 01, 02 ... 12
     */
    getCurrentMonthText: function () {
        var date = new Date();
        return addZero(date.getMonth() + 1);
    },
    /**
     * get tomorrow month 01, 02 ... 12
     */
    getTomorrowMonth: function () {
        var date = this.tomorrow();
        return date.getMonth();
    },
    /**
     * get full current year ex: 2017
     */
    getCurrentYear: function () {
        var date = new Date();
        return date.getFullYear();
    },
    /**
     * get current time in hh:ii format or hh:ii:ss format depends on withSeconds arg
     * @param  withSeconds bool
     * @returns string
     */
    getCurrentTime: function (withSeconds) {
        withSeconds = withSeconds === undefined ? false : withSeconds;
        var date = new Date();

        var second = addZero(date.getSeconds());
        var time = this.getCurrentHour() + ':' + this.getCurrentMinute();
        if (withSeconds === true) {
            time += ':' + second;
        }
        return time;
    },
    /**
     * get current gregorian date
     * @returns string
     */
    getCurrentDate: function (locale, weekday , year, month, day) {
        weekday = weekday || "long";
        year = year || "numeric";
        month = month || "short";
        day = day || "numeric";
        var date = new Date();
        var options = {weekday: weekday, year: year, month: month, day: day};

        if (locale.indexOf("ar") === 0) {
            options = {month: "short"};
            if(locale === "ar-FR"){
                locale = "ar-DZ";
            }
            return addZero(date.getDate()) + ' ' + date.toLocaleDateString(locale, options) + ' ' + date.getFullYear();
        }

        try {
            return date.toLocaleDateString(locale, options).firstCapitalize();
        } catch (e) {
            return date.toLocaleDateString('fr-FR', options).firstCapitalize();
        }

    },
    getLastSundayOfMonth: function (month) {
        var date = new Date();
        date.setMonth(month);
        date.setDate(31);
        date.setDate(date.getDate() - date.getDay());
        return date.getDate();
    },
    /**
     * true if date between last sunday of march and last sunday of october
     * @param {Boolean} tomorrow
     * @returns {Boolean}
     */
    isDst: function (tomorrow) {
        var date = new Date();
        if (tomorrow === true) {
            date = dateTime.tomorrow();
        }

        date.setHours(4);
        return date.dst();
    },
    /**
     * @returns {Date}
     */
    tomorrow: function () {
        var date = new Date();
        date.setDate(date.getDate() + 1);
        date.setHours(3);
        return date;
    },
    /**
     * @param string time
     * @param string timeDisplayFormat
     * @returns String
     */
    formatTime: function (time, timeDisplayFormat) {

        if (timeDisplayFormat === "24") {
            return time;
        }

        if (!time.includes(':') ) {
            return time;
        }

        time = time.split(":");
        var hours = time[0];
        var minutes = time[1];
        var seconds = time[2] ? time[2] : null;
        var ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        var strTime = hours + ':' + minutes;
        if (seconds) {
            strTime += ':' + seconds;
        }
        strTime += '<small>' + ampm + '</small>';
        return strTime;
    }
};