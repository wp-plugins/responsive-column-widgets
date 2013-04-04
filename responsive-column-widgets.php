<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://en.michaeluno.jp/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns with a responsive design.
	Version: 1.0.8.6
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.2 and PHP >= 5.2.4
	Text Domain: responsive-column-widgets
	Domain Path: /lang
*/

/*
 * Used Actions - parameters
 * RCW_action_started : triggred right after all necessary classes are loaded. The option object is passed to the first parametr.
 *  1. the option object
 * Lots of hoooks for admin pages supproted by Admin Page Framework.
 * */
 
 
// Constants
// We use two keys for the options. One for the actual options and the other is for the admin pages.
define( "RESPONSIVECOLUMNWIDGETSKEY", "responsive_column_widgets" );
define( "RESPONSIVECOLUMNWIDGETSKEYADMIN", "responsive_column_widgets_admin" );

// define( "RESPONSIVECOLUMNWIDGETSBASENAME", plugin_basename( __FILE__ ) );
define( "RESPONSIVECOLUMNWIDGETSFILE", __FILE__ );
define( "RESPONSIVECOLUMNWIDGETSDIR", dirname( __FILE__ ) );
define( "RESPONSIVECOLUMNWIDGETSURL", plugins_url('', __FILE__ ) );

// Global variables
// - Arrays
$arrResponsiveColumnWidgetsClasses = isset( $arrResponsiveColumnWidgetsClasses ) ? $arrResponsiveColumnWidgetsClasses : array();	// stores the class paths.
// - Objects
$oResponsiveColumnWidgets_Options = null;	// the option object which stores and manipulates necessary settings.
$oResponsiveColumnWidgets = null;			// the core object which handles rendering widgets.


// Adds class paths to the above $arrResponsiveColumnWidgetsClasses array and load them when the plugins_loaded hook is triggered.
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

class ResponsiveColumnWidgets_RegisterClasses {
	
	function __construct( $strClassDirPath ) {
		
		// Prepare properties.
		$this->arrClassPaths =  glob( $strClassDirPath . '*.php' );
		$this->strClassDirPath = $strClassDirPath;
		$this->arrClassNames = array_map( array( $this, 'GetNameWOExtFromPath' ), $this->arrClassPaths );
		$this->SetupClassArray();
				
	}
	function SetupClassArray() {
		
		global $arrResponsiveColumnWidgetsClasses;			
		foreach( $this->arrClassNames as $strClassName ) {
			
			// if it's set, do not register ( add it to the array ).
			if ( isset( $arrResponsiveColumnWidgetsClasses[$strClassName] ) ) continue;
			
			$arrResponsiveColumnWidgetsClasses[$strClassName] = $this->strClassDirPath . $strClassName;	
		}

	}
	function RegisterClasses() {
		
		spl_autoload_register( array( $this, 'CallbackFromAutoLoader' ) );

		// Prepare the option object	
		global $oResponsiveColumnWidgets_Options;
		$oResponsiveColumnWidgets_Options = new ResponsiveColumnWidgets_Option( RESPONSIVECOLUMNWIDGETSKEY );	
		
		// For plugin extensions
		do_action( 'RCW_action_started', $oResponsiveColumnWidgets_Options );

	}
	function GetNameWOExtFromPath( $str ) {
		
		return basename( $str, '.php' );	// returns the file name without the extension
		
	}
	function CallbackFromAutoLoader( $strClassName ) {
		
		if ( ! in_array( $strClassName, $this->arrClassNames ) ) return;
		
		global $arrResponsiveColumnWidgetsClasses;
		include_once( $arrResponsiveColumnWidgetsClasses[$strClassName] . '.php' );
		
	}
	
}
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
				'classes' => array(),
				'constants'	=> array(),
			),
			True, 			// if it fails it will deactivate the plugin
			null			// do not hook
		),
		'CheckRequirements'
	)
);

register_deactivation_hook( RESPONSIVECOLUMNWIDGETSFILE, 'ResponsiveColumnWidgets_CleanupTransients' );
function ResponsiveColumnWidgets_CleanupTransients() {
	
	// Delete transients
	global $wpdb, $table_prefix;
	$strPrefixFeedTransient = 'RCWFeed_';	
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_%{$strPrefixFeedTransient}%' )" );
	$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout%{$strPrefixFeedTransient}%' )" );
	
}
/*
 *  To start up
 */
function ResponsiveColumnWidgets_Startup() {
			
	global $oResponsiveColumnWidgets_Options;
	
	// Must be done after registering the classes.
	global $oResponsiveColumnWidgets;
	$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_Core( 'responsive_column_widgets', $oResponsiveColumnWidgets_Options );

	// Admin Page - $oAdmin is local 
	$oAdmin = new ResponsiveColumnWidgets_Admin_Page( 
		RESPONSIVECOLUMNWIDGETSKEYADMIN,
		RESPONSIVECOLUMNWIDGETSFILE
	);		
	$oAdmin->SetOptionObject( $oResponsiveColumnWidgets_Options );
		
	// Load events
	new ResponsiveColumnWidgets_Events( $oResponsiveColumnWidgets_Options );
		
	// Auto-insert - since 1.0.8, some parts of code have been separated from the core class.
	new ResponsiveColumnWidgets_AutoInsert( $oResponsiveColumnWidgets_Options, $oResponsiveColumnWidgets );

}

/*
 * For general plugin users.
 * */
function ResponsiveColumnWidgets( $arrParams ) {
	
	global $oResponsiveColumnWidgets;

	if ( !isset( $oResponsiveColumnWidgets ) ) {
		_e( 'Responsive Column Widgets classes have not been instantiated. Try using this later than the plugins_loaded hook.', 'responsive-column-widgets' );
		return;
	}
	
	// render the widget box
	$oResponsiveColumnWidgets->RenderWidgetBox( $arrParams );
	
}
