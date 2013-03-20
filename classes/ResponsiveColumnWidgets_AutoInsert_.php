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
	protected $numPostID;	// since 1.0.7 - stores the current post ID.
		
	protected $bIsPost;		// since 1.0.7 - used to auto insert widgets into posts
	protected $bIsPage;		// since 1.0.7 - used to auto insert widgets into pages
	protected $bIsFront;	// since 1.0.7 - used to auto insert widgets into posts / pages
	
	// Container arrays - since 1.0.8
	protected $arrAutoInsertIntoPosts = array();		// Stores the sidebar(widget box) IDs which will be inserted into post contents.
	protected $arrAutoInsertIntoPages = array();		// Stores the sidebar(widget box) IDs which will be inserted into page contents.
	protected $arrAutoInsertIntoFooter = array();		// Stores the sidebar(widget box) IDs which will be inserted into the footer.
	protected $arrAutoInsertIntoComments = array();		// Stores the sidebar(widget box) IDs which will be inserted into comments.
	protected $arrAutoInsertIntoCommentFormAbove = array();	// Stores the sidebar(widget box) IDs which will be inserted into the above comment form.
	protected $arrAutoInsertIntoCommentFormBelow = array();	// Stores the sidebar(widget box) IDs which will be inserted into the below comment form.

	function __construct( &$oOption, &$oCore ) {
		
		
		// Objects
		$this->oOption = $oOption;
		$this->oCore = $oCore;
		
		// Auto Insertions
		// The init hook is too early to perform the functions including is_single(), is_page() etc.
		add_action( 'wp_head', array( $this, 'SetUpAutoInsertions' ) );

	}
	/*
	 *  Auto Insertions
	 */
	public function SetUpAutoInsertions() {

		// since 1.0.7, renamed to SetUpAutoInsertions from SetUpPostInfo in 1.0.8
		// These funcitons must be called after the query, $wp_query, has been set up.
		$this->bIsPost = is_single();
		$this->bIsPage = is_page();
		$this->bIsFront = is_home() || is_front_page() ? True : False;		
		$this->numPostID = $this->GetPostID();
		
		// Set up the container arrays
		foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => &$arrBoxOptions ) {
			
			if ( isset( $arrBoxOptions['insert_footer'] ) && $arrBoxOptions['insert_footer'] )
				$this->arrAutoInsertIntoFooter[] = $strSidebarID;
			
			if ( isset( $arrBoxOptions['insert_posts']['post'] ) && $arrBoxOptions['insert_posts']['post'] )			
				$this->arrAutoInsertIntoPosts[] = $strSidebarID;

			if ( isset( $arrBoxOptions['insert_posts']['page'] ) && $arrBoxOptions['insert_posts']['page'] )			
				$this->arrAutoInsertIntoPages[] = $strSidebarID;

			if ( isset( $arrBoxOptions['insert_comments'] ) && $arrBoxOptions['insert_comment'] )			
				$this->arrAutoInsertIntoComments[] = $strSidebarID;

			if ( isset( $arrBoxOptions['insert_comment_form_positions'] ) ) {
				
				if ( $arrBoxOptions['insert_comment_form_positions']['above'] ) 
					$this->arrAutoInsertIntoCommentFormAbove[] = $strSidebarID;
				
				if ( $arrBoxOptions['insert_comment_form_positions']['below'] ) 
					$this->arrAutoInsertIntoCommentFormBelow[] = $strSidebarID;
			
			}
		}
		if ( count( $this->arrAutoInsertIntoFooter ) > 0 )
			add_action( 'wp_footer', array( $this, 'AddWidgetboxInFooter' ) );
			
		if ( count( $this->arrAutoInsertIntoPosts ) > 0 || count( $this->arrAutoInsertIntoPages ) > 0  )
			add_action( 'the_content', array( $this, 'AddWidgetboxInPostContent' ) );
		
		if ( count( $this->arrAutoInsertIntoComments ) > 0 )
			add_action( 'comment_text', array( $this, 'AddWidgetBoxInComment' ), 10, 2 );	// apply_filters( 'comment_text', get_comment_text( $comment_ID ), $comment );
		
		if ( count( $this->arrAutoInsertIntoCommentFormAbove ) > 0 ) 
			add_action( 'comment_form_before', array( $this, 'AddWidgetBoxInCommentFormAbove' ) );	// do_action( 'comment_form_before' );
			
		if ( count( $this->arrAutoInsertIntoCommentFormBelow ) > 0 ) 
			add_action( 'comment_form_after', array( $this, 'AddWidgetBoxInCommentFormBelow' ) );	// do_action( 'comment_form_after' ); 
		
	}
	protected function GetPostID() {	// since 1.0.7
		
		global $wp_query;
		if ( is_object( $wp_query->post ) ) return $wp_query->post->ID;	
		
	}
	public function AddWidgetBoxInComment( $strCommentText, $oComment ) {
		
		// since 1.0.8
	// $GLOBALS['comment']->comment_ID	
		return $strCommentText;
		
	}
	public function AddWidgetBoxInCommentFormAbove() {
			
		// since 1.0.8 - callback for the action hooks,  comment_form_before and comment_form_after
		foreach ( $this->arrAutoInsertIntoCommentFormAbove as $strSidebarID ) {
						
			$arrBoxOptions = $this->oOption->arrOptions['boxes'][ $strSidebarID ];
						
			// If the disable option for the front page is enabled, skip.
			if ( $this->bIsFront && $arrBoxOptions['insert_comment_form_disable_front'] ) continue;

			// If the disable option for the post id matches the current post ID, skip.
			if ( $this->IsPostIn( $this->numPostID, $arrBoxOptions['insert_comment_form_disable_post_ids'] ) ) continue;
			
			$this->RenderWidgetBox( array( 'sidebar' => $strSidebarID ) );
					
		}		
	}
	public function AddWidgetBoxInCommentFormBelow() {

		// since 1.0.8 - callback for the action hooks,  comment_form_before and comment_form_after
		foreach ( $this->arrAutoInsertIntoCommentFormBelow as $strSidebarID ) {
						
			$arrBoxOptions = $this->oOption->arrOptions['boxes'][ $strSidebarID ];
						
			// If the disable option for the front page is enabled, skip.
			if ( $this->bIsFront && $arrBoxOptions['insert_comment_form_disable_front'] ) continue;

			// If the disable option for the post id matches the current post ID, skip.
			if ( $this->IsPostIn( $this->numPostID, $arrBoxOptions['insert_comment_form_disable_post_ids'] ) ) continue;
			
			$this->RenderWidgetBox( array( 'sidebar' => $strSidebarID ) );
					
		}			
	}
	public function AddWidgetboxInFooter() {
		
		// since 1.0.5, in 1.0.8, moved from the core object
		foreach ( $this->arrAutoInsertIntoFooter as $strSidebarID ) {
						
			$arrBoxOptions = $this->oOption->arrOptions['boxes'][ $strSidebarID ];
						
			// If the disable option for the front page is enabled, skip.
			if ( $this->bIsFront && $arrBoxOptions['insert_footer_disable_front'] ) continue;

			// If the disable option for the post id matches the current post ID, skip.
			if ( $this->IsPostIn( $this->numPostID, $arrBoxOptions['insert_footer_disable_ids'] ) ) continue;
			
			$this->RenderWidgetBox( array( 'sidebar' => $strSidebarID ) );
					
		}
	}
	public function AddWidgetboxInPostContent( $strContent ) {
				
		// since 1.0.7, in 1.0.8, moved from the core object
		$arr = $this->bIsPost ? $this->arrAutoInsertIntoPosts : ( $this->bIsPage ? $this->arrAutoInsertIntoPages : array() );
		foreach ( $arr as $strSidebarID ) {
			
			$arrBoxOptions = $this->oOption->arrOptions['boxes'][ $strSidebarID ];
		
			// If the disable option for the front page is enabled, skip.
			if ( $this->bIsFront && $arrBoxOptions['insert_posts_disable_front'] ) continue;

			// If the disable option for the post id matches the current post ID, skip.
			if ( $this->IsPostIn( $this->numPostID, $arrBoxOptions['insert_posts_disable_ids'] ) ) continue;
					
			$strContent = $arrBoxOptions['insert_posts_positions']['above'] ? $this->GetWidgetBoxOutput( array( 'sidebar' => $strSidebarID ) ) . $strContent : $strContent;
			$strContent = $arrBoxOptions['insert_posts_positions']['below'] ? $strContent . $this->GetWidgetBoxOutput( array( 'sidebar' => $strSidebarID ) ) : $strContent;
		
		} 	
		return $strContent;
		
	}
	protected function IsPostIn( $numPostID, &$arrPostIDs ) {
	
		// since 1.0.7, in 1.0.8, moved from the core object
		if ( is_string( $arrPostIDs ) )
			$arrPostIDs = $this->oOption->ConvertStringToArray( $arrPostIDs );
		
		if ( ! is_array( $arrPostIDs ) ) return null;

		if ( in_array( $numPostID, $arrPostIDs ) ) return True;
	
	}	
	
	protected function RenderWidgetBox( $arrParams ) {	// since 1.0.8

		$this->oCore->RenderWidgetBox( $arrParams );
		
	}
	protected function GetWidgetBoxOutput( $arrParams ) {	// since 1.0.8
		
		return $this->oCore->GetWidgetBoxOutput( $arrParams );
		
	}
}
