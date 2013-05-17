<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://en.michaeluno.jp/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns with a responsive design.
	Version: 1.1.4.1
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.2 and PHP >= 5.2.4
	Text Domain: responsive-column-widgets
	Domain Path: /lang
*/

/*
 * Used Actions - parameters
 * RCW_action_started : triggered right after all necessary classes are loaded. The option object is passed to the first parameter.
 *  1. the option object
 * Lots of hooks for admin pages supported by Admin Page Framework.
 * */
 
 
// Constants
// We use two keys for the options. One for the actual options and the other is for the admin pages.
define( "RESPONSIVECOLUMNWIDGETSKEY", "responsive_column_widgets" );
define( "RESPONSIVECOLUMNWIDGETSKEYADMIN", "responsive_column_widgets_admin" );

// define( "RESPONSIVECOLUMNWIDGETSBASENAME", plugin_basename( __FILE__ ) );
define( "RESPONSIVECOLUMNWIDGETSFILE", __FILE__ );
define( "RESPONSIVECOLUMNWIDGETSDIR", dirname( __FILE__ ) );
define( "RESPONSIVECOLUMNWIDGETSURL", plugins_url( '', __FILE__ ) );

// Global variables
// - Arrays
$arrResponsiveColumnWidgetsClasses = isset( $arrResponsiveColumnWidgetsClasses ) ? $arrResponsiveColumnWidgetsClasses : array();	// stores the class paths.
$arrResponsiveColumnWidgets_Flags = array( 	// Since 1.1.0 - stores flag values that need to be global.
	'base_style' => false,		// Indicates whether the base CSS rules have been loaded or not.
	'arrIDCounters'	 => array(),	// Since 1.1.1 - stores how many times particular widget box's rendering requests are made. Used to assign an ID selector to an ID attribute.
	'arrWidgetIDAttributes' => array(),	// Since 1.1.1 - stores used ID attributes for widgets to avoid validation errors.
	'arrUserCustomStyles' => array(), 				// since 1.1.2 - stores box IDs whose user custom CSS rules are loaded.
	'arrWidgetBoxRenderingCallerIDs' => array(), 	// since 1.1.2 - stores the caller IDs of widget box rendering request based on the widget box's sidebar ID and the used parameter values.
	'arrEnqueueStyleParams' => array(),	// since 1.1.2.1 - stores parameter arrays passed by the ResponsiveColumnWidgets_EnqueueStyle() function.
);
// - Objects
$oResponsiveColumnWidgets_Options = null;		// the option object which stores and manipulates necessary plugin settings.
$oResponsiveColumnWidgets = null;				// the core object which handles rendering widgets.

// Adds class paths to the above $arrResponsiveColumnWidgetsClasses array and loads them when the plugins_loaded hook is triggered.
if ( ! class_exists( 'ResponsiveColumnWidgets_RegisterClasses' ) )
	include_once( dirname( RESPONSIVECOLUMNWIDGETSFILE ) . '/classes/ResponsiveColumnWidgets_RegisterClasses.php' );
add_action( 
	'plugins_loaded',
	// The necessary classes are loaded with the plugins_loaded hook to allow other plugins to modify the 
	// $arrResponsiveColumnWidgetsClasses global array which contains the path info of all registering classes.
	array( new ResponsiveColumnWidgets_RegisterClasses( RESPONSIVECOLUMNWIDGETSDIR . '/classes/' ), 'RegisterClasses' )
);	
// Setup function 
add_action(
	'RCW_action_started',
	'ResponsiveColumnWidgets_Startup'
);

/*
 *  Activation / Deactivation Hook
 * */
register_activation_hook( RESPONSIVECOLUMNWIDGETSFILE, 'ResponsiveColumnWidgets_SetupTransients' );
function ResponsiveColumnWidgets_SetupTransients() {
	
	wp_schedule_single_event( time(), 'RCWP_action_setup_transients' );		
	
}
if ( ! class_exists( 'ResponsiveColumnWidgets_Requirements' ) )
	include_once( dirname( RESPONSIVECOLUMNWIDGETSFILE ) . '/classes/ResponsiveColumnWidgets_Requirements.php' );
