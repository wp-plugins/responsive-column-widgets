<?php
/**
	Cleans up temporary items left in the database. Moved from the main plugin file.
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.2.1
 
 used actions: RCW_action_started
 
*/

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
		$oResponsiveColumnWidgets_Options = new ResponsiveColumnWidgets_Option( 
			RESPONSIVECOLUMNWIDGETSKEY, 
			defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ? RESPONSIVECOLUMNWIDGETSPROFILE : RESPONSIVECOLUMNWIDGETSFILE
		);	
		
		// For plugin extensions
		do_action( 'RCW_action_started', $oResponsiveColumnWidgets_Options );

	}
	function GetNameWOExtFromPath( $str ) {
		
		return basename( $str, '.php' );	// returns the file name without the extension
		
	}
	function CallbackFromAutoLoader( $strClassName ) {
		
		if ( ! in_array( $strClassName, $this->arrClassNames ) ) return;
		
		global $arrResponsiveColumnWidgetsClasses;
		include_once( $arrResponsiveColumnWidgetsClasses[ $strClassName ] . '.php' );
		
	}
	
}