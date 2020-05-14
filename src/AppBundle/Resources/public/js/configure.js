$("#configuration_save").click(function () {
    $("form").submit();
});

$("#predefined-calendar").autocomplete({
    source: function (request, response) {
        $.ajax({
            url: $("#predefined-calendar").data("remote"),
            dataType: "json",
            data: {
                query: request.term
            },
            success: function (data) {
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        window.location.href = $("#predefined-calendar").data("copyPath").replace("-id-", ui.item.id);
    }
});


/**
 * check and hilight imcompleted months
 */

function checkAndHilightIncompletedMonths() {
    $(".month-panel").each(function (i, elm) {
        var panel = elm;
        $(panel).find(".panel-heading").css("background-color", " #e2e2e2");
        $(panel).find(".calendar-prayer-time").each(function (i, input) {
            $(input).css("background-color", "#ffffff");
            if (!/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/.test($(input).val())) {
                $(input).css("background-color", "#f8d4d4");
                $(panel).find(".panel-heading").css("background-color", "#f2dede");
            }
        });
    });
}

$("#configuration_sourceCalcul").bind("change keyup", function (event) {
    $(".api, .calendar").addClass("hidden");
    $("." + $(this).val()).removeClass("hidden");
});

$("#configuration_prayerMethod").bind("change keyup", function (event) {
    $(".degree").addClass("hidden");
    if ($(this).val() === 'CUSTOM') {
        $(".degree").removeClass("hidden");
    }
});

$(".month-panel .calendar-prayer-time").bind("keyup", function (event) {
    checkAndHilightIncompletedMonths();
});

/**
 * On change file input (fill calendar) process fill prayer times from csv file
 */
$(".fill-calendar").change(function (e) {
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        var self = this;
        var reader = new FileReader();
        // Read file into memory as UTF-8      
        reader.readAsText(this.files[0]);
        // Handel load file
        reader.onload = function (event) {
            var csv = event.target.result;
            processFillMonthPrayerTimes(csv, self);
        };
        // Handle errors load
        reader.onerror = function (evt) {
            if (evt.target.error.name == "NotReadableError") {
                alert("Unable to read csv file");
            }
        };
    } else {
        alert("This fonctionality is not fully supported in your browser.");
    }
});

/**
 * Fill prayer times from API
 */
function fillCalendarFromApi(e) {

    e.preventDefault();

    waitingDialog.show();

    setTimeout(function () {
        var prayerMethod = $('#configuration_prayerMethod').val();
        var prayTimes = new PrayTimes(prayerMethod);

        if (prayerMethod === "CUSTOM") {
            var fajrDegree = $('#configuration_fajrDegree').val();
            var ishaDegree = $('#configuration_ishaDegree').val();

            if (fajrDegree) {
                prayTimes.adjust({"fajr": parseFloat(fajrDegree)});
            }
            if (ishaDegree) {
                prayTimes.adjust({"isha": parseFloat(ishaDegree)});
            }
        }

        prayTimes.adjust({"asr": $('#configuration_asrMethod').val()});

        var highLatsMethod = $('#configuration_highLatsMethod').val();

        if (highLatsMethod) {
            prayTimes.adjust({"highLats": highLatsMethod});
        }

        var months = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for (var month = 0; month <= 11; month++) {
            for (var day = 1; day <= months[month]; day++) {
                var date = new Date();
                date.setMonth(month);
                date.setDate(day);
                var latitude = $('#latitude').val();
                var longitude = $('#longitude').val();
                var timezone = $('#configuration_timezone').val();
                var pt = prayTimes.getTimes(date, [parseFloat(latitude), parseFloat(longitude)], timezone, 0);
                var times = {
                    1: pt.fajr,
                    2: pt.sunrise,
                    3: pt.dhuhr,
                    4: pt.asr,
                    5: pt.maghrib,
                    6: pt.isha
                };

                for (var prayer = 1; prayer <= 6; prayer++) {
                    var inputPrayer = $("input[name='configuration[calendar][" + month + "][" + day + "][" + prayer + "]']");
                    inputPrayer.val(times[prayer]);
                    inputPrayer.trigger("change");
                }
            }
        }
        checkAndHilightIncompletedMonths();
        waitingDialog.hide();
    }, 500)
}


/**
 * Read CSV and fill prayerTimes in input elements
 * @param {string} csv
 * @param {object} inputFile
 */
function processFillMonthPrayerTimes(csv, inputFile) {
    try {
        var panel = $(inputFile).parents(".panel-body");
        var panelId = panel.attr("id");
        var month = panelId.split('_');
        var error = false;
        var lines = csv.split(/(?:\r?\n)/g);
        var val;
        month = month[1];
        for (var day = 1; day < lines.length; day++) {
            var line = lines[day].split(/,|;/);
            for (var prayer = 1; prayer < line.length; prayer++) {
                var inputPrayer = $("input[name='configuration[" + $(inputFile).data("calendar") + "][" + month + "][" + day + "][" + prayer + "]']");
                val = line[prayer].trim();
                if (/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/.test(val) === false) {
                    error = true;
                }
                inputPrayer.val(val);
                inputPrayer.trigger("change");
            }
        }
        checkAndHilightIncompletedMonths();
        if (error) {
            $(panel).find(".alert-danger").removeClass("hidden");
        } else {
            $(panel).find(".alert-success").removeClass("hidden");
        }
    } catch (e) {
        $(panel).find(".alert-danger").removeClass("hidden");
    }
}

function handleErrorsDisplay() {
    $(".has-error").parents(".panel-collapse").collapse("show");
}

/**
 * Jumua as duh handling checkbox
 */
$("#configuration_jumuaAsDuhr").bind("change", function (event) {
    if ($(this).is(":checked")) {
        $(".jumua-bloc-time").hide();
    } else {
        $(".jumua-bloc-time").show();
    }
});

/**
 * No jumua checkbox handling
 */
$("#configuration_jumua").bind("change", function (event) {
    if ($(this).is(":checked")) {
        $(".jumua-bloc").show();
    } else {
        $(".jumua-bloc").hide();
    }
});

/**
 * jumu`a Reminder an blackScreen checkbox handling
 */
$(".jumuaTimeoutHandler input").bind("change", function () {
    var $jumuaTimeoutParent = $("#configuration_jumuaTimeout").parent();
    $jumuaTimeoutParent.hide();

    $(".jumuaTimeoutHandler input").each(function (i, e) {
        if ($(e).is(":checked")) {
            $jumuaTimeoutParent.show();
            return false;
        }
    })

});

$("#configuration_randomHadithEnabled").bind("change", function (event) {
    if ($(this).is(":checked")) {
        $(".hadith-block").show();
    } else {
        $(".hadith-block").hide();
    }
});

function iqamaSettingsDisplayHandler() {
    var $iqamaCheckbox = $("#configuration_iqamaEnabled");
    if ($iqamaCheckbox.is(":checked")) {
        $(".iqama-settings").show();
    } else {
        $(".iqama-settings").hide();
    }
}

$("#configuration_iqamaEnabled").bind("change", function (event) {
    iqamaSettingsDisplayHandler();
});

$("." + $("#configuration_sourceCalcul").val()).removeClass("hidden");

$("#configuration_prayerMethod").trigger("change");
$("#configuration_jumuaAsDuhr").trigger("change");
$("#configuration_jumua").trigger("change");
$("#configuration_randomHadithEnabled").trigger("change");
$(".jumuaTimeoutHandler input").trigger("change");

function dstDisplayHandler() {
    var $dst = $("#configuration_dst");
    if ($dst.val() == "1") {
        $(".dst-bloc").show();
    } else {
        $(".dst-bloc").hide();
    }
}

$("#configuration_dst").change(function () {
    dstDisplayHandler();
});


function wakeUpAzanDisplayHandler() {
    var $wakeForFajrTime = $("#configuration_wakeForFajrTime");
    if ($wakeForFajrTime.val() > 0) {
        $(".wakeAzanVoice").show();
    } else {
        $(".wakeAzanVoice").hide();
    }
}

$("#configuration_wakeForFajrTime").change(function () {
    wakeUpAzanDisplayHandler();
});

var wakeAzanVoice = new Audio();
$("#configuration_wakeAzanVoice").change(function () {
    $("#azabStop").removeClass("hidden");
    wakeAzanVoice.pause();
    wakeAzanVoice = new Audio('/static/mp3/' + $(this).val() + '.mp3');
    wakeAzanVoice.play();
});

$("#azabStop").click(function () {
    wakeAzanVoice.pause();
});

function backgroundHandler() {
    var $backgroundType = $("#configuration_backgroundType");
    $(".motif, .color").hide();
    $("." + $backgroundType.val()).show();
}

$("#configuration_backgroundType").change(function () {
    backgroundHandler();
});

function themeIllustrationHandler() {
    let themeSelector = $("#configuration_theme");
    let img = $('#illustration');
    let imgPattern = img.data('src');
    img.attr('src', imgPattern.replace('name', themeSelector.val()))
}

$("#configuration_theme").bind("change", function (event) {
    themeIllustrationHandler()
});

$("input[name*='configuration[calendar][1][28]']").bind("change keyup", function (event) {
    let name = $(this).attr("name").replace('28', '29');
    $("input[name='" + name + "']").val($(this).val());
});


function timezoneHandler() {
    let $timezone = $("#configuration_timezoneName");
    let $dst = $("#configuration_dst");
    let $infoDstChange = $("#info_dst_change");
    $infoDstChange.hide();
    // if Europe timezone hide dst and force it to auto
    $dst.parent().show();
    if ($timezone.val().includes('Europe')) {
        $dst.parent().hide();
        $dst.val("2");
        $infoDstChange.show();
    }

    if ($timezone.val().includes('Algiers')) {
        $dst.val("0");
    }

    $dst.trigger("change")
}

$("#configuration_timezoneName").bind("change keyup", function () {
    timezoneHandler();
});

timezoneHandler();
dstDisplayHandler();
checkAndHilightIncompletedMonths();
handleErrorsDisplay();
iqamaSettingsDisplayHandler();
wakeUpAzanDisplayHandler();
backgroundHandler();
themeIllustrationHandler();