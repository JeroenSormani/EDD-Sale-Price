<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class EDDSP_Sale_Price.
 *
 * Sale price class manages the actual price changing.
 *
 * @class		EDDSP_Sale_Price
 * @version		1.0.0
 * @author		Jeroen Sormani
 */
class EDDSP_Sale_Price {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Modify simple price
		add_filter( 'edd_get_download_price', array( $this, 'maybe_display_sale_price' ), 10, 2 );

		// Modify variable prices
		add_filter( 'edd_get_variable_prices', array( $this, 'maybe_display_variable_sale_prices' ), 10, 2 );

		// Modify edd_price() function
		add_filter( 'edd_download_price_after_html', array( $this, 'edd_price_maybe_display_sale_price' ), 10, 4 );

		add_filter( 'edd_purchase_link_args', array( $this, 'maybe_display_sale_price_text' ) );

		// Checkout price
		add_filter( 'edd_cart_item_price_label', array( $this, 'checkout_maybe_display_sale_price' ), 10, 3 );

		// Variable price
		remove_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing' );
		add_action( 'edd_purchase_link_top', array( $this, 'edd_purchase_variable_pricing' ), 10, 2 );

	}


	/**
	 * Sale price.
	 *
	 * Display the simple sale price instead of the regular price.
	 * This method actually replaces the regular price with the sale price.
	 *
	 * @since 1.0.0
	 *
	 * @param	double 	$price 			Regular price of the product.
	 * @param	int		$download_id	ID of the download we're changing the price for.
	 * @return	double					The new price, if the product is in sale this will be the sale price.
	 */
	public function maybe_display_sale_price( $price, $download_id ) {

		// Bail if its admin - we don't want to change the regular price
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) :
			return $price;
		endif;

		$sale_price = get_post_meta( $download_id, 'edd_sale_price', true );

		if ( ! empty( $sale_price ) ) :
			$price = $sale_price;
		endif;

		return $price;

	}


	/**
	 * Sale price.
	 *
	 * Display the variable sale price instead of the regular price.
	 * This method actually replaces the regular price with the sale price.
	 *
	 * @since 1.0.0
	 *
	 * @param	array 	$prices 		Array of regular prices for a single product.
	 * @param	int		$download_id	ID of the download we're changing the price for.
	 * @return	double					Array of new prices, if the variant is in sale this will be the sale price.
	 */
	public function maybe_display_variable_sale_prices( $prices, $download_id ) {

		// Bail if its admin - we don't want to change the regular price
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) :
			return $prices;
		endif;

		foreach ( $prices as $key => $price ) :

			if ( isset( $price['sale_price'] ) && ! empty( $price['sale_price'] ) ) :
				$prices[ $key ]['regular_amount'] 	= $price['amount'];
				$prices[ $key ]['amount'] 			= $price['sale_price'];
			endif;

		endforeach;

		return $prices;

	}


	/**
	 * Sale price for edd_price().
	 *
	 * Display the sale price for the function edd_price().
	 *
	 * @since 1.0.0
	 *
	 * @param	string	$formatted_price 	Formatted price label, includes span wrapper.
	 * @param	int		$download_id		ID of the download.
	 * @param	string	$price				Formatted price label.
	 * @param	int		$price_id			If its a variable priced product, the price ID.
	 * @return	string						Formatted price label with sale price.
	 */
	public function edd_price_maybe_display_sale_price( $formatted_price, $download_id, $price, $price_id ) {

		if ( edd_has_variable_prices( $download_id ) ) :

			$prices = edd_get_variable_prices( $download_id );

			if ( false !== $price_id && isset( $prices[ $price_id ] ) ) {
				$regular_price 	= (float) $prices[ $price_id ]['regular_amount'];
				$sale_price 	= (float) $prices[ $price_id ]['sale_price'];
			} else {

				// Get lowest price id
				foreach ( $prices as $key => $price ) {

					if ( empty( $price['amount'] ) ) {
						continue;
					}

					if ( ! isset( $min ) ) {
						$min = $price['amount'];
					} else {
						$min = min( $min, $price['amount'] );
					}

					if ( $price['amount'] == $min ) {
						$min_id = $key;
					}

				}
				$lowest_id = $min_id;

				// Set prices
				$regular_price 	= isset( $prices[ $lowest_id ]['regular_amount'] ) ? $prices[ $lowest_id ]['regular_amount'] : $prices[ $lowest_id ]['amount'];
				$sale_price 	= isset( $prices[ $lowest_id ]['sale_price'] ) ? $prices[ $lowest_id ]['sale_price'] : null;

			}

		else :

			$regular_price 	= get_post_meta( $download_id, 'edd_price', true );
			$sale_price 	= get_post_meta( $download_id, 'edd_sale_price', true );

		endif;

		if ( isset( $sale_price ) && ! empty( $sale_price ) ) :
			$formatted_price = '<del>' . edd_currency_filter( edd_format_amount( $regular_price ) ) . '</del>&nbsp;' . $formatted_price;
		endif;

		return $formatted_price;

	}


	/**
	 * Purchase button sale price.
	 *
	 * Display the strikethrough regular price on the purchase button.
	 * Currently not enabled because it looks like value is loaded over JS,
	 * and the HTML tag 's' is visible for a second.
	 *
	 * @since 1.0.0
	 *
	 * @param	array	$args	List of arguments for the payment button.
	 * @return	array			List of arguments for the payment button.
	 */
	public function maybe_display_sale_price_text( $args ) {
return $args;

		$add_to_cart_text 	= edd_get_option( 'add_to_cart_text' );
		$default_args 		= apply_filters( 'edd_purchase_link_defaults', array(
			'text' => ! empty( $add_to_cart_text ) ? $add_to_cart_text : __( 'Purchase', 'edd' ),
		) );

		$download 			= new EDD_Download( $args['download_id'] );
		$variable_pricing	= $download->has_variable_prices();

		// Bail if its a variable priced button
		if ( $variable_pricing ) :
			return $args;
		endif;

		if ( $args['price'] && $args['price'] !== 'no' ) {
			$regular_price 	= get_post_meta( $args['download_id'], 'edd_price', true );
			$sale_price 	= get_post_meta( $args['download_id'], 'edd_sale_price', true );
		}

		if ( ! isset( $sale_price ) || empty( $sale_price ) ) :
			return $args;
		endif;

		$button_text = ! empty( $args['text'] ) ? '&nbsp;&ndash;&nbsp;' . $default_args['text'] : '';

		if ( isset( $sale_price ) && false !== $sale_price ) {

			if ( 0 != $sale_price ) {
				$args['text'] = '<s>' . edd_currency_filter( edd_format_amount( $regular_price ) ) . '</s>&nbsp;' . edd_currency_filter( edd_format_amount( $sale_price ) ) . $button_text;
			}

		}

		return $args;

	}


	/**
	 * Checkout sale price.
	 *
	 * Display the sale price, and the regular price with a strike at the checkout.
	 * This requires a hook added in EDD 2.3.0
	 *
	 * @since 1.0.0, EDD 2.4.0
	 *
	 * @param	double 	$price 			Regular price of the product.
	 * @param	int		$download_id	ID of the download we're changing the price for.
	 * @return	double					The new price, if the product is in sale this will be the sale price.
	 */
	public function checkout_maybe_display_sale_price( $label, $item_id, $options ) {

		global $edd_options;

		$download		= new EDD_Download( $item_id );
		$regular_price 	= get_post_meta( $item_id, 'edd_price', true );
		$price 			= edd_get_cart_item_price( $item_id, $options );

		// Get sale price if it exists
		if ( $download->has_variable_prices() ) :
			$prices = $download->get_prices();
			$sale_price = $prices[ $options['price_id'] ]['sale_price'];
		else :
			$sale_price	= get_post_meta( $item_id, 'edd_sale_price', true );
		endif;

		// Bail if no sale price is set
		if ( empty( $sale_price ) ) :
			return $label;
		endif;

		$label 		= '';
		$price_id 	= isset( $options['price_id'] ) ? $options['price_id'] : false;

		if ( ! edd_is_free_download( $item_id, $price_id ) && ! edd_download_is_tax_exclusive( $item_id ) ) {

			if ( edd_prices_show_tax_on_checkout() && ! edd_prices_include_tax() ) {

				$regular_price 	+= edd_get_cart_item_tax( $item_id, $options, $regular_price );
				$price 			+= edd_get_cart_item_tax( $item_id, $options, $price );

			} if ( ! edd_prices_show_tax_on_checkout() && edd_prices_include_tax() ) {

				$regular_price 	-= edd_get_cart_item_tax( $item_id, $options, $regular_price );
				$price 			-= edd_get_cart_item_tax( $item_id, $options, $price );

			}

			if ( edd_display_tax_rate() ) {

				$label = '&nbsp;&ndash;&nbsp;';

				if ( edd_prices_show_tax_on_checkout() ) {
					$label .= sprintf( __( 'includes %s tax', 'edd' ), edd_get_formatted_tax_rate() );
				} else {
					$label .= sprintf( __( 'excludes %s tax', 'edd' ), edd_get_formatted_tax_rate() );
				}

				$label = apply_filters( 'edd_cart_item_tax_description', $label, $item_id, $options );

			}
		}

		$regular_price 	= '<del>' . edd_currency_filter( edd_format_amount( $regular_price ) ) . '</del>';
		$price 			= edd_currency_filter( edd_format_amount( $price ) );

		return $regular_price . ' ' . $price . $label;

	}


	/**
	 * Display variable price.
	 *
	 * Display the variable price with a strikethrough in the list.
	 * NOTE! This function replaces an entire EDD function!
	 *
	 * @since 1.0.0
	 *
	 * @param	int		$download_id	ID of the download to get the labels for.
	 * @param	array	$args			Array of arguments related to the download price.
	 */
	public function edd_purchase_variable_pricing( $download_id = 0, $args = array() ) {

		global $edd_options;

		$variable_pricing = edd_has_variable_prices( $download_id );
		$prices = apply_filters( 'edd_purchase_variable_prices', edd_get_variable_prices( $download_id ), $download_id );

		if ( ! $variable_pricing || ( false !== $args['price_id'] && isset( $prices[$args['price_id']] ) ) ) {
			return;
		}

		if ( edd_item_in_cart( $download_id ) && ! edd_single_price_option_mode( $download_id ) ) {
			return;
		}

		$type = edd_single_price_option_mode( $download_id ) ? 'checkbox' : 'radio';
		$mode = edd_single_price_option_mode( $download_id ) ? 'multi' : 'single';

		do_action( 'edd_before_price_options', $download_id ); ?>
		<div class="edd_price_options edd_<?php echo esc_attr( $mode ); ?>_mode">
			<ul>
				<?php
				if ( $prices ) :
					$checked_key = isset( $_GET['price_option'] ) ? absint( $_GET['price_option'] ) : edd_get_default_variable_price( $download_id );
					foreach ( $prices as $key => $price ) :

						?><li id="edd_price_option_<?php echo $download_id . '_' . sanitize_key( $price['name'] ); ?>" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
							<label for="<?php echo esc_attr( 'edd_price_option_' . $download_id . '_' . $key ); ?>">
								<input type="<?php echo $type; ?>" <?php checked( apply_filters( 'edd_price_option_checked', $checked_key, $download_id, $key ), $key ); ?>
									name="edd_options[price_id][]" id="<?php echo esc_attr( 'edd_price_option_' . $download_id . '_' . $key ); ?>"
									class="<?php echo esc_attr( 'edd_price_option_' . $download_id ); ?>" value="<?php echo esc_attr( $key ); ?>"/>
									<span class='edd_price_option_wrap'>
										<span class="edd_price_option_name" itemprop="description"><?php echo esc_html( $price['name'] ); ?></span>
										<span class="edd_price_option_sep">&ndash;</span>&nbsp;<?php

										if ( isset( $price['sale_price'] ) && ! empty( $price['sale_price'] ) && isset( $price['regular_amount'] ) ) :
											?><span class="edd_price_option_price regular_price" itemprop="price"><del><?php
												echo edd_currency_filter( edd_format_amount( $price['regular_amount'] ) );
											?></del></span>&nbsp;<?php
										endif;

										?><span class="edd_price_option_price" itemprop="price"><?php echo edd_currency_filter( edd_format_amount( $price['amount'] ) ); ?></span>
									</span>
							</label><?php
								do_action( 'edd_after_price_option', $key, $price, $download_id );
						?></li><?php

					endforeach;
				endif;
				do_action( 'edd_after_price_options_list', $download_id, $prices, $type );
				?>
			</ul>
		</div><!--end .edd_price_options-->
		<?php
		do_action( 'edd_after_price_options', $download_id );

	}


}
