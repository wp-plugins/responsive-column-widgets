<?php
/*
	Plugin Name: Responsive Column Widgets
	Plugin URI: http://michaeluno.jp/en/responsive-column-widgets
	Description: Creates a widget box which displays widgets in columns.
	Version: 1.0.0
	Author: Michael Uno
	Author URI: http://michaeluno.jp
	Requirements: This plugin requires WordPress >= 3.0 and PHP >= 5.1.2
*/

$strCSSDirURL =  plugins_url( '' ,  __FILE__ )  . '/css/';
$oResponsiveColumnWidgets = new ResponsiveColumnWidgets_AddStyleToHeaderByShortCode( 'responsive_column_widgets', $strCSSDirURL );

function ResponsiveColumnWidgets($arrParams) {

	
	global $oResponsiveColumnWidgets;
	$arrParams = $arrParams + array(
			'columns' => 1,
			'sidebar' => 'responsive_column_widgets',
	);

	// render the widget box
	$oResponsiveColumnWidgets->RenderWidgetBox($arrParams);
}

class ResponsiveColumnWidgets_AddStyleToHeaderByShortCode {
	
	private $strShortCode;
	private $strCSSDirURL;
	
	function __construct($strShortCode, $strCSSDirURL) {
		
		// properties
		$this->strShortCode = $strShortCode;
		$this->strCSSDirURL = $strCSSDirURL;

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
	
	function RenderWidgetBoxWithShortCode($arrAttributes) { // the parameter is automatically passed
		// this is a shortcode call back 
		extract( shortcode_atts( array(
								'columns' => 1,
								'sidebar' => 'responsive_column_widgets',
								), $arrAttributes 
								) 
				);
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered, return
		return '<div class="responsive_column_widget_area responsive_column_widgets_box">' . $this->RenderWidgets( $sidebar, $columns ) . '</div>';
	}
	
	public function RenderWidgetBox($arrParams) {
		// this method is called by a function.
		
		extract($arrParams);
		if ( ! is_active_sidebar( $sidebar ) ) return;	// if nothing is registered, return
		echo '<div class="responsive_column_widget_area responsive_column_widgets_box">' . $this->RenderWidgets( $sidebar, $columns ) . '</div>';
	}
	
	// render widgets
	function RenderWidgets( $index = 1, $numcolumns ) {

		global $wp_registered_sidebars, $wp_registered_widgets;

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
		if ( empty( $sidebars_widgets ) )
			return false;

		if ( empty($wp_registered_sidebars[$index]) || !array_key_exists($index, $sidebars_widgets) || !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index]) )
			return false;

		$sidebar = $wp_registered_sidebars[$index];

		$strBuffer = '';
		$numCount = 0;
		foreach ( (array) $sidebars_widgets[$index] as $id ) {
						
			if ( !isset($wp_registered_widgets[$id]) ) continue;

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
			if ( is_callable($callback) ) {			
				call_user_func_array($callback, $params);		// will echo widgets
				$strNewRowClass = ( $numCount == 0 || ( $numCount % $numcolumns ) == 0 ) ? 'responsive_column_widgets_newrow' : '';			
				if ( $numCount == 0 || ( $numCount % $numcolumns ) == 0 ) {
					$bTagClosed = false;
					$strBuffer .= '<div class="responsive_column_widgets_row"><div class="col element_of_' . $numcolumns . ' ' . $strNewRowClass . '">' . ob_get_contents() . '</div>';
				}
				else
					$strBuffer .= '<div class="col element_of_' . $numcolumns . '">' . ob_get_contents() . '</div>';
				
				// close the section(row) div tag
				if ( ( ( 1 + $numCount ) % $numcolumns ) == 0 ) {
					$bTagClosed = True;
					$strBuffer .= '</div>';
				} 

				$numCount++;
			}
			ob_end_clean();
		}
		// close the section(row) div tag in case it is ended prior to closing it
		if ( empty( $bTagClosed ) ) $strBuffer .= '</div>';
		
		return $strBuffer;
	}
	
}