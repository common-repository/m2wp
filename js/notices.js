(function($) {
    $(function () {
        $('.notice').click(function (event) {
            var $el = $(event.target);
            console.log($el.parent().attr('id'));
            if ($el.hasClass('notice-dismiss')) {
                $.post(ajaxurl, {
                    'action': 'm2i_notices',
                    'id': $el.parent().attr('id')
                });
            }
        });
    });
})(jQuery);