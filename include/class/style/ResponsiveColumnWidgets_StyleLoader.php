<?php
/**
 * Loads a widget box style in the head tag. 
 *  
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * 
 * @remark      This class is not mean to be extensible as used earlier than the auto-loader, which requires direct inclusion.
 * @remark      This class must be instantiated per a combination of request of rendering widget box and the parameters
 * as it stores those info into the object properties.    
 * @since       1.1.2.1
 */
class ResponsiveColumnWidgets_StyleLoader {
    
    /**
     * Sets up hooks and properties.
     */
    function __construct( $aParams, $aHooks=array( 'wp_head', 'login_head', 'admin_head' ) ) {
        
        // Properties
        $this->aParams = $aParams;
        
        // Hooks
        foreach( $aHooks as $_sHook ) {
            add_action( $_sHook, array( $this, '_replyToAddStyle' ) );
        }
            
    }
        
    /**
     * 
     * @since       1.1.2.1
     * @remark      Callback for the 'wp_head', 'login_head', and 'admin_head' action hooks.
     */
    public function _replyToAddStyle() {   
        echo ResponsiveColumnWidgets_Styles::getStyle( $this->aParams ); 
    }
        
}
