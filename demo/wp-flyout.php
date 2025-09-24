<?php
/**
 * Plugin Name: WP Flyout Demo - Multi-Table Version
 * Description: Demo plugin showcasing WP Flyout with Products, Bundles, and Orders
 * Version: 3.0.0
 * Author: ArrayPress
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Manual requires since we're not using autoloader in demo
require_once __DIR__ . '/src/Traits/Renderable.php';
require_once __DIR__ . '/src/Flyout.php';
require_once __DIR__ . '/src/Components/InfoGrid.php';
require_once __DIR__ . '/src/Components/Badge.php';
require_once __DIR__ . '/src/Components/FileManager.php';
require_once __DIR__ . '/src/Components/FormField.php';
require_once __DIR__ . '/src/Components/ActionBar.php';
require_once __DIR__ . '/src/Components/Image.php';
require_once __DIR__ . '/src/Components/Toggle.php';
require_once __DIR__ . '/src/Components/EmptyState.php';
require_once __DIR__ . '/src/Components/NotesPanel.php';
require_once __DIR__ . '/src/Components/Link.php';
require_once __DIR__ . '/src/Components/OrderItems.php';
require_once __DIR__ . '/src/Functions.php';

use ArrayPress\WPFlyout\Flyout;
use ArrayPress\WPFlyout\Components\InfoGrid;
use ArrayPress\WPFlyout\Components\Badge;
use ArrayPress\WPFlyout\Components\FileManager;
use ArrayPress\WPFlyout\Components\FormField;
use ArrayPress\WPFlyout\Components\ActionBar;
use ArrayPress\WPFlyout\Components\Image;
use ArrayPress\WPFlyout\Components\Toggle;
use ArrayPress\WPFlyout\Components\EmptyState;
use ArrayPress\WPFlyout\Components\NotesPanel;
use ArrayPress\WPFlyout\Components\Link;
use ArrayPress\WPFlyout\Components\OrderItems;

/**
 * Products List Table
 */
class WP_Flyout_Products_Table extends WP_List_Table {

    private $demo_data = [];

    public function __construct() {
        parent::__construct( [
                'singular' => 'product',
                'plural'   => 'products',
                'ajax'     => false
        ] );

        $this->demo_data = [
                [
                        'id'          => 1,
                        'title'       => 'Logo Pack Vol. 1',
                        'description' => '50 minimalist logo templates',
                        'price'       => 29.00,
                        'status'      => 'active'
                ],
                [
                        'id'          => 2,
                        'title'       => 'Icon Set Pro',
                        'description' => 'Professional icon collection',
                        'price'       => 49.00,
                        'status'      => 'active'
                ],
                [
                        'id'          => 3,
                        'title'       => 'WordPress Theme',
                        'description' => 'Premium business theme',
                        'price'       => 199.00,
                        'status'      => 'inactive'
                ]
        ];
    }

    public function get_columns() {
        return [
                'cb'      => '<input type="checkbox" />',
                'title'   => 'Product',
                'price'   => 'Price',
                'status'  => 'Status',
                'actions' => 'Actions'
        ];
    }

    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], [] ];
        $this->items           = $this->demo_data;
    }

    public function single_row( $item ) {
        echo '<tr data-id="' . esc_attr( $item['id'] ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="product[]" value="%s" />', $item['id'] );
    }

    public function column_title( $item ) {
        return sprintf( '<strong>%s</strong><br><small>%s</small>',
                esc_html( $item['title'] ),
                esc_html( $item['description'] )
        );
    }

    public function column_price( $item ) {
        return '£' . number_format( $item['price'], 2 );
    }

    public function column_status( $item ) {
        $color = $item['status'] === 'active' ? '#00a32a' : '#646970';

        return sprintf( '<span style="color: %s;">● %s</span>', $color, ucfirst( $item['status'] ) );
    }

    public function column_actions( $item ) {
        return sprintf(
                '<button class="button button-small" data-flyout-trigger="product-flyout" data-flyout-action="load" data-id="%d">View</button>',
                $item['id']
        );
    }
}

/**
 * Bundles List Table
 */
class WP_Flyout_Bundles_Table extends WP_List_Table {

    private $demo_data = [];

