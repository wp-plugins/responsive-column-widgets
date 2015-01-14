<?php
/**
 * Register PHP classes to be auto loaded.
 *   
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl   http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1.2.1
*/

/**
 * Register PHP classes to be auto loaded.
 * @deprecated      1.2.0   Use `ResponsiveColumnWidgets_RegisterClasses2`.
 */
class ResponsiveColumnWidgets_RegisterClasses {
    
    function __construct( $strClassDirPath ) {
        
        // Prepare properties.
        $this->arrClassPaths =  glob( $strClassDirPath . '*.php' );
        $this->strClassDirPath = $strClassDirPath;
        $this->arrClassNames = array_map( array( $this, 'GetNameWOExtFromPath' ), $this->arrClassPaths );
        $this->setClassArray();
                
    }
    function setClassArray() {
        
        global $arrResponsiveColumnWidgetsClasses;            
        foreach( $this->arrClassNames as $strClassName ) {
            
            // if it's set, do not register ( add it to the array ).
            if ( isset( $arrResponsiveColumnWidgetsClasses[$strClassName] ) ) continue;
            
            $arrResponsiveColumnWidgetsClasses[$strClassName] = $this->strClassDirPath . $strClassName;    
        }

    }
    function RegisterClasses() {
        
        spl_autoload_register( array( $this, '_replyToAutoloader' ) );

    }
    function GetNameWOExtFromPath( $str ) {
        
        return basename( $str, '.php' );    // returns the file name without the extension
        
    }
    
    /**
     * Respond to the auto loader callback.
     * 
     * @since       1.2.0        Changed the name from 'CallbackFromAutoLoader'.
     */ 
    public function _replyToAutoloader( $sClassName ) {
        
        if ( ! in_array( $sClassName, $this->arrClassNames ) ) { 
            return; 
        }        
        include( $GLOBALS['arrResponsiveColumnWidgetsClasses'][ $sClassName ] . '.php' );
        
    }
    
}