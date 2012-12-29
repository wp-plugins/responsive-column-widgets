<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://michaeluno.jp/en/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns with a responsive design.
	Version: 1.0.1
	Author: miunosoft
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/


$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_AddStyleToHeaderByShortCode( 'responsive_column_widgets' );

function ResponsiveColumnWidgets($arrParams) {
	/*
	 * For general plugin users.
	 * */
	
	global $oResponsiveColumnWidgets;

	// render the widget box
	$oResponsiveColumnWidgets->RenderWidgetBox($arrParams);
}

class ResponsiveColumnWidgets_AddStyleToHeaderByShortCode {
	
	private $strShortCode;
	private $strCSSDirURL;
	private $arrDefaultParams = array(	'columns' => 1,
										'sidebar' => 'responsive_column_widgets',
										'maxwidgets' => 0,
										'maxrows' => 0,
										'omit' => '',
										'showonly' => '',
								);
	
	function __construct($strShortCode) {
		
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
				'before_widget' => '<aside id="%1$s" class="widget %2$s">',
				'after_widget' => '</aside>',
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
	
	function RenderWidgetBoxWithShortCode($arrParams) { // the parameter is automatically passed
		// this is a shortcode call back 
		$arrParams = shortcode_atts( $this->arrDefaultParams , $arrParams )	;
		extract($arrParams);
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered in the given name of sidebar, return
		
		return '<div class="responsive_column_widget_area responsive_column_widgets_box">' . $this->RenderWidgets( $sidebar, $arrParams ) . '</div>';
	}
	
	public function RenderWidgetBox($arrParams) {
		// this method is called by a function outside the class.
		$arrParams = $arrParams + $this->arrDefaultParams;
		extract($arrParams);
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered in the given name of sidebar, return
		
		echo '<div class="responsive_column_widget_area responsive_column_widgets_box">' . $this->RenderWidgets( $sidebar, $arrParams ) . '</div>';
	}
	
	// render widgets
	function RenderWidgets( $index = 1, $arrParams ) {

		global $wp_registered_sidebars, $wp_registered_widgets;

		// extract the parameters
		extract($arrParams);
		$arrColumns = preg_split( '/[, ]+/', $columns, -1, PREG_SPLIT_NO_EMPTY );
		$arrOmits = preg_split( '/[, ]+/', $omit, -1, PREG_SPLIT_NO_EMPTY );
		$arrShowOnlys = preg_split( '/[, ]+/', $showonly, -1, PREG_SPLIT_NO_EMPTY );
		
		if ( is_int($index) ) {
			$index = "sidebar-$index";
		} else {
			$index = sanitize_title($index);
			foreach ( (array) $wp_registered_sidebars as $key => $value ) {
				if ( sanitize_title($value['name']) == $index ) {
					$index = $key;
					break;
				}
			}
		}

		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( empty( $sidebars_widgets ) ) return false;
		if ( empty( $wp_registered_sidebars[$index]) || !array_key_exists( $index, $sidebars_widgets ) || !is_array( $sidebars_widgets[$index] ) || empty( $sidebars_widgets[$index] ) ) return false;

		$sidebar = $wp_registered_sidebars[$index];

		$strBuffer = '';		// stores the buffer output
		$numTotalCount = 1;		// stores the total number of rendered widgtes
		$numCountInRow = 0;		// this does not count the entire number of loaded widgets but the widgets loaded in a row
		$numRow = 0;			// stores the iterating row
		$numColumns = $arrColumns[$numRow];	// the default is 1
		$numWidgterOrder = 0;	// for the omit parameter		
		$bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;	// if showonly is set, render only the specified widget id.
		
		foreach ( (array) $sidebars_widgets[$index] as $id ) {
			
			if ( !isset($wp_registered_widgets[$id]) ) continue;
			
			if ( in_array( ++$numWidgterOrder, $arrOmits ) ) continue;			// if omit ids matche
			if ( $bShowOnly && !in_array( $numWidgterOrder, $arrShowOnlys ) ) continue;	// if show only orders match
			
			$params = array_merge(
				array( array_merge( $sidebar, array('widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name']) ) ),
				(array) $wp_registered_widgets[$id]['params']
			);

			// Substitute HTML id and class attributes into before_widget
			$classname_ = '';
			foreach ( (array) $wp_registered_widgets[$id]['classname'] as $cn ) {
				if ( is_string($cn) )
					$classname_ .= '_' . $cn;
				elseif ( is_object($cn) )
					$classname_ .= '_' . get_class($cn);
			}
			$classname_ = ltrim($classname_, '_');
			$params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname_);
			$params = apply_filters( 'dynamic_sidebar_params', $params );
			$callback = $wp_registered_widgets[$id]['callback'];
			do_action( 'dynamic_sidebar', $wp_registered_widgets[$id] );

			ob_start();
			if ( is_callable( $callback ) ) {			
				call_user_func_array( $callback,$params );		// will echo widgets
						
				// if the widget is the first item in a row
				$strNewRowClass = ( $numCountInRow == 0 || ( $numCountInRow % $numColumns ) == 0 ) ? 'responsive_column_widgets_newrow' : '';	
				if ( $numCountInRow == 0 || ( $numCountInRow % $numColumns ) == 0 ) {
					
					// check if the nubmer of rows has reached the limit
					if ( ( $maxrows != 0 && $numRow >= $maxrows ) ) { ob_end_clean(); break; }
					$numColumns	= ( isset( $arrColumns[$numRow] ) ) ? $arrColumns[$numRow] :  $numColumns;	// set the column number of this row			
					$bTagClosed = false;
					$strBuffer .= '<div class="responsive_column_widgets_row"><div class="col element_of_' . $numColumns . ' ' . $strNewRowClass . '">' . ob_get_contents() . '</div>';
					$numRow++;		// increment the row number
					$numCountInRow = 0;	// reset the count 
				} else
					$strBuffer .= '<div class="col element_of_' . $numColumns . '">' . ob_get_contents() . '</div>';

				// close the section(row) div tag
				if ( ( ( 1 + $numCountInRow ) % $numColumns ) == 0 ) { $bTagClosed = True; $strBuffer .= '</div>'; } 
					
				// check if the number of rendered widgtes reached the limit
				if ( (  $maxwidgets != 0 &&  $numTotalCount >= $maxwidgets ) ) { ob_end_clean(); break;	}				
				
				// the number of rendered widgets
				$numCountInRow++;	$numTotalCount++;
			}
			ob_end_clean();

		}
		// close the section(row) div tag in case it is ended prior to closing it
		if ( empty( $bTagClosed ) ) $strBuffer .= '</div>';
		
		return $strBuffer;
	}
	
}