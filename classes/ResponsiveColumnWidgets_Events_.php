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
		
		// for activation hook
		add_action( 'RCWP_action_setup_transients', array( $this, 'SetUpTransients' ) );
		
		// for SimplePie cache renewal events - since 1.0.7
		if ( isset( $_GET['doing_wp_cron'] ) )	// if WP Cron is the one which loaded the page,
			add_action( 'RCWP_action_simplepie_renew_cache', array( $this, 'RenewCaches' ) );
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
// sleep( 3 );
		$oFeed->init();	
// echo '<pre>' . 'URLs: ' . print_r( $vURLs, true ) . PHP_EOL . '</pre>';	// shoud not be printed ever
// file_put_contents( dirname( __FILE__ ) . '/info_renewed.txt' , 
	// __FILE__ . PHP_EOL 
	// . __METHOD__ . PHP_EOL
	// . print_r( 'The cache has been renewed!', true ) . PHP_EOL
	// . 'URLs: ' . print_r( $vURLs, true ) . PHP_EOL
	// . 'URLs: ' . print_r( $_GET, true ) . PHP_EOL
	// . 'Current Time: ' . time() . PHP_EOL
	// . PHP_EOL . PHP_EOL
	// , FILE_APPEND 
// );	
		
	}
}


