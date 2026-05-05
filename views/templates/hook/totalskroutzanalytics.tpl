{**
 * 2007-2026 PrestaShop
 *
 * @author    Gerasimos Antypas
 * @copyright Copyright (c) Netcraftgr
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *}
<!-- Skroutz Analytics Order Products Script start -->
{if $skroutz_cod_payment_name && $order->payment == $skroutz_cod_payment_name}
<script>
	{literal}
		skroutz_analytics('ecommerce', 'addOrder',{
			order_id:    '{/literal}{$order->id_cart|escape:'quotes':'UTF-8'}{literal}',                                                                // Order ID. Required.
			revenue:     '{/literal}{$order->total_products_wt + $order->total_shipping_tax_incl - $skroutz_cod_fee|escape:'quotes':'UTF-8'}{literal}',	  // Grand Total. Includes Tax and Shipping.
			shipping:    '{/literal}{$order->total_shipping_tax_incl - $skroutz_cod_fee|escape:'quotes':'UTF-8'}{literal}',                                  // Total Shipping Cost.
			tax:         '{/literal}{$taxamt = $order->total_paid_tax_incl - $order->total_paid_tax_excl}{$taxamt|escape:'quotes':'UTF-8'}{literal}'	// Total Tax.
		});
	{/literal}
</script>
{else}
<script>
	{literal}
		skroutz_analytics('ecommerce', 'addOrder', {
			order_id:    '{/literal}{$order->id_cart|escape:'quotes':'UTF-8'}{literal}',                                                                // Order ID. Required.
			revenue:     '{/literal}{$order->total_products_wt + $order->total_shipping_tax_incl|escape:'quotes':'UTF-8'}{literal}',	  // Grand Total. Includes Tax and Shipping.
			shipping:    '{/literal}{$order->total_shipping_tax_incl|escape:'quotes':'UTF-8'}{literal}',                                                // Total Shipping Cost.
			tax:         '{/literal}{$taxamt = $order->total_paid_tax_incl - $order->total_paid_tax_excl}{$taxamt|escape:'quotes':'UTF-8'}{literal}'	// Total Tax.
		});
	{/literal}
</script>


{/if}

<script>
{foreach from=$order_products item=product}
{if isset($product.product_attribute_id) && $product.product_attribute_id}
	{assign var=skroutz_product_id value="`$product.product_id`.`$product.product_attribute_id`"}
{else}
	{assign var=skroutz_product_id value=$product.product_id}
{/if}

		{literal}
			skroutz_analytics('ecommerce', 'addItem',{
			order_id:   '{/literal}{$order->id_cart|escape:'quotes':'UTF-8'}{literal}',                  // Order ID. Required.
			product_id: '{/literal}{$skroutz_product_id|escape:'quotes':'UTF-8'}{literal}',              // Product ID. Required.
			name:       '{/literal}{$product.product_name|escape:'quotes':'UTF-8'}{literal}',            // Product Name. Required.
			price:      '{/literal}{$product.product_price_wt|escape:'quotes':'UTF-8'}{literal}',    // Price per Unit. Required.
			quantity:   '{/literal}{$product.product_quantity|escape:'quotes':'UTF-8'}{literal}'         // Quantity of Items. Required.
	  		});
	  	{/literal}

{/foreach}
</script>


<!-- Skroutz Analytics Order Products Script end -->
