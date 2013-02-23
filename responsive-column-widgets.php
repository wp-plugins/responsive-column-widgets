<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://en.michaeluno.jp/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns with a responsive design.
	Version: 1.0.2
	Author: miunosoft
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/


$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_AddStyleToHeaderByShortCode( 'responsive_column_widgets' );

function ResponsiveColumnWidgets( $arrParams ) {
	/*
	 * For general plugin users.
	 * */
	
	global $oResponsiveColumnWidgets;

	// render the widget box
	$oResponsiveColumnWidgets->RenderWidgetBox( $arrParams );
}

class ResponsiveColumnWidgets_AddStyleToHeaderByShortCode {
	
	private $strShortCode;
	private $strCSSDirURL;
	private $arrDefaultParams = array(	
		'columns' => 1,
		'sidebar' => 'responsive_column_widgets',
		'maxwidgets' => 0,
		'maxrows' => 0,
		'omit' => '',
		'showonly' => '',
	);
	private $strClassAttrBox ='responsive_column_widget_area responsive_column_widgets_box widget-area';
	
	function __construct( $strShortCode ) {
		
		// properties
		$this->strShortCode = $strShortCode;
		$this->strCSSDirURL = plugins_url( '' ,  __FILE__ )  . '/css/';

		// register this plugin sidebar; if already registered, it will do nothing
		$this->RegisterSidebar();
		
		// add the stylesheet
		add_action( 'wp_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );	// set the order number to 100 which is quite low to load it after others have loaded
		
		// add shortcode
		add_shortcode( $this->strShortCode, array( $this, 'RenderWidgetBoxWithShortCode' ) );
		
		// parse the $post object to check shortcode in the the_posts function.
		// add_action( 'the_posts', array( $this, 'ParsePostObject' ) );
	}
	
	function RegisterSidebar() {
		
		global $wp_registered_sidebars;
	
		if ( array_key_exists( 'Responsive_Column_Widgets', $wp_registered_sidebars ) ) return;

		if ( function_exists( 'register_sidebar' ) ) {
			register_sidebar(array(
				'name' => 'Responsive Column Widgets',
				'id' => 'responsive_column_widgets',		// must be all lowercase
				'description' => 'Displays widgets in responsive columns',
				'before_widget' => '<aside id="%1$s" class="%2$s"><div class="widget">',
				'after_widget' => '</div></aside>',
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			));	
		}		
	}
	
	// Function to hook to "the_posts" (just edit the two variables)
	function ParsePostObject( $posts ) {		// $posts is passed automatically

		if ( empty( $posts ) ) return $posts;
		$bFound = false;

		foreach ( $posts as $post ) {
		
			if ( stripos( $post->post_content, '[' . $this->strShortCode ) !== false ) {
						
				add_shortcode( $this->strShortCode, array( $this, 'RenderWidgetBoxWithShortCode' ) );
				$bFound = true;
				break;
			}
		}

		if ( $bFound ) // $this->AddStyleSheetInHeader(); //add_action('wp_head', array( $this, 'metashortcode_setmeta' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );	// set the order number to 100 which is quite low to load it after others have loaded
		
		// always return $posts; otherwise, "the page not found" will be displayed
		return $posts;		
	}
	
	public function AddStyleSheetInHeader() {
		wp_enqueue_style( 'responsive_column_widgets_enqueue_style',  $this->strCSSDirURL . '/responsive_column_widgets.css');
	}
	
	function RenderWidgetBoxWithShortCode( $arrParams ) { // the parameter is automatically passed
		// this is a shortcode call back 
		$arrParams = shortcode_atts( $this->arrDefaultParams , $arrParams )	;
		extract( $arrParams );
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered in the given name of sidebar, return
		
		return '<div class="' . $this->strClassAttrBox . '">' . $this->RenderWidgets( $sidebar, $arrParams ) . '</div>';
	}
	
	public function RenderWidgetBox($arrParams) {
		// this method is called by a function outside the class.
		$arrParams = $arrParams + $this->arrDefaultParams;
		extract( $arrParams );
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered in the given name of sidebar, return
		
		echo '<div class="' . $this->strClassAttrBox . '">' . $this->RenderWidgets( $sidebar, $arrParams ) . '</div>';
	}
	
	// render widgets
	function GetIndex( $vIndex ) {
		global $wp_registered_sidebars;
		if ( is_int( $vIndex ) ) return "sidebar-$vIndex";

		$vIndex = sanitize_title( $vIndex );
		foreach ( ( array ) $wp_registered_sidebars as $strKey => $arrValue ) {
			if ( sanitize_title( $arrValue['name'] ) == $vIndex ) 
				return $strKey;
		}
		return $vIndex;
	}
	function IsRenderable( $index, $sidebars_widgets ) {
		global $wp_registered_sidebars;
		if ( empty( $sidebars_widgets ) ) return false;
		if ( empty( $wp_registered_sidebars[$index]) ) return false;
		if ( !array_key_exists( $index, $sidebars_widgets ) ) return false;
		if ( !is_array( $sidebars_widgets[$index] ) ) return false;
		if ( empty( $sidebars_widgets[$index] ) ) return false;
		return true;
	}
	function RenderWidgets( $index = 1, $arrParams ) {

		global $wp_registered_sidebars, $wp_registered_widgets;

		// extract the parameters
		extract( $arrParams );
		$arrMaxCols = preg_split( '/[, ]+/', $columns, -1, PREG_SPLIT_NO_EMPTY );
		$arrOmits = preg_split( '/[, ]+/', $omit, -1, PREG_SPLIT_NO_EMPTY );
		$arrShowOnlys = preg_split( '/[, ]+/', $showonly, -1, PREG_SPLIT_NO_EMPTY );
		
		$index = $this->GetIndex( $index );
		$sidebars_widgets = wp_get_sidebars_widgets();
		
		if ( ! $this->IsRenderable( $index, $sidebars_widgets ) ) return false;

		$sidebar = $wp_registered_sidebars[$index];
		
		$numWidgetOrder = 0;	// for the omit parameter		
		$bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;	// if showonly is set, render only the specified widget id.
		
		$arrWidgetBuffer = array();
		foreach ( ( array ) $sidebars_widgets[$index] as $id ) {
			
			if ( !isset( $wp_registered_widgets[$id] ) ) continue;
			
			if ( in_array( ++$numWidgetOrder, $arrOmits ) ) continue;			// if omit ids matche
			if ( $bShowOnly && !in_array( $numWidgetOrder, $arrShowOnlys ) ) continue;	// if show only orders match
			
			$params = array_merge(
				array(	array_merge( 
							$sidebar, array('widget_id' => $id, 
							'widget_name' => $wp_registered_widgets[$id]['name'] ) 
						)
				),
				(array) $wp_registered_widgets[$id]['params']
			);

			// Substitute HTML id and class attributes into before_widget
			$classname_ = '';
			foreach ( ( array ) $wp_registered_widgets[$id]['classname'] as $cn ) {
				if ( is_string( $cn ) )
					$classname_ .= '_' . $cn;
				elseif ( is_object( $cn ) )
					$classname_ .= '_' . get_class( $cn );
			}
			$classname_ = ltrim($classname_, '_');
			$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $id, $classname_ );
			$params = apply_filters( 'dynamic_sidebar_params', $params );
			$callback = $wp_registered_widgets[$id]['callback'];
			do_action( 'dynamic_sidebar', $wp_registered_widgets[$id] );

			ob_start();
			if ( is_callable( $callback ) ) {			
				call_user_func_array( $callback, $params );		// will echo widgets	
				$arrWidgetBuffer[] = ob_get_contents();
			}
			ob_end_clean();
		}

		// Now $arrWidgetBuffer contains the necessary data for output.
		$strBuffer = '';		// stores the buffer output
		$numColPosInRow = 0;	// number of the widgets loaded in a row, zero base.
		$numRowPos = 0;			// stores the iterating row, zero base.
		$strNewColClassAttr = 'responsive_column_widgets_firstcol';
		$bIsRowTagClosed = False;	
		foreach ( $arrWidgetBuffer as $nIndex => $strItem ) {
			
			// check if the number of rendered widgtes reached the limit
			if ( (  $maxwidgets != 0 &&  $nIndex >= $maxwidgets ) ) break;

			// if the number of allowed rows reached the limit
			if ( ( $maxrows != 0 && $numRowPos >= $maxrows ) ) break;
			$numMaxCols	= ( isset( $arrMaxCols[$numRowPos] ) ) ? $arrMaxCols[$numRowPos] :  $numMaxCols;	// set the column number of this row		
			$strItem = ( $numColPosInRow == 0  ? '<div class="responsive_column_widgets_row">' : '' )
				. '<div class="col element_of_' . $numMaxCols . ' ' 
				. ( ( $numColPosInRow == 0 ) ? $strNewColClassAttr : '' ) 	// if it's in the first col.
				. ' ">' 
				. $strItem
				. '</div>';
			if ( $numColPosInRow == 0 ) $bIsRowTagClosed = False;

			// increment the position
			$numColPosInRow++;	
			
			// check if it the last item in a row
			if ( ( $numColPosInRow % $numMaxCols ) == 0 ) {
				$strItem .= '</div>';		// clse the tag
				$numRowPos++;				// increment the row position
				$numColPosInRow = 0;		// reset the column position
				$bIsRowTagClosed = True;	// set the closed flag.
			}
			
			// add the item 
			$strBuffer .= $strItem;			
		}
		
		// close the section(row) div tag in case it is ended prior to closing it
		if ( empty( $bIsRowTagClosed ) ) $strBuffer .= '</div>';
		
		return $strBuffer;
	}
	
}