<?php
/**
 * Plugin Name: Woocommerce Quantity Settings Field
 * Plugin URI:  https://jjmontalban.github.io
 * Description: Create a new option in product creation. You can to set a minimum, a maximum one or a quantity range for products.
 * Author:      JJMontalban
 * Author URI:  https://jjmontalban.github.io
 * Text Domain: woo-quantity-settings
 * Domain Path: /lang
 * Version:     1.0.0
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

//Translations
function wqs_plugin_load_textdomain()
{
    $text_domain	= 'woo-quantity-settings';
    $path_languages = basename( dirname(__FILE__) ) . '/lang/';
    
    load_plugin_textdomain( $text_domain, false, $path_languages );
}
add_action('plugins_loaded', 'wqs_plugin_load_textdomain');

             

//https://fluentthemes.com/
class FT_AdminNotice {
	
	/**
     * Register the activation hook
     */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'ft_install' ) );
	}
	
    /**
     * Deactivate the plugin and display a notice if the dependent plugin is not active.
     */
    public function ft_install() {
        if ( ! class_exists( 'WooCommerce' ) ) 
        {
            $this->ft_deactivate_plugin();
            wp_die( sprintf(__( 'This plugin requires Woocommerce to be installed and activated. You can download WooCommerce latest version %1$s or go back to %2$s', 'woo-quantity-settings' ), 
                '<strong><a href="https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip">Woocommerce</a></strong>', 
                '<strong><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">plugins</a></strong>' 
                ) );
        }
    }

    /**
     * Function to deactivate the plugin
     */
    protected function ft_deactivate_plugin() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }

}

new FT_AdminNotice();













add_action( 'woocommerce_product_options_pricing', 'wc_qty_add_product_field' );

