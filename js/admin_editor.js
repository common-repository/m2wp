/** 
 * @since 1.0.6
 */
(function($) {
    $(function() {
    	var $m2i_shortcodes_options_modal = $('#' + m2i_shortcodes_options_modal);
        $m2i_shortcodes_options_modal.insertAfter('#wpfooter');
        $m2i_shortcodes_options_modal.find('div[data-class]').each(function () {
            $(this).html('<form>' + $(this).html() + '</form>');
        });

        var $m2i_shortcode_frame_button = $('#' + m2i_shortcodes_options_modal + ' .button'),
            $m2i_shortcode_button = $('a.button.thickbox.m2i-add-shortcode'),
            m2i_modal_dimensions = {height: 400, width: 700},
            m2i_get_modal_css = function () {
                return {
                    marginLeft: '-' + parseInt((m2i_modal_dimensions.width / 2)) + 'px',
                    width: m2i_modal_dimensions.width + 'px',
                    height: m2i_modal_dimensions.height + 'px',
                    marginTop: (parseInt((m2i_modal_dimensions.height / 2)) - 50) + 'px'
                };
            };

        var m2i_insert_shorcode_fun = function () {
            var $modal = $(this).parent().parent();
            var $active_div = $modal.find(($modal.find('li.ui-state-active > a').attr('href')));
            var $form = $active_div.find('form'), widget_class = $active_div.attr('data-class');
            var form_data = new FormData($form.get(0));
            form_data.append('class', widget_class);
            $.ajax({
                type: 'POST',
                url: ajaxurl + '?action=m2i_get_shortcode',
                processData: false,
                contentType: false,
                data: form_data,
                success: function (data) {
                    send_to_editor(data);
                },
            });
        };
        var $m2i_shortcodes_modal_clone = $m2i_shortcodes_options_modal.clone();
        $m2i_shortcodes_options_modal.children('#m2i-modal-tabs').tabs();

        $m2i_shortcode_button.click(function (e) {
            e.preventDefault();
            tb_show(this.title, this.href, false);
            $("#TB_window").css(m2i_get_modal_css()).attr('data-m2i-modal', 'true');
            $("#TB_ajaxContent").css({width: (m2i_modal_dimensions.width - 30) + 'px', height: (m2i_modal_dimensions.height - 17 - 29) + 'px'});
            if ($('body').hasClass('post-new-php')) {
                var $clone = $m2i_shortcodes_modal_clone.clone();
                $clone.find('#m2i-modal-tabs').tabs();
                $clone.find('.button').click(m2i_insert_shorcode_fun);
                $("#TB_ajaxContent").append($clone.children());
            }
            this.blur();
            return false;
        });

        $m2i_shortcode_frame_button.click(m2i_insert_shorcode_fun);

        $(window).resize(function () {
            $('#TB_window[data-m2i-modal="true"]').css(m2i_get_modal_css());
        });
    });
})(jQuery);