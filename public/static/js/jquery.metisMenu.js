;(function ($, window, document, undefined) {

    var pluginName = "metisMenu",
        defaults = {
            toggle: true
        };

    function Plugin(element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    Plugin.prototype = {
        init: function () {
            var $this = $(this.element),
                $toggle = this.settings.toggle;
            $this.find('li').has('ul').children('ul').children('li').children('a').removeClass('active-menu');
            var url = window.location.href;
            var aaa = ($this.find('li').has('ul').children('ul').children('li').children('a'));
            aaa.each(function(){
                //var mstr = ($(this).attr('href').substring(0,$(this).attr('href').lastIndexOf('/')));
                var mstr = $(this).attr('href');
                if(url.indexOf(mstr)!=-1){
                    $(this).addClass('active-menu');
                    $(this).parent('li').parent('ul').parent('li').addClass('in active');
                }
            });
            $this.find('li.active').has('ul').children('ul').addClass('collapse in');
            $this.find('li').not('.active').has('ul').children('ul').addClass('collapse');
            $this.find('li').has('ul').children('a').on('click', function (e) {
                e.preventDefault();
                $(this).parent('li').toggleClass('active').children('ul').collapse('toggle');
                if ($toggle) {
                    $(this).parent('li').siblings().removeClass('active').children('ul.in').collapse('hide');
                }
            });
        }
    };

    $.fn[ pluginName ] = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);
