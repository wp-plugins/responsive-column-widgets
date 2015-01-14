<?php
/**
    Methods used for debugging
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.3
 * 
    
*/

class ResponsiveColumnWidgets_Debug {

    static public function DumpArray( $arr, $strFilePath=null ) {
        
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
        
        if ( $strFilePath ) {
            
            file_put_contents( 
                $strFilePath , 
                date( "Y/m/d H:i:s" ) . PHP_EOL
                . print_r( $arr, true ) . PHP_EOL . PHP_EOL
                , FILE_APPEND 
            );                    
            
        }
        return '<pre class="dump-array">' . esc_html( print_r( $arr, true ) ) . '</pre>';
        
    }
    
    /**
     * Retrieves the output of the given array contents.
     * 
     * If a file pass is given, it saves the output in the file.
     * 
     * @since            1.1.10
     */
    static public function getArray( $asArray, $sFilePath=null, $bEscape=true ) {
            
        if ( $sFilePath ) self::logArray( $asArray, $sFilePath );            
        
        return $bEscape
            ? "<pre class='dump-array'>" . htmlspecialchars( print_r( $asArray, true ) ) . "</pre>"    // esc_html() has a bug that breaks with complex HTML code.
            : print_r( $asArray, true );    // non-escape is used for exporting data into file.
        
    }    
    
    /**
     * Logs the given variable output to a file.
     * 
     * @remark      The alias of the `logArray()` method.
     * @since       1.2.0
     **/
    static public function log( $vValue, $sFilePath=null ) {
                
        static $_iPageLoadID; // identifies the page load.
        static $_nGMTOffset;
        static $_fPreviousTimeStamp = 0;
        $_iPageLoadID       = $_iPageLoadID ? $_iPageLoadID : uniqid();     
        $_oCallerInfo       = debug_backtrace();
        $_sCallerFunction   = isset( $_oCallerInfo[ 1 ]['function'] ) ? $_oCallerInfo[ 1 ]['function'] : '';
        $_sCallerClasss     = isset( $_oCallerInfo[ 1 ]['class'] ) ? $_oCallerInfo[ 1 ]['class'] : '';
        $sFilePath          = ! $sFilePath
            ? WP_CONTENT_DIR . DIRECTORY_SEPARATOR . get_class() . '_' . $_sCallerClasss . '_' . date( "Ymd" ) . '.log'
            : ( true === $sFilePath
                ? WP_CONTENT_DIR . DIRECTORY_SEPARATOR . get_class() . '_' . date( "Ymd" ) . '.log'
                : $sFilePath
            );
        $_nGMTOffset            = isset( $_nGMTOffset ) ? $_nGMTOffset : get_option( 'gmt_offset' );
        $_fCurrentTimeStamp     = microtime( true );
        $_nNow                  = $_fCurrentTimeStamp + ( $_nGMTOffset * 60 * 60 );
        $_nMicroseconds         = round( ( $_nNow - floor( $_nNow ) ) * 10000 );
        $_nMicroseconds         = str_pad( $_nMicroseconds, 4, '0' );
        $_nElapsed              = round( $_fCurrentTimeStamp - $_fPreviousTimeStamp, 3 );
        $_aElapsedParts         = explode( ".", ( string ) $_nElapsed );
        $_sElapsedFloat         = str_pad( isset( $_aElapsedParts[ 1 ] ) ? $_aElapsedParts[ 1 ] : 0, 3, '0' );
        $_sElapsed              = isset( $_aElapsedParts[ 0 ] ) ? $_aElapsedParts[ 0 ] : 0;
        $_sElapsed              = strlen( $_sElapsed ) > 1 ? '+' . substr( $_sElapsed, -1, 2 ) : ' ' . $_sElapsed;
        $_sHeading              = date( "Y/m/d H:i:s", $_nNow ) . '.' . $_nMicroseconds . ' ' 
            . $_sElapsed . '.' . $_sElapsedFloat . ' ' . $_iPageLoadID . ' '  
            . ResponsiveColumnWidgets_Registry::Version . ' '
            . "{$_sCallerClasss}::{$_sCallerFunction} " 
            . current_filter() . ' '
            . self::getCurrentURL() . ' '            
            ;
        $_sType                 = gettype( $vValue );
        $_iLengths              = is_string( $vValue ) || is_integer( $vValue )
            ? strlen( $vValue  )
            : ( is_array( $vValue )
                ? count( $vValue )
                : null
            );
        $vValue                 = is_object( $vValue )
            ? ( method_exists( $vValue, '__toString' ) 
                ? ( string ) $vValue          // cast string
                : ( array ) $vValue           // cast array
            )
            : $vValue;
        $vValue                 = is_array( $vValue )
            ? self::getSliceByDepth( $vValue, 5 )
            : $vValue;
        file_put_contents( 
            $sFilePath, 
            $_sHeading . PHP_EOL 
                . '(' 
                    . $_sType 
                    . ( null !== $_iLengths ? ', length: ' . $_iLengths : '' )
                . ') '
                . print_r( $vValue, true ) . PHP_EOL . PHP_EOL,
            FILE_APPEND 
        );     
        $_fPreviousTimeStamp = $_fCurrentTimeStamp;
        
    }     
        /**
         * Logs the given array output into the given file.
         * 
         * @since           1.1.10
         * @deprecated      1.2.0       Use `log()`.
         */
        static public function logArray( $asArray, $sFilePath=null ) {
            self::log( $asArray, $sFilePath );                   
        }        
    