    public function __construct() {
        parent::__construct( [
                'singular' => 'bundle',
                'plural'   => 'bundles',
                'ajax'     => false
        ] );

        $this->demo_data = [
                [
                        'id'     => 1,
                        'title'  => 'Starter Bundle',
                        'files'  => 3,
                        'status' => 'active'
                ],
                [
                        'id'     => 2,
                        'title'  => 'Premium Bundle',
                        'files'  => 7,
                        'status' => 'active'
                ]
        ];
    }

    public function get_columns() {
        return [
                'cb'      => '<input type="checkbox" />',
                'title'   => 'Bundle',
                'files'   => 'Files',
                'status'  => 'Status',
                'actions' => 'Actions'
        ];
    }

    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], [] ];
        $this->items           = $this->demo_data;
    }

    public function single_row( $item ) {
        echo '<tr data-id="' . esc_attr( $item['id'] ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="bundle[]" value="%s" />', $item['id'] );
    }

    public function column_title( $item ) {
        return sprintf( '<strong>%s</strong>', esc_html( $item['title'] ) );
    }

    public function column_files( $item ) {
        return sprintf( '%d files', $item['files'] );
    }

    public function column_status( $item ) {
        $color = $item['status'] === 'active' ? '#00a32a' : '#646970';

        return sprintf( '<span style="color: %s;">● %s</span>', $color, ucfirst( $item['status'] ) );
    }

    public function column_actions( $item ) {
        return sprintf(
                '<button class="button button-small" data-flyout-trigger="bundle-flyout" data-flyout-action="load" data-id="%d">Edit</button>',
                $item['id']
        );
    }
}

/**
 * Orders List Table
 */
class WP_Flyout_Orders_Table extends WP_List_Table {

    private $demo_data = [];

    public function __construct() {
        parent::__construct( [
                'singular' => 'order',
                'plural'   => 'orders',
                'ajax'     => false
        ] );

        $this->demo_data = [
                [
                        'id'       => 1001,
                        'customer' => 'John Smith',
                        'items'    => 2,
                        'total'    => 78.00,
                        'status'   => 'completed',
                        'date'     => '2025-01-20'
                ],
                [
                        'id'       => 1002,
                        'customer' => 'Jane Doe',
                        'items'    => 3,
                        'total'    => 277.00,
                        'status'   => 'processing',
                        'date'     => '2025-01-21'
                ],
                [
                        'id'       => 1003,
                        'customer' => 'Bob Wilson',
                        'items'    => 1,
                        'total'    => 29.00,
                        'status'   => 'pending',
                        'date'     => '2025-01-22'
                ]
        ];
    }

    public function get_columns() {
        return [
                'cb'       => '<input type="checkbox" />',
                'order'    => 'Order',
                'customer' => 'Customer',
                'total'    => 'Total',
                'status'   => 'Status',
                'actions'  => 'Actions'
        ];
    }

    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], [] ];
        $this->items           = $this->demo_data;
    }

    public function single_row( $item ) {
        echo '<tr data-id="' . esc_attr( $item['id'] ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="order[]" value="%s" />', $item['id'] );
    }

    public function column_order( $item ) {
        return sprintf( '<strong>#%d</strong><br><small>%s</small>',
                $item['id'],
                date( 'M j, Y', strtotime( $item['date'] ) )
        );
    }

    public function column_customer( $item ) {
        return sprintf( '%s<br><small>%d items</small>',
                esc_html( $item['customer'] ),
                $item['items']
        );
    }

    public function column_total( $item ) {
        return '<strong>£' . number_format( $item['total'], 2 ) . '</strong>';
    }

    public function column_status( $item ) {
        $badges = [
                'completed'  => '<span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px;">Completed</span>',
                'processing' => '<span style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 3px;">Processing</span>',
                'pending'    => '<span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 3px;">Pending</span>'
        ];

        return $badges[ $item['status'] ] ?? $item['status'];
    }

    public function column_actions( $item ) {
        return sprintf(
                '<button class="button button-small" data-flyout-trigger="order-flyout" data-flyout-action="load" data-id="%d">View</button> ' .
                '<button class="button button-small" data-flyout-trigger="order-edit-flyout" data-flyout-action="load" data-id="%d">Edit</button>',
                $item['id'],
                $item['id']
        );
    }
}

/**
 * Main Demo Plugin Class
 */
class WP_Flyout_Demo_Plugin {

    private $demo_products = [];
    private $demo_orders = [];

