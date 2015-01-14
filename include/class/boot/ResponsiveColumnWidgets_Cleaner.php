<?php
/**
    Cleans up temporary items left in the database.
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.2.1
 
*/

class ResponsiveColumnWidgets_Cleaner {

    // Just instantiate the class to perform a clean-up.
    
    public static function CleanTransients( $arrPrefixes=array( 'RCWFeed', 'RCWUserAds_', 'RCW_IMG', 'RCW_Cache' ) ) {

        // Delete transients
        global $wpdb, $table_prefix;
        
        // This method also serves for the deactivation callback and in that case, an empty value is passed to the first parameter.
        $arrPrefixes = empty( $arrPrefixes ) ? array( 'RCWFeed', 'RCWUserAds_', 'RCW_IMG', 'RCW_Cache' ) : $arrPrefixes;    
        
        foreach( $arrPrefixes as $strPrefix ) {
            $wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_%{$strPrefix}%' )" );
            $wpdb->query( "DELETE FROM `" . $table_prefix . "options` WHERE `option_name` LIKE ( '_transient_timeout_%{$strPrefix}%' )" );
        }
    
    }
    
}