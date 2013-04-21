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
	
	Todo: there is a claim that the scoped attribute is invalid in HTML5. 
	
*/
class ResponsiveColumnWidgets_Core_ {
	
	// Objects
	protected $oOption;		// deals with the plugin options
	protected $oReplace;	// since 1.1.1 - manipulates HTML elements.
	
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
	protected $arrScopedStyles = array();	// since 1.1.1 - stores scoped style tags temporalily with keys of the widget box ID.
	
	// depricated as of 1.1.1 protected $arrFlagsStyleMaxColsByPixel = array();		// since 1.0.8 stores the flags to indicate whether the style rule for max cols by pixel has been added or not.
	
	function __construct( $strShortCode, &$oOption ) {
		
		// Objects
		$this->oOption = $oOption;
		$this->oReplace = new ResponsiveColumnWidgets_HTMLElementReplacer();
		
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

			/* REMOVE MARGINS AS ALL GO FULL WIDTH AT 240 PIXELS */
			@media only screen and (max-width: 240px) {
				.{$this->strClassAttrColumn} { 
					margin: 1% 0 1% 0%;
				}
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

			.{$this->strClassAttrColumn}:first-child { margin-left: 0; } /* all browsers except IE6 and lower */

			/* GRID COLUMN SETUP  */
			.{$this->strClassAttrColumn} {
				display: block;
				float:left;
				margin: 1% 0 1% 1.6%;
			}					
			
			/*  GRID OF TWO   ============================================================================= */
			.element_of_1 { width: 100%; }
			.element_of_2 {	width: 49.2%; }
			.element_of_3 {	width: 32.2%; }
			.element_of_4 {	width: 23.8%; }
			.element_of_5 {	width: 18.72%; }
			.element_of_6 {	width: 15.33%; }
			.element_of_7 {	width: 12.91%; }
			.element_of_8 { width: 11.1%; }
			.element_of_9 {	width: 9.68%; }
			.element_of_10 { width: 8.56%; }
			.element_of_11 { width: 7.63%; }
			.element_of_12 { width: 6.86%; }
		";
		
		/*
			SECTIONS  ============================================================================= 
			.{$this->strClassAttrRow} {
				clear: both;
				padding: 0px;
				margin: 0px;
			}
	

		 */
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
		$strIDAttr = $this->oOption->SanitizeAttribute( "{$this->oOption->oInfo->Name} {$this->oOption->oInfo->Version}" );
		return "<style type='text/css' name='{$strIDAttr}' {$strScoped}>" 
			. apply_filters( 'RCW_filter_base_styles', $strCSS )
			. "</style>" . PHP_EOL;
		
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
	
	protected function GenerateAvailableID( $arrExistingIDs=array(), $strID='' ) {	// since 1.1.1
	
		// A utility function to generate a unique name.
		// $arrExistingIDs should be numerically indexed one-dimensional array.
		$strID = empty( $strID ) ? uniqid() : $strID;
		
		if ( ! in_array( $strID, $arrExistingIDs ) )
			return $strID;
		
		// Get the last digits
		preg_match( '/^(.+\D)(\d+)$/', $strID, $arrMatches );	
		if ( ! isset( $arrMatches[2] ) ) 
			$strID .= '_2';
		else
			$strID = $arrMatches[1] . ( $arrMatches[2] + 1 );

		// Do recursively
		return $this->GenerateAvailableID( $arrExistingIDs, $strID );
		
	}
	protected function GenerateUniqueID( $strID='' ) {	// since 1.1.1
		
		global $arrResponsiveColumnWidgets_Flags;
	
		$strID = $this->GenerateAvailableID( $arrResponsiveColumnWidgets_Flags['arrWidgetIDAttributes'], $strID );
	
		$arrResponsiveColumnWidgets_Flags['arrWidgetIDAttributes'][] = $strID;

		return $strID;
			
	}
		
	protected function GetIDAttributeWithSidebarID( $strSidebarID, $bUpdate=True ) {	// since 1.1.1
		
		global $arrResponsiveColumnWidgets_Flags;

		// Format the count if it's not set yet.
		if ( ! isset( $arrResponsiveColumnWidgets_Flags['arrBoxIDs'][ $strSidebarID ] ) )
			$arrResponsiveColumnWidgets_Flags['arrBoxIDs'][ $strSidebarID ] = 0;
		
		// Increment the count.
		if ( $bUpdate ) 		
			$arrResponsiveColumnWidgets_Flags['arrBoxIDs'][ $strSidebarID ]++;
		
		// Return the ID attribute with the count. Use a hyphen for the connector.
		return $strSidebarID . '-' . $arrResponsiveColumnWidgets_Flags['arrBoxIDs'][ $strSidebarID ];
		
	}

	public function GetWidgetBoxOutput( $arrParams ) {	// since 1.0.4
		
		// The function callback for shortcode. Notice that the last part is returning the output.

		$arrParams['sidebar'] = ! empty( $arrParams['sidebar'] ) ? $arrParams['sidebar'] : $this->GetWidgetBoxSidebarIDFromParams( $arrParams );	// $arrParams['sidebar'] = ! empty( $arrParams['sidebar'] ) ? $arrParams['sidebar'] : $this->arrDefaultParams['sidebar'];		
		$arrDefaultParams = isset( $this->oOption->arrOptions['boxes'][ $arrParams['sidebar'] ] ) ? $this->oOption->arrOptions['boxes'][ $arrParams['sidebar'] ] + $this->arrDefaultParams : $this->arrDefaultParams;
		$arrParams = shortcode_atts( $arrDefaultParams, $arrParams );		

		if ( ! is_active_sidebar( $arrParams['sidebar'] ) ) 
			return '<p>' . $arrParams['message_no_widget'] . '</p>';	// if nothing is registered in the given name of sidebar, return
				
		// The direct parameters take precedence
		if ( isset( $this->oOption->arrOptions['boxes'][ $arrParams['sidebar'] ] ) )
			$arrParams = $arrParams + $this->oOption->arrOptions['boxes'][ $arrParams['sidebar'] ];			
		
		// Get the widget output buffer.
		$strIDAttribute = $this->GetIDAttributeWithSidebarID( $arrParams['sidebar'], true );	// passing true will increment the count of the loaded times.
		$strOut = '<div id="' . $strIDAttribute . '" class="' . $this->strClassAttrBox1 . ' ' . $this->strClassAttrBox2 . ' ' . $arrParams['sidebar'] . '">' 
			. $this->GetOutputWidgetBuffer( $arrParams['sidebar'], $arrParams ) 
			. '</div>';
					
		// Load the scoped styles. Using add_action( 'wp_print_footer_scripts', ... ) will not work if the widget is inserted after the hook funciton is executed ( too late ).
		$strScopedStyles = $this->arrScopedStyles[ $arrParams['sidebar'] ];	// must be done after GetOutputWidgetBuffer() is called since the method will update the styles.
		unset( $this->arrScopedStyles[ $arrParams['sidebar'] ] );
		$strOut .= $strScopedStyles . $this->GetCredit();
			
		// Done!
		return $strOut;
		
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
	protected function GetCorrectSidebarID( $vIndex ) {
		
		global $wp_registered_sidebars;
		if ( is_int( $vIndex ) ) return "sidebar-$vIndex";

		$vIndex = sanitize_title( $vIndex );
		foreach ( ( array ) $wp_registered_sidebars as $strKey => $arrValue ) {
			if ( sanitize_title( $arrValue['name'] ) == $vIndex ) 
				return $strKey;
		}
		return $vIndex;
		
	}
	protected function IsRenderable( $strSidebarID, $arrSidebarsWidgets ) {
		
		global $wp_registered_sidebars;
		if ( empty( $arrSidebarsWidgets ) ) return false;
		if ( empty( $wp_registered_sidebars[ $strSidebarID ] ) ) return false;
		if ( !array_key_exists( $strSidebarID, $arrSidebarsWidgets ) ) return false;
		if ( !is_array( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
		if ( empty( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
		return true;
		
	}
	
	protected function GetWidgetsBufferAsArray( $strSidebarID, $arrSidebarsWidgets, $arrShowOnlys, $arrOmits ) {	// since 1.1.1
		
		global $wp_registered_sidebars, $wp_registered_widgets;
		$arrWidgetBuffer = array();	// stores the returning widget buffer outputs, one key for one widget.
		
		/*
			$arrSidebarInfo contains the following keys ( the values are as an example ):
			[name] => Responsive Column Widgets
			[id] => responsive_column_widgets
			[description] => The default widget box of Responsive Column Widgets.
			[class] => 
			[before_widget] => <aside id="%1$s" class="%2$s"><div class="widget">
			[after_widget] => </div></aside>
			[before_title] => <h3 class="widget-title">
			[after_title] => </h3>			
		*/
		$arrSidebarInfo = $wp_registered_sidebars[ $strSidebarID ];	

		$numWidgetOrder = 0;	// for the omit parameter		
		$bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;	// if showonly is set, render only the specified widget id.
		
		
		foreach ( ( array ) $arrSidebarsWidgets[ $strSidebarID ] as $strWidgetID ) {
			
			if ( !isset( $wp_registered_widgets[ $strWidgetID ] ) ) continue;
			
			if ( in_array( ++$numWidgetOrder, $arrOmits ) ) continue;			// if omit ids match
			if ( $bShowOnly && !in_array( $numWidgetOrder, $arrShowOnlys ) ) continue;	// if show-only orders match
			
			$arrParams = array_merge(
				array(	
					array_merge( 
						$arrSidebarInfo, 
						array(
							'widget_id' => $strWidgetID, 
							'widget_name' => $wp_registered_widgets[ $strWidgetID ]['name'] 
						) 
					)
				),
				( array ) $wp_registered_widgets[ $strWidgetID ]['params']
			);

			// Substitute HTML id and class attributes into before_widget
			$strClassName = '';
			foreach ( ( array ) $wp_registered_widgets[ $strWidgetID ]['classname'] as $cn ) {
				
				if ( is_string( $cn ) )
					$strClassName .= '_' . $cn;
				elseif ( is_object( $cn ) )
					$strClassName .= '_' . get_class( $cn );
					
			}
			$strClassName = ltrim( $strClassName, '_' );
			$strIDAttribute_Sidebar = $this->GetIDAttributeWithSidebarID( $strSidebarID, false );
			// $strIDAttribute_Aside = $this->GenerateUniqueID(  $strIDAttribute_Sidebar . '_aside_' . $strWidgetID );
			// $strIDAttribute_Widget = $this->GenerateUniqueID( $strIDAttribute_Sidebar . '_widget_' . $strWidgetID );
			$arrParams[0]['before_widget'] = sprintf( $arrParams[0]['before_widget'], '', $strClassName );	// the second parameter is for the backward compatibility.
			// $arrParams[0]['before_widget'] = sprintf( $arrParams[0]['before_widget'], $strWidgetID, $strClassName );
			
			// Prepend the sidebar ID to the widget ID attribute.
			// $arrParams[0]['widget_id'] = $strIDAttribute_Widget;
			
// echo $this->oOption->DumpArray( $arrParams );			
			
			$arrParams = apply_filters( 'dynamic_sidebar_params', $arrParams );
			$callback = $wp_registered_widgets[ $strWidgetID ]['callback'];
			do_action( 'dynamic_sidebar', $wp_registered_widgets[ $strWidgetID ] );

			ob_start();
			if ( is_callable( $callback ) ) {		
			
				call_user_func_array( $callback, $arrParams );		// will echo widgets	
				$arrWidgetBuffer[] = $this->oReplace->RemoveIDAttributes( ob_get_contents() );	// deletes the ID tags here.
				
			}
			ob_end_clean();
			
		} // end of foreach()
		return $arrWidgetBuffer;
		
	}
	protected function GetBaseStylesIfNotAddedYet() {	// Since 1.1.0, moved from the core method in 1.1.1
		
		// If the timing to load the styles is set to the first box's rendering, 
		global $arrResponsiveColumnWidgets_Flags;
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& $this->oOption->arrOptions['general']['general_css_timimng_to_load'] == 1 
			&& ! $arrResponsiveColumnWidgets_Flags['base_style']
			) {
			
			$arrResponsiveColumnWidgets_Flags['base_style'] = true;
			return $this->GetBaseStyles( true );	// passing true assigns the scoped attribute in the tag.
			
		}		
		
		return '';
		
	}	


	protected function FormatPositionsArray( &$arrMaxColsByPixel ) {	// since 1.1.1

		// Format the positions array.
		/* the structure looks like: 
		 * $arrPositions = array(
			 * $intScreenMaxWidth => array(
				'intMaxCols' => $arrMaxCols,
				'intCurrentMaxCol' => 0,
				'intColPosInRow' => 0,
				'intRowPos' => 0,	// stores the iterating row, zero base.
				'bIsRowTagClosed' => false,
			* )
		  )
		*/			
	
		$arrPositions = array();	// returning array
		foreach ( $arrMaxColsByPixel as $intScreenMaxWidth => $arrMaxCols ) {
			$arrPositions[ $intScreenMaxWidth ] =  array(
				'intMaxCols' => $arrMaxCols,
				'intCurrentMaxCol' => 0,
				'intColPosInRow' => 0,
				'intRowPos' => 0,
				'intScreenMaxWidth' => $intScreenMaxWidth,	// this is refered from the methods that need to know the screen max-width of the passed position array.
			);
		}
		return $arrPositions;
		
	}
	
	/*
	 * Buffer formatting methods
	 * */
	protected function UpdateBuffer_CloseTag( &$strBuffer, $strTag='div' ) {	// since 1.1.1
		
		$strBuffer = $strBuffer ? $strBuffer : ''; 	// avoid null or zero to be a string.
		$strBuffer .= '</' . $strTag . '>';
		return true;
		
	}
	protected function GetCurrentMaxColumns( &$arrPosition ) {	// since 1.1.1
		
		return ( isset( $arrPosition['intMaxCols'][ $arrPosition['intRowPos'] ] ) ) 
			? $arrPosition['intMaxCols'][ $arrPosition['intRowPos'] ] 
			:  $arrPosition['intCurrentMaxCol'];
	
	}
	protected function GetClassAttributes_Column( &$arrParams, &$arrPosition ) {		// since 1.1.1
		
		// Determine the prefix.
		$intScreenMaxWidth = trim( $arrPosition['intScreenMaxWidth'] );
		$bAddPrefix = $intScreenMaxWidth == 0 ? false : true;
// echo '$intScreenMaxWidth: ' . $intScreenMaxWidth . '<br />';		
// echo '$bAddPrefix: ' . $bAddPrefix . '<br />';		
		$strPrefixColumns = ! $bAddPrefix ? '' : 'max_' . $intScreenMaxWidth . '_';
		$strPrefixElementOf = ! $bAddPrefix ? 'element_of_' : 'max_' . $intScreenMaxWidth . '_element_of_';
		$strPrefixColumnItem = ! $bAddPrefix ? '' : 'max_' . $intScreenMaxWidth . '_';
		$strPrefixRow =!  $bAddPrefix ? '' : 'max_' . $intScreenMaxWidth . '_';
// return 'test';	
// echo '$strPrefixColumns: ' . $strPrefixColumns . '<br />';		
		// Add the class attributes.
		$strClassAttributes = $strPrefixColumns . $this->strClassAttrColumn  . ' '
			. $strPrefixElementOf . $arrPosition['intCurrentMaxCol'] . ' '
			. $strPrefixColumnItem . $this->strClassAttrColumn . '_' . ( $arrPosition['intColPosInRow'] + 1 ) . ' '
			. $strPrefixRow . $this->strClassAttrRow . '_' . ( $arrPosition['intRowPos'] + 1 );			
		
		return $strClassAttributes;
		
	}	
	protected function UpdateBuffer( &$strItem, &$strBuffer, &$arrParams, &$arrPosition ) {	// since 1.1.1
	
		$intScreenMaxWidth = $arrPosition['intScreenMaxWidth'];
		$strClassAttrRow = $intScreenMaxWidth == 0 ? $this->strClassAttrRow : 'max_' . $intScreenMaxWidth . '_' . $this->strClassAttrRow;
// echo $strClassAttrRow . '<br />';
		$strOpeningTag = $arrPosition['intColPosInRow'] == 0 ? '<div class="' . $strClassAttrRow . '">' : '';
		$strBuffer .= $strOpeningTag 
			. '<div class="' . $this->GetClassAttributes_Column( $arrParams, $arrPosition ) . '">' 
			. $strItem
			. '</div>';
	
	}
	protected function UpdatePositions( &$arrPosition ) {	// since 1.1.1
			
		// Increment the position
		$arrPosition['intColPosInRow']++;
		
		// If the current column position can be divided without any surplus by the maximum number of allowed columns, it meand it's the last item in the row.
		if ( ( $arrPosition['intColPosInRow'] % $arrPosition['intCurrentMaxCol'] ) == 0 ) {
			
			$arrPosition['intRowPos']++;				// increment the row position
			$arrPosition['intColPosInRow'] = 0;		// reset the column position
			
		}			
		
	}	
	protected function UpdateBuffer_CloseRowTag_IfLastColumn( &$strBuffer, &$arrPosition, $strTag='div' ) {	// since 1.1.1
		
		// Closes the row tag and returns the flag indicating whether it's closed or not.
								
		if ( ( $arrPosition['intColPosInRow'] % $arrPosition['intCurrentMaxCol'] ) != 0 ) 
			return false;
		
		// Close the tag and flag it.
		$strBuffer .= '</' . $strTag . '>';
		return true;		
		
	}	
	protected function GetWidgetBufferByScreenMaxWidth( &$arrWidgetBuffers, &$arrParams, $arrPosition ) {	// since 1.1.1
		
		// Set up variables
		$strBuffer = '';
		$strTag = 'div';
		$bIsRowTagClosed = False;	
		
		/*
		 * $arrPosition = array(
				'intMaxCols' => $arrMaxCols,
				'intCurrentMaxCol' => 0,
				'intColPosInRow' => 0,
				'intRowPos' => 0,	// stores the iterating row, zero base.
			* )
		  )
		*/	
		foreach( $arrWidgetBuffers as $nIndex => $strItem ) {
					
				
			// Set the column number of this row.
			$arrPosition['intCurrentMaxCol'] = $this->GetCurrentMaxColumns( $arrPosition );	
			
			$this->UpdateBuffer( $strItem, $strBuffer, $arrParams, $arrPosition ); // the updating array is passed as reference.

			// Check if it is the last item in a row
			$this->UpdatePositions( $arrPosition );	// $arrPositions is passed by reference.
		
			$bIsRowTagClosed = $this->UpdateBuffer_CloseRowTag_IfLastColumn( $strBuffer, $arrPosition );
			
			// Check 
			// 1. if the number of rendered widgtes reached the limit.
			// 2. if the number of allowed rows reached the limit
			if ( (  $arrParams['maxwidgets'] != 0 &&  ( $nIndex + 1 ) >= $arrParams['maxwidgets'] ) 	// $nIndex is zero-base.
				|| ( $arrParams['maxrows'] != 0 && $arrPosition['intRowPos'] >= $arrParams['maxrows'] )	// $arrPosition['intRowPos'] is also zero base but it's incremented by the method in this iteration.
			) {
				// $bIsRowTagClosed = $this->UpdateBuffer_CloseTag( $strBuffer );
				break;
			}				
			
		}	// end of foreach
		
		// Close the section(row) div tag in case it is ended prior to closing it.
		if ( ! $bIsRowTagClosed )
			$this->UpdateBuffer_CloseTag( $strBuffer, $strTag );
			
		$strBuffer = force_balance_tags( $strBuffer );
			
		// Okay, done.
		return $strBuffer;
		
	}	
	
	protected function GetOutputWidgetBuffer( $vIndex=1, &$arrParams ) {

		// First, check if the sidebar is rendeable.
		$strSidebarID = $this->GetCorrectSidebarID( $vIndex );
		$arrSidebarsWidgets = wp_get_sidebars_widgets();
		if ( ! $this->IsRenderable( $strSidebarID, $arrSidebarsWidgets ) ) return false;

		// Next, store the output buffers into an array.
		$arrWidgetBuffers = $this->GetWidgetsBufferAsArray( 
			$strSidebarID, 
			$arrSidebarsWidgets,
			$this->oOption->ConvertStringToArray( $arrParams['showonly'] ),
			$this->oOption->ConvertStringToArray( $arrParams['omit'] )
		);

		// Now, $arrWidgetBuffers contains the necessary data for the output. Then we are going to store the output buffer
		// in another array by screen max-width.
		
		// $arrMaxCols = $this->oOption->ConvertStringToArray( $arrParams['columns'] );
			
// $arrParams['columns'] = '4, 5, 1';		
// $arrParams['columns'] = '4| 600:3|480:1';
// $arrParams['columns'] = '600:3, 2, 4';
// $arrParams['columns'] = '800:4, 5, 1 | 3, 2, 1 | 480: 3, 4, 1';
// echo $this->oOption->DumpArray( $arrParams['columns'] );		
// return;
		
		// Format the column array that contains the information of max column numbers by screen max-width.
		$arrMaxColumns = $this->oOption->SetMinimiumScreenMaxWidth(
			$this->oOption->FormatColumnArray( 
				$arrParams['columns'], 	
				$arrParams['default_media_only_screen_max_width'] 
			)		
		);
		
// echo $this->oOption->DumpArray( $arrMaxColumns );
		
		// Format the positions array. 
		$arrPositions = $this->FormatPositionsArray( $arrMaxColumns );
		unset( $arrMaxColumns );	// make it clear that $arrMaxColumns won't be used anymore.
		
// echo $this->oOption->DumpArray( $arrPositions );		
// return;
		
		// $strBuffer stores the buffer output and $arrBuffers stores formatted output strings.
		$strBuffer = '';
		$arrBuffers = array(); 
	
		// Okay, go. Enclose the buffer output string with the tag with the class attribute of screen max-size.
		foreach ( $arrPositions as $intScreenMaxWidth => $arrPosition ) 
			$arrBuffers[ $intScreenMaxWidth ] = '<div class="' . $strSidebarID . '_' . $intScreenMaxWidth . '">'
				. $this->GetWidgetBufferByScreenMaxWidth( $arrWidgetBuffers, $arrParams, $arrPosition )
				. '</div>';
			
		foreach ( $arrBuffers as $intScreenMaxWidth => $strWidgetItem ) 
			$strBuffer .= $strWidgetItem;
		
		// Todo: there is a claim that the scoped attribute is invalid in HTML5. 
		$this->arrScopedStyles[ $strSidebarID ] = $this->SetWidgetBoxStyle( $strSidebarID, $arrParams, $arrPositions );

		// Okay, done.
		return $strBuffer;
		
	}
	protected function GetVisibilityRules( $strSidebarID, $arrScreenMaxWidths, $intCurrentScreenMaxWidth=0 ) {	// since 1.1.1
		
		// Disable the visibility of the widget box elements for the other screen widths.
		$strStyleRules = '';
		$strIDAttribute = $this->GetIDAttributeWithSidebarID( $strSidebarID, false );	// the second parameter needs to be false not to increment the count.
		foreach ( $arrScreenMaxWidths as $intScreenMaxWidth ) {
			
			$strDisplay = $intScreenMaxWidth == $intCurrentScreenMaxWidth ? 'block' : 'none';					
			$strStyleRules .= " #{$strIDAttribute} .{$strSidebarID}_{$intScreenMaxWidth} { display: {$strDisplay}; } " . PHP_EOL;
			
		}	
		return $strStyleRules;
		
	}	
	protected function SetWidgetBoxStyle( &$strSidebarID, &$arrParams, &$arrPositions ) {	// since 1.1.1
		
		// There might be a previous value. This is important since the scoped style tags will be embedded in the footer at once with other widget boxes.
		$strStyle = isset( $this->arrScopedStyles[ $strSidebarID ] ) ? $this->arrScopedStyles[ $strSidebarID ] : '';
			
		// Add the base CSS rules if not loaded yet. 
		$strStyle .= $this->GetBaseStylesIfNotAddedYet();
		
		// Add the widget box specific style.
		$strStyle .= $this->GetWidgetBoxStyle( $strSidebarID, $arrPositions );

		// If the custom style for the widget box has not been added yet,
		$strStyle .= $this->GetCustomStyleIfNotAddedYet( $strSidebarID, $arrParams['custom_style'] );
			
		return $strStyle;
		
	}	
	protected function GetWidgetBoxStyle( $strSidebarID, $arrPositions ) {	// since 1.1.1
				
		$strIDAttribute = $this->GetIDAttributeWithSidebarID( $strSidebarID, false );	// the second parameter needs to be false not to increment the count.	
		$strStyleRules = "<style type='text/css' name='style_{$strIDAttribute}' scoped>";	// The name attribute is invalid in a scoped tag.
		
		$arrScreenMaxWidths = array_keys( $arrPositions );
		$strStyleRules .= $this->GetVisibilityRules( $strSidebarID, $arrScreenMaxWidths, 0 );
			
		krsort( $arrPositions );	// needs to be sorted by decsending order. The larger width rules will be overriden.
		
		foreach ( $arrPositions as $intScreenMaxWidth => $arrPosition ) {
									
			// if the screen max-width is 0, meaning no-limit, skip, because it's already defined in the base rules.
			if ( $intScreenMaxWidth == 0 ) continue;
			
			// Set the prefixes.
			$strPrefixElementOf = 'max_' . $intScreenMaxWidth . '_element_of_';
			$strPrefixColumn = 'max_' . $intScreenMaxWidth . '_' . $this->strClassAttrColumn . '_';
			$strPrefixRow = 'max_' . $intScreenMaxWidth . '_' . $this->strClassAttrRow . '_';				
				
			// okay, add the rules.
			$strStyleRules .= "@media only screen and (max-width: {$intScreenMaxWidth}px) {" . PHP_EOL;
			
			foreach ( $this->strColPercentages as $intElement => $strWidthPercentage ) 	{
				
				$strClearLeft = $intElement == 1 ? "clear: left;" : "";
				$strMargin = $intElement == 1 ? "margin: 1% 0 1% 0%;" : "";
				$strFloat = "display: block; float:left;";
				$strStyleRules .= " #{$strIDAttribute}.{$strSidebarID} .{$strPrefixElementOf}{$intElement} { width:{$strWidthPercentage}; {$strClearLeft} {$strMargin} {$strFloat} } " . PHP_EOL;
			
			}
			
			$strStyleRules .= " #{$strIDAttribute}.{$strSidebarID} .{$strPrefixColumn}1 { clear: left; } " . PHP_EOL;	// the first column element
			$strStyleRules .= " #{$strIDAttribute}.{$strSidebarID} .{$strPrefixRow} { clear: both; padding: 0px; margin: 0px; } " . PHP_EOL;		// rows
			
			// Disable the visibility of the widget box elements for the other screen widths.
			$strStyleRules .= $this->GetVisibilityRules( $strSidebarID, $arrScreenMaxWidths, $intScreenMaxWidth );
			
			$strStyleRules .= "}" . PHP_EOL;
			
		/*
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
	
		}
				
		return $strStyleRules . '</style>';
		
	}
	protected function GetCustomStyleIfNotAddedYet( $strSidebarID, $strCustomStyle ) {	// since 1.1.1

		$strCustomStyle = trim( $strCustomStyle );
		if ( ! empty( $strCustomStyle ) && (  ! isset( $this->arrFlagsCustomStyleAdded[ $strSidebarID ] ) || ! $this->arrFlagsCustomStyleAdded[ $strSidebarID ] ) )
			return $this->AddCustomStyle( $strSidebarID, $strCustomStyle );
		return '';
		
	}
	protected function AddCustomStyle( $strSidebarID, $strCustomStyle ) {		// since 1.0.6
		
		$strIDAttribute = 'style_custom_' . $this->GetIDAttributeWithSidebarID( $strSidebarID, false );
		$strStyleRules = '<style type="text/css" name="' . $strIDAttribute . '" scoped>' 
			. $strCustomStyle
			. '</style>' . PHP_EOL;

		$this->arrFlagsCustomStyleAdded[ $strSidebarID ] = true;
		return $strStyleRules;	
		
	}
	
	/*
	 * Utilities
	 * */
	
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