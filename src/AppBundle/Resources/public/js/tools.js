/* ##### string ##### */
String.prototype.firstCapitalize = function () {
    return this.charAt(0).toUpperCase() + this.slice(1);
};

/**
 * check if string match time hh:mm
 * @returns {Boolean}
 */
String.prototype.matchTime = function () {
    var regex = /^\d{2}:\d{2}$/g;
    return regex.test(this);
};

String.prototype.hashCode = function () {
    var hash = 0;
    if (this.length == 0) return hash;
    for (i = 0; i < this.length; i++) {
        char = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
};


/* ##### time ##### */
Date.prototype.stdTimezoneOffset = function () {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
};

Date.prototype.dst = function () {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
};


/* ##### others ##### */
/**
 * add zero to number if < to 10, ex : 1 becomes 01
 * @param  value int
 * @returns {String}
 */
function addZero(value) {
    if (value < 10) {
        value = '0' + value;
    }
    return value;
}

/**
 * Reload page if internet connection is available
 */
function reloadIfConnected() {
    $.ajax({
        url: window.location.href,
        method: 'HEAD',
        success: function () {
            location.reload();
        }
    });
}

function fullscreen() {
    var el = document.documentElement;
    if (el.requestFullscreen) {
        el.requestFullscreen();
    }
    else if (el.mozRequestFullScreen) {
        el.mozRequestFullScreen();
    }
    else if (el.webkitRequestFullscreen) {
        el.webkitRequestFullscreen();
    }
    else if (el.msRequestFullscreen) {
        el.msRequestFullscreen();
    }
    setTimeout(function () {
        $("body").height($(document).height());
    }, 500);
}

function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    }
    else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    }
    else if (document.webkitCancelFullScreen) {
        document.webkitCancelFullScreen();
    }
    else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }
    $("body").height($(document).height());
}

/**
 * Set font size to contain all screen
 * @param elem
 * @param marge
 */
function fixFontSize(elem, marge) {
    marge = marge || 30;
    let $elem = $(elem);
    let $parent = $elem.parent();
    $elem.css('font-size', "90px");
    while ($elem.height() > $parent.height() - parseInt(marge)) {
        $elem.css('font-size', (parseInt($elem.css('font-size')) - 5) + "px");
    }
}

function setLocalTTL(key, value, ttl) {
    const now = new Date();
    const item = {
        value: value,
        expiry: now.getTime() + ttl
    };
    localStorage.setItem(key, JSON.stringify(item))
}

function getLocalTTL(key) {
    const itemStr = localStorage.getItem(key);
    if (!itemStr) {
        return null
    }
    const item = JSON.parse(itemStr);
    const now = new Date();
    if (now.getTime() > item.expiry) {
        return null
    }
    return item.value
}