<?php
/**
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.4
 * @description	Creates links for the user.
*/
class ResponsiveColumnWidgets_UserAds_
{
	/*
		Used Option Key: amazonautolinks_userads
	*/
	
	// properties
	private $oTextFeed;
	private $oSkyscraperFeed;
	private $oTopBannerFeed;
	protected $strURLFeed160x600 = 'http://feeds.feedburner.com/GANLinkBanner160x600Random40';
	protected $strURLFeedText = 'http://feeds.feedburner.com/GANLinkTextRandom40';
	protected $strURLFeed60x468 = 'http://feeds.feedburner.com/GANBanner60x468';
		
	function GetTextAd( $numItems=1 ) {

		if ( ! is_object( $this->oTextFeed ) ) {
			$this->oTextFeed = $this->GetFeedObj( $this->strURLFeedText );
		}
		$strOut = '';
		foreach ( $this->oTextFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= $item->get_content();
		$strOut = '<div align="left" style="">' . $strOut . "</div>"; 
		return $strOut;
			
	}		
	function GetTopBanner( $numItems=1 ) {
		
		if ( ! is_object( $this->oTopBannerFeed ) ) 
			$this->oTopBannerFeed = $this->GetFeedObj( $this->strURLFeed60x468 );
			
		$strOut = '';
		foreach ( $this->oTopBannerFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= '<div style="clear:right; margin:0; padding:0;">' . $item->get_content() . '</div>';
	
		return '<div style="float:right; margin:0; padding:0;">' . $strOut . "</div>";
	}	
	function GetSkyscraper( $numItems=2 ) {
		
		if ( ! is_object( $this->oSkyscraperFeed ) ) 
			$this->oSkyscraperFeed = $this->GetFeedObj( $this->strURLFeed160x600 );
		
		$strOut = '';
		foreach ( $this->oSkyscraperFeed->get_items( 0, $numItems ) as $item ) 
			$strOut .= '<div style="clear:right;">' . $item->get_content() . '</div>';

		return '<div style="float:right; padding: 0px 0 0 20px;">' . $strOut . "</div>";
	}	
	function InitializeTextFeed( $arrUrls='' ) {
	
		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeedText : $arrUrls;
		$this->oTextFeed = $this->GetFeedObj( $arrUrls, False );
		
	}	
	function InitializeTopBannerFeed( $arrUrls='' ) {

		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeed60x468 : $arrUrls;
		$this->oTopBannerFeed = $this->GetFeedObj( $arrUrls, False );

	}	
	function InitializeBannerFeed( $arrUrls='' ) {
		
		$arrUrls = ( empty( $arrUrls ) ) ? $this->strURLFeed160x600 : $arrUrls;
		$this->oSkyscraperFeed = $this->GetFeedObj( $arrUrls, False );
		
	}		
	function GetFeedObj( $arrUrls, $bEnableCache=True ) {
		
		$oFeed = new ResponsiveColumnWidgets_SimplePie();
		
		// Setup Caches
		$oFeed->enable_cache( $bEnableCache );
		$oFeed->set_cache_class( 'WP_Feed_Cache' );
		$oFeed->set_file_class( 'WP_SimplePie_File' );
		$oFeed->enable_order_by_date( true );			// Making sure that it works with the defult setting. This does not affect the sortorder set by the option, $option['sortorder']		

		// Set Sort Order
		$oFeed->set_sortorder( 'random' );

		// Set urls
		$oFeed->set_feed_url( $arrUrls );	
		$oFeed->set_item_limit( 1 );
		
		// this should be set after defineing $urls
		$oFeed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 3600, $arrUrls ) );	
		$oFeed->set_stupidly_fast( true );
		$oFeed->init();			
		return $oFeed;
		
	}	
	function SetupTransients() {
	
		$this->InitializeTopBannerFeed();
		$this->InitializeBannerFeed();
		$this->InitializeTextFeed();
		
	}
}