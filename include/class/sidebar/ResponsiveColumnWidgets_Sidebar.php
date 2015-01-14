<?php
/**
 * Registers plugin sidebars.
 *   
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013-2015, Michael Uno
 * @authorurl   http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Registers plugin sidebars.
 * 
 * @since       1.2.0
 */
class ResponsiveColumnWidgets_Sidebar { 
    
    /**
     * Registers this plugin sidebar; if already registered, it will do nothing.
     */
    public function __construct( $oOption ) {

        $this->oOption = $oOption;
        if ( isset( $this->oOption->arrOptions['general']['delay_register_sidebar'] ) && $this->oOption->arrOptions['general']['delay_register_sidebar'] ) {
            add_action( 'widgets_init', array( $this, '_replyToRegisterSidebar' ), 999 );
            return;
        } 
        $this->_replyToRegisterSidebar();
        
    }
    
    /*
     * Registers saved sidebars
     * */
    public function _replyToRegisterSidebar() {
        
        if ( ! function_exists( 'register_sidebar' ) ) { return; }
        
        foreach ( $this->oOption->arrOptions['boxes'] as $_sSidebarID => $_aBoxOptions ) {
            
            if ( array_key_exists( 'Responsive_Column_Widgets', $GLOBALS['wp_registered_sidebars'] ) ) { 
                // @todo        Examine whether this can be 'break;' or checked outside the loop.
                continue; 
            }
            
            register_sidebar( 
                array(
                    'name'          => $_aBoxOptions['label'],
                    'id'            => strtolower( $_aBoxOptions['sidebar'] ), // must be all lowercase
                    'description'   => $_aBoxOptions['description'],
                    'before_widget' => $_aBoxOptions['before_widget'],
                    'after_widget'  => $_aBoxOptions['after_widget'],
                    'before_title'  => $_aBoxOptions['before_title'],
                    'after_title'   => $_aBoxOptions['after_title'],
                ) 
            );    
        }

    }    

}