function ruleChangeStatus(elm) {
    elm.removeClass('text-danger');
    elm.addClass('text-success');
    let i = elm.find('i')
    i.removeClass('fas fa-times-circle');
    i.addClass('fas fa-check-circle');
}

$("input[name*='plainPassword']").bind('change keyup', function (e) {
    let v = e.target.value;
    let p = $(".pwd-rules p");
    let i = $(".pwd-rules i");
    p.attr('class', '');
    i.attr('class', '');
    p.addClass('text-danger');
    i.addClass('fas fa-times-circle');

    if (v.length >= 12) {
        ruleChangeStatus($(p[0]));
    }
    if (/(?=.*?[A-Z])/.test(v)) {
        ruleChangeStatus($(p[1]));
    }
    if (/(?=.*?[a-z])/.test(v)) {
        ruleChangeStatus($(p[2]));
    }
    if (/(?=.*?[0-9])/.test(v)) {
        ruleChangeStatus($(p[3]));
    }
    if (/(?=.*?[^\w\s])/.test(v)) {
        ruleChangeStatus($(p[4]));
    }
});