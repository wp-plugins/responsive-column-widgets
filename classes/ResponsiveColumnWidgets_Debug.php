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
		
		$mem_usage = memory_get_usage(true);
	   
		if ($mem_usage < 1024)
			echo $mem_usage." bytes";
		elseif ($mem_usage < 1048576)
			echo round($mem_usage/1024,2)." kilobytes";
		else
			echo round($mem_usage/1048576,2)." megabytes";
		   
		echo "<br/>";
		
	} 		
	
	static public function DumpOption( $strKey ) {

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
		
		$oOption = & $GLOBALS['oResponsiveColumnWidgets_Options'];		
		if ( ! isset( $oOption->arrOptions[ $strKey ] ) ) return;
		
		die( ResponsiveColumnWidgets_Debug::DumpArray( $oOption->arrOptions[ $strKey ] ) );
		
		
	}
}