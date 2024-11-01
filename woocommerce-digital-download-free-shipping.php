<?php
/*
Plugin Name: WooCommerce Digital Download Free Shipping
Plugin URI: http://truemedia.ca/plugins/woocommerce-digital-shipping
Description: Provides Free shipping to Digital Downloads, set by Product Shipping Class.
Version: 1.0
Author: Jamez Picard
Author URI: http://truemedia.ca/
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

add_action('woocommerce_shipping_init', 'woocommerce_digitalfreeshipping_shipping_init', 0);

function woocommerce_digitalfreeshipping_shipping_init() {
	
/**
 * Free Shipping Method
 *
 * A simple shipping method for free shipping
 *
 * @class 		WC_Free_Shipping
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class Digital_Free_Shipping extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        $this->id 			= 'digital_free_shipping';
        $this->method_title = __('Digital Free Shipping', 'woocommerce');
		$this->init();
    }

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {
		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
        $this->enabled		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->availability = $this->settings['availability'];
		$this->countries 	= $this->settings['countries'];
		$this->enabled_class 	= $this->settings['enabled_class'];

		// Actions
		add_action('woocommerce_update_options_shipping_'.$this->id, array(&$this, 'process_admin_options'));
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	global $woocommerce;

    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable Free Digital Shipping', 'woocommerce' ),
							'default' 		=> 'yes'
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'Digital Download (No Shipping)', 'woocommerce' )
						),
			'enabled_class' => array(
							'title' 		=> __( 'Shipping Class', 'woocommerce' ),
							'type' 			=> 'select',
							'description' 	=> __('No Shipping cost for products in this Shipping Class', 'woocommerce'),
							'default' 		=> '',
							'options'		=> $this->get_shipping_classes() 
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __('All allowed countries', 'woocommerce'),
								'specific' 	=> __('Specific Countries', 'woocommerce')
							)
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						)
			);

    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Digital Free Shipping', 'woocommerce'); ?></h3>
    	<p><?php _e('Provides Free shipping to Digital Downloads.  It allows you to set this by Product Shipping Class, so that other products can have shipping costs, the easy way.  <br />This free shipping is only active if the items in the cart are ALL in this shipping class. Otherwise, your normal shipping methods apply.', 'woocommerce'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    }


    /**
     * is_available function.
     *
     * @access public
     * @param mixed $package
     * @return bool
     */
    function is_available( $package = array() ) {
    	global $woocommerce;

    	if ( $this->enabled == "no" ) return false;
		
		if (empty($this->enabled_class)) return false;

		$ship_to_countries = '';

		if ( $this->availability == 'specific' ) {
			$ship_to_countries = $this->countries;
		} else {
			if ( get_option('woocommerce_allowed_countries') == 'specific' )
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
		}

		if ( is_array( $ship_to_countries ) )
			if ( ! in_array( $package['destination']['country'], $ship_to_countries ) )
				return false;

		// Enabled logic
		$is_available = true;

		// Check for Prodct Shipping Class
		foreach ( $package['contents'] as $item_id => $values ) {
					
					//if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
						
					// Get Shipping class ($values['data'] is ProductID)
					$shipping_class = wp_get_post_terms( $values['data']->id, 'product_shipping_class' , array("fields" => "ids"));
					
					if (!in_array($this->enabled_class,$shipping_class)) {
						
						$is_available = false;
						break;
					}
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available );
    }


    /**
     * calculate_shipping function.
     *
     * @access public
     * @return array
     */
    function calculate_shipping() {
    	$args = array(
    		'id' 	=> $this->id,
    		'label' => $this->title,
    		'cost' 	=> 0,
    		'taxes' => false
    	);
    	$this->add_rate( $args );
    }
	
	function get_shipping_classes() {
		$arr = array();
		$shipping_class = get_terms(array('product_shipping_class'), array('hide_empty' => 0));
		foreach($shipping_class as $ship) {
			$arr[$ship->term_id] = $ship->name;
		}
		return $arr;
	}

}

/**
 * add_free_shipping_method function.
 *
 * @package		WooCommerce/Classes/Shipping
 * @access public
 * @param array $methods
 * @return array
 */
function add_digital_shipping_method( $methods ) {
	$methods[] = 'Digital_Free_Shipping';
	return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_digital_shipping_method' );

} // End woocommerce_digitalfreeshipping_shipping_init()


register_activation_hook( __FILE__, 'woocommerce_digitalfreeshipping_activate' );
function woocommerce_digitalfreeshipping_activate() {
	$shipclasses = get_terms( 'product_shipping_class', array('hide_empty'=>0, 'fields'=>'ids') );
	if (empty($shipclasses) || count($shipclasses) == 0) {
		// Create a default shipping class 'Digital Products'
		$term = wp_insert_term( 'Digital Products', 'product_shipping_class', $args = array('slug' => 'digital-products') ); 
	}
	// Done! (doesn't make sense to auto-select one - that might create issues).
}

} // End check if WooCommerce is active