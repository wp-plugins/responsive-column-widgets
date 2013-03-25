<?php
/**
	Extends the SimplePie library. 
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.4
*/

/*
 * Custom Hooks
 * - RCWP_action_simplepie_renew_cache : the event action that renew caches in the background.
 * 		
 * */

// make sure that SimplePie has been already loaded
// very importat. Without this line, the cache setting breaks. 
// Do not include class-simplepie.php, which causes the unknown class warning.
if ( ! class_exists( 'SimplePie' ) ) 
	require_once( ABSPATH . WPINC . '/class-feed.php' );		

// If the WordPress version is below 3.5, which uses SimplePie below 1.3.
if ( version_compare( get_bloginfo('version') , '3.5', "<" ) ) {	

	class ResponsiveColumnWidgets_SimplePie__ extends SimplePie {
		
		public static $sortorder = 'random';
		public function sort_items( $a, $b ) {

			// Sort 
			// by date
			if ( self::$sortorder == 'date' ) 
				return $a->get_date('U') <= $b->get_date('U');		
			// by title ascending
			if ( self::$sortorder == 'title' ) 
				return self::sort_items_by_title( $a, $b );
			// by title decending
			if ( self::$sortorder == 'title_descending' ) 
				return self::sort_items_by_title_descending( $a, $b );
			// by random 
			return rand( -1, 1 );	
			
		}		
	}
	
} else {
	
	class ResponsiveColumnWidgets_SimplePie__ extends SimplePie {
		
		public static $sortorder = 'random';
		public static function sort_items( $a, $b ) {

			// Sort 
			// by date
			if ( self::$sortorder == 'date' ) 
				return $a->get_date('U') <= $b->get_date('U');		
			// by title ascending
			if ( self::$sortorder == 'title' ) 
				return self::sort_items_by_title( $a, $b );
			// by title decending
			if ( self::$sortorder == 'title_descending' ) 
				return self::sort_items_by_title_descending( $a, $b );
			// by random 
			return rand( -1, 1 );	
			
		}		
	}	

}
	
class ResponsiveColumnWidgets_SimplePie_ extends ResponsiveColumnWidgets_SimplePie__ {
	
	public $classver = 'standard';
	public static $sortorder = 'random';
	public static $bKeepRawTitle = false;
	public static $strCharEncoding = 'UTF-8';
	var $vSetURL;	// stores the feed url(s) set by the user.
	var $bIsBackground = false;		// indicates whether it is from the event action ( background call )
	var $numCacheLifetimeExpand = 100;
	var $strRealCacheModTimePrefix = 'RCWFeed_M__';	// the double underscores are used.
	/*
	 * For backgound cache renewal task.
	 * */
	public function set_feed_url( $url ) {
		
		$this->vSetURL = $url;	// array or string
		parent::set_feed_url( $url );
		
	}
	public function init() {

		// Setup Caches
		$this->enable_cache( True );
		
		// force the cache class to the custom plugin cache class
		$this->set_cache_class( 'ResponsiveColumnWidgets_Cache' );
		$this->set_file_class( 'WP_SimplePie_File' );
			
		// fore the life time to be expanded so that the cache barely refreshes by itself.
		$this->cache_duration = $this->cache_duration * $this->numCacheLifetimeExpand;
		
		if ( isset( $this->vSetURL ) && ! $this->bIsBackground ) {
			
			$bHasExpired = false;
// $arrDump = array();
			foreach ( ( array) $this->vSetURL as $strURL ) {
// $arrDump[] = get_transient( 'RCWFeed_M_' . '_' . md5( $strURL ) );
				// 'RCWFeed_M__' stores the saved time that the cache was created with the real expiration life time.
				// 'RCWFeed_M_' also stores the same value but it won't barely expires. Threfore, check the former to see if the cache should be renewed or not.
				// be careful with the use of md5() since the user may set a different type for it. ( IIRC, SimplePie supporeted changing the hash type.)
				$numMod = ( int ) get_transient( $this->strRealCacheModTimePrefix . md5( $strURL ) );
				if ( $numMod + ( $this->cache_duration / $this->numCacheLifetimeExpand ) < time() ) {
					$bHasExpired = true;
					break;
				}
			}
					
			// If the current time exceeds the saved time + life time, it means expired.
			// The event action must be loaded (added) when the plugin is loaded. We use a separate event object for that.
			// wp_schedule_single_event() requires the argument to be enclosed in an array.
			if ( $bHasExpired ) {
// file_put_contents( dirname( __FILE__ ) . '/info_expired.txt' , 
	// __FILE__ . PHP_EOL 
	// . __METHOD__ . PHP_EOL
	// . print_r( 'The cache is expired! Scheduleing the background renewal event.', true ) . PHP_EOL
	// . 'Set Cache Duration: ' . print_r( $this->cache_duration, true ) . PHP_EOL
	// . 'Saved Modified Times: ' . print_r( $arrDump, true ) . PHP_EOL
	// . 'URLs: ' . print_r( $this->vSetURL, true ) . PHP_EOL
	// . 'Current Time: ' . time() . PHP_EOL
	// . PHP_EOL . PHP_EOL
	// , FILE_APPEND 
// );	
				// let the scheduling task at the end of the script.
				add_action( 'shutdown', array( $this, 'ScheduleCacheRenewal' ) );
				
			}
						
		}	

		return parent::init();
		
	}
	public function ScheduleCacheRenewal() {
		// Giving a random delay prevents multiple tasks from running at the same time and causing the page load slow down.
		// WP Cron runs in the background; however, if the registered tasks takes the server resources too much such as CPU usage, the loading page takes some time to complete.
		// + rand( 5, 20 )
		if ( wp_next_scheduled( 'RCWP_action_simplepie_renew_cache', array( $this->vSetURL ) ) ) 
			return;
		
		// Delete the transient so that the event method can check whether it really needs to be renewed or not.
		foreach( ( array ) $this->vSetURL as $strURL ) 
			delete_transient( $this->strRealCacheModTimePrefix . md5( $strURL ) );
		
		wp_schedule_single_event( time() + 5, 'RCWP_action_simplepie_renew_cache', array( $this->vSetURL ) );
	}
	public function SetBackground( $bIsBackground=false ) {
		
		$this->bIsBackground = $bIsBackground;
		
	}

