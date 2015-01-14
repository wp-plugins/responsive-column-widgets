<?php
/**
    Encodes strings
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @since        1.1.6
*/

if ( ! class_exists( 'IXR_Message' ) ) include_once( ABSPATH . WPINC . '/class-IXR.php' );
class ResponsiveColumnWidgets_Decoder extends IXR_Message {
    
    public function __construct() {}
    public function decodeBase64( $bin ) {

        // Some over-sensitive users have hysterical allergy against the base64 decode function so avoid using that. 
        // Instead, use the code of the core. I don't get why we should not use it in plugins while the core is using it. 
    
        $this->params = array();    // make sure it's empty
        $this->_currentTagContents = $bin;
        $this->tag_close( '', 'base64' );
        return $this->params[0];
        
    }
    
}