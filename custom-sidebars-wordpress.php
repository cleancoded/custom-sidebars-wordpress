<?php
/**
 * Plugin Name: Custom Sidebars for WordPress
 * Version: 1.0.0
 * Description: Create new sidebars and select which ones are used while creating and editing pages
 * Author: CLEANCODED
 * Author URI: https://cleancoded.com
 * Plugin URI: https://github.com/cleancoded/custom-sidebars-wordpress
 * Text Domain: custom-sidebars-wordpress
 * Domain Path: /languages
 * @package Custom_Sidebars_WordPress
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Class
 *
 * @since 1.0.0
 * @package cc_Sidebar_Selector
 */
final class cc_Sidebar_Selector {

	/**
	 * Plugin version
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $version = '1.0.0';
	
	/**
	 * Option key, and option page slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $key = 'cc_sidebar_selector_options';
	
	/**
	 * Options page metabox id
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $metabox_id = 'cc_sidebar_selector_option_metabox';
	
	/**
	 * Supported Post Types
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $post_types;
	
	/**
	 * Default Sidebar
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $default_sidebar;
	
	/**
	 * Options Page Title
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $options_page_title;
	
	/**
	 * Options Page Hook
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $options_page = '';
	
	
	/**
	 * Instance of the class
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance;
	
	/**
	 * Sidebar Selector Instance
	 *
	 * @since 1.0.0
	 * @return cc_Sidebar_Selector
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof cc_Sidebar_Selector ) ) {
			
			self::$instance = new cc_Sidebar_Selector;
			self::$instance->constants();
			self::$instance->includes();
			self::$instance->hooks();

		}
		return self::$instance;
	}
	
	/**
	 * Constants
	 *
	 * @since 1.0.0
	 */
	public function constants() {

		// Version
		define( 'cc_SIDEBAR_SELECTOR_VERSION', $this->version );

		// Directory path
		define( 'cc_SIDEBAR_SELECTOR_DIR', plugin_dir_path( __FILE__ ) );

		// Directory URL
		define( 'cc_SIDEBAR_SELECTOR_URL', plugin_dir_url( __FILE__ ) );

		// Base name
		define( 'cc_SIDEBAR_SELECTOR_BASE', plugin_basename( __FILE__ ) );
	}
	
	/**
	 * Includes
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// CMB2
		require_once( cc_SIDEBAR_SELECTOR_DIR . 'cmb2/init.php' );

	}
	
	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Setup Variables
		add_action( 'widgets_init',            array( $this, 'setup_variables' ) );
				
		// Register Widget Areas
		add_action( 'widgets_init',       array( $this, 'register_widget_areas' ) );
		
		// Display Sidebar
		add_action( 'cc_sidebar_selector', array( $this, 'display_sidebar' ) );
		
		// Metabox
		add_action( 'cmb2_admin_init', array( $this, 'add_metabox' ) );
		
		// Option Page
		add_action( 'admin_init',      array( $this, 'register_options_page_setting' ) );
		add_action( 'admin_menu',      array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );		
		
		
	}
	
	/**
	 * Set option page title
	 *
	 * @since 1.0.0
	 */
	public function setup_variables() {

		// Post Types
		$this->post_types = apply_filters( 'cc_sidebar_selector_post_types', array( 'page' ) );
		
		// Default Sidebar
		$this->default_sidebar = apply_filters( 'cc_sidebar_selector_default_sidebar', array( 'name' => 'Default Sidebar', 'id' => 'default-sidebar' ) );
		
		// Set options page title
		$this->options_page_title = __( 'Edit Widget Areas', 'cc-sidebar-selector' );

	}
	
	/**
	 * Register Widget Areas
	 *
	 * @since 1.0.0
	 *
	 */
	function register_widget_areas() {
		
		// Default Sidebar
		register_sidebar( wp_parse_args( $this->default_sidebar, apply_filters( 'cc_sidebar_selector_widget_area_args', array() ) ) );	
		
		// Custom Widget Areas
		$widget_areas = get_option( $this->key )['widget_areas'];
		if( empty( $widget_areas ) )
			return;
		
		foreach( $widget_areas as $args ) {
			register_sidebar( wp_parse_args( $args, apply_filters( 'cc_sidebar_selector_widget_area_args', array() ) ) );
		}

	}

