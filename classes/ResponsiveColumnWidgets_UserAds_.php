<?php
/**
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.4
 * @description	Creates links for the user.
*/
class ResponsiveColumnWidgets_UserAds_ {

	// properties
	protected $oTextFeed;
	protected $oSkyscraperFeed;
	protected $oTopBannerFeed;
	protected $strURLFeed160x600 = 'http://feeds.feedburner.com/GANLinkBanner160x600Random40';
	protected $strURLFeedText = 'http://feeds.feedburner.com/GANLinkTextRandom40';
	protected $strURLFeed60x468 = 'http://feeds.feedburner.com/GANBanner60x468';
	protected $strURLFeed728x90 = 'http://feeds.feedburner.com/CustomBanner728x90';
	
	function __construct( &$oOption=null ) {
		
		global $oResponsiveColumnWidgets_Options;
		$this->oOption = isset( $oOption ) ? $oOption : $oResponsiveColumnWidgets_Options;
	
	}
	function SetOptionObj( &$oOption ) {
		
		$this->oOption = $oOption;
		
	}
	function GetTextAd( $numItems=1 ) {

		$this->oTextFeed = $this->GetFeedObj( $this->strURLFeedText );

		$strOut = '';
		foreach ( $this->oTextFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= $item->get_content();
		$strOut = '<div align="left" style="">' . $strOut . "</div>"; 
		return $strOut;
			
	}		
	function GetTopBanner( $numItems=1 ) {
		
		$this->oTopBannerFeed = $this->GetFeedObj( $this->strURLFeed60x468 );
			
		$strOut = '';
		foreach ( $this->oTopBannerFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= '<div style="clear:right; margin:0; padding:0;">' . $item->get_content() . '</div>';
	
		return '<div style="float:right; margin:0; padding:0;">' . $strOut . "</div>";
		
	}	
	function GetSkyscraper( $numItems=2 ) {
		
		$this->oSkyscraperFeed = $this->GetFeedObj( $this->strURLFeed160x600 );
		
		$strOut = '';
		foreach ( $this->oSkyscraperFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= '<div style="clear:right;">' . $item->get_content() . '</div>';

		return '<div style="float:right; padding: 0px 0 0 20px;">' . $strOut . "</div>";
		
	}	
	function GetBottomBanner( $numItems=1 ) {
		
		$this->oBottomBannerFeed = $this->GetFeedObj( $this->strURLFeed728x90 );
		
		$strOut = '';
		foreach ( $this->oBottomBannerFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= '<div style="clear:both;">' . $item->get_content() . '</div>';

		return '<div style="float:both; margin-top: 20px;">' . $strOut . "</div>";
		
	}
	function InitializeTextFeed( $arrUrls='' ) {
	
		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeedText : $arrUrls;
		$this->oTextFeed = $this->GetFeedObj( $arrUrls, 0 );
		
	}	
	function InitializeTopBannerFeed( $arrUrls='' ) {

		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeed60x468 : $arrUrls;
		$this->oTopBannerFeed = $this->GetFeedObj( $arrUrls, 0 );

	}	
	function InitializeBannerFeed( $arrUrls='' ) {
		
		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeed160x600 : $arrUrls;
		$this->oSkyscraperFeed = $this->GetFeedObj( $arrUrls, 0 );
		
	}		
	function GetFeedObj( $arrUrls, $numCacheDuration=3600 ) {	// 60 seconds * 60 = 1 hour
		
		$oFeed = new ResponsiveColumnWidgets_SimplePie();
		
		// Set Sort Order
		$oFeed->set_sortorder( 'random' );

		// Set urls
		$oFeed->set_feed_url( $arrUrls );	
		$oFeed->set_item_limit( 1 );
		
		// this should be set after defineing $urls
		$oFeed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', $numCacheDuration, $arrUrls ) );	
		$oFeed->set_stupidly_fast( true );
		
		// If the cache lifetime is explicitly set to 0, do not trigger the background renewal cache event
		if ( $numCacheDuration == 0 )
			$oFeed->SetBackground( true );	// setting it true will be considerd the background process; thus, it won't trigger the renewal event.
		
		// set_stupidly_fast() disables this internally so turn it on manually because it will trigger the custom sort method
		$oFeed->enable_order_by_date( true );	
		$oFeed->init();			
		return $oFeed;
		
	}	
	function SetupTransients() {
	
		$this->InitializeTopBannerFeed();
		$this->InitializeBannerFeed();
		$this->InitializeTextFeed();
		
	}
}