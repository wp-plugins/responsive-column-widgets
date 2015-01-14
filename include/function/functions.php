<?php
/**
 * User functions for general plugin users
 *   
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl   http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *
 */

/**
 * Renders the widget box.
 */
function ResponsiveColumnWidgets( $aParams=array(), $aOutput=array() ) {

    if ( ! isset( $GLOBALS['oResponsiveColumnWidgets'] ) ) {
        _e( 'Responsive Column Widgets classes have not been instantiated. Try using this later than the plugins_loaded hook.', 'responsive-column-widgets' );
        return;
    }

    $GLOBALS['oResponsiveColumnWidgets']->RenderWidgetBox( 
        $aParams, 
        $aOutput, 
        false       // additional styles will use the scoped attribute
    );
    
}

/**
 * Schedules to load the given widget box's ( sidebar ID ) style in the head tag.
 * 
 * This is used to avoid the style tag to be embedded inside the body tag with the scoped attribute
 * for the use of shortcode, the PHP code ( the above ResponsiveColumnWidgets() function ), and user-defined custom hooks.
 * This must be done prior to the head tag.
 * 
 * @since    1.1.2.1
 */
function ResponsiveColumnWidgets_EnqueueStyle( $aParams ) {
    
    if ( ! is_array( $aParams ) ) {
        return;
    }
    
    $GLOBALS['arrResponsiveColumnWidgets_Flags']['arrEnqueueStyleParams'][] = $aParams;
    
}