	/**
	 * Display Sidebar
	 *
	 * @since 1.0.0
	 */
	function display_sidebar() {
	
		$sidebar = false;

		if( is_singular( $this->post_types ) ) 
			$sidebar = get_post_meta( get_the_ID(), '_cc_selected_sidebar', true );
		
		$sidebar = apply_filters( 'cc_sidebar_selector_override', $sidebar );
			
		if( ! $sidebar )
			$sidebar = $this->default_sidebar['id'];			
			
		if( is_active_sidebar( $sidebar ) )
			dynamic_sidebar( $sidebar );
	}
	
	/**
	 * Add Metabox
	 *
	 * @since 1.0.0 
	 *
	 */
	function add_metabox() {

		// Default Sidebar
		$options = array( $this->default_sidebar['id'] = $this->default_sidebar['name'] );
		
		// Custom Sidebars	
		$widget_areas = cmb2_get_option( $this->key, 'widget_areas' );
		if( $widget_areas ) {
			foreach( $widget_areas as $widget_area ) {
				$options[$widget_area['id']] = esc_attr( $widget_area['name'] );
			}
		}
		
		$metabox = new_cmb2_box( array( 
			'id'           => 'cc_sidebar_selector',
			'title'        => __( 'Sidebar Selector', 'cc-sidebar-selector' ),
			'object_types' => $this->post_types,
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => false,
		) );
		
		$metabox->add_field( array( 
			'name'    => __( 'Selected Sidebar', 'cc-sidebar-selector' ),
			'id'      => '_cc_selected_sidebar',
			'type'    => 'select',
			'default' => 'default',
			'options' => $options,
		) );
	}
	
	/**
	 * Register Option Page Setting
	 *
	 * @since 1.0.0
	 */
	public function register_options_page_setting() {
	
		// Register setting
		register_setting( $this->key, $this->key );
	}
	
	/**
	 * Add Options Page
	 *
	 * @since 1.0.0
	 */
	function add_options_page() {

		$this->options_page = add_theme_page( $this->options_page_title, $this->options_page_title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}
	
	/**
	 * Admin Page Display
	 *
	 * @since 1.0.0
	 */
	function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}
	
	/**
	 * Add Options Page Metabox
	 *
	 * @since 1.0.0
	 */
	function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		// Create metabox
		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		// Create repeatable field group
		$group_field_id = $cmb->add_field( array( 
			'id'      => 'widget_areas',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Widget Area {#}', 'cc-sidebar-selector' ),
				'add_button'    => __( 'Add Widget Area', 'cc-sidebar-selector' ),
				'remove_button' => __( 'Remove Widget Area', 'cc-sidebar-selector' ),
				'sortable'      => false,
			)
		) );
		
		// Add fields
		$cmb->add_group_field( $group_field_id, array( 
			'name'        => __( 'Name', 'cc-sidebar-selector' ),
			'id'          => 'name',
			'type'        => 'text',
			'description' => __( 'The name is how it appears in Appearance > Widgets.', 'cc-sidebar-selector' ),
		) );
		
		$cmb->add_group_field( $group_field_id, array( 
			'name'        => __( 'ID', 'cc-sidebar-selector' ),
			'id'          => 'id',
			'type'        => 'text',
			'sanitization_cb' => 'sanitize_title',
/*			'sanitization_cb' => 'cc_sidebar_selection_sanitize_slug',
			'attributes'  => array(
				'disabled' => true,
			)
*/		) );
		
		$cmb->add_group_field( $group_field_id, array( 
			'name'        => __( 'Description', 'cc-sidebar-selector' ),
			'id'          => 'description',
			'type'        => 'textarea',
		) );

	}
	
	/**
	 * Register settings notices for display
	 *
	 * @since  1.0.0
	 * @param  int   $object_id Option key
	 * @param  array $updated   Array of updated fields
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'cc-sidebar-selector' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}
	
}

/**
 * The function which returns the cc_Sidebar_Selector instance 
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.0.0
 * @return object
 */
function cc_sidebar_selector() {
	return cc_Sidebar_Selector::instance();
}
cc_sidebar_selector();

/**
 * Sanitize widget area slug 
 *
 * @since 1.0.0
 * @return string
 */
function cc_sidebar_selection_sanitize_slug( $value, $args, $field ) {

	$name = sanitize_title( $field->group->value[$field->index]['name'] ) . '-sidebar';
	$name = str_replace( '-sidebar-sidebar', '-sidebar', $name );
	return $name;

}