    public function __construct() {
        $this->init_demo_data();

        add_action( 'admin_init', [ $this, 'register_flyouts' ] );
        add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // AJAX handlers
        add_action( 'wp_ajax_get_product_details', [ $this, 'ajax_get_product_details' ] );
        add_action( 'wp_ajax_demo_notes_add', [ $this, 'ajax_add_note' ] );
        add_action( 'wp_ajax_demo_notes_delete', [ $this, 'ajax_delete_note' ] );
    }

    /**
     * Initialize demo data
     */
    private function init_demo_data() {
        $this->demo_products = [
                'price_abc' => [
                        'product_id' => 'prod_123',
                        'price_id'   => 'price_abc',
                        'name'       => 'Logo Pack Vol. 1',
                        'price'      => 29.00,
                        'thumbnail'  => ''
                ],
                'price_def' => [
                        'product_id' => 'prod_456',
                        'price_id'   => 'price_def',
                        'name'       => 'Icon Set Pro',
                        'price'      => 49.00,
                        'thumbnail'  => ''
                ],
                'price_ghi' => [
                        'product_id' => 'prod_789',
                        'price_id'   => 'price_ghi',
                        'name'       => 'WordPress Theme',
                        'price'      => 199.00,
                        'thumbnail'  => ''
                ]
        ];

        $this->demo_orders = [
                1001 => [
                        'items'    => [
                                [
                                        'product_id' => 'prod_123',
                                        'price_id'   => 'price_abc',
                                        'name'       => 'Logo Pack Vol. 1',
                                        'price'      => 29.00,
                                        'quantity'   => 2,
                                        'thumbnail'  => ''
                                ],
                                [
                                        'product_id' => 'prod_456',
                                        'price_id'   => 'price_def',
                                        'name'       => 'Icon Set Pro',
                                        'price'      => 49.00,
                                        'quantity'   => 1,
                                        'thumbnail'  => ''
                                ]
                        ],
                        'subtotal' => 107.00,
                        'discount' => 10.00,
                        'tax'      => 9.70,
                        'total'    => 106.70
                ]
        ];
    }

    /**
     * Register flyouts
     */
    public function register_flyouts() {
        // Order View Flyout
        $order_view = new Flyout( 'order-flyout', [ 'width' => 'large' ] );
        $order_view->setup_ajax( 'demo_order_view', [
                'load' => [ $this, 'build_order_view_flyout' ]
        ] );

        // Order Edit Flyout
        $order_edit = new Flyout( 'order-edit-flyout', [ 'width' => 'large' ] );
        $order_edit->setup_ajax( 'demo_order_edit', [
                'load' => [ $this, 'build_order_edit_flyout' ],
                'save' => [ $this, 'save_order' ]
        ] );

        // Bundle Flyout
        $bundle = new Flyout( 'bundle-flyout', [ 'width' => 'large' ] );
        $bundle->setup_ajax( 'demo_bundle', [
                'load'   => [ $this, 'build_bundle_flyout' ],
                'save'   => [ $this, 'save_bundle' ],
                'delete' => [ $this, 'delete_bundle' ]
        ] );

        // Product View Flyout
        $product = new Flyout( 'product-flyout', [ 'width' => 'medium' ] );
        $product->setup_ajax( 'demo_product', [
                'load' => [ $this, 'build_product_flyout' ]
        ] );
    }

    /**
     * Build order view flyout
     */
    public function build_order_view_flyout( $flyout, $request ) {
        $order_id = intval( $request['id'] ?? 0 );
        $order    = $this->demo_orders[ $order_id ] ?? null;

        $flyout->set_title( 'Order #' . $order_id );

        // Add tabs
        $flyout->add_tab( 'items', 'Items', true )
               ->add_tab( 'customer', 'Customer' )
               ->add_tab( 'notes', 'Notes' );

        // Items tab - using OrderItems in view mode
        if ( $order ) {
            $order_items = new OrderItems( $order['items'], [
                    'mode'     => 'view',
                    'subtotal' => $order['subtotal'],
                    'discount' => $order['discount'],
                    'tax'      => $order['tax'],
                    'total'    => $order['total']
            ] );
            $flyout->add_content( 'items', $order_items->render() );
        }

        // Customer tab
        $customer_info = new InfoGrid( [
                'Name'    => 'John Smith',
                'Email'   => 'john@example.com',
                'Phone'   => '+44 20 1234 5678',
                'Address' => '123 High Street<br>London, UK',
        ], [ 'columns' => 1, 'escape' => false ] );
        $flyout->add_content( 'customer', '<h3>Customer Information</h3>' . $customer_info->render() );

        // Notes tab
        $notes = NotesPanel::editable( 'order', $order_id, [], [
                'ajax_prefix' => 'demo_notes',
                'nonce'       => wp_create_nonce( 'demo_notes_nonce' )
        ] );
        $flyout->add_content( 'notes', '<h3>Order Notes</h3>' . $notes->render() );
    }

