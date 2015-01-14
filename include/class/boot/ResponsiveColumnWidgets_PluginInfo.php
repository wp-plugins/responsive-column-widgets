<?php
/**
    Retrieves and returns the plugin information.
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0.9
*/

// Not extensible as this is used by the extensions and activation hooks.
class ResponsiveColumnWidgets_PluginInfo {
    
    // Properties
    protected $strPluginFilePath;    // Stores the plugin main script path. Should be set with the constructor.
    public $arrPluginInfo;        // Stores the plugin information.    
    public $Name;
    public $PluginURI;
    public $Version;
    public $Description;
    public $Author;
    public $AuthorURI;
    public $TextDomain;
    public $DomainPath;
    public $Network;
    public $_sitewide;
    public $Type;    // custom property that indicates the plugin type.
    
    function __construct( $strPath ) {
        
        $this->strPluginFilePath = $strPath;
        
        $this->arrPluginInfo    = $this->GetPluginInfo( $this->strPluginFilePath );
        $this->Name                = $this->arrPluginInfo['PluginName'];
        $this->PluginName        = $this->arrPluginInfo['PluginName'];
        $this->PluginURI        = $this->arrPluginInfo['PluginURI'] ? $this->arrPluginInfo['PluginURI'] : 'Responsive Column Widgets';
        $this->Version            = $this->arrPluginInfo['Version'];
        $this->Description        = $this->arrPluginInfo['Description'];
        $this->Author            = $this->arrPluginInfo['Author'];
        $this->AuthorURI        = $this->arrPluginInfo['AuthorURI'];
        $this->TextDomain        = $this->arrPluginInfo['TextDomain'];
        $this->DomainPath        = $this->arrPluginInfo['DomainPath'];
        $this->Network            = $this->arrPluginInfo['Network'];
        $this->_sitewide        = $this->arrPluginInfo['_sitewide'];
        
        $this->Type = ( strpos( 'Pro', $this->PluginName ) !== false ) ? 1 : 0;    // stores 1 for Pro, 0 for free.
        
    }
    
    /**
     * Returns an array string plugin information.
     * 
     * @see the get_plugin_data() function defined in ABSPATH . 'wp-admin/includes/plugin.php'
     */
    protected function GetPluginInfo( $sFilePath ) {
                
        $_aDefault = array(
            'PluginName'    => 'Plugin Name',    //'Responsive Column Widgets',
            'PluginURI'     => 'Plugin URI',    // 'http://wordpress.org/plugins/responsive-column-widgets/',
            'Version'        => 'Version',
            'Description'    => 'Description',    //'Creates a widget box which displays widgets in columns with a responsive design.',
            'Author'        => 'Author',        // 'miunosoft',
            'AuthorURI'        => 'AuthorURI',        //'http://en.michaeluno.jp',
            'TextDomain'    => 'TextDomain',    // 'responsive-column-widgets',
            'DomainPath'    => 'DomainPath',    // './languages',
            'Network'        => 'Network',
            // Site Wide Only is deprecated in favor of Network.
            '_sitewide' => 'Site Wide Only',
        );
        
        // There are cases that the path is not passed; there are various reasons for it 
        // such as allowing the redirecting parameter value of the caller function to be null for backward compatibility etc.
        return $sFilePath 
            ? get_file_data( $sFilePath, $_aDefault, '' )
            : $_aDefault;
                
    }
}