<?php
/**
 *	Handles the plugin action events
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.4
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
			if ( $_GET['rcw_ajax_request'] == 2 ) 
				add_action( 'wp_loaded', array( $this, 'DumpSidebarHierarchy' ) );
				
		}
		
		// For SimplePie cache renewal events - since 1.0.7
		if ( isset( $_GET['doing_wp_cron'] ) )	// if WP Cron is the one which loaded the page,
			add_action( 'RCWP_action_simplepie_renew_cache', array( $this, 'RenewCaches' ) );
	
		// Redirects
		if ( isset( $_GET['responsive_column_widgets_link'] ) && $_GET['responsive_column_widgets_link'] ) {
			
			$oRedirect = new ResponsiveColumnWidgets_Redirects;
			$oRedirect->Go( $_GET['responsive_column_widgets_link'] );
			exit;
			
		}
			
		// Draw cached image.
		if ( isset( $_GET['responsive_column_widgets_image'] ) && $_GET['responsive_column_widgets_image'] ) {
			
			$oImageLoader = new ResponsiveColumnWidgets_ImageHandler( 'RCW' );
			$oImageLoader->Draw( $_GET['responsive_column_widgets_image'] );
			exit;
			
		}
	
		// Debug API that dumps requested option values
		if ( isset( $_GET['responsive_column_widgets_debug'] ) ) {
			
			ResponsiveColumnWidgets_Debug::DumpOption( $_GET['responsive_column_widgets_debug'] );
			exit;
			
		}
	
	}
	
	public function WidgetRegistrationAjaxCallback() {	 // since 1.1.3 
		
		$oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
		$oSH->DumpSidebarHierarchyAsJSON();
		
	}
	public function DumpSidebarHierarchy() {	// since 1.1.3

		$oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
		$oSH->DumpSidebarHierarchy();
		
	}
	
	function SetUpTransients() {
				
		// Setup Transients
		$oUA = new ResponsiveColumnWidgets_UserAds();
		$oUA->SetupTransients();
				
	}
	
	function RenewCaches( $vURLs ) {
		
		// If the transient exists, it has been renewed during the scheduling process. 
		// So avoid duplicated renew tasks.
		$bTransientExists = False;
		foreach( ( array ) $vURLs as $strURL ) 
			$bTransientExists = get_transient( 'RCWFeed_M__' . md5( $strURL ) ) ? True : False;
		if ( $bTransientExists ) return;
		
		// Setup Caches
		$oFeed = new ResponsiveColumnWidgets_SimplePie();

		// Set urls
		$oFeed->set_feed_url( $vURLs );	
		
		// this should be set after defineing $vURLs
		$oFeed->set_cache_duration( 0 );	// 0 seconds, means renew the cache right away.
	
		// Set the background flag to True so that it won't trigger the event action reccursively.
		$oFeed->SetBackground( True );
		$oFeed->init();	
		
	}
}


