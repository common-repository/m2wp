<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I built-in slider template
 * 
 * @version 1.3.1
 * 
 * @since 1.0.6
 *
 * This template can be overridden by copying it to yourtheme/m2i-templates/slider.php
 * 
 * HOWEVER, on occasion Magento 2 WordPress Integration will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * 
 */

$mediaBaseUrl = $mediaBaseUrl . '/catalog/product/';
?>
<script>
	if (typeof _addToCart === 'undefined') {
		function _addToCart(location, postData, productUrl) {
			var $this = jQuery(this);
			jQuery.ajax({
				type: 'post',
				url: location,
				data: jQuery.parseJSON(postData),
				dataType: 'json',
				cache: false,
				beforeSend: function () {
					var addToCartButtonTextAdding = '<?php echo $block->escapeHtml( __( 'Adding...' ) ); ?>';
					$this.addClass('disabled');
					$this.find('span').text(addToCartButtonTextAdding);
					$this.attr('title', addToCartButtonTextAdding);
				},
				success: function (data) {
					if (data.backUrl) {
						window.location.href = data.backUrl;
					}
					var addToCartButtonTextAdded = '<?php echo $block->escapeHtml( __( 'Added' ) ); ?>';

					$this.find('span').text(addToCartButtonTextAdded);
					$this.attr('title', addToCartButtonTextAdded);

					setTimeout(function () {
						var addToCartButtonTextDefault = '<?php echo $block->escapeHtml( __( 'Add to Cart' ) ); ?>';

						$this.removeClass('disabled');
						$this.find('span').text(addToCartButtonTextDefault);
						$this.attr('title', addToCartButtonTextDefault);
					}, 1000);
				},
				error: function () {
					var redirectingUrl = '<?php echo $block->escapeHtml( __( 'Redirecting to product page...' ) ); ?>';
					$this.find('span').text(redirectingUrl);
					$this.attr('title', redirectingUrl);
					setTimeout(function () {
						window.location.href = productUrl;
					}, 500);
				}
			});
		}
	}
</script>
<ul id="<?php echo $attrs['dom_id']; ?>" class="m2i-slider">
	<?php
	/* @var $product \Magento\Catalog\Model\Product */
	foreach ( m2i_get_category_products( $attrs['cats_ids'] ) as $product ):
		$id = $product->getId();
		$addToCartUrl = $block->getAddToCartUrl( $product );
		$addToCartPostData = wp_json_encode( array(
		    'product' => $product->getEntityId(),
		    'form_key' => $obj->get( '\Magento\Framework\Data\Form\FormKey' )->getFormKey()
		) );
		$productUrl = m2i_get_product_url( $product );
		?>
		<li class="m2i-product">
			<a href="<?php echo $block->escapeUrl( $productUrl ); ?>">
				<?php
				try {
					$image = $block->getImage( $product, 'category_page_grid' )->toHtml();
					echo $image;
				} catch ( Exception $e ) {
					?>
					<img src="<?php echo $mediaBaseUrl . $product->getImage(); ?>" />
				<?php } ?>
			</a>
			<button class="action primary" 
				onclick="_addToCart.call(this, '<?php echo $addToCartUrl; ?>', '<?php echo $block->escapeHtmlAttr( $addToCartPostData ); ?>', '<?php echo $productUrl; ?>')"
				type="button" 
				title="<?php echo $block->escapeHtmlAttr( __( 'Add to Cart' ) ); ?>">
				<span><?php echo $block->escapeHtml( __( 'Add to Cart' ) ); ?></span>
			</button>
			<p class="product-name">
				<strong>
					<a title="<?php echo $block->escapeHtml( $product->getName() ); ?>" 
					   href="<?php echo $block->escapeUrl( $productUrl ); ?>"><?php echo $block->escapeHtml( $product->getName() ); ?></a>
				</strong>
			</p>
			<?php
			echo $block->getProductPriceHtml(
				$product, 
				\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
				\Magento\Framework\Pricing\Render::ZONE_ITEM_LIST, 
				array('price_id_suffix' => '-m2i')
			);
			?>
		</li>
	<?php endforeach; ?>
</ul>
<script>
	jQuery(function () {
		var sliderWidth = jQuery('#<?php echo $attrs['dom_id']; ?>').width();
		var q = <?php echo $attrs['qty']; ?>;
		var a = <?php echo $attrs['margin']; ?> / 100.0;
		var minWidth = 150.0;
		q = Math.min(parseInt(sliderWidth / minWidth), q);
		jQuery('#<?php echo $attrs['dom_id']; ?>').bxSlider({
			slideWidth: (1 - a) * sliderWidth / (q * a + (q - 0.5) * (1 - a)),
			minSlides: q,
			maxSlides: <?php echo $attrs['qty']; ?>,
			slideMargin: a * sliderWidth / (q * a + (q - 0.5) * (1 - a)),
			shrinkItems: true,
            touchEnabled: false
		});
	});
</script>
