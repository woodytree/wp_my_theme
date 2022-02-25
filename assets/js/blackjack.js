var j = jQuery.noConflict();

function getCookie(c) {
    var v = document.cookie.match(new RegExp('(?:^|; )' + c.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
    return v ? decodeURIComponent(v[1]) : undefined
}

function setCookie(c, v) {
    var date = new Date();
    date.setTime(date.getTime());
    time = 30 * 1000 * 60 * 60 * 24;
    var expires = new Date(date.getTime() + (time));
    document.cookie = '' + c + ' = ' + v + '; expires=' + expires.toGMTString() + ''
}

function jSwitch(e) {
	var onclick = j(e).attr('onclick');
	var background = j(e).css('background');
    j(e).removeAttr('onclick').attr('disabled', 'disabled').css('background', 'orange');
    setTimeout(function() {
        j(e).attr('onclick', onclick).removeAttr('disabled').css('background', background)
    }, 5000)
}

function replaceHtml(e, d, f) {
    if (f) {
		j(e).css('display', 'none').html(d).fadeIn('fast')
    } else {
        j('#' + e).css('display', 'none').html(d).fadeIn('fast')
    }
}

function checkElement() {
    var v = getCookie('blackjack_bet'),
        v = (v ? v : 1);
    j('input[value="' + v + '"]').attr('checked', true)
}

function blackjackAction(action, bet, e) {
    var bet_checked = j('#blackjack input:checked').val();
    var bet = (bet ? bet : (bet_checked ? bet_checked : 0));
    var e = (e ? e : 'blackjack');
    if (action == 'start') {
        if (getCookie('blackjack_bet') != bet) {
            setCookie('blackjack_bet', bet)
        }
    }
    j.post(blackjack_params.ajaxurl, { // receive php params ( ajaxurl through blackjack_params )
        'action': 'blackjack', // action handler
        'blackjack_action': action,
        'blackjack_bet': bet
    }, function(d) {
        if (e != 'blackjack') {
			jSwitch(e);
            replaceHtml(e, d, true)
        } else {
            if (action == 'tostart') {
                replaceHtml(e, d, false);
                checkElement()
            } else {
                replaceHtml(e, d, false)
            }
        }
    });
    return false
}

j(function() {
    checkElement()
})