	/*
	 * For sort
	 * */
	public function set_sortorder( $sortorder ) {
		self::$sortorder = $sortorder;
	}
	public function set_keeprawtitle( $bKeepRawTitle ) {
		self::$bKeepRawTitle = $bKeepRawTitle;		
	}
	public function set_charset_for_sort( $strCharEncoding ) {
		self::$strCharEncoding = $strCharEncoding;		
	}

	public static function sort_items_by_title( $a, $b ) {
		$a_title = ( self::$bKeepRawTitle ) ? $a->get_title() : preg_replace('/#\d+?:\s?/i', '', $a->get_title());
		$b_title = ( self::$bKeepRawTitle ) ? $b->get_title() : preg_replace('/#\d+?:\s?/i', '', $b->get_title());
		$a_title = html_entity_decode( trim( strip_tags( $a_title ) ), ENT_COMPAT | ENT_HTML401, self::$strCharEncoding );
		$b_title = html_entity_decode( trim( strip_tags( $b_title ) ), ENT_COMPAT | ENT_HTML401, self::$strCharEncoding );
		return strnatcasecmp( $a_title, $b_title );	
	}
	public static function sort_items_by_title_descending( $a, $b ) {
		$a_title = ( self::$bKeepRawTitle ) ? $a->get_title() : preg_replace('/#\d+?:\s?/i', '', $a->get_title());
		$b_title = ( self::$bKeepRawTitle ) ? $b->get_title() : preg_replace('/#\d+?:\s?/i', '', $b->get_title());
		$a_title = html_entity_decode( trim( strip_tags( $a_title ) ), ENT_COMPAT | ENT_HTML402, self::$strCharEncoding );
		$b_title = html_entity_decode( trim( strip_tags( $b_title ) ), ENT_COMPAT | ENT_HTML402, self::$strCharEncoding );
		return strnatcasecmp( $b_title, $a_title );
	}
	

	function set_force_cache_class( $class = 'ResponsiveColumnWidgets_Cache' ) {
		$this->cache_class = $class;
	}
	function set_force_file_class( $class = 'SimplePie_File' ) {
		$this->file_class = $class;
	}	
}

class ResponsiveColumnWidgets_Cache extends SimplePie_Cache {
	/**
	 * Create a new SimplePie_Cache object
	 *
	 * @static
	 * @access public
	 */
	function create( $location, $filename, $extension ) {
		return new ResponsiveColumnWidgets_Feed_Cache_Transient( $location, $filename, $extension );
	}
}
class ResponsiveColumnWidgets_Feed_Cache_Transient {
	var $name;
	var $mod_name;
	var $lifetime = 43200; //Default lifetime in cache of 12 hours
	var $real_name;
	var $real_mod_name;
	var $numExpand = 100;
	var $strPrefixName = 'RCWFeed_';
	var $strPrefixModName = 'RCWFeed_M_';
	
	function __construct( $location, $filename, $extension ) {
		// $location : './cache'
		// $filename : md5( $url )	e.g. b22d9dad80577a8e66a230777d91cc6e // <-- the hash type may be changed by the user.
		// $extension: spc
		
		$this->name = $this->strPrefixName . $filename;
		$this->mod_name = $this->strPrefixModName . $filename;
		$this->real_mod_name = $this->strPrefixModName . '_' . $filename;	// save the real file modified date
		$this->lifetime = apply_filters( 
			'wp_feed_cache_transient_lifetime', 
			$this->lifetime, 	
			$filename
		);	
		
	}

	function save( $data ) {
		
		if ( is_a( $data, 'SimplePie' ) )
			$data = $data->data;

		set_transient( $this->real_mod_name, time(), $this->lifetime );
		
		// make it 100 times longer so that it barely gets expires by itself
		set_transient( $this->name, $data, $this->lifetime * $this->numExpand );	
		set_transient( $this->mod_name, time(), $this->lifetime * $this->numExpand );
		
		return true;
		
	}
	
	function load() {
		return get_transient( $this->name );
	}

	function mtime() {
		return get_transient( $this->mod_name );
	}

	function touch() {
		set_transient( $this->real_mod_name, time(), $this->lifetime );
		return set_transient( $this->mod_name, time(), $this->lifetime * $this->numExpand );
	}

	function unlink() {
		delete_transient( $this->real_mod_name );
		delete_transient( $this->name );
		delete_transient( $this->mod_name );
		return true;
	}
}
