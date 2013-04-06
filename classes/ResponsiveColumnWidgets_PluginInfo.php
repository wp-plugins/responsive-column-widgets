<?php
/**
	Retrieves and returns the plugin information.
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.9
*/

// Not extensible as this is used by the extensions and activation hooks.
class ResponsiveColumnWidgets_PluginInfo {
	
	// Properties
	protected $strPluginFilePath;	// Stores the plugin main script path. Should be set with the constructor.
	public $arrPluginInfo;		// Stores the plugin information.	
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
	public $Type;	// custom property that indicats the plugin type.
	
	function __construct( $strPath ) {
		
		$this->strPluginFilePath = $strPath;
		
		$this->arrPluginInfo = $this->GetPluginInfo( $this->strPluginFilePath );
		$this->Name = $this->arrPluginInfo['Name'];
		$this->PluginURI = $this->arrPluginInfo['PluginURI'];
		$this->Version = $this->arrPluginInfo['Version'];
		$this->Description = $this->arrPluginInfo['Description'];
		$this->Author = $this->arrPluginInfo['Author'];
		$this->AuthorURI = $this->arrPluginInfo['AuthorURI'];
		$this->TextDomain = $this->arrPluginInfo['TextDomain'];
		$this->DomainPath = $this->arrPluginInfo['DomainPath'];
		$this->Network = $this->arrPluginInfo['Network'];
		$this->_sitewide = $this->arrPluginInfo['_sitewide'];
		
		$this->Type = ( strpos( 'Pro', $this->Name ) !== false ) ? 1 : 0;	// stores 1 for Pro, 0 for free.
		
	}
	
	protected function GetPluginInfo( $strFilePath ) {
		
		// Extracted from the get_plugin_data() function defined in ABSPATH . 'wp-admin/includes/plugin.php'
		// Since there are those who change the location of wp-admin, include() will fail in such cases. 
		
		$arrDefault = array(
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'TextDomain' => 'Text Domain',
			'DomainPath' => 'Domain Path',
			'Network' => 'Network',
			// Site Wide Only is deprecated in favor of Network.
			'_sitewide' => 'Site Wide Only',
		);
		
		// There are cases that the path is not passed; there are various reasons for it 
		// such as allowing the redirecting parameter value of the caller function to be null for backward compatiliby etc.
		if ( ! $strFilePath ) return $arrDefault;
		
		// The get_file_data() funciton is defined in /wp-includes/functions.php so it's safe to use that.
		return get_file_data( 
			$strFilePath, 
			$arrDefault,
			'plugin' 
		);		
		
	}
}