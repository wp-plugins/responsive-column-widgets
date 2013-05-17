<?php
/**
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.4
 * @description	Creates links for the user.
*/
class ResponsiveColumnWidgets_UserAds_ {

	// Properties
	protected $arrURLFeedText = array( 
		'http://feeds.feedburner.com/GANLinkTextRandom40'
	);
	protected $arrURLFeed160x600 = array(
		'http://feeds.feedburner.com/GANLinkBanner160x600Random40',
		'http://feeds.feedburner.com/RawBanner160x600',
	);
	protected $arrURLFeed468x60 = array(
		'http://feeds.feedburner.com/GANBanner60x468',
		'http://feeds.feedburner.com/RawBanner468x60'
	);
	protected $arrURLFeed728x90 = array(
		'http://feeds.feedburner.com/RawBanner728x90',
		'http://feeds.feedburner.com/CustomBanner728x90',
	);
		
	// Container arrays
	protected $arrFeedItems = array();	// stores fetched feed items.
		
	// Objects
	protected $oOption;	// stores the option object.
		
	function __construct( &$oOption=null ) {
		
		global $oResponsiveColumnWidgets_Options;
		$this->oOption = isset( $oOption ) ? $oOption : $oResponsiveColumnWidgets_Options;
					
	}
	function SetOptionObj( &$oOption ) {
		$this->oOption = $oOption;
	}

	protected function FetchItems( $arrURLs, $numItems=1 ) {	// since 1.1.4
		
		$strURLID = md5( serialize( is_string( $arrURLs ) ? array( $arrURLs ) : $arrURLs ) );
		
		if ( ! isset( $this->arrFeedItems[ $strURLID ] ) ) 
			$this->arrFeedItems[ $strURLID ] = array();
			
		// If it's out of stock, fill the array by fetching the feed.
		if ( count( $this->arrFeedItems[ $strURLID ] ) < $numItems ) {	
			
			$oReplace = new ResponsiveColumnWidgets_HTMLElementReplacer( get_bloginfo( 'charset' ) );
			
			// When an array of urls is passed to the Simple Pie's set_feed_url() method, the memory usage increases largely.
			// So fetch the feeds one by one per url and store the output into an array.
			foreach( $arrURLs as $strURL ) {
				
				$oFeed = $this->GetFeedObj( $strURL, $numItems * 3 );	// multiplied by three to store items more than enough for next calls.
				foreach ( $oFeed->get_items( 0, $numItems * 3 ) as $item ) 
					$this->arrFeedItems[ $strURLID ][] = $oReplace->Perform( $item->get_content() );
				
				// For PHP below 5.3 to release the memory.
				$oFeed->__destruct(); // Do what PHP should be doing on it's own.
				unset( $oFeed ); 
				
			}
			unset( $oReplace );
			
		}
		
		$strOut = '';
		shuffle( $this->arrFeedItems[ $strURLID ] );
		for ( $i = 1; $i <= $numItems; $i++ ) 
			$strOut .= array_pop( $this->arrFeedItems[ $strURLID ] );		
		return $strOut; 		
		
	}
	public function GetBottomBanner( $numItems=1 ) {
					
		return '<div style="margin: 20px 0 8px; width: 728px;">' 
			. $this->FetchItems( $this->arrURLFeed728x90, $numItems )
			. "</div>";
		
	}	
	public function GetSkyscraper( $numItems=2 ) {
		
		return '<div style="float:right; padding: 0px 0 0 20px; width: 160px;">' 
			. $this->FetchItems( $this->arrURLFeed160x600, $numItems )
			. "</div>";
		
	}		
	public function GetTopBanner( $numItems=1 ) {
		
		return '<div style="float:right; margin:0; padding:0; width: 468px;">' 
			. $this->FetchItems( $this->arrURLFeed468x60, $numItems )
			. "</div>";
		
	}	
	public function GetTextAd( $numItems=1 ) { 
	
		return '<div align="left" style="">' 
			. $this->FetchItems( $this->arrURLFeedText, $numItems )
			. "</div>"; 

	}
	
	function InitializeFeeds() {

		// This is used to create transients to prevent delays in page load.
	
		$arrAllURLs = array_merge( 
			$this->arrURLFeedText,  
			$this->arrURLFeed160x600,
			$this->arrURLFeed468x60,
			$this->arrURLFeed728x90
		);

		foreach( $arrAllURLs as $strURL ) {
			
			// Passing 0 to the third parameter is the key, which renews the cache.
			$oTextFeed = $this->GetFeedObj( $strURL, 1, 0 );	
			
			// For PHP below 5.3 to release the memory.
			$oTextFeed->__destruct(); // Do what PHP should be doing on it's own.
			unset( $oTextFeed ); 			
			
		}	
	}

	function GetFeedObj( $arrUrls, $numItem=1, $numCacheDuration=3600 ) {	// 60 seconds * 60 = 1 hour
		
		$oFeed = new ResponsiveColumnWidgets_SimplePie();
		
		// Set Sort Order
		$oFeed->set_sortorder( 'random' );

		// Set urls
		$oFeed->set_feed_url( $arrUrls );	
		$oFeed->set_item_limit( $numItem );	
		
		// This should be set after defining $urls
		$oFeed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', $numCacheDuration, $arrUrls ) );	
		$oFeed->set_stupidly_fast( true );
		
		// If the cache lifetime is explicitly set to 0, do not trigger the background renewal cache event
		if ( $numCacheDuration == 0 )
			$oFeed->SetBackground( true );	// setting it true will be considered the background process; thus, it won't trigger the renewal event.
		
		// Set_stupidly_fast() disables this internally so turn it on manually because it will trigger the custom sort method
		$oFeed->enable_order_by_date( true );	
		$oFeed->init();			
		return $oFeed;
		
	}	
	function SetupTransients() {
		
		$this->InitializeFeeds();
		$this->GetTopBanner();
		$this->GetSkyscraper();
		$this->GetBottomBanner();
		$this->GetTextAd();
		
	}
}