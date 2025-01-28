<?php
/**
 * File containing the class Order_Bumps.
 *
 * @package Order_Bumps
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles core plugin hooks and action setup.
 *
 * @since 1.0.0
 */
class OrderBumps {
    /**
     * The single instance of the class.
     *
     * @var self
     * @since  1.0.0
     */
    private static $instance = null;

    /**
     * The condition provider instance.
     *
     * This property holds an instance of the ConditionProvider class
     * used to manage and evaluate conditions for order bumps.
     *
     * @var ConditionProvider
     * @since 1.0.0
     */
    public ConditionProvider $condition_provider;


    /**
     * Main Order_Bumps Instance.
     *
     * Ensures only one instance of Order_Bumps is loaded or can be loaded.
     *
     * @since  0.0.1
     * @static
     * @see WPSCHEMA()
     * @return self Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Includes.
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/interfaces/interface-product-display-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-cart-total-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-cart-item-count-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-condition-provider.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-composite-condition.php';
        include_once ORDER_BUMPS_PLUGIN_DIR . '/includes/conditions/class-user-logged-in-condition.php';


        // Actions.
        add_action( 'after_setup_theme', [ $this, 'load_plugin_textdomain' ] );

        // Initialize condition provider
        $this->condition_provider = new ConditionProvider();

        // Register conditions
        $this->register_conditions();

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Add order bump section to the checkout page
        add_action('woocommerce_checkout_before_order_review', [$this, 'display_order_bumps']);

        // Register AJAX actions
        add_action('wp_ajax_get_order_bump_products', [$this, 'get_order_bump_products']);
        add_action('wp_ajax_nopriv_get_order_bump_products', [$this, 'get_order_bump_products']);
        add_action('wp_ajax_add_product_to_cart', [$this, 'add_product_to_cart']);
        add_action('wp_ajax_nopriv_add_product_to_cart', [$this, 'add_product_to_cart']);
    }

    /**
     * Register conditions for order bumps.
     */
    protected function register_conditions() {
        $cart_total_threshold = get_option('order_bumps_cart_total', 500);
        $cart_item_count_threshold = get_option('order_bumps_item_count', 2);

        $cart_total_condition = new CartTotalCondition($cart_total_threshold);
        $cart_item_count_condition = new CartItemCountCondition($cart_item_count_threshold);

        $this->condition_provider->register_condition($cart_total_condition);
        $this->condition_provider->register_condition($cart_item_count_condition);
    }

    public function enqueue_assets() {
        wp_enqueue_script(
            'order-bumps-js',
            ORDER_BUMPS_PLUGIN_URL . '/assets/order-bumps.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script('order-bumps-js', 'orderBumpConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    public function display_order_bumps() {
        echo '<div id="order-bump-products"></div>';
    }

    public function get_order_bump_products() {
        // Initialize the composite condition
        $compositeCondition = new CompositeCondition();

        // Get conditions from the condition provider
        $conditions = $this->condition_provider->get_conditions();

        // Add conditions to the composite condition (using addConditions to handle multiple at once)
        $compositeCondition->addConditions($conditions);

        // Allow external developers to add custom conditions
        do_action('add_order_bump_conditions', $compositeCondition);

        // Set logical evaluation (AND/OR) dynamically via a filter
        $compositeCondition->setLogic(apply_filters('order_bump_conditions_logic', 'AND'));

        // Evaluate conditions
        if (!$compositeCondition->isSatisfied()) {
            wp_send_json_error(['message' => 'Conditions not met for displaying order bumps.']);
            return;
        }

        // Fetch products for the order bump (can be customized)
        $product_ids = apply_filters('order_bump_product_ids', $this->get_default_order_bump_products());
        $products = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                $image_data = wp_get_attachment_image_src($product->get_image_id(), 'thumbnail');
                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => wc_price($product->get_price()),
                    'image' => $image_data ? $image_data[0] : '', // Fallback to empty string if no image
                ];
            }
        }


        if (!empty($products)) {
            wp_send_json_success($products);
        } else {
            wp_send_json_error(['message' => 'No products available for order bumps.']);
        }
    }


    public function add_product_to_cart() {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);

        if (WC()->cart->add_to_cart($product_id, $quantity)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Unable to add the product to the cart.']);
        }
    }

    /**
     * Get default order bump products based on specific criteria.
     *
     * @return array Array of product IDs.
     */
    private function get_default_order_bump_products(): array {
        // First, try to get product IDs from an external hook or configuration
        $product_ids = apply_filters('modify_order_bump_product_ids', [187, 36]);

        // If the product IDs are provided (e.g., from settings or a config file)
        if (!empty($product_ids)) {
            // Validate and filter product IDs (static approach)
            $valid_product_ids = array_filter(
                array_map(
                    function($id) {
                        $product = wc_get_product($id);
                        return ($product && $product->is_purchasable() && $product->is_in_stock()) ? $id : null;
                    },
                    $product_ids
                )
            );
        } else {
            // Fallback to dynamic fetching of products with empty query arguments
            $args = apply_filters('set_order_bump_product_query_args', []);

            // Fetch products based on the provided query args, ensuring we avoid empty args
            if (empty($args)) {
                // If no args are provided, we can set a default query or skip fetching
                return []; // Or some other behavior if you want to handle this case
            }
            $products = wc_get_products($args);

            // Validate and filter products (dynamic approach)
            $valid_product_ids = array_filter(
                array_map(
                    function($product) {
                        if ($product->is_purchasable() && $product->is_in_stock()) {
                            return $product->get_id();
                        }
                        return null;
                    },
                    $products
                )
            );
        }

        // Allow other plugins or modules to modify the final product IDs
        return apply_filters('modify_order_bump_product_ids', $valid_product_ids, $args ?? []);
    }

    /**
     * Loads textdomain for plugin.
     */
    public function load_plugin_textdomain() {
        load_textdomain( 'wp-schema', WP_LANG_DIR . '/wp-schema/wp-schema-' . apply_filters( 'plugin_locale', get_locale(), 'wp-schema' ) . '.mo' );
        load_plugin_textdomain( 'wp-schema', false, ORDER_BUMPS_PLUGIN_DIR . '/languages/' );
    }
}