<?php
/**
    Retrieves widget options.
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.3
 * 
    
*/

class ResponsiveColumnWidgets_WidgetOptions {
    
    
    public function GetHierarchyBase() {    // since 1.1.3
        
        // Retrieves the widget options saved via the widget form in the admin page ( widgets.php )
        // and stores them in an array and returns it.
        
        // Retrieve the plugin widgets' options set via the form of the widget in widgets.php.
        $arrWidgetOptions = $this->GetRegisteredWidgetOptionsByBaseID();

        // Save the hierarchical relationship into an array. Each key has the name of sidebar ID and the element holds the values of the sidebar IDs that is embedded in.
        $arrHierarchy = array();
        foreach( $arrWidgetOptions as $arrWidgetOption ) {
            
            if ( ! is_array( $arrWidgetOption ) ) continue;    
            if ( ! isset( $arrWidgetOption['sidebarid_selected'] ) ) continue;
            
            $strSidebarID_Parent = $arrWidgetOption['sidebarid_parent'];
            if ( ! isset( $arrHierarchy[ $strSidebarID_Parent ] ) )
                $arrHierarchy[ $strSidebarID_Parent ] = array();
            
            if ( ! in_array( $arrWidgetOption['sidebarid_selected'], $arrHierarchy[ $strSidebarID_Parent ] ) )
                $arrHierarchy[ $strSidebarID_Parent ][] = $arrWidgetOption['sidebarid_selected'];
                
        }        

        return $arrHierarchy;
        
    }
    
    public function GetRegisteredWidgetOptionsByBaseID( &$arrWPRegisteredWidgets=array(), $strBaseID='responsive_column_widget_box' ) {
        
        // Retrieves registered widgets by the given base ID. The default is 'responsive_column_widget_box', which means that the widget options of this plugin's widget will be gathered.
        // Note that this includes inactive_widgets' widgets. inactive_widgets is the sidebar ID that stores inactive widgets.
        $arrWPRegisteredWidgets = empty( $arrWPRegisteredWidgets ) ? $GLOBALS['wp_registered_widgets'] : $arrWPRegisteredWidgets;
        $arrWidgets = array();

        foreach ( $arrWPRegisteredWidgets as $strWidgetID => $oRegisteredWidget ) {
            
            // The element ['callback'][0]->id_base stores the base ID of the widget. 
            if ( ! isset( $oRegisteredWidget['callback'][0]->id_base ) ) continue;
            if ( $oRegisteredWidget['callback'][0]->id_base != $strBaseID ) continue;

            // There is a possibility that this widget belongs to the inactive_widgtets sidebar, which we do not want.
            // Disable warnings and errors by placing the @ mark in front of the function; a strange error(looking like a bug) occurs when removing a widget and refreshes the page,
            // in the function line 946, if ( !$widget_id || $widget_id == $wp_registered_widgets[$widget]['id'] ), the warning is undefined index of 'widget id'( the value of $widget ).
            if ( 
                ! @is_active_widget( false, $strWidgetID, $oRegisteredWidget['callback'][0]->id_base, true ) 
            ) { 
                continue; 
            }
            
            $strOptionName = $oRegisteredWidget['callback'][0]->option_name;
            $intKey = $oRegisteredWidget['params'][0]['number'];

            $arrWidgetData = get_option( $strOptionName );

            $arrWidgets[ $strWidgetID ] = ( array ) $arrWidgetData[ $intKey ];

        }

        return $arrWidgets;
        
    }    
}