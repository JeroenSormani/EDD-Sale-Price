<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class EDDSP_Admin_Product.
 *
 * Admin product class adds settings to the product edit screen.
 *
 * @class		EDDSP_Admin_Product
 * @version		1.0.0
 * @author		Jeroen Sormani
 */
class EDDSP_Admin_Product {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add simple sale price field
		add_action( 'edd_after_price_field', array( $this, 'simple_sale_price_field' ) );

		// Save custom sale field
		add_action( 'edd_save_download', array( $this, 'save_custom_sale_fields' ) );

		/*************************
		 * Variable price hooks
		 ************************/

		// Add sale price to args
		add_filter( 'edd_price_row_args', array( $this, 'edd_price_row_args' ), 10, 2 );

		// Display sale price field
		add_action( 'edd_download_price_option_row', array( $this, 'variable_sale_price_field' ), 1, 3 );


		register_meta(
			'post',
			'edd_sale_price',
			array(
				'object_subtype'    => 'download',
				'sanitize_callback' => array( $this, 'sanitize_price' ),
				'type'              => 'float',
				'description'       => __( 'The sale price of the product.', 'easy-digital-downloads' ),
				'show_in_rest'      => true,
			)
		);

		if ( ! has_filter( 'sanitize_post_meta_edd_sale_price' ) ) {
			add_filter( 'sanitize_post_meta_edd_sale_price', array( $this, 'sanitize_price' ), 10, 4 );
		}
	}


	/**
	 * Sale price field.
	 *
	 * Display the simple sale price field below the normal price field.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the current download being edited.
	 */
	public function simple_sale_price_field( $post_id ) {

		$price 				= edd_get_download_price( $post_id );
		$sale_price			= get_post_meta( $post_id, 'edd_sale_price', true );
		$variable_pricing 	= edd_has_variable_prices( $post_id );
		$prices				= edd_get_variable_prices( $post_id );
		$single_option_mode	= edd_single_price_option_mode( $post_id );

		$price_display		= $variable_pricing ? ' style="display:none;"' : '';
		$variable_display	= $variable_pricing ? '' : ' style="display:none;"';

		?><div id="edd_regular_sale_price_field" class="edd_pricing_fields" <?php echo $price_display; ?>><?php

			$price_args = array(
				'name'	=> 'edd_sale_price',
				'value' => is_numeric( $sale_price ) ? esc_attr( edd_format_amount( $sale_price ) ) : '',
				'class'	=> 'edd-price-field edd-sale-price-field'
			);

			$currency_position = edd_get_option( 'currency_position' );
			if ( empty( $currency_position ) || $currency_position == 'before' ) :
				echo edd_currency_filter( '' ) . ' ' . EDD()->html->text( $price_args ) . ' ';
			else :
				echo EDD()->html->text( $price_args ) . ' ' . edd_currency_filter( '' ) . ' ';
			endif;

			_e( 'Sale price', 'edd-sale-price' );

		?></div><?php

	}


	/**
	 * Save sale price.
	 *
	 * Save the sale price
	 *
	 * @since 1.0.0
	 *
	 * @param	integer $post_id 	Post ID for this product
	 */
	public function save_custom_sale_fields( $post_id ) {

		if ( isset( $_POST[ 'edd_sale_price' ] ) ) {
			$new = sanitize_text_field( $_POST[ 'edd_sale_price' ] );
			
			if ( '' !== $new ) {
				$new = apply_filters( 'edd_metabox_save_edd_sale_price', $_POST[ 'edd_sale_price' ] );
			}

			update_post_meta( $post_id, 'edd_sale_price', $new );
		}

	}

	/**
	 * Perform some sanitization on the amount field including not allowing negative values by default
	 *
	 * @since  1.0.5
	 * @param  float $price The price to sanitize
	 * @return float        A sanitized price
	 */
	public function sanitize_price( $price ) {

		$allow_negative_prices = apply_filters( 'edd_allow_negative_prices', false );

		if ( ! $allow_negative_prices && $price < 0 ) {
			$price = 0;
		}

		return '' != $price ? edd_sanitize_amount( $price ) : $price;
	}

	/**
	 * Sale price args.
	 *
	 * Add the sale price to the arguments to use later in $this->variable_sale_price_field().
	 *
	 * @since 1.0.0
	 *
	 * @param	array $args 	List of existing arguments being passed.
	 * @param	array $values 	List of set values for this specific price variation.
	 * @return	array			List of modified arguments being passed.
	 */
	public function edd_price_row_args( $args, $values ) {

		$args['sale_price'] = isset( $values['sale_price'] ) ? edd_sanitize_amount( $values['sale_price'] ) : '';

		return $args;

	}


	/**
	 * Sale price header.
	 *
	 * Add the 'sale price' header to the variable prices table.
	 *
	 * @since 1.0.0
	 */
	public function add_variable_sale_price_header() {

		?><th style="width: 100px"><?php _e( 'Sale price', 'edd-sale-price' ); ?></th><?php

	}


	/**
	 * Variable sale price.
	 *
	 * Display the variable sale price field.
	 *
	 * @since 1.0.0
	 *
	 * @param	int 	$post_id 	ID of the download post.
	 * @param	int 	$key		Index key of the current price variation.
	 * @param	array	$args		Array of value arguments.
	 */
	public function variable_sale_price_field( $post_id, $key, $args ) {

		$args = wp_parse_args( $args, array(
			'sale_price' => null,
		) );

		$price_args = array(
			'name'	=> 'edd_variable_prices[' . $key . '][sale_price]',
			'value' => is_numeric( $args['sale_price'] ) ? esc_attr( edd_format_amount( $args['sale_price'] ) ) : '',
			'class'	=> 'edd-price-field edd-sale-price-field'
		);

		?><div class="edd-custom-price-option-section">
			<div class="edd-custom-price-option-section-content"><?php
				$currency_position = edd_get_option( 'currency_position' );
				if ( empty( $currency_position ) || $currency_position == 'before' ) :
					?><span><?php echo edd_currency_filter( '' ) . ' ' . EDD()->html->text( $price_args ) . ' ' . __( 'Sale price', 'edd-sale-price' ); ?></span><?php
				else :
					?><span><?php echo EDD()->html->text( $price_args ) . ' ' . edd_currency_filter( '' ) . ' ' . __( 'Sale price', 'edd-sale-price' ); ?></span><?php
				endif;
			?></div>
		</div><?php

	}


}
