<?php
/**
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0.7
*/

/**
 * Checks the specified requirements and if it fails, it deactivates the plugin.
 */
class ResponsiveColumnWidgets_Requirements {

    // Properties
    protected $strAdminNotice = '';    // admin notice
    protected $bSufficient = true;    // tells whether it suffices for all the requirements.
    protected $bDeactivate = true;    // indicates whether automatically deactivate the plugin if the verification fails.
    protected $arrParams = array();
    protected $arrDefaultParams = array(
        'php' => array(
            'version' => '5.2.4',
            'error' => 'The plugin requires the PHP version %1$s or higher.',
        ),
        'wordpress' => array(
            'version' => '3.0',
            'error' => 'The plugin requires the WordPress version %1$s or higher.',
        ),
        'functions' => array(
            // e.g. 'echo' => 'The plugin requires the %1$s function.',
            // e.g. 'mblang' => 'The plugin requires the mbstring extension.',
        ),
        'classes' => array(
            // e.g. 'DOMDocument' => 'The plugin requires the DOMXML extension.',
        ),
        'constants'    => array(
            // e.g. 'THEADDONFILE' => 'The plugin requires the ... addon to be installed.',
        ),
    );
    protected $strPluginFilePath;
    
    function __construct( $strPluginFilePath, $arrParams=array(), $bDeactivate=True, $strHook='' ) {
        
        // avoid undefined index warnings.
        $this->arrParams = $arrParams + $this->arrDefaultParams;    
        $this->arrParams['php'] = $this->arrParams['php'] + $this->arrDefaultParams['php'];
        $this->arrParams['wordpress'] = $this->arrParams['wordpress'] + $this->arrDefaultParams['wordpress'];

        $this->strPluginFilePath = $strPluginFilePath;
        $this->bDeactivate = $bDeactivate;
        
        // Objects
        if ( ! class_exists( 'ResponsiveColumnWidgets_PluginInfo' ) ) {
            include_once( dirname( $strPluginFilePath ) . '/classes/ResponsiveColumnWidgets_PluginInfo.php'  );
        }

        $this->strAdminNotice = '<strong>' . 'Responsive Column Widgets' . '</strong><br />';

        if ( ! empty( $strHook ) ) 
            add_action( $strHook, array( $this, 'CheckRequirements' ) );
        else if ( $strHook == '' )        
            $this->CheckRequirements();
        else if ( is_null( $strHook ) )
            return $this;    // do nothing if it's null
            
    }

    function CheckRequirements() {
        /*
         * Do not call this method with register_activation_hook(). For some reasons, it won't trigger the deactivate_plugins() function.
         * */
             
        if ( !$this->IsSufficientPHPVersion( $this->arrParams['php']['version'] ) ) {
            $this->bSufficient = False;
            $this->strAdminNotice .= sprintf( $this->arrParams['php']['error'], $this->arrParams['php']['version'] ) . '<br />';
        }

        if ( !$this->IsSufficientWordPressVersion( $this->arrParams['wordpress']['version'] ) ) {
            $this->bSufficient = False;
            $this->strAdminNotice .= sprintf( $this->arrParams['wordpress']['error'], $this->arrParams['wordpress']['version'] ) . '<br />';
        }
        
        // 'The plugin requires the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mb string extension</a> installed on the server.
        if ( count( $arrNonFoundFuncs = $this->CheckFunctions( $this->arrParams['functions'] ) ) > 0 ) {
            $this->bSufficient = False;
            foreach ( $arrNonFoundFuncs as $i => $strError ) 
                $this->strAdminNotice .= $strError . '<br />';
                
        }
        if ( count( $arrNonFoundClasses = $this->CheckClasses( $this->arrParams['classes'] ) ) > 0 ) {
            $this->bSufficient = False;
            foreach ( $arrNonFoundClasses as $i => $strError ) 
                $this->strAdminNotice .= $strError . '<br />';
        }
        if ( count( $arrNonFoundConstants = $this->CheckConstants( $this->arrParams['constants'] ) ) > 0 ) {
            $this->bSufficient = False;
            foreach ( $arrNonFoundConstants as $i => $strError ) 
                $this->strAdminNotice .= $strError . '<br />';
        }
        
        if ( !$this->bSufficient ) {

            add_action( 'admin_notices', array( $this, 'ShowAdminNotice' ) );    
            if ( $this->bDeactivate )
                deactivate_plugins( $this->strPluginFilePath );

        }
    }
    
    function ShowAdminNotice() {
        $strMsg = $this->bDeactivate ? '<strong>' . __( 'Deactivating the plugin.', 'responsive-column-widgets' ) . '</strong>' : '';
        echo '<div class="error"><p>' 
            . $this->strAdminNotice     // it ends with <br />
            . $strMsg
            . '</p></div>';
    }
    
    protected function IsSufficientPHPVersion( $strPHPver ) {
        
        if ( version_compare( phpversion(), $strPHPver, ">=" ) ) return true;
            
    }
    protected function IsSufficientWordPressVersion( $strWPver ) {
        
        global $wp_version;
        if ( version_compare( $wp_version, $strWPver, ">=" ) ) return true;
        
    }
    protected function CheckClasses( $arrClasses ) {
        
        $arrClasses = $arrClasses ? $arrClasses : $this->arrParams['classes'];
        $arrNonExistentClasses = array();
        foreach( $arrClasses as $strClass => $strError ) 
            if ( ! class_exists( $strClass ) )
                $arrNonExistentClasses[] = sprintf( $strError, $strClass );
        return $arrNonExistentClasses;
        
    }
    protected function CheckFunctions( $arrFuncs ) {
        
        // returns non-existent functions as array.
        $arrFuncs = $arrFuncs ? $arrFuncs : $this->arrParams['functions'];
        $arrNonExistentFuncs = array();
        foreach( $arrFuncs as $strFunc => $strError ) 
            if ( ! function_exists( $strFunc ) ) 
                $arrNonExistentFuncs[] = sprintf( $strError, $strFunc );
        return $arrNonExistentFuncs;
        
    }    
    protected function CheckConstants( $arrConstants ) {
        
        // returns non-existent constants as array.
        $arrConstants = $arrConstants ? $arrConstants : $this->arrParams['constants'];
        $arrNonExistentConstants = array();
        foreach( $arrConstants as $strConstant => $strError ) 
            if ( ! defined( $strConstant ) ) 
                $arrNonExistentConstants[] = sprintf( $strError, $strConstant );
        return $arrNonExistentConstants;
        
    }    
    
}