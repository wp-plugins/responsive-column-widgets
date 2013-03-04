<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://en.michaeluno.jp/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns with a responsive design.
	Version: 1.0.4.5
	Author: Michael Uno (miunosoft)
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/

// Constants
// We use two keys for the options. One for the actual options and the other is for admin pages.
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
$oResponsiveColumnWidgets_Options = null;	// the option object which stores and manipulate necessary settings.
$oResponsiveColumnWidgets = null;			// the core object which handles rendering widgets.


// Adds class paths to the above $arrResponsiveColumnWidgetsClasses array and load them when the plugins_loaded hook is triggered.
add_action( 
	'plugins_loaded',
	// The necessary classes are loaded with the plugins_loaded hook to allow other plugins to modify the 
	// $arrResponsiveColumnWidgetsClasses global array which contains the path info of all registering classes.
	array( new ResponsiveColumnWidgets_RegisterClasses( RESPONSIVECOLUMNWIDGETSDIR . '/classes/' ), 'RegisterClasses' )
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

		// Start running the plugin
		ResponsiveColumnWidgets_Startup();

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
 *  Activation Hook
 * */
register_activation_hook( RESPONSIVECOLUMNWIDGETSFILE, 'ResponsiveColumnWidgets_SetupTransients' );
function ResponsiveColumnWidgets_SetupTransients() {
	
	// Prepare Classes
	$oRC = new ResponsiveColumnWidgets_RegisterClasses( RESPONSIVECOLUMNWIDGETSDIR . '/classes/' );
	$oRC->RegisterClasses();
	
	// Setup Transients
	$oUA = new ResponsiveColumnWidgets_UserAds();
	$oUA->SetupTransients();
	
}
/*
 *  To start up
 */
function ResponsiveColumnWidgets_Startup() {
		
	// Prepare the option object	
	global $oResponsiveColumnWidgets_Options;
	$oResponsiveColumnWidgets_Options = new ResponsiveColumnWidgets_Option( RESPONSIVECOLUMNWIDGETSKEY );
	
	// Must be done after registering the classes.
	global $oResponsiveColumnWidgets;
	$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_Core( 'responsive_column_widgets', $oResponsiveColumnWidgets_Options );

	// Admin Page - $oAdmin is local 
	$oAdmin = new ResponsiveColumnWidgets_Admin_Page( RESPONSIVECOLUMNWIDGETSKEYADMIN );		
	$oAdmin->SetOptionObject( $oResponsiveColumnWidgets_Options );
	
}

/*
 * For general plugin users.
 * */
function ResponsiveColumnWidgets( $arrParams ) {
	
	global $oResponsiveColumnWidgets;

	if ( !isset( $oResponsiveColumnWidgets ) ) {
		_e( 'Responsive Column Widgets classes have not been instantiated. Try using this later than the plugins_loaded hook.', 'responsive-columns-widgets' );
		return;
	}
	
	// render the widget box
	$oResponsiveColumnWidgets->RenderWidgetBox( $arrParams );
	
}
