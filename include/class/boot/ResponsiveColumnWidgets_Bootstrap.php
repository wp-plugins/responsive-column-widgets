<?php
/**
 * Loads the plugin.
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013-2015, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.0
*/

/**
 * Loads the plugin.
 * 
 * @action      RCW_action_started      Triggered when the plugin components are fully loaded. 
 */
final class ResponsiveColumnWidgets_Bootstrap {
    
    static private $_bLoaded = false;
    
    public function __construct( $sFilePath )  {
        
        // Prevent loading multiple times.
        if ( self::$_bLoaded ) {
            return;
        }
        self::$_bLoaded = true;
        
        // Properties
        $this->sFilePath = $sFilePath;
        $this->bIsAdmin  = is_admin();
        
        // Constants and Variables
        $this->_setConstants();
        $this->_setGlobals();

        // Files
        $this->_include();
     
        // Activation / Deactivation Hooks
        register_activation_hook( $this->sFilePath, array( $this, 'replyToDoUponPluginActivation' ) );     
        register_deactivation_hook( $this->sFilePath, array( $this, 'replyToDoUponPluginDeactivation' ) );        
        
        // Delayed Plugin Components
        add_action( 'plugins_loaded', array( $this, 'replyToLoadPluginComponents' ) );
        
        // Localization
        add_action( 'init', array( $this, 'replyToLocalize' ) );
        
    }
    
    /**
     * Include necessary files.
     */
    private function _include() {
            
        // Libraries    
        if ( $this->bIsAdmin ) {
            include( dirname( $this->sFilePath ) . '/include/library/ResponsiveColumnWidgets_Admin_Page_Framework.php' );
        }
        
        // Auto loader
        include( dirname( $this->sFilePath ) . '/include/class/boot/ResponsiveColumnWidgets_RegisterClasses.php' );
        include( dirname( $this->sFilePath ) . '/include/class/boot/ResponsiveColumnWidgets_RegisterClasses2.php' );
        
        // User functions.
        include( dirname( $this->sFilePath ) . '/include/function/functions.php' );
        
        // Register Classes of Booting components.
        $this->_registerClasses_Boot();
        
    }
        private function _registerClasses_Boot() {
            
            $_aClassFiles = array();            
            include( dirname( $this->sFilePath ) . '/include/include-class-list-boot.php' );
            
            // The parameter of ResponsiveColumnWidgets_RegisterClasses2 is a bit different from ResponsiveColumnWidgets_RegisterClasses
            new ResponsiveColumnWidgets_RegisterClasses2(
                array(),        // scanning dir
                array(),        // search options
                $_aClassFiles   // pre-defined class inclusion list
            );
            
        }
        
    private function _setConstants() {
        
        // We use two keys for the options. One for the actual options and the other is for the admin pages.
        define( "RESPONSIVECOLUMNWIDGETSKEY", "responsive_column_widgets" );
        define( "RESPONSIVECOLUMNWIDGETSKEYADMIN", "responsive_column_widgets_admin" );

        define( "RESPONSIVECOLUMNWIDGETSFILE", $this->sFilePath );
        define( "RESPONSIVECOLUMNWIDGETSDIR", dirname( $this->sFilePath ) );
        define( "RESPONSIVECOLUMNWIDGETSURL", plugins_url( '', $this->sFilePath ) );
        
    }
    
    private function _setGlobals() {
        
        /**
         * An array holding class paths to use. This will be refereed by the spl autoloader.
         */
        $GLOBALS['arrResponsiveColumnWidgetsClasses'] = isset( $GLOBALS['arrResponsiveColumnWidgetsClasses'] ) 
            ? $GLOBALS['arrResponsiveColumnWidgetsClasses']
            : array();    
        
        /**
         * Stores flag values that need to be global.
         * @since       1.1.0
         */
        $GLOBALS['arrResponsiveColumnWidgets_Flags'] = array(     
            'base_style'                        => false,      // Indicates whether the base CSS rules have been loaded or not.
            'arrIDCounters'                     => array(),    // 1.1.1+ - stores how many times particular widget box's rendering requests are made. Used to assign an ID selector to an ID attribute.
            'arrWidgetIDAttributes'             => array(),    // 1.1.1+ - stores used ID attributes for widgets to avoid validation errors.
            'arrUserCustomStyles'               => array(),    // 1.1.2+ - stores box IDs whose user custom CSS rules are loaded.
            'arrWidgetBoxRenderingCallerIDs'    => array(),    // 1.1.2+ - stores the caller IDs of widget box rendering request based on the widget box's sidebar ID and the used parameter values.
            'arrEnqueueStyleParams'             => array(),    // 1.1.2.1+ - stores parameter arrays passed by the ResponsiveColumnWidgets_EnqueueStyle() function.
        );
        
        /**
         * the option object which stores and manipulates necessary plugin settings.
         */
        $GLOBALS['oResponsiveColumnWidgets_Options'] = null;
        /**
         * the core object which handles rendering widgets.
         */
        $GLOBALS['oResponsiveColumnWidgets']         = null;

    }
    
