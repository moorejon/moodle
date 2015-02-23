var block_elisdashboard_addradioactions = function() {
    var showwidgetinfo = function(widget) {
        var widgetinfoelements = document.getElementsByClassName('block_elisdashboard_widgetinfo');
        for (var i = 0; i < widgetinfoelements.length; i++) {
            if (widgetinfoelements[i].getAttribute('data-eliswidgetident') === widget) {
                widgetinfoelements[i].style.display = 'block';
            } else {
                widgetinfoelements[i].style.display = 'none';
            }
        }
    }

    var widgetradioelements = document.getElementsByClassName('block_elisdashboard_widgetradio');
    for (var i = 0; i < widgetradioelements.length; i++) {
        widgetradioelements[i].onchange = function(){
            var widgetident = this.getAttribute('data-eliswidgetident');
            showwidgetinfo(widgetident);
        };
    }
}

block_elisdashboard_addradioactions();