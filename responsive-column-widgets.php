<?php
/*
    Plugin Name:        Responsive Column Widgets
    Plugin URI:         http://en.michaeluno.jp/responsive-column-widgets
    Description:        Creates a widget box which displays widgets in columns with a responsive design.
    Author:             Michael Uno (miunosoft)
    Author URI:         http://michaeluno.jp
    Requirements:       This plugin requires WordPress >= 3.3 and PHP >= 5.2.4
    Text Domain:        responsive-column-widgets
    Domain Path:        /lang
    Version:            1.2.0
*/

/**
 * The base registry information.
 * 
 * @since       1.2.0
 */
class ResponsiveColumnWidgets_Registry_Base {

	const Version        = '1.2.0';    // <--- DON'T FORGET TO CHANGE THIS AS WELL!!
	const Name           = 'Admin Page Framework';
	const Description    = 'Facilitates WordPress plugin and theme development.';
	const URI            = 'http://en.michaeluno.jp/';
	const Author         = 'miunosoft (Michael Uno)';
	const AuthorURI      = 'http://en.michaeluno.jp/';
	const Copyright      = 'Copyright (c) 2015, Michael Uno';
	const License        = 'GPL v2 or later';
	const Contributors   = '';
	
}
/**
 * Provides plugin information.
 * 
 * The plugin will refer to these information.
 * 
 * @since       1.2.0
 * @remark      
 */
final class ResponsiveColumnWidgets_Registry extends ResponsiveColumnWidgets_Registry_Base {
	        
    /**
     * The plugin option key used for the options table.
     */
    static public $aOptionKeys = array(
        // 'main'    => 'admin_page_framework_loader',
        // 'demo'    => 'admin_page_framework_demo',
    );
    
    /**
     * The transient prefix. 
     * 
     * @remark      This is also accessed from uninstall.php so do not remove.
     * @remark      Up to 8 characters as transient name allows 45 characters or less ( 40 for site transients ) so that md5 (32 characters) can be added
     */
	const TransientPrefix           = 'RCW_';
    
    /**
     * The text domain slug and its path.
     * 
     * These will be accessed from the bootstrap script.
     */
	const TextDomain                = 'responsive-column-widgets';
	const TextDomainPath            = '/language';    
    	    
	// These properties will be defined in the setUp() method.
	static public $sFilePath = '';
	static public $sDirPath  = '';
	
    /**
     * Requirements.
     */    
    static public $aRequirements = array(
        'php' => array(
            'version'   => '5.2.4',
            'error'     => 'The plugin requires the PHP version %1$s or higher.',
        ),
        'wordpress'         => array(
            'version'   => '3.3',
            'error'     => 'The plugin requires the WordPress version %1$s or higher.',
        ),
        'mysql'             => array(
            'version'   => '5.0',
            'error'     => 'The plugin requires the MySQL version %1$s or higher.',
        ),
        'functions'     =>  '', // disabled
        // array(
            // e.g. 'mblang' => 'The plugin requires the mbstring extension.',
        // ),
        'classes'       => '', // disabled
        // array(
            // e.g. 'DOMDocument' => 'The plugin requires the DOMXML extension.',
        // ),
        'constants'     => '',  // disabled
        // array(
            // e.g. 'THEADDONFILE' => 'The plugin requires the ... addon to be installed.',
            // e.g. 'APSPATH' => 'The script cannot be loaded directly.',
        // ),
        'files'         =>  '', // disabled
        // array(
            // e.g. 'home/my_user_name/my_dir/scripts/my_scripts.php' => 'The required script could not be found.',
        // ),
    );    
    
    /**
     * Used admin pages.
     */
    static public $aAdminPages = array(
        // key => 'page slug'
        // 'about'     => 'apfl_about',
        // 'tool'      => 'apfl_tools',
        // 'help'      => 'apfl_contact',
    );
    
    /**
     * Used post types.
     */
    static public $aPostTypes = array(
    );
    
    /**
     * Used taxonomies.
     */
    static public $aTaxonomies = array(
    );
    
    /**
     * Used shortcodes.
     */
    static public $aShortcodes = array(
        'main'  => 'responsive_column_widgets',
    );
    
	/**
	 * Sets up static properties.
	 */
	static function setUp( $sPluginFilePath=null ) {
	                    
		self::$sFilePath = $sPluginFilePath ? $sPluginFilePath : __FILE__;
		self::$sDirPath  = dirname( self::$sFilePath );
	    
	}    
	
	/**
	 * Returns the URL with the given relative path to the plugin path.
	 * 
	 * Example:  ResponsiveColumnWidgets_Registry::getPluginURL( 'asset/css/meta_box.css' );
     * @since       3.5.0
	 */
	public static function getPluginURL( $sRelativePath='' ) {
		return plugins_url( $sRelativePath, self::$sFilePath );
	}
    
    /**
     * Returns the information of this class.
     * 
     * @since       3.5.0
     */
    static public function getInfo() {
        $_oReflection = new ReflectionClass( __CLASS__ );
        return $_oReflection->getConstants()
            + $_oReflection->getStaticProperties()
        ;
    }    
    
}
// Registry set-up.
ResponsiveColumnWidgets_Registry::setUp( __FILE__ );
 
// Bail if accessed directly. Not exit here as uninstall.php will access this file to get registry information.
if ( ! defined( 'ABSPATH' ) ) { return; }

include( dirname( __FILE__ ) . '/include/class/boot/ResponsiveColumnWidgets_Bootstrap.php' );
new ResponsiveColumnWidgets_Bootstrap( __FILE__ );