    /**
     * Load localization files.
     * 
     * @remark      A callback for the 'init' hook.
     * @xince       1.2.0
     */
    public function replyToLocalize() {
        
        // This plugin does not have messages to be displayed in the front end.
        if ( ! $this->bIsAdmin ) { return; }
        
        load_plugin_textdomain( 
            'responsive-column-widgets', 
            false, 
            dirname( plugin_basename( ResponsiveColumnWidgets_Registry::$sFilePath ) ) . '/lang/'
        );
        load_plugin_textdomain( 
            'admin-page-framework', 
            false, 
            dirname( plugin_basename( ResponsiveColumnWidgets_Registry::$sFilePath ) ) . '/lang/'
        );                        
        
    }            
    
    /**
     * Called when the plugin gets activated.
     */
    public function replyToDoUponPluginDeactivation() {
        
        // @todo: clear transients
        // 'ResponsiveColumnWidgets_Cleaner::CleanTransients'
        
    }
    
    /**
     * Called when the plugin gets deactivated.
     */
    public function replyToDoUponPluginActivation() {
        
        // @todo: Check requirements.
        // $this->_checkRequirements();
        wp_schedule_single_event( time(), 'RCWP_action_setup_transients' );
        
    }

    
    /**
     * Loads plugin components.
     * 
     * @remark      A callback for the 'plugin_loaded' action hook.
     */
    public function replyToLoadPluginComponents() {
        
        // Register Classes - This is done after all plugins are loaded because extension plugins modifies the default loading class array.
        $this->_registerClasses();
                
        // Option object
        $GLOBALS['oResponsiveColumnWidgets_Options'] = new ResponsiveColumnWidgets_Option( 
            RESPONSIVECOLUMNWIDGETSKEY, 
            defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) 
                ? RESPONSIVECOLUMNWIDGETSPROFILE 
                : RESPONSIVECOLUMNWIDGETSFILE
        );    
                        
        // Load the core.
        $GLOBALS['oResponsiveColumnWidgets'] = new ResponsiveColumnWidgets_Core(
            $GLOBALS['oResponsiveColumnWidgets_Options']
        );

        // Admin Page 
        if ( is_admin() ) {
            $_oAdmin = new ResponsiveColumnWidgets_Admin_Page( 
                RESPONSIVECOLUMNWIDGETSKEYADMIN,
                RESPONSIVECOLUMNWIDGETSFILE
            );        
            $_oAdmin->SetOptionObject( $GLOBALS['oResponsiveColumnWidgets_Options'] );
        }
        
        // Load events
        new ResponsiveColumnWidgets_Events( $GLOBALS['oResponsiveColumnWidgets_Options'] );
            
        // Auto-insert [1.0.8+]
        new ResponsiveColumnWidgets_AutoInsert( $GLOBALS['oResponsiveColumnWidgets'] );

        // Widgets.
        add_action( 'widgets_init', 'ResponsiveColumnWidgets_Widget::RegisterWidget' );
        
        // Shortcode
        new ResponsiveColumnWidgets_Shortcode( ResponsiveColumnWidgets_Registry::$aShortcodes['main'] );
        
        // Sidebar
        new ResponsiveColumnWidgets_Sidebar( $GLOBALS['oResponsiveColumnWidgets_Options'] );
        
        // Compatibility issues
        add_action( 'sidebar_admin_setup', 'ResponsiveColumnWidgets_Widget::fixAsyncSaveBug' );    // for ajax async calls
        
        // For plugin extensions
        do_action( 'RCW_action_started', $GLOBALS['oResponsiveColumnWidgets_Options'] );
        
    }
        /**
         * Registers PHP classes to be auto-loaded.
         * 
         * @since       1.2.0
         */
        private function _registerClasses() {
            
            $_aClassFiles   = array();
            include( dirname( $this->sFilePath ) . '/include/include-class-list.php' );
            
            $_aClassList    = $this->_getSanitizedClassList( ( array ) $GLOBALS['arrResponsiveColumnWidgetsClasses'] )
                + $_aClassFiles;
                
            new ResponsiveColumnWidgets_RegisterClasses2(
                array(),        // scanning dirs
                array(),        // search options
                $_aClassList    // pre defined class list array
            );
                
        }
            /**
             * Sanitizes the inclusion class list.
             * 
             * In the previous versions, the values of the class list array do not have file extensions. Like this:
             * <coed>
             * array(
             *      'MyCLass' => '../responsive-column-widgets/classes/MyClass'
             *      'AnotherClass' => '../responsive-column-widgets/classes/AnotherClass'
             * )
             * </code>
             * The new autoloader class expects the value to have the exact file path. So sanitize tha array here.
             * 
             * @since       1.2.0
             */
            private function _getSanitizedClassList( array $aClassList, $asAllowedExts=array( 'php', 'inc' ) ) {
                
                $_aAllowedExts = is_array( $asAllowedExts ) ? $asAllowedExts : array( $asAllowedExts );
                foreach( $aClassList as &$_sClassPath ) {
                        
                    $_sClassPath = trim( $_sClassPath );

                    // If the path end widh the allowed file extension, it is okay.
                    if ( in_array( pathinfo( $_sClassPath,  PATHINFO_EXTENSION ), $_aAllowedExts ) ) {
                        continue;
                    }
                    
                    // Set it to the default file extension.
                    $_sClassPath = $_sClassPath . '.php';
                    
                }
                return $aClassList;
                
            }
 
}