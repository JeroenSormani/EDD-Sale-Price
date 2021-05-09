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
		add_filter( 'edd_price_option_output', array( $this, 'add_sales_price' ), 10, 6 );

	}


	private function get_regular_price( $item_id ) {
		$price = get_post_meta( $item_id, 'edd_price', true );

		return edd_sanitize_amount( $price );
	}

	private function get_sale_price( $item_id ) {
		$price = get_post_meta( $item_id, 'edd_sale_price', true );

		return is_numeric( $price ) ? edd_sanitize_amount( $price ) : false;
	}


	/**
	 * Sale price.
	 *
	 * Display the simple sale price instead of the regular price.
	 * This method actually replaces the regular price with the sale price.
	 *
	 * @since 1.0.0
	 *
	 * @param	float 	$price 			Regular price of the product.
	 * @param	int		$download_id	ID of the download we're changing the price for.
	 * @return	float					The new price, if the product is in sale this will be the sale price.
	 */
	public function maybe_display_sale_price( $price, $download_id ) {

		// Bail if its admin - we don't want to change the regular price
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) :
			return $price;
		endif;

		$sale_price = $this->get_sale_price( $download_id );

		if ( is_numeric( $sale_price ) ) {
			$price = $sale_price;
		}

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
	 * @return	float					Array of new prices, if the variant is in sale this will be the sale price.
	 */
	public function maybe_display_variable_sale_prices( $prices, $download_id ) {

		// Bail if its admin - we don't want to change the regular price
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) :
			return $prices;
		endif;

		if ( is_array( $prices ) ) :
			foreach ( $prices as $key => $price ) :

				if ( isset( $price['sale_price'] ) && is_numeric( $price['sale_price'] ) ) :
					$prices[ $key ]['regular_amount'] = $price['amount'];
					$prices[ $key ]['amount']         = $price['sale_price'];
				endif;

			endforeach;
		endif;

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

			$regular_price 	= $this->get_regular_price( $download_id );
			$sale_price 	= $this->get_sale_price( $download_id );

		endif;

		if ( is_numeric( $sale_price ) ) :
			$formatted_price = '<del>' . edd_currency_filter( edd_format_amount( $regular_price ) ) . '</del>&nbsp;' . edd_currency_filter( edd_format_amount( $sale_price ) );
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

		if ( ! apply_filters( 'eddsp_display_regular_price_text_buy_button', false ) ) {
			return $args;
		}

		$add_to_cart_text 	= edd_get_option( 'add_to_cart_text' );
		$default_args 		= apply_filters( 'edd_purchase_link_defaults', array(
			'text' => ! empty( $add_to_cart_text ) ? $add_to_cart_text : __( 'Purchase', 'edd' ),
		) );

		$download 			= new EDD_Download( $args['download_id'] );
		$variable_pricing	= $download->has_variable_prices();

		// Bail if its a variable priced button
		if ( $variable_pricing ) {
			return $args;
		}

		if ( $args['price'] && $args['price'] !== 'no' ) {
			$regular_price 	= $this->get_regular_price( $args['download_id'] );
			$sale_price 	= $this->get_sale_price( $args['download_id'] );
		}

		if ( ! isset( $sale_price ) || ! is_numeric( $sale_price ) ) {
			return $args;
		}

		$button_text = ! empty( $args['text'] ) ? '&nbsp;&ndash;&nbsp;' . $default_args['text'] : '';

		if ( isset( $sale_price ) && false !== $sale_price ) {
			$args['text'] = '<s>' . edd_currency_filter( edd_format_amount( $regular_price ) ) . '</s>&nbsp;' . edd_currency_filter( edd_format_amount( $sale_price ) ) . $button_text;
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
	 * @param	float 	$price 			Regular price of the product.
	 * @param	int		$download_id	ID of the download we're changing the price for.
	 * @return	float					The new price, if the product is in sale this will be the sale price.
	 */
	public function checkout_maybe_display_sale_price( $label, $item_id, $options ) {

		global $edd_options;

		$download		= new EDD_Download( $item_id );
		$regular_price 	= $this->get_regular_price( $item_id );
		$price 			= edd_get_cart_item_price( $item_id, $options );

		// Get sale price if it exists
		if ( $download->has_variable_prices() ) :
			$prices = $download->get_prices();
			$regular_price 	= isset( $prices[ $options['price_id'] ]['regular_amount'] ) ? $prices[ $options['price_id'] ]['regular_amount'] : $regular_price;
			$sale_price 	= isset( $prices[ $options['price_id'] ]['sale_price'] ) ? $prices[ $options['price_id'] ]['sale_price'] : '';
		else :
			$sale_price	= $this->get_sale_price( $item_id );
		endif;

		// Bail if no sale price is set
		if ( ! is_numeric( $sale_price ) ) :
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
	 * Display the variable price with a strikethrough in the list.
	 *
	 * @since 1.0.4
	 *
	 * @param	string	$price_output	The HTML output of the variable price
	 * @param	int		$download_id	The ID of the download being viewed
	 * @param	int		$key			The key of this variable price in the array of variable prices for this product.
	 * @param	array	$price			The array of data about this price
	 * @param	string	$form_id		The HTML ID of the form containing these variable prices
	 * @param	string	$item_prop		The HTML item prop attribute
	 * @param	string	$price_output	The filtered/modified HTML output of the variable price
	 */
	public function add_sales_price( $price_output, $download_id, $key, $price, $form_id, $item_prop ) {

		if ( isset( $price['sale_price'] ) && is_numeric( $price['sale_price'] ) && isset( $price['regular_amount'] ) ) :

			// Re-construct the price output to include the sale price strikethrough.
			$price_output = '<span class="edd_price_option_name"' . $item_prop . '>' . esc_html( $price['name'] ) . '</span><span class="edd_price_option_sep">&nbsp;&ndash;&nbsp;</span><span class="edd_price_option_price regular_price" itemprop="price"><del>' . edd_currency_filter( edd_format_amount( $price['regular_amount'] ) ) . '</del></span>&nbsp;<span class="edd_price_option_price">' . edd_currency_filter( edd_format_amount( $price['amount'] ) ) . '</span>';

		endif;

		return $price_output;

	}


}
