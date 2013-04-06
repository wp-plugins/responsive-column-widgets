<?php
/**
	Inserts widgets boxes into the pre-defined area of page contents. 
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.8, moved from ResponsiveColumnWidgets_Core_.
*/

class ResponsiveColumnWidgets_AutoInsert_ {
	
	// Objects
	protected $oOption;		// the option object
	protected $oCore;		// the core object
	
	// Dynamic Properties
	protected $intPostID;	// since 1.0.7 - stores the current post ID.	
	protected $arrCatIDs;	// since 1.0.9 - stores the category IDs assigned to the current post ID.
	protected $strPostType;	// since 1.0.9 - stores the current post type.
	
	// Container arrays
	protected $arrEnabledBoxIDs = array();	// since 1.0.9 - stores widget box IDs that enable auto-insert.
	protected $arrHookFilters = array(		// since 1.0.9 - stores all the registered filters.
		'the_content' => array(),
		'comment_text' => array(),
	);	
	protected $arrHookActions = array(		// since 1.0.9 - stores all the registered actions.
		'wp_footer' => array(),		
	);	
	protected $arrDisplayedPageTypes = array(	// since 1.0.9 - stores the flags indicating the displaying page type.
		'is_home' => false,
		'is_archive' => false,
		'is_404' => false,
		'is_search' => false,
	);
	// protected $arrDisplayedPostTypes = array(	// since 1.0.9 - stores the flags indicating the displaying post type.
		// 'post' => false,
		// 'page' => false,
	// );	
	
	function __construct( &$oOption, &$oCore ) {
		
		// Objects
		$this->oOption = $oOption;
		$this->oCore = $oCore;
		
		// Auto Insertions - The init hook is too early to perform the functions including is_single(), is_page() etc. as $wp_query is not established yet.
		add_action( 'wp_head', array( $this, 'SetUpAutoInsertions' ) );

	}
	
	/*
	 *  Auto Insertions
	 */
	function __Call( $strMethodName, $vArgs=null ) {	// since 1.0.9
		
		// Redirect the dynamic callbacks
		// callback_filter_
		$intLength = strlen( 'callback_filter_' );
		if ( substr( $strMethodName, 0, $intLength ) == 'callback_filter_' ) 
			return $this->DoFilter( substr( $strMethodName, $intLength ), $vArgs[0] );
			
		// callback_action_
		$intLength = strlen( 'callback_action_' );
		if ( substr( $strMethodName, 0, strlen( 'callback_action_' ) ) == 'callback_action_' ) 
			return $this->DoAction( substr( $strMethodName, $intLength ), $vArgs[0] );
		
		// Unknown
		return $vArgs[0];
		
	}
	public function DoFilter( $strFilter, $strContent ) {	// since 1.0.9
		
		if ( ! isset( $this->arrHookFilters[ $strFilter ]  ) ) return;

		$strPre = '';
		$strPost = '';
		foreach( $this->arrHookFilters[ $strFilter ] as $strSidebarID ) {
			
			// 'autoinsert_position'  0: above, 1: below, 2: both			
			$intPositionType = $this->oOption->arrOptions['boxes'][ $strSidebarID ]['autoinsert_position'];
			if ( $intPositionType == 0 || $intPositionType == 2 )
				$strPre .= $this->oCore->GetWidgetBoxOutput( array( 'sidebar' => $strSidebarID ) );
			if ( $intPositionType == 1 || $intPositionType == 2 )
				$strPost .= $this->oCore->GetWidgetBoxOutput( array( 'sidebar' => $strSidebarID ) );
			
		}
// echo $this->strPostType . '<br />';	
// echo '<pre>Current Cat ID: ' . print_r( $this->arrCatIDs, true ) . '</pre>';
// echo '<pre>Disabled Cat IDs: ' . print_r( $arrBoxOptions['autoinsert_disable_categories'], true ) . '</pre>';
		return $strPre . $strContent . $strPost;
		
	}
	public function DoAction( $strAction, $vArg ) {		// since 1.0.9
		
		if ( ! isset( $this->arrHookActions[ $strAction ]  ) ) return;

		foreach( $this->arrHookActions[ $strAction ] as $strSidebarID ) 
			$this->oCore->RenderWidgetBox( array( 'sidebar' => $strSidebarID ) );	
		
	}
	
