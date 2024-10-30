(function($) {
    $(function() {
		$.m2i_create_select = function () {
			$(".m2i-product-search").each(function (e) {
				if ($(this).attr('id').match(/.+(__i__).+/)) {
                    return;
                }
				$(this).select2({
					ajax: {
						url: ajaxurl,
						type: 'post',
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								action: 'search_products',
								data: {
									q: params.term,
									page: params.page
								},
								security: m2i_search.nonce
							};
						},
						processResults: function (data, params) {
							params.page = params.page || 1;

							return {
								results: data.items,
								pagination: {
									more: (params.page * 10) < data.total_count
								}
							};
						},
						cache: true
					},
					escapeMarkup: function (markup) {
						return markup;
					},
					minimumInputLength: 1
				});
			});
		};
    });
})(jQuery);