register_activation_hook( 
	RESPONSIVECOLUMNWIDGETSFILE,
	array( 
		new ResponsiveColumnWidgets_Requirements( 
			RESPONSIVECOLUMNWIDGETSFILE,
			array(
				'php' => array(
					'version' => '5.2.4',
					'error' => 'The plugin requires the PHP version %1$s or higher.',
				),
				'wordpress' => array(
					'version' => '3.2',
					'error' => 'The plugin requires the WordPress version %1$s or higher.',
				),
				'functions' => array(
					// 'unknown_func' => 'The plugin requires the %1$s function to be installed.',
				),
				// 'classes' => array(
					// 'DOMDocument' => sprintf( __( 'The plugin requires the <a href="%1$s">libxml</a> extension to be activated.', 'pseudo-image' ), 'http://www.php.net/manual/en/book.libxml.php' ),
				// ),
				'constants'	=> array(),
			),
			True, 			// if it fails it will deactivate the plugin
			null			// do not hook
		),
		'CheckRequirements'
	)
);

if ( ! class_exists( 'ResponsiveColumnWidgets_Cleaner' ) )
	include_once( dirname( RESPONSIVECOLUMNWIDGETSFILE ) . '/classes/ResponsiveColumnWidgets_Cleaner.php' );
register_deactivation_hook( RESPONSIVECOLUMNWIDGETSFILE, 'ResponsiveColumnWidgets_Cleaner::CleanTransients' );

/*
 *  To start up
 */
function ResponsiveColumnWidgets_Startup() {
			
	global $oResponsiveColumnWidgets_Options;
	
	// Must be done after registering the classes.
	global $oResponsiveColumnWidgets;
	$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_Core( RESPONSIVECOLUMNWIDGETSKEY, $oResponsiveColumnWidgets_Options );

	// Admin Page - $oAdmin is local 
	$oAdmin = new ResponsiveColumnWidgets_Admin_Page( 
		RESPONSIVECOLUMNWIDGETSKEYADMIN,
		RESPONSIVECOLUMNWIDGETSFILE
	);		
	$oAdmin->SetOptionObject( $oResponsiveColumnWidgets_Options );
		
	// Load events
	new ResponsiveColumnWidgets_Events( $oResponsiveColumnWidgets_Options );
		
	// Auto-insert - since 1.0.8, some parts of code have been separated from the core class.
	new ResponsiveColumnWidgets_AutoInsert( $oResponsiveColumnWidgets );

	// Register the widget.
	add_action( 'widgets_init', 'ResponsiveColumnWidgets_Widget::RegisterWidget' );
}

/*
 * Front-end functions for general plugin users.
 * */
function ResponsiveColumnWidgets( $arrParams=array() ) {
	
	global $oResponsiveColumnWidgets;

	if ( !isset( $oResponsiveColumnWidgets ) ) {
		_e( 'Responsive Column Widgets classes have not been instantiated. Try using this later than the plugins_loaded hook.', 'responsive-column-widgets' );
		return;
	}
	
	// Render the widget box.
	$oResponsiveColumnWidgets->RenderWidgetBox( $arrParams, false );	// the second parameter indicates that additional styles will use the scoped attribute.
	
}
function ResponsiveColumnWidgets_EnqueueStyle( $arrParams ) {	// since 1.1.2.1
	
	global $arrResponsiveColumnWidgets_Flags;
	
	// Schedules to load the given widget box's ( sidebar ID ) style in the head tag.
	// This is used to avoid the style tag to be embedded inside the body tag with the scoped attribute
	// for the use of shortcode, the PHP code ( the above ResponsiveColumnWidgets() function ), and user-defined custom hooks.
	// This must be done prior to the head tag.
	
	if ( is_array( $arrParams ) )
		$arrResponsiveColumnWidgets_Flags['arrEnqueueStyleParams'][] = $arrParams;
	
}