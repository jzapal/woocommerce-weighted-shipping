<?php

/*
Plugin Name: Woocommerce weighted shipping 
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Allows shipping option based on total order weight in Woocommerce
Version: 1.0
Author: Jan Zapał
Author URI: http://URI_Of_The_Plugin_Author
License: GPL2
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once (ABSPATH."wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-settings-api.php");
require_once (ABSPATH."wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-shipping-method.php");

/**
 * weighted Rate Shipping Method
 *
 * A simple shipping method for a weighted fee per item or per order
 *
 * @class 		WC_Shipping_weighted_Rate
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Weighted_Rate extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
        $this->id 						= 'weighted_rate';
        $this->method_title 			= __( 'Weighted Rate', 'woocommerce' );
		$this->weighted_rate_option 		= 'woocommerce_weighted_rates';
		$this->method_description 	    = __( 'weighted rate allows to apply shipping price basing on order weight.', 'woocommerce' );

    	$this->init();
    }

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 		  = $this->get_option( 'title' ); 
		$this->availability   = $this->get_option( 'availability' ); 
		$this->countries 	  = $this->get_option( 'countries' ); 
		$this->type 		  = $this->get_option( 'type' );
		//$this->tax_status	  = $this->get_option( 'tax_status' ); 
		$this->options 		  = (array) explode( "\n", $this->get_option( 'options' ) );

		// Load weighted rates
		$this->get_weighted_rates();
		
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_weighted_rates' ) );
		//add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'save_default_costs' ) );
		
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
							'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
							'default' 		=> 'no',
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'Weighted Rate', 'woocommerce' ),
							'desc_tip'      => true
						),
			'availability' => array(
							'title' 		=> __( 'Availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __( 'All allowed countries', 'woocommerce' ),
								'specific' 	=> __( 'Specific Countries', 'woocommerce' ),
							),
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries,
						),
			'options' => array(
							'title' 		=> __( 'Additional Rates', 'woocommerce' ),
							'type' 			=> 'textarea',
							'description'	=> __( 'Maximum order weight and it\'s price. Each pair in new line. Example: <code>1000 | 2.90 </code>.', 'woocommerce' ),
							'default'		=> '',
							'desc_tip'      => true,
							'placeholder'	=> __( 'Option Name | Additional Cost', 'woocommerce' )
						),
			
			);

    }

	/**
     * calculate_shipping function.
     *
     * @access public
     * @param array $package (default: array())
     * @return void
     */
    function calculate_shipping( $package = array() ) {
    	global $woocommerce;
		
		$rates = $this->weighted_rates;
		
		$weight =  $woocommerce->cart->cart_contents_weight;
		$price = 0;
		
		foreach ($rates as $rate){
			if ( $rate['weight'] >= $weight )
				$price = $rate['price'];
		}
		
		
		if ($price != 0) {
			$args = array(
	    		'id' 	=> $this->id,
	    		'label' => $this->title,
	    		'cost' 	=> $price
	    	);
	    	$this->add_rate( $args );
		}
	}
	

	function process_weighted_rates(){
		global $woocommerce;
		
		$weight = $woocommerce->cart->cart_contents_weight;
		
		//update_option( $this->weighted_rate_option, $_POST['woocommerce_weighted_rate_options']);
		//Array ( [0] => 2000 | 20 [1] => 3000 | 25 )
		$options = $_POST['woocommerce_weighted_rate_options'];
		$options = explode("\n",$options);
		foreach ($options as $option){
			$opts = explode("|",$option);
			$rates[] = array( 'weight'=>$opts[0], 'price'=>$opts[1]  );
		}
		foreach ($rates as $val)
  		{
    		$sortArrayPrice[] = $val['price'];
  		}
		array_multisort($sortArrayPrice, $rates);
		
		//$this->rates = $rates;
		update_option( $this->weighted_rate_option, $rates);	
	}
	
	  /**
     * get_flat_rates function.
     *
     * @access public
     * @return void
     */
    function get_weighted_rates() {
    	$this->weighted_rates = array_filter( (array) get_option( $this->weighted_rate_option ) );
    }

}

function add_weighted_rate_method( $methods ) {
	$methods[] = 'WC_Shipping_Weighted_Rate'; return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_weighted_rate_method' );

add_action('plugins_loaded', 'wpml_fix_ajax_install');
function wpml_fix_ajax_install(){
    global $sitepress;
    if(defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && isset($_REQUEST['lang']) ){
        // remove WPML legacy filter, as it is not doing its job for ajax calls
        remove_filter('locale', array($sitepress, 'locale'));
        add_filter('locale', 'wpml_ajax_fix_locale');
        function wpml_ajax_fix_locale($locale){
            global $sitepress;
            // simply return the locale corresponding to the "lang" parameter in the request
            return $sitepress->get_locale($_REQUEST['lang']);
        }
    }
}
