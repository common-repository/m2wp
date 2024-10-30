(function($) {
    $(function() {
		var $m2i_tds = {
			dir: jQuery('#m2i_mage_dir').parent()
		};

		var m2i_imgs_src = {
			loading: m2i_urls.img + '/loading.gif',
			question: m2i_urls.img + '/question.png',
			success: m2i_urls.img + '/success.png',
			exclam: m2i_urls.img + '/exclam.png'
		};

		if ($m2i_tds.dir.length) {
			$m2i_tds.dir.append('<img class="m2i_status_check" src="" />');

			var m2i_check_ajax = function () {
				$m2i_tds.dir.find('img.m2i_status_check').attr('src', m2i_imgs_src.loading);

				var dir_input = $m2i_tds.dir.find('input[type="text"]');
				var submit_button = $('#submit');

				var dir_already_disabled = dir_input.hasClass('disabled');

				if (!dir_already_disabled) {
					dir_input.addClass('disabled').attr('disabled', 'disabled');
					submit_button.addClass('disabled').attr('disabled', 'disabled');
				}

				$.post(ajaxurl, {
					'action': 'm2i_check_magento',
					'm2i_mage_dir': dir_input.val(),
					'm2i_mage_store': $('#m2i_mage_store_code option:selected').val()
				}, function (response) {
					var dir_src = m2i_imgs_src.question, title = '';

					if (typeof response.success !== 'undefined') {
						if (response.success) {
							dir_src = m2i_imgs_src.success;
							title = response.data;
						} else {
							for (var i in response.data) {
								title += response.data[i].message + ' ';
								switch( response.data[i].code ){
									case 'autoload_file__error':
									case 'bootstrap_class__error':
										dir_src = m2i_imgs_src.exclam;
										break;
								}
							}
						}
					}

					var $img_status = $m2i_tds.dir.find('img.m2i_status_check');
					$img_status.attr('src', dir_src);
					$img_status.attr('title', title);

					if ( !dir_already_disabled) {
						dir_input.removeClass('disabled').removeAttr('disabled');
						submit_button.removeClass('disabled').removeAttr('disabled');
					}

				});
			};

			m2i_check_ajax();

			$m2i_tds.dir.find('input[type="text"]').change(function () {
				m2i_check_ajax();
			});
		}

		$.fn.flag_dependencies = function () {
			var ids_hide = (this.data('dependencies-hide') || '').split(',');
			var ids_show = (this.data('dependencies-show') || '').split(',');
			var ids_hide_selector = '';
			var ids_show_selector = '';
			var $this = this;

			for (var i = 0; i < ids_hide.length; i++)
				ids_hide_selector += '#' + ids_hide[i] + ',';
			if (ids_hide_selector !== '')
				ids_hide_selector = ids_hide_selector.slice(0, -1);

			for (var i = 0; i < ids_show.length; i++)
				ids_show_selector += '#' + ids_show[i] + ',';
			if (ids_show_selector !== '')
				ids_show_selector = ids_show_selector.slice(0, -1);

			var action = function () {
				if ($this.get(0).checked) {
					$(ids_hide_selector).parent().parent().hide(250);
					$(ids_show_selector).parent().parent().show(250);
				} else {
					$(ids_hide_selector).parent().parent().show(250);
					$(ids_show_selector).parent().parent().hide(250);
				}
			};

			action();
			this.change(action);
		};

		$('input[type="checkbox"]').each(function () {
			$(this).flag_dependencies();
		});

		$.fn.select_multi_ordering = function () {
			var $this = $(this), $select = $this.clone();
			var $minus = $('<a class="multi-ordering" data-role="minus">&ndash;</a>').insertAfter($this);
			var $plus = $('<a class="multi-ordering" data-role="plus">+</a>').insertAfter($this);
			if (!m2i_options.disable_select2) {
				$this.select2( { tags: true } );
			}
			$select.removeAttr('id').removeAttr('data-others').find('option[selected]').removeAttr('selected');

			var others = $this.attr('data-others');
			if (others !== undefined) {
				others = JSON.parse(others);
				for (var i = others.length - 1; i >= 0; i--) {
					var $new_select = $select.clone();
					var $found_child = $new_select.children('option[value="' + others[i] + '"]');
					if ( $found_child.size() ) {
						$found_child.attr('selected', 'selected');
					} else {
						$new_select.append('<option value="' + others[i] + '" selected="selected">' + others[i] + '</option>');
					}
					$new_select.insertAfter($this);
				}
			}

			$plus.click(function () {
				if (m2i_options.disable_select2) {
					$select.clone().insertBefore($(this));
				} else {
					$select.clone().insertBefore($(this)).select2( { tags: true } );
				}
			});

			$minus.click(function () {
				$(this).prev().prev().remove();
				if (!m2i_options.disable_select2) {
					$(this).prev().prev().remove();
				}
			});
		};

		$('select[data-type="multi-ordering"][id]:not([multiple])').each(function () {
			$(this).select_multi_ordering();
		});
		if (!m2i_options.disable_select2) {
			$('select:not(#m2i_mage_store_code)').select2( { tags: true } );
		}

		$('#m2i_mage_store_code').change(function (e) {
			var val = $('#m2i_mage_store_code option:selected').val();
			$('#m2i_mage_default_store_code').val(val);
			m2i_check_ajax();
		});
		$('#m2i_mage_default_store_code').change(function () {
			var val = $('#m2i_mage_default_store_code').val();
			$('#m2i_mage_store_code option:selected').removeAttr("selected");
			$('#m2i_mage_store_code').append($('<option>', {value: val}).text(val).attr("selected", "selected"));
			m2i_check_ajax();
		});

		if (!m2i_options.disable_select2) {
			var select = $("#m2i_mage_store_code");
			var options = select.find('option').map(function (i, e) {
				return {
					id: $(e).val(),
					text: $(e).text(),
					element: {selected: false}
				};
			});
			select.select2({
				data: options,
				tags: true
			});
		}
    });
})(jQuery);