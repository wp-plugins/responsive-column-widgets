<?php
/**
    Handles IDs for HTML elements or option items etc.
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl   http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1.2
 * 

*/
class ResponsiveColumnWidgets_IDHandler_ {

    /**
     * Generates ID based on the passed prefix and the array.
     * 
     * @remark            Used from an instantiated object so it must be public.
     * @remark            Uses a md5 hash with the given prefix and the parameter as the identifier.
     * @remark            'call_id' parameter key will be omitted. This helps for third party scripts to identify the callbacks and helps to enqueue own styles in the head tag.
     */ 
    public function GetCallID( $strPrefix, $arr=array() ) {
        
        unset( $arr[ 'call_id' ] );    
        krsort( $arr );    // for style id which should match the serialized parameter structure.
        return $strPrefix . '_' . md5( serialize( $arr ) );        
        
    }
    
    public function SetUsedID( $strID, $strKey='id' ) {    // used from an instantiated object so it must be public.
        
        // Sets the given string as a used ID in a global variable.
        global $arrResponsiveColumnWidgets_Flags;        
        
        if ( ! isset( $arrResponsiveColumnWidgets_Flags[ $strKey ] ) )
            $arrResponsiveColumnWidgets_Flags[ $strKey ] = array();
        
        if ( ! in_array( $strID, $arrResponsiveColumnWidgets_Flags[ $strKey ] ) )
            $arrResponsiveColumnWidgets_Flags[ $strKey ][] = $strID;
        
    }
    
    public function GenerateIDSelector( $strSidebarIDHash, $bUpdate=True ) {    // since 1.1.1, moved from the core class in 1.1.2, must be public as the core class uses it.
        
        global $arrResponsiveColumnWidgets_Flags;

        // Format the count if it's not set yet.
        if ( ! isset( $arrResponsiveColumnWidgets_Flags['arrIDCounters'][ $strSidebarIDHash ] ) )
            $arrResponsiveColumnWidgets_Flags['arrIDCounters'][ $strSidebarIDHash ] = 0;
        
        // Increment the count.
        if ( $bUpdate )         
            $arrResponsiveColumnWidgets_Flags['arrIDCounters'][ $strSidebarIDHash ]++;
        
        // Return the ID attribute with the count. Use a hyphen for the connector.
        return $strSidebarIDHash . '-' . $arrResponsiveColumnWidgets_Flags['arrIDCounters'][ $strSidebarIDHash ];
        
    }
}