    /**
     * Build order edit flyout
     */
// Update the build_order_edit_flyout method in the demo plugin:
    public function build_order_edit_flyout( $flyout, $request ) {
        $order_id = intval( $request['id'] ?? 0 );
        $order    = $this->demo_orders[ $order_id ] ?? null;

        $flyout->set_title( $order_id ? 'Edit Order #' . $order_id : 'New Order' );

        // Hidden fields for form
        $content = '';
        if ( $order_id ) {
            $content .= '<input type="hidden" name="id" value="' . $order_id . '">';
        }
        $content .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'demo_order_nonce' ) . '">';

        // Product list for selector
        $products = [];
        foreach ( $this->demo_products as $product ) {
            $products[] = [
                    'price_id' => $product['price_id'],
                    'name'     => $product['name'],
                    'price'    => $product['price']
            ];
        }

        // Create OrderItems component in edit mode
        $order_items = new OrderItems(
                $order ? $order['items'] : [],
                [
                        'mode'            => 'edit',
                        'products'        => $products,
                        'name_prefix'     => 'order_items',
                        'ajax_action'     => 'get_product_details',
                        'currency_symbol' => '£',
                        'empty_text'      => 'No products added yet. Select a product above to get started.'
                ]
        );

        $content .= $order_items->render();

        $flyout->add_content( '', $content );