    /**
     * Logs the given array output into the given file.
     * 
     * @since           1.1.10
     * @deprecated      1.2.0       Use `log()`.
     */
    static public function _logArray( $asArray, $sFilePath=null ) {
        
        static $_iPageLoadID;
        $_iPageLoadID = $_iPageLoadID ? $_iPageLoadID : uniqid();        
        
        $_oCallerInfo = debug_backtrace();
        $_sCallerFunction = isset( $_oCallerInfo[ 1 ]['function'] ) ? $_oCallerInfo[ 1 ]['function'] : '';
        $_sCallerClasss = isset( $_oCallerInfo[ 1 ]['class'] ) ? $_oCallerInfo[ 1 ]['class'] : '';
        $sFilePath = $sFilePath
            ? $sFilePath
            : WP_CONTENT_DIR . DIRECTORY_SEPARATOR . get_class() . '_' . date( "Ymd" ) . '.log';        
        file_put_contents( 
            $sFilePath,
            date( "Y/m/d H:i:s", current_time( 'timestamp' ) ) . ' ' . "{$_iPageLoadID} {$_sCallerClasss}::{$_sCallerFunction} " . self::getCurrentURL() . PHP_EOL    
            . print_r( $asArray, true ) . PHP_EOL . PHP_EOL,
            FILE_APPEND 
        );            
            
    }        
    
    /**
     * Retrieves the currently loaded page url.
     * 
     * @since            1.1.10
     */
    static public function getCurrentURL() {
        $sSSL = ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? true:false;
        $sServerProtocol = strtolower( $_SERVER['SERVER_PROTOCOL'] );
        $sProtocol = substr( $sServerProtocol, 0, strpos( $sServerProtocol, '/' ) ) . ( ( $sSSL ) ? 's' : '' );
        $sPort = $_SERVER['SERVER_PORT'];
        $sPort = ( ( !$sSSL && $sPort=='80' ) || ( $sSSL && $sPort=='443' ) ) ? '' : ':' . $sPort;
        $sHost = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        return $sProtocol . '://' . $sHost . $sPort . $_SERVER['REQUEST_URI'];
    }    
    
    static public function EchoMemoryUsage() {
        
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
                   
        echo self::GetMemoryUsage() . "<br/>";
        
    }         

    static public function GetMemoryUsage( $intType=1 ) {    // since 1.1.4
       
       $intMemoryUsage = $intType == 1 ? memory_get_usage( true ) : memory_get_peak_usage( true );
       
        if ( $intMemoryUsage < 1024 ) return $intMemoryUsage . " bytes";
        
        if ( $intMemoryUsage < 1048576 ) return round( $intMemoryUsage/1024,2 ) . " kilobytes";
        
        return round( $intMemoryUsage / 1048576,2 ) . " megabytes";
           
    }         
    
    static public function DumpOption( $strKey ) {

        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
        
        $oOption = & $GLOBALS['oResponsiveColumnWidgets_Options'];        
        if ( ! isset( $oOption->arrOptions[ $strKey ] ) ) return;
        
        die( ResponsiveColumnWidgets_Debug::DumpArray( $oOption->arrOptions[ $strKey ] ) );
        
        
    }
    
    /**
     * Slices the given array by depth.
     * 
     * @since       1.2.0
     */
    static public function getSliceByDepth( array $aSubject, $iDepth=0 ) {

        foreach ( $aSubject as $_sKey => $_vValue ) {
            if ( is_object( $_vValue ) ) {
                $aSubject[ $_sKey ] = method_exists( $_vValue, '__toString' ) 
                    ? ( string ) $_vValue           // cast string
                    : get_object_vars( $_vValue );  // convert it to array.
            }
            if ( is_array( $_vValue ) ) {
                if ( $iDepth > 0 ) {
                    $aSubject[ $_sKey ] = self::getSliceByDepth( $_vValue, --$iDepth );
                    continue;
                } 
                unset( $aSubject[ $_sKey ] );
            }
        }
        return $aSubject;
        
    }            
    
}