function wc_qty_add_product_field() 
{
    global $product_object;

    $values = $product_object->get_meta( '_qty_args' );

    echo '</div>
            <div class="options_group quantity hide_if_grouped">
                <style>div.qty-args.hidden { display:none; }</style>';

    woocommerce_wp_checkbox( array( // Checkbox.
        'id'            => 'qty_args',
        'label'         => __( 'Quantity settings', 'woo-quantity-settings' ),
        'value'         => empty( $values ) ? 'no' : 'yes',
        'description'   => __( 'Activate the configuration of the quantities field.', 'woo-quantity-settings' ),
    ) );

    echo '<div class="qty-args hidden">';

    woocommerce_wp_text_input( array(
            'id'                => 'qty_min',
            'type'              => 'number',
            'label'             => __( 'Minimum amount', 'woo-quantity-settings' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Minimum amount allowed (>0)', 'woo-quantity-settings' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '0'),
            'value'             => isset( $values['qty_min']) && $values['qty_min'] > 0 ? ( int ) $values['qty_min'] : 0,
    ) );

    woocommerce_wp_text_input( array(
            'id'                => 'qty_max',
            'type'              => 'number',
            'label'             => __( 'Maximum Quantity', 'woo-quantity-settings' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Set the maximum allowed quantity limit (a number greater than 0). Value "-1" is unlimited', 'woo-quantity-settings' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '-1'),
            'value'             => isset($values['qty_max']) && $values['qty_max'] > 0 ? (int) $values['qty_max'] : -1,
    ) );

    woocommerce_wp_text_input( array(
            'id'                => 'qty_step',
            'type'              => 'number',
            'label'             => __( 'Quantity Range', 'woo-quantity-settings' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Show range amount allowed', 'woo-quantity-settings' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '1'),
            'value'             => isset($values['qty_step']) && $values['qty_step'] > 1 ? ( int ) $values['qty_step'] : 1,
    ) );

    echo '</div>';
}

// Show/hide setting fields (admin product pages)
add_action( 'admin_footer', 'product_type_selector_filter_callback' );

function product_type_selector_filter_callback() 
{
    global $pagenow, $post_type;

    if( in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) && $post_type === 'product' ) :
        ?>
        <script>
        jQuery(function($)
        {
            if( $('input#qty_args').is(':checked') && $('div.qty-args').hasClass('hidden') ) {
                $('div.qty-args').removeClass('hidden')
            }
            $('input#qty_args').click(function(){
                if( $(this).is(':checked') && $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').removeClass('hidden');
                } else if( ! $(this).is(':checked') && ! $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').addClass('hidden');
                }
            });
        });
        </script>
        <?php
    endif;
}

// Save quantity setting fields values
add_action( 'woocommerce_admin_process_product_object', 'wc_save_product_quantity_settings' );

function wc_save_product_quantity_settings( $product ) 
{
    if ( isset($_POST['qty_args']) ) {
        $values = $product->get_meta('_qty_args');

        $product->update_meta_data( '_qty_args', array(
            'qty_min' => isset( $_POST['qty_min'] ) && $_POST['qty_min'] > 0 ? ( int ) wc_clean( $_POST['qty_min'] ) : 0,
            'qty_max' => isset( $_POST['qty_max'] ) && $_POST['qty_max'] > 0 ? ( int ) wc_clean($_POST['qty_max'] ) : -1,
            'qty_step' => isset ($_POST['qty_step'] ) && $_POST['qty_step'] > 1 ? ( int ) wc_clean( $_POST['qty_step'] ) : 1,
        ) );
    } else {
        $product->update_meta_data( '_qty_args', array() );
    }
}

// The quantity settings in action on front end
add_filter( 'woocommerce_quantity_input_args', 'filter_wc_quantity_input_args', 99, 2 );

function filter_wc_quantity_input_args( $args, $product ) 
{
    if ( $product->is_type( 'variation' ) ) {
        $parent_product = wc_get_product( $product->get_parent_id() );
        $values  = $parent_product->get_meta( '_qty_args' );
    } else {
        $values  = $product->get_meta( '_qty_args' );
    }

    if ( ! empty( $values ) ) {
        // Min value
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $args['min_value'] = $values['qty_min'];

            if( ! is_cart() ) {
                $args['input_value'] = $values['qty_min']; // Starting value
            }
        }

        // Max value
        if ( isset( $values['qty_max'] ) && $values['qty_max'] > 0 ) {
            $args['max_value'] = $values['qty_max'];

            if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
                $args['max_value'] = min( $product->get_stock_quantity(), $args['max_value'] );
            }
        }

        // Step value
        if ( isset( $values['qty_step'] ) && $values['qty_step'] > 1 ) {
            $args['step'] = $values['qty_step'];
        }
    }

    return $args;
}

// Ajax add to cart, set "min quantity" as quantity on shop and archives pages
add_filter( 'woocommerce_loop_add_to_cart_args', 'filter_loop_add_to_cart_quantity_arg', 10, 2 );

function filter_loop_add_to_cart_quantity_arg( $args, $product ) 
{
    $values  = $product->get_meta( '_qty_args' );

    if ( ! empty( $values ) ) {
        // Min value
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $args['quantity'] = $values['qty_min'];
        }
    }
    return $args;
}

// The quantity settings in action on front end (For variable products and their variations)
add_filter( 'woocommerce_available_variation', 'filter_wc_available_variation_price_html', 10, 3);

function filter_wc_available_variation_price_html( $data, $product, $variation ) 
{
    $values  = $product->get_meta( '_qty_args' );

    if ( ! empty( $values ) ) {
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $data['min_qty'] = $values['qty_min'];
        }

        if ( isset( $values['qty_max'] ) && $values['qty_max'] > 0 ) {
            $data['max_qty'] = $values['qty_max'];

            if ( $variation->managing_stock() && ! $variation->backorders_allowed() ) {
                $data['max_qty'] = min( $variation->get_stock_quantity(), $data['max_qty'] );
            }
        }
    }

    return $data;
}