        // Footer
        $actions = new ActionBar();
        if ( $order_id ) {
            $actions->add_submit( 'Update Order' );
        } else {
            $actions->add_submit( 'Create Order' );
        }
        $actions->add_cancel();
        $flyout->set_footer( $actions->render() );
    }

    /**
     * Build bundle flyout
     */
    public function build_bundle_flyout( $flyout, $request ) {
        $bundle_id = intval( $request['id'] ?? 0 );

        $flyout->set_title( $bundle_id ? 'Edit Bundle' : 'Create Bundle' );

        // Bundle form fields
        $content = FormField::text( 'title', 'Bundle Title', [
                'required'    => true,
                'placeholder' => 'e.g., Starter Bundle'
        ] )->render();

        // File manager
        $files = $bundle_id ? [
                [ 'name' => 'file1.zip', 'url' => 'https://example.com/file1.zip' ],
                [ 'name' => 'file2.pdf', 'url' => 'https://example.com/file2.pdf' ]
        ] : [];

        $file_manager = new FileManager( $files, 'files' );
        $content      .= '<h3>Bundle Files</h3>' . $file_manager->render();

        $flyout->add_content( '', $content );

        // Footer
        $actions = new ActionBar();
        $actions->add_submit( $bundle_id ? 'Update Bundle' : 'Create Bundle' )
                ->add_cancel();
        $flyout->set_footer( $actions->render() );
    }

    /**
     * Build product flyout
     */
    public function build_product_flyout( $flyout, $request ) {
        $product_id = intval( $request['id'] ?? 0 );

        $flyout->set_title( 'Product Details' );

        $info = new InfoGrid( [
                'Product ID' => 'prod_' . $product_id,
                'Created'    => 'January 15, 2025',
                'Status'     => 'Active',
                'Stock'      => 'Unlimited'
        ] );

        $flyout->add_content( '', '<h3>Product Information</h3>' . $info->render() );
    }

    /**
     * AJAX: Get product details
     */
    public function ajax_get_product_details() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'order_items_nonce' ) ) {
            wp_send_json_error( 'Invalid security token' );
        }

        $price_id = sanitize_text_field( $_POST['price_id'] ?? '' );

        if ( isset( $this->demo_products[ $price_id ] ) ) {
            wp_send_json_success( $this->demo_products[ $price_id ] );
        }

        wp_send_json_error( 'Product not found' );
    }

    /**
     * Save order
     */
    public function save_order( $data ) {
        // Process order items
        $items = $data['order_items'] ?? [];
        $total = 0;

        foreach ( $items as $item ) {
            $total += floatval( $item['price'] ) * intval( $item['quantity'] );
        }

        // Return order ID
        return intval( $data['id'] ?? rand( 1000, 9999 ) );
    }

    /**
     * Save bundle
     */
    public function save_bundle( $data ) {
        return intval( $data['id'] ?? rand( 100, 999 ) );
    }

    /**
     * Delete bundle
     */
    public function delete_bundle( $id ) {
        return true;
    }

    /**
     * AJAX handlers for notes
     */
    public function ajax_add_note() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'demo_notes_nonce' ) ) {
            wp_send_json_error( 'Invalid security token' );
        }

        wp_send_json_success( [
                'id'      => uniqid(),
                'content' => sanitize_textarea_field( $_POST['content'] ?? '' ),
                'author'  => wp_get_current_user()->display_name,
                'date'    => 'Just now'
        ] );
    }

    public function ajax_delete_note() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'demo_notes_nonce' ) ) {
            wp_send_json_error( 'Invalid security token' );
        }
        wp_send_json_success();
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        add_menu_page(
                'WP Flyout Demo',
                'Flyout Demo',
                'manage_options',
                'wp-flyout-demo',
                [ $this, 'render_products_page' ],
                'dashicons-slides',
                30
        );

        add_submenu_page(
                'wp-flyout-demo',
                'Products',
                'Products',
                'manage_options',
                'wp-flyout-demo',
                [ $this, 'render_products_page' ]
        );

        add_submenu_page(
                'wp-flyout-demo',
                'Bundles',
                'Bundles',
                'manage_options',
                'wp-flyout-bundles',
                [ $this, 'render_bundles_page' ]
        );

        add_submenu_page(
                'wp-flyout-demo',
                'Orders',
                'Orders',
                'manage_options',
                'wp-flyout-orders',
                [ $this, 'render_orders_page' ]
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [
                'toplevel_page_wp-flyout-demo',
                'flyout-demo_page_wp-flyout-bundles',
                'flyout-demo_page_wp-flyout-orders'
        ] ) ) {
            return;
        }

        // Main flyout assets
        wp_enqueue_style( 'wp-flyout', plugin_dir_url( __FILE__ ) . 'assets/css/wp-flyout.css', [], '3.0.0' );
        wp_enqueue_script( 'wp-flyout', plugin_dir_url( __FILE__ ) . 'assets/js/wp-flyout.js', [
                'jquery',
                'jquery-ui-sortable'
        ], '3.0.0', true );

        // Order Items component assets - MUST be loaded after wp-flyout
        wp_enqueue_style( 'wp-flyout-order-items', plugin_dir_url( __FILE__ ) . 'assets/css/order-items.css', [ 'wp-flyout' ], '1.0.0' );
        wp_enqueue_script( 'wp-flyout-order-items', plugin_dir_url( __FILE__ ) . 'assets/js/order-items.js', [
                'jquery',
                'wp-flyout'
        ], '1.0.0', true );

        // Localize script for OrderItems - MUST be after script enqueue
        wp_localize_script( 'wp-flyout-order-items', 'wpOrderItems', [
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'order_items_nonce' ),
                'action'   => 'get_product_details',
                'currency' => '£'
        ] );

        // Localize main flyout script
        wp_localize_script( 'wp-flyout', 'wpFlyout', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wp_flyout_nonce' )
        ] );

        wp_enqueue_media();
    }

    /**
     * Render products page
     */
    public function render_products_page() {
        $table = new WP_Flyout_Products_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1>Products</h1>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render bundles page
     */
    public function render_bundles_page() {
        $table = new WP_Flyout_Bundles_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Bundles</h1>
            <button class="page-title-action" data-flyout-trigger="bundle-flyout" data-flyout-action="load">
                Add Bundle
            </button>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render orders page
     */
    public function render_orders_page() {
        $table = new WP_Flyout_Orders_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Orders</h1>
            <button class="page-title-action" data-flyout-trigger="order-edit-flyout" data-flyout-action="load">
                New Order
            </button>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the demo plugin
new WP_Flyout_Demo_Plugin();