<?php
/**
	Displays widgtes in multiple columns
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 * 

	@filters
	RCW_filter_base_styles: applies to the base CSS rules of the plugin.
	
*/
class ResponsiveColumnWidgets_Core_ {
	
	// Default properties
	protected $strShortCode;
	protected $strCSSDirURL;
	protected $strPluginName = 'responsive-column-widgets';		// used to the name attribute of the script
	protected $arrDefaultParams = array();	// will be overriden by the option object's array in the constructor.
	protected $strColPercentages = array(
		1 =>	'100%',
		2 =>	'49.2%',
		3 =>	'32.2%',
		4 =>	'23.8%',
		5 =>	'18.72%',
		6 =>	'15.33%',
		7 =>	'12.91%',
		8 =>	'11.1%',
		9 =>	'9.68%',
		10 =>	'8.56%',
		11 =>	'7.63%',
		12 =>	'6.86%',
	);		
	// protected $strClassAttrBox ='responsive_column_widget_area responsive_column_widgets_box widget-area';
	protected $strClassAttrBox1 ='responsive_column_widgets_box';		// overriden by the option
	protected $strClassAttrBox2 ='widget-area';
	protected $strClassAttrSidebarID = 'responsive_column_widgets';
	protected $strClassAttrRow = 'responsive_column_widgets_row';		// overriden by the option
	protected $strClassAttrColumn = 'responsive_column_widgets_column';	// overriden by the option
	protected $strClassAttrMaxColsByPixel = '';
	
	
	// Flags
	protected $bIsFormInDynamicSidebarRendered = false;
	
	// Container arrays
	protected $arrFlagsCustomStyleAdded = array();		// since 1.0.8 stores the flags to indicate whether the custom style for the widget box has been added or not. 
	protected $arrFlagsStyleMaxColsByPixel = array();		// since 1.0.8 stores the flags to indicate whether the style rule for max cols by pixel has been added or not.
	