	protected function SetupAutoInsertEnabledBoxes() {	// since 1.0.9
		
		foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => &$arrBoxOptions ) 
			if ( isset( $arrBoxOptions['autoinsert_enable'] ) && $arrBoxOptions['autoinsert_enable'] )
				$this->arrEnabledBoxIDs[] = $strSidebarID;
		
	}
	protected function SetupPageTypeProperties() {	// since 1.0.9
		
		// MUST BE CALLED AFTER $wp_query IS ESTABLISHED.
		
		$this->arrDisplayedPageTypes = array(
			'is_home' => ( is_home() || is_front_page() ),
			'is_archive' => is_archive(),
			'is_404' => is_404(),
			'is_search' => is_search(),			
		);
		// $this->arrDisplayedPostTypes = array(	// since 1.0.9 - stores the flags indicating the displaying post type.
			// 'post' => is_single(),
			// 'page' => is_page(),
		// );			

		$this->intPostID = $this->GetPostID();
		$this->arrCatIDs = wp_get_post_categories( $this->intPostID );
		$this->strPostType = get_post_type( $this->intPostID );
		
	}	
	protected function IsAutoInsertEnabledPage( &$arrBoxOptions ) {
		
		// 'autoinsert_enable_pagetypes'	=> array( 
			// 'is_home' => false,
			// 'is_archives' => false,
			// 'is_404' => false,
			// 'is_search' => false,		
		// ),
		// 'autoinsert_enable_posttypes'	=> array( 'post' => false, 'page' => false ),
		// 'autoinsert_enable_categories'	=> array(),	// the category ID, in most cases 1 is Uncategoriezed.
		// 'autoinsert_enable_post_ids'	=> array(),	

		/*
		 *  First, check whether or not the loading page matches the disabled criteria. If so, return false.
		 */
		
		// Disabled Page Types
		foreach ( ( $arrBoxOptions['autoinsert_disable_pagetypes'] ) as $strPageType => $bDisable ) 
			if ( $bDisable && $this->arrDisplayedPageTypes[ $strPageType ] ) return false;
	
		// Disabled Categories
		$arrDisabledCatIDs = array_keys( $arrBoxOptions['autoinsert_disable_categories'], true );
		foreach ( $this->arrCatIDs as $intCatID )
			if ( in_array( $intCatID, $arrDisabledCatIDs ) ) 
				return false;
				
		// Disabled Post IDs	
		if ( in_array( $this->intPostID, $arrBoxOptions['autoinsert_disable_post_ids'] ) ) return false;
		
		// Disabled Post Types.
		$arrDisalbedPostTypes = array_keys( $arrBoxOptions['autoinsert_disable_posttypes'], True );
		if ( in_array( $this->strPostType, $arrDisalbedPostTypes ) ) return false;
		
		/*
		 * Now, check if the user specifies the enable options and if the option is set ( at least one of the items are checked ),
		 * apply the condition and return true or false.
		 * */
		
		
		// Enabled Page Types
		$arrEnabledPageTypes = array_keys( $arrBoxOptions['autoinsert_enable_pagetypes'], true );		
		foreach ( $arrEnabledPageTypes as $strPageType ) 
			if ( $this->arrDisplayedPageTypes[ $strPageType ] ) return true;	// the current loading page is an enabled one.	
		if ( count( $arrEnabledPageTypes ) > 0 ) return false;	// if one of the items is checked, return false.			
			
		// Enabled Categories - this applies only to posts. ( not for pages and custom post types ) 
		if ( strtolower( $this->strPostType ) == strtolower( 'post' ) ) {
			
			$arrEnabledCatIDs = array_keys( $arrBoxOptions['autoinsert_enable_categories'], true );		
			foreach ( $this->arrCatIDs as $intCatID )
				if ( in_array( $intCatID, $arrEnabledCatIDs ) ) return true;				
			if ( count( $arrEnabledCatIDs ) > 0 ) return false;	// if one of the items is checked, return false.
			
		}	
			
		// Enabled Post IDs	
		if ( in_array( $this->intPostID, $arrBoxOptions['autoinsert_enable_post_ids'] ) ) return true;
		if ( count( $arrBoxOptions['autoinsert_enable_post_ids'] ) > 0 ) return false;
		
		// Enabled Post Types.
		$arrEnabledPostTypes = array_keys( $arrBoxOptions['autoinsert_enable_posttypes'], true );
		
// echo '<pre>' . print_r( $arrEnabledPostTypes, true ). '</pre>';		
// echo '<pre>' . print_r( $arrEnabledCatIDs, true ). '</pre>';		
// echo '<pre>' . print_r( $this->arrCatIDs, true ). '</pre>';
		
		if ( in_array( $this->strPostType, $arrEnabledPostTypes ) ) return true;
		if ( count( $arrEnabledPostTypes ) > 0 ) return false;
			
		return true;
		
	}
	protected function SetupHooks() {	// since 1.0.9
		
		// Set up the filter container array, and the action container array.
		foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => &$arrBoxOptions ) {
			
			if ( ! in_array( $strSidebarID, $this->arrEnabledBoxIDs ) ) continue;
			
			// If it's not an enabled page, skip.
			if ( ! $this->IsAutoInsertEnabledPage( $arrBoxOptions ) ) continue;
			
			// Add the filters into the container array.
			if ( isset( $arrBoxOptions['autoinsert_enable_areas']['the_content'] ) && $arrBoxOptions['autoinsert_enable_areas']['the_content'] )
				$this->arrHookFilters['the_content'][] = $strSidebarID;
			if ( isset( $arrBoxOptions['autoinsert_enable_areas']['comment_text'] ) && $arrBoxOptions['autoinsert_enable_areas']['comment_text'] )
				$this->arrHookFilters['comment_text'][] = $strSidebarID;	
			foreach( $arrBoxOptions['autoinsert_enable_filters'] as $strFilter ) 
				$this->arrHookFilters[ $strFilter ][] = $strSidebarID;
				
			// Add the actions into the container array.
			if ( isset( $arrBoxOptions['autoinsert_enable_areas']['wp_footer'] ) && $arrBoxOptions['autoinsert_enable_areas']['wp_footer'] )
				$this->arrHookActions['wp_footer'][] = $strSidebarID;
			foreach( $arrBoxOptions['autoinsert_enable_actions'] as $strAction ) 
				$this->arrHookActions[ $strAction ][] = $strSidebarID;
			
		}

		// Add hooks!
		foreach ( $this->arrHookFilters as $strKey => $arrSidebarIDs ) 
			if ( count( $arrSidebarIDs ) > 0 ) 
				$bAdded = add_action( $strKey, array( $this, "callback_filter_{$strKey}" ) );
				
		foreach ( $this->arrHookActions as $strKey => $arrSidebarIDs ) 
			if ( count( $arrSidebarIDs ) > 0 )
				add_action( $strKey, array( $this, "callback_action_{$strKey}" ) );
				
	}
	public function SetUpAutoInsertions() {		// since 1.0.7, renamed to SetUpAutoInsertions from SetUpPostInfo in 1.0.8, revised in 1.0.9, used by a hook so must be public.
				
		// First check if there are widget boxes that enable auto-insert.
		$this->SetupAutoInsertEnabledBoxes();
		if ( count( $this->arrEnabledBoxIDs ) < 1 ) return;	// if there is no boxes enabled, there is nothing to do.
		
		// Set up properties which stores what kind of page is displayed
		$this->SetupPageTypeProperties();

		// Extract all necessary hooks. Divide filters and actions.
		$this->SetupHooks();
		
	}
	protected function GetPostID() {	// since 1.0.7
		
		global $wp_query;
		if ( isset( $wp_query->post ) && is_object( $wp_query->post ) ) return $wp_query->post->ID;	
		
	}
	
	protected function IsPostIn( $intPostID, &$arrPostIDs ) {
	
		// since 1.0.7, in 1.0.8, moved from the core object
		if ( is_string( $arrPostIDs ) )
			$arrPostIDs = $this->oOption->ConvertStringToArray( $arrPostIDs );
		
		if ( ! is_array( $arrPostIDs ) ) return null;

		if ( in_array( $intPostID, $arrPostIDs ) ) return True;
	
	}	

}
