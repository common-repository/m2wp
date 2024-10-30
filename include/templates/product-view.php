<?php

defined( 'ABSPATH' ) || exit;

/**
 * M2I built-in product-view template
 * 
 * @version 1.3
 * 
 * @since 1.0.6
 *
 * This template can be overridden by copying it to yourtheme/m2i-templates/product-view.php
 * 
 * HOWEVER, on occasion Magento 2 WordPress Integration will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * 
 */

/* @var $product \Magento\Catalog\Model\Product */
$id = $product->getId();
$addToCartUrl = $block->getAddToCartUrl( $product );
$addToCartPostData = wp_json_encode( array(
    'product' => $product->getEntityId(),
    'form_key' => $obj->get( '\Magento\Framework\Data\Form\FormKey' )->getFormKey()
) );
$productUrl = m2i_get_product_url( $product );
if ( ! $product->isAvailable() ) {
    return;
}
?>
<div class="m2i-single-product">
	<div class="product-image">
		<a href="<?php echo $block->escapeUrl( $productUrl ); ?>">
			<?php
			try {
				$image = $block->getImage( $product, 'category_page_grid' )->toHtml();
				echo $image;
			} catch ( Exception $e ) {
				?>
				<img src="<?php echo $mediaBaseUrl . $product->getImage(); ?>">
			<?php } ?>
		</a>
	</div>
	<div class="product-info">
		<h2 class="product-name">
			<a title="<?php echo $block->escapeHtml( $product->getName() ) ?>" 
			   href="<?php echo $block->escapeUrl( $productUrl ); ?>"><?php echo $block->escapeHtml( $product->getName() ) ?></a>
		</h2>
		<?php if ( $product->isInStock() ): ?>
			<strong class="in-stock"><?php echo $block->escapeHtml( __( 'In Stock' ) ); ?></strong>
		<?php endif; ?>
		<span><?php echo $block->escapeHtml( __( 'SKU:' ) ), $product->getSku(); ?></span>
		<div class="price">
			<?php
			echo $block->getProductPriceHtml(
				$product, \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE, \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST, array(
			    'price_id_suffix' => '-m2i'
				)
			);
			?>
		</div>
		<div class="to-cart">
			<button class="action tocart primary product-id-<?php echo $id; ?>"
				type="button" title="<?php echo $block->escapeHtmlAttr( __( 'Add to Cart' ) ); ?>">
				<span><?php echo $block->escapeHtml( __( 'Add to Cart' ) ); ?></span>
			</button>
			<label for="qty"><?php echo $block->escapeHtml( __( 'Quantity' ) ); ?>&nbsp;
				<input name="qty" type="number" min="1" class="qty product-id-<?php echo $id; ?>" value="1"/>
			</label>
		</div>
	</div>
	<div class="m2i-clear"></div>
	<script>
		jQuery(function () {
			jQuery('button.product-id-<?php echo $id; ?>').first().click(function (e) {
				e.preventDefault();
				var location = "<?php echo $addToCartUrl; ?>";
				var postData = <?php echo $addToCartPostData; ?>;
				var $this = jQuery(this);
				postData['qty'] = jQuery('input.qty.product-id-<?php echo $id; ?>').attr('value');
				jQuery.ajax({
					type: 'post',
					url: location,
					data: postData,
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
							window.location.href = '<?php echo $productUrl; ?>';
						}, 500);
					}
				});
				return false;
			});
		});
	</script>
</div>