	function __construct( $strShortCode, &$oOption ) {
		
		// option
		$this->oOption = $oOption;
		
		// properties
		$this->arrDefaultParams = $oOption->arrDefaultParams + $oOption->arrDefaultSidebarArgs;
		
		$this->strShortCode = $strShortCode;
		$this->strCSSDirURL = RESPONSIVECOLUMNWIDGETSURL . '/css/';
		$this->strClassAttrMaxColsByPixel = get_class( $this );

		$this->strClassAttrBox1 = $this->oOption->arrOptions['general']['general_css_class_attributes']['box'];
		$this->strClassAttrRow = $this->oOption->arrOptions['general']['general_css_class_attributes']['row'];
		$this->strClassAttrColumn = $this->oOption->arrOptions['general']['general_css_class_attributes']['column'];

		// register this plugin sidebar; if already registered, it will do nothing
		$this->RegisterSidebar();
		
		// add the stylesheet
		// add_action( 'wp_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );	// set the order number to 100 which is quite low to load it after others have loaded
		// add_action( 'login_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );
		// add_action( 'admin_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );
		
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& ! $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) {	// 0 for the header
			
			add_action( 'wp_head', array( $this, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['login'] )
				add_action( 'login_head', array( $this, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['admin'] )			
				add_action( 'admin_head', array( $this, 'AddStyleSheet' ) );
		
		}
		
		
		// add shortcode
		add_shortcode( $this->strShortCode, array( $this, 'GetWidgetBoxOutput' ) );
		
		// parse the $post object to check shortcode in the the_posts function.
		// add_action( 'the_posts', array( $this, 'ParsePostObject' ) );
		
		// hook the dynamic sidebar output ( widget container )
		// add_filter( 'dynamic_sidebar_params', array( $this, 'CheckSidebarLoad' ) );
		// add_action( 'dynamic_sidebar', array( $this, 'AddFormInDynamicSidebar' ) );
			
		// Debug
		// if ( defined( 'WP_DEBUG' ) )
			// add_action( 'wp_footer', array( $this->oOption, 'EchoMemoryLimit' ) );
			// add_action( 'wp_footer', array( $this, 'EchoMemoryUsage' ) );
			
	}
	/*
	 * Style Sheet
	 * */
	public function AddStyleSheet() {
	
		echo $this->GetBaseStyles();
		
	}
	protected function GetBaseStyles( $bIsScoped=false ) {	// since 1.1.0
		
		$strScoped = $bIsScoped ? "scoped" : "";
		$strCSS = "
			.{$this->strClassAttrBox1} .widget {
				padding: 4px;
				line-height: 1.5em;
				width: auto;
				height: auto;
			}
			.{$this->strClassAttrColumn}_1 {
				margin-left: 0px;
				clear: left;
			}

			/*  SECTIONS  ============================================================================= */
			.{$this->strClassAttrRow} {
				clear: both;
				padding: 0px;
				margin: 0px;
			}
			/*  GROUPING  ============================================================================= */
			.{$this->strClassAttrBox1}:before,
			.{$this->strClassAttrBox1}:after {
				content:'';
				display:table;
			}
			.{$this->strClassAttrBox1}:after {
				clear:both;
			}
			.{$this->strClassAttrBox1} {
				float: none;
				width: 100%;		
				zoom:1; /* For IE 6/7 (trigger hasLayout) */
			}
			/*  GRID COLUMN SETUP   ==================================================================== */
			.{$this->strClassAttrColumn} {
				display: block;
				float:left;
				margin: 1% 0 1% 1.6%;
			}
			.{$this->strClassAttrColumn}:first-child { margin-left: 0; } /* all browsers except IE6 and lower */
			/*  REMOVE MARGINS AS ALL GO FULL WIDTH AT 600 PIXELS */
			@media only screen and (max-width: 600px) {
				.{$this->strClassAttrColumn} { 
					margin: 1% 0 1% 0%;
				}
			}

			/*  GRID OF TWO   ============================================================================= */
			.element_of_1 {
				width: 100%;
			}
			.element_of_2 {
				width: 49.2%;
			}
			.element_of_3 {
				width: 32.2%; 
			}
			.element_of_4 {
				width: 23.8%;
			}
			.element_of_5 {
				width: 18.72%;
			}
			.element_of_6 {
				width: 15.33%;
			}
			.element_of_7 {
				width: 12.91%;
			}
			.element_of_8 {
				width: 11.1%; 
			}
			.element_of_9 {
				width: 9.68%; 
			}
			.element_of_10 {
				width: 8.56%; 
			}
			.element_of_11 {
				width: 7.63%; 
			}
			.element_of_12 {
				width: 6.86%; 
			}
		";
			/*  GO FULL WIDTH AT LESS THAN 600 PIXELS 
			@media only screen and (max-width: 600px) {
				.element_of_2,
				.element_of_3,
				.element_of_4,
				.element_of_5,
				.element_of_6,
				.element_of_7,
				.element_of_8,
				.element_of_9,
				.element_of_10,
				.element_of_11,
				.element_of_12	
				{	width: 100%;  }
			}
			*/			
		
		return "<style type='text/css' name='{$this->oOption->oInfo->Name} {$this->oOption->oInfo->Version}' {$strScoped}>" 
			. apply_filters( 'RCW_filter_base_styles', $strCSS )
			. "</style>";
		
	}
	
	/*
	 * Registers saved sidebars
	 * */
	function RegisterSidebar() {
		
		global $wp_registered_sidebars;
	
		if ( array_key_exists( 'Responsive_Column_Widgets', $wp_registered_sidebars ) ) return;

		if ( ! function_exists( 'register_sidebar' ) ) return;
		
		foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => $arrBoxOptions ) 			
			register_sidebar( 
				array(
					'name' => $arrBoxOptions['label'],
					'id' => strtolower( $arrBoxOptions['sidebar'] ), // must be all lowercase
					'description' => $arrBoxOptions['description'],
					'before_widget' => $arrBoxOptions['before_widget'],
					'after_widget' => $arrBoxOptions['after_widget'],
					'before_title' => $arrBoxOptions['before_title'],
					'after_title' => $arrBoxOptions['after_title'],
				) 
			);		
			
	}
	
	/*
	 * Rendering form elements in dynamic sidebars in the Widgets setting page. - currently not used 
	 * */
	function CheckSidebarLoad( $arrSidebarParams ) {	// since 1.0.4	
		
		global $pagenow;
		
		if ( ! isset( $arrSidebarParams[0]['id'] ) ) return $arrSidebarParams;
		
		if (  $pagenow != 'widgets.php' ) return $arrSidebarParams;
		
		if ( $arrSidebarParams[0]['id'] != $this->arrDefaultParams['sidebar'] ) return $arrSidebarParams;
			
		if ( $this->bIsFormInDynamicSidebarRendered ) return $arrSidebarParams;
		
		echo '<div class="sidebar-description">';
		echo '<p class="description">' .  __( 'Example Shortcode', 'responsive-column-widgets' ) . ':<br />' 
			. '[ ' . $this->arrDefaultParams['sidebar'] . ' columns="4" ]' . '</p>';
		echo '<p class="description">' .  __( 'Example PHP Code', 'responsive-column-widgets' ) . ':<br />' 
			. '&lt;?php ResponsiveColumnWidgets( array( \'columns\' => 4 ) ); ?&gt;' . '</p>';
		echo '</div>';
		
		// echo '<p>' . print_r( $arrSidebarParams, true ) . '</p>';
		
		$this->bIsFormInDynamicSidebarRendered = True;
		
		return $arrSidebarParams;
		
	}
	function AddFormInDynamicSidebar( $arrSidebarArgs ) {
		
		// since 1.0.4 - currently not used
		
		if ( !isset( $arrSidebarArgs['callback'] ) || !is_string( $arrSidebarArgs['callback'] ) ) return;
		
		if ( $arrSidebarArgs['callback'] != 'wp_widget_control' ) return;
		
		// echo '<pre>' . print_r( $arrSidebarArgs, true ) . '</pre>';

	}
	
	/*
	 *  Checks whether the displaying post contains the shortcode for this plugin. 
	 *  It's not currently used.	
	 */
	function ParsePostObject( $posts ) {		// $posts is passed automatically

		if ( empty( $posts ) ) return $posts;
		$bFound = false;

		foreach ( $posts as &$post ) {
		
			if ( stripos( $post->post_content, '[' . $this->strShortCode ) !== false ) {
						
				add_shortcode( $this->strShortCode, array( $this, 'GetWidgetBoxOutput' ) );
				$bFound = true;
				break;
				
			}
		}

		if ( $bFound ) // $this->AddStyleSheetInHeader(); //add_action('wp_head', array( $this, 'metashortcode_setmeta' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );	// set the order number to 100 which is quite low to load it after others have loaded
		
		// always return $posts; otherwise, "the page not found" will be displayed
		return $posts;		
		
	}
	public function AddStyleSheetInHeader() {	// methods used by hooks must be public.
		
		wp_enqueue_style( 
			'responsive_column_widgets',  
			$this->strCSSDirURL 
			. 'responsive_column_widgets.css?'
			. 'rcw_version=' . $this->oOption->oInfo->Version 
			. '&type=' . $this->oOption->oInfo->Type 
		);
	
	}
		
	function GetWidgetBoxSidebarIDFromParams( $arrParams ) {	// since 1.0.4
		
		if ( isset( $arrParams['label'] ) && ! empty( $arrParams['label'] ) ) 
			foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => &$arrBoxOptions ) 
				if ( $arrBoxOptions['label'] == $arrParams['label'] ) return $strSidebarID;
			
