<?php
/**
 *    Handles the plugin action events
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0.4
 * 
*/

class ResponsiveColumnWidgets_Events_ {
    
    function __construct( &$oOption ) {
        
        $this->oOption = $oOption;
        
        // For activation hook
        add_action( 'RCWP_action_setup_transients', array( $this, 'SetUpTransients' ) );
        
        // Since 1.1.3 - for widget registration Ajax callback
        if ( isset( $_GET['rcw_ajax_request'] ) ) {
            
            add_action( 'wp_ajax_nopriv_get_sidebar_hierarchy', array( $this, 'WidgetRegistrationAjaxCallback' ) );
            add_action( 'wp_ajax_get_sidebar_hierarchy', array( $this, 'WidgetRegistrationAjaxCallback' ) );
            
            // This is for manual checks.
            if ( $_GET['rcw_ajax_request'] == 2 && defined( 'WP_DEBUG' ) && WP_DEBUG )
                add_action( 'wp_loaded', array( $this, 'DumpSidebarHierarchy' ) );
                
        }
        
        // For SimplePie cache renewal events - since 1.0.7
        if ( isset( $_GET['doing_wp_cron'] ) )    // if WP Cron is the one which loaded the page,
            add_action( 'RCWP_action_simplepie_renew_cache', array( $this, 'RenewCaches' ) );
    
        // Redirects
        if ( isset( $_GET['responsive_column_widgets_link'] ) && $_GET['responsive_column_widgets_link'] && is_user_logged_in() ) {
            
            $oRedirect = new ResponsiveColumnWidgets_Redirects;
            $oRedirect->Go( $_GET['responsive_column_widgets_link'] );
            exit;
            
        }
            
        // Draw cached image.
        if ( isset( $_GET['responsive_column_widgets_image'] ) && $_GET['responsive_column_widgets_image'] && is_user_logged_in() ) {
            
            $oImageLoader = new ResponsiveColumnWidgets_ImageHandler( 'RCW' );
            $oImageLoader->Draw( $_GET['responsive_column_widgets_image'] );
            exit;
            
        }
    
        // Since 1.1.3 - Debug API that dumps requested option values
        if ( isset( $_GET['responsive_column_widgets_debug'] ) ) {
            
            ResponsiveColumnWidgets_Debug::DumpOption( $_GET['responsive_column_widgets_debug'] );
            exit;
            
        }

        // Since 1.1.4 - For debug info embedded into the footer.
        if (
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
        )    
            add_action( 'wp_footer', array( $this, 'PrintDebugInfo' ) );
    
    
        // Since 1.1.5.3 - For shortcode execution in text widgets.
        if ( isset( $this->oOption->arrOptions['general']['execute_shortcode_in_widgets'] ) ) {
        
            if ( $this->oOption->arrOptions['general']['execute_shortcode_in_widgets'] == 1 )
                add_filter( 'widget_text', 'do_shortcode' );
            else if ( $this->oOption->arrOptions['general']['execute_shortcode_in_widgets'] == 2 )
                add_filter( 'RCW_filter_widgetbox_output', 'do_shortcode' );
                
        }
    }
    
    public function PrintDebugInfo() {    // since 1.1.4
            
        echo '<p>Memory Usage: ' . ResponsiveColumnWidgets_Debug::GetMemoryUsage( 1 ) . '</p>';
        echo '<p>Memory Peak Usage: ' . ResponsiveColumnWidgets_Debug::GetMemoryUsage( 2 ) . '</p>';
        
    }
    
    public function WidgetRegistrationAjaxCallback() {     // since 1.1.3 
        
        $oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
        $oSH->DumpSidebarHierarchyAsJSON();
        
    }
    public function DumpSidebarHierarchy() {    // since 1.1.3

        $oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
        $oSH->DumpSidebarHierarchy();
        
    }
    
    function SetUpTransients() {
                
        // Setup Transients
        $oUA = new ResponsiveColumnWidgets_UserAds();
        $oUA->SetupTransients();
                
    }
    
    function RenewCaches( $vURLs ) {

// ResponsiveColumnWidgets_Debug::DumpArray( $vURLs, dirname( __FILE__ ) . '/cache_renewal.txt'  );        

        // Setup Caches
        $oFeed = new ResponsiveColumnWidgets_SimplePie();

        // Set urls
        $oFeed->set_feed_url( $vURLs );    
        
        // this should be set after defining $vURLs
        $oFeed->set_cache_duration( 0 );    // 0 seconds, means renew the cache right away.
    
        // Set the background flag to True so that it won't trigger the event action recursively.
        $oFeed->SetBackground( True );
        $oFeed->init();    
        
    }
}


