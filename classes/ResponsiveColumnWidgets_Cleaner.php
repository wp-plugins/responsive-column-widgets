<?php
/**
	Cleans up temporary items left in the database.
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.2.1
 
*/

class ResponsiveColumnWidgets_Cleaner {

	// Just instantiate the class to perform a clean-up.
	
	public static function CleanTransients() {
		
		// Delete transients
		global $wpdb, $table_prefix;
		$strPrefixFeedTransient = 'RCWFeed_';	
		$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_%{$strPrefixFeedTransient}%' )" );
		$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout%{$strPrefixFeedTransient}%' )" );
		
		$strPrefixTransient = 'RCW_IMG';	
		$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_%{$strPrefixTransient}%' )" );
		$wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout%{$strPrefixTransient}%' )" );
		
	}
	
}