		// if nothing could be found, returns the default box ID
		return $this->arrDefaultParams['sidebar'];
			
	}
	public function GetWidgetBoxOutput( $arrParams ) {	// since 1.0.4
		
		// The function callback for shortcode. Notice that the last part is returning the output.

		$arrParams['sidebar'] = ! empty( $arrParams['sidebar'] ) ? $arrParams['sidebar'] : $this->GetWidgetBoxSidebarIDFromParams( $arrParams );	// $arrParams['sidebar'] = ! empty( $arrParams['sidebar'] ) ? $arrParams['sidebar'] : $this->arrDefaultParams['sidebar'];		
		$arrDefaultParams = isset( $this->oOption->arrOptions['boxes'][$arrParams['sidebar']] ) ? $this->oOption->arrOptions['boxes'][$arrParams['sidebar']] + $this->arrDefaultParams : $this->arrDefaultParams;
		$arrParams = shortcode_atts( $arrDefaultParams, $arrParams );		
		extract( $arrParams );
		if ( ! is_active_sidebar( $sidebar ) ) {
			echo '<p>' . $arrParams['message_no_widget'] . '</p>';
			return;	// if nothing is registered in the given name of sidebar, return
		}
		
		// The direct parameters take precedence
		if ( isset( $this->oOption->arrOptions['boxes'][$sidebar] ) )
			$arrParams = $arrParams + $this->oOption->arrOptions['boxes'][$sidebar];			
		
		return '<div class="' . $this->strClassAttrBox1 . ' ' . $this->strClassAttrBox2 . ' ' . $sidebar . '">' 
			. $this->OutputWidgetBuffer( $sidebar, $arrParams ) 
			. '</div>'
			. $this->GetCredit();
		
	}
	public function RenderWidgetBox( $arrParams ) {
		
		// Called by a function outside the class so it must be public. 
		// Notice that the last part is echo.
		
		echo $this->GetWidgetBoxOutput( $arrParams );
		
	}
	protected function GetCredit() {
		
		$strCredit = defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ? 'Responsive Column Widgets Pro' : 'Responsive Column Widgets';
		$strVendor = 'miunosoft http://michaeluno.jp';
		return "<!-- Rendered with {$strCredit} by {$strVendor} -->";
		
	}
	
	/*
	 * Retrieve widget output buffers.
	 * */
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
	function OutputWidgetBuffer( $index = 1, &$arrParams ) {

		global $wp_registered_sidebars, $wp_registered_widgets;

		// extract the parameters 
		// Todo : review whether it is readable to use extact(); otherwise, find an alternative for extract().
		extract( $arrParams );
		$arrMaxCols = $this->oOption->ConvertStringToArray( $columns );
		$arrOmits = $this->oOption->ConvertStringToArray( $omit );		
		$arrShowOnlys = $this->oOption->ConvertStringToArray( $showonly );	
		$arrOffsetsByPixel = $this->oOption->ConvertStringToArray( $offsets, ',', ':' );
		$strCustomStyle = trim( $custom_style );
		
		$index = $this->GetIndex( $index );
		$sidebars_widgets = wp_get_sidebars_widgets();
		
		if ( ! $this->IsRenderable( $index, $sidebars_widgets ) ) return false;

		$sidebar = $wp_registered_sidebars[$index];
		
		$numWidgetOrder = 0;	// for the omit parameter		
		$bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;	// if showonly is set, render only the specified widget id.
		
		$arrWidgetBuffer = array();
		foreach ( ( array ) $sidebars_widgets[$index] as $id ) {
			
			if ( !isset( $wp_registered_widgets[$id] ) ) continue;
			
			if ( in_array( ++$numWidgetOrder, $arrOmits ) ) continue;			// if omit ids match
			if ( $bShowOnly && !in_array( $numWidgetOrder, $arrShowOnlys ) ) continue;	// if show-only orders match
			
			$params = array_merge(
				array(	
					array_merge( 
						$sidebar, 
						array(
							'widget_id' => $id, 
							'widget_name' => $wp_registered_widgets[$id]['name'] 
						) 
					)
				),
				( array ) $wp_registered_widgets[$id]['params']
			);

			// Substitute HTML id and class attributes into before_widget
			$classname_ = '';
			foreach ( ( array ) $wp_registered_widgets[$id]['classname'] as $cn ) {
				
				if ( is_string( $cn ) )
					$classname_ .= '_' . $cn;
				elseif ( is_object( $cn ) )
					$classname_ .= '_' . get_class( $cn );
					
			}
			$classname_ = ltrim( $classname_, '_' );
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
		
		// Since 1.1.0
		// If the timing to load the styles is set to the first box's rendering, 
		global $arrResponsiveColumnWidgets_Flags;
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& $this->oOption->arrOptions['general']['general_css_timimng_to_load'] == 1 
			&& ! $arrResponsiveColumnWidgets_Flags['base_style']
			) {
			
			$strBuffer .= $this->GetBaseStyles( true );
			$arrResponsiveColumnWidgets_Flags['base_style'] = true;
			
		}
		
						
		$numColPosInRow = 0;	// the number of the widgets loaded in a row, zero base.
		$numRowPos = 0;			// stores the iterating row, zero base.
		$bIsRowTagClosed = False;	
		foreach ( $arrWidgetBuffer as $nIndex => $strItem ) {
			
			// check if the number of rendered widgtes reached the limit
			if ( (  $maxwidgets != 0 &&  $nIndex >= $maxwidgets ) ) break;

			// if the number of allowed rows reached the limit
			if ( ( $maxrows != 0 && $numRowPos >= $maxrows ) ) break;
			$numMaxCols	= ( isset( $arrMaxCols[$numRowPos] ) ) ? $arrMaxCols[$numRowPos] :  $numMaxCols;	// set the column number of this row		
			$strItem = ( $numColPosInRow == 0  ? '<div class="' . $this->strClassAttrRow . '">' : '' )
				. '<div class="' . $this->strClassAttrColumn . ' element_of_' . $numMaxCols . ' ' 
				. $this->strClassAttrColumn . '_' . ( $numColPosInRow + 1 ) . ' ' // insert the class attribute indicationg the current column position
				. $this->strClassAttrRow 	. '_' . ( $numRowPos + 1 ) . ' '		// insedrt the class attribute indicationg the current row position
				. ' ">' 
				. $strItem
				. '</div>';
			if ( $numColPosInRow == 0 ) $bIsRowTagClosed = False;

			// increment the position
			$numColPosInRow++;	
			
			// check if it is the last item in a row
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
	
		// If the style for max cols by pixel has not been added, add it. ( since 1.0.3 )
		if ( ! isset( $this->arrFlagsStyleMaxColsByPixel[ $index ] ) || ! $this->arrFlagsStyleMaxColsByPixel[ $index ] )
			$strBuffer .= $this->AddStyleForMaxColsByPixel( $index, $arrOffsetsByPixel );

		// If the custom style for the widget box has not been added yet,
		if ( ! empty( $strCustomStyle ) && (  ! isset( $this->arrFlagsCustomStyleAdded[ $index ] ) || ! $this->arrFlagsCustomStyleAdded[ $index ] ) )
			$strBuffer .= $this->AddCustomStyle( $index, $strCustomStyle );
			
		return $strBuffer;
		
	}
	function AddCustomStyle( $strSidebarID, $strCustomStyle ) {		// since 1.0.6
		
		$strStyleRules = '<style type="text/css" scoped>' 
			. $strCustomStyle
			. '</style>';

		$this->arrFlagsCustomStyleAdded[ $strSidebarID ] = true;
		return $strStyleRules;	
		
	}
	function AddStyleForMaxColsByPixel( $strSidebarID, $arrOffsetsByPixel ) {	// added since 1.0.3
		
		$strStyleRules = '<style type="text/css" scoped>';
		
		if ( count( $arrOffsetsByPixel ) == 0 ) { $arrOffsetsByPixel = array( array( 480, 12 ) ); }
		foreach( $arrOffsetsByPixel as $arrOffsetByPixel ) {
			// e.g. array( 740, 3)  
			$numPixel = $arrOffsetByPixel[0];
			$numOffset = $arrOffsetByPixel[1];
			if ( $numPixel == 0 ) continue;
			
			$strStyleRules .= '@media only screen and (max-width: ' . $numPixel . 'px) { ';
			$num=1; 
			// the class attribute has to be .{the sidebar ID} + .element_of_
			for ( $i = 2; $i <= 12; $i++ ) {
				if ( $i <= $numOffset )
					$strStyleRules .= ' .' . $strSidebarID . ' .element_of_' . $i . ' { width: ' . $this->strColPercentages[1] . ' } ';
				else  {
					++$num;
					$strPercent = isset( $this->strColPercentages[ $num ] ) ? $this->strColPercentages[ $num ] : '100%';
					$strStyleRules .= ' .' . $strSidebarID . ' .element_of_' . $i . ' { width: ' . $strPercent . ' } ';
				}
			}
			$strStyleRules .= ' }' . PHP_EOL;
	
		}

		$strStyleRules .= '</style>';
		$this->arrFlagsStyleMaxColsByPixel[ $strSidebarID ] = true;
		return $strStyleRules;
		
	}
	
	/*
	 *  Debug
	 */
	function EchoMemoryUsage() {
		$mem_usage = memory_get_usage(true);
	   
		if ($mem_usage < 1024)
			echo $mem_usage." bytes";
		elseif ($mem_usage < 1048576)
			echo round($mem_usage/1024,2)." kilobytes";
		else
			echo round($mem_usage/1048576,2)." megabytes";
		   
		echo "<br/>";
	} 		
}