<?php
/**
	Methods used for debugging
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.3
 * 
	
*/

class ResponsiveColumnWidgets_Debug {

	static public function DumpArray( $arr, $strFilePath=null ) {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		if ( $strFilePath ) {
			
			file_put_contents( 
				$strFilePath , 
				date( "Y/m/d H:i:s" ) . PHP_EOL
				. print_r( $arr, true ) . PHP_EOL . PHP_EOL
				, FILE_APPEND 
			);					
			
		}
		return '<pre class="dump-array">' . esc_html( print_r( $arr, true ) ) . '</pre>';
		
	}
	
	static public function EchoMemoryUsage() {
		
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
				   
		echo self::GetMemoryUsage() . "<br/>";
		
	} 		

    static public function GetMemoryUsage( $intType=1 ) {	// since 1.1.4
       
	   $intMemoryUsage = $intType == 1 ? memory_get_usage( true ) : memory_get_peak_usage( true );
       
        if ( $intMemoryUsage < 1024 ) return $intMemoryUsage . " bytes";
        
		if ( $intMemoryUsage < 1048576 ) return round( $intMemoryUsage/1024,2 ) . " kilobytes";
        
        return round( $intMemoryUsage / 1048576,2 ) . " megabytes";
           
    } 		
	
	static public function DumpOption( $strKey ) {

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		$oOption = & $GLOBALS['oResponsiveColumnWidgets_Options'];		
		if ( ! isset( $oOption->arrOptions[ $strKey ] ) ) return;
		
		die( ResponsiveColumnWidgets_Debug::DumpArray( $oOption->arrOptions[ $strKey ] ) );
		
		
	}
}