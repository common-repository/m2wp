/* Tooltips var. go from PHP side */
(function($) {
    $(function () {
		for (var name in tooltips) {
			var name_s = "[name='" + name + "']";
			jQuery(name_s).after('<img src="' + m2i_urls.img + '/grey_question.png" class="tooltip" title="' + tooltips[name] + '">');
			jQuery(name_s + ' + img').tooltip();
		}

		jQuery('img.m2i_status_check').tooltip();
    });
})(jQuery);