<?php
/**
	Displays widgets in multiple columns
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 * @sub-classes ResponsiveColumnWidgets_Styles, ResponsiveColumnWidgets_WidgetBox, ResponsiveColumnWidgets_IDHandler
 * @filters		RCW_filter_widgetbox_output - applies to the outputs of widget boxes
	
*/
class ResponsiveColumnWidgets_Core_ {
	
	// Objects
	public $oOption;		// deals with the plugin options. Made it public in 1.1.2 to allow the AutoInsert class access this object. In 1.1.2.1 the StyleLoader class also uses it.
	public $oStyle;		// since 1.1.2 - manipulates CSS rules. It is public because the Auto-Insert class uses it. In 1.1.2.1 the StyleLoader class also uses it.
	public $oDecode;	// since 1.1.6 - decodes encrypted html contents as cache saved in a transient.
		
	// Default properties
	protected $strShortCode;

	protected $strPluginName = 'responsive-column-widgets';		// used to the name attribute of the script
	protected $arrDefaultParams = array();	// will be overridden by the option object's array in the constructor.
			
	protected $strClassSelectorBox2 ='widget-area';
	public $arrClassSelectors = array(	// overridden by the option in the constructor, made it public in 1.1.2.1 to allow the StyleLoader class to access it.
		'box' => 'responsive_column_widgets_box',
		'column' => 'responsive_column_widgets_column',
		'row' => 'responsive_column_widgets_row',
	);

	protected $arrSidebarHierarchies;	// stores the array containing hierarchy information of the sidebars selected in the plugin widget.
	
	// Flags
	protected $bIsFormInDynamicSidebarRendered = false;

	function __construct( $strShortCode, &$oOption ) {
				
		// properties
		$this->arrDefaultParams = $oOption->arrDefaultParams + $oOption->arrDefaultSidebarArgs;
		
		$this->strShortCode = $strShortCode;
		// $this->strCSSDirURL = RESPONSIVECOLUMNWIDGETSURL . '/css/';

		$this->arrClassSelectors = array( 
			'box'		=> $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['box'] ),
			'column'	=> $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['column'] ),
			'row'		=> $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['row'] ),
		);

		// Objects
		$this->oOption = $oOption;
		$this->oStyle = new ResponsiveColumnWidgets_Styles( 
			$oOption, 
			$this->arrClassSelectors
		);
		$this->oDecode = new ResponsiveColumnWidgets_Decoder;
				
		// Register this plugin sidebar; if already registered, it will do nothing
		$this->RegisterSidebar();	// must be called after $this->oOption is set.
		
		// Add the stylesheet	
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& ! $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) {	// 0 for the header
			
			add_action( 'wp_head', array( $this->oStyle, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['login'] )
				add_action( 'login_head', array( $this->oStyle, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['admin'] )			
				add_action( 'admin_head', array( $this->oStyle, 'AddStyleSheet' ) );
		
		}
		
		// Add the shortcode.
		add_shortcode( $this->strShortCode, array( $this, 'GetWidgetBoxOutput' ) );
					
	}
	
	/*
	 * Registers saved sidebars
	 * */
	function RegisterSidebar() {
		
		global $wp_registered_sidebars;

		if ( ! function_exists( 'register_sidebar' ) ) return;
		
		foreach ( $this->oOption->arrOptions['boxes'] as $strSidebarID => $arrBoxOptions ) 	{
			
			if ( array_key_exists( 'Responsive_Column_Widgets', $GLOBALS['wp_registered_sidebars'] ) ) continue;
			
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

	}

	
	/*
	 * The core methods to render widget boxes. RenderWidgetBox() and GetWidgetBoxOutput().
	*/
	public function RenderWidgetBox( $arrParams, $bIsStyleNotScoped=false ) {	// must be public as this is called from instantiated objects.
		
		echo $this->GetWidgetBoxOutput( $arrParams, $bIsStyleNotScoped );	// do echo, not return.
		
	}	
	public function GetWidgetBoxOutput( $arrParams, $bIsStyleNotScoped=false ) {	// since 1.0.4
		
		// This method can be the callback for shortcode or manually called by the front-end function.
		// Notice that the last part is returning the output.
		$arrParams = $this->oOption->FormatParameterArray( $arrParams );

		// If this is a callback for the shortcode, the second parameter will be false. Reverse the value.
		$bIsStyleScoped = $bIsStyleNotScoped ? false : true;

		// If nothing is registered in the given name of sidebar, return
		if ( ! is_active_sidebar( $arrParams['sidebar'] ) ) 
			return '<p>' . $arrParams['message_no_widget'] . '</p>';	

		// Check sidebar dependency conflicts
		if ( $this->isDependencyConflict( $arrParams['sidebar'] ) )
			return '<p class="error"><strong>Responsive Column Widget</strong>: ' . __( 'A dependency conflict occurred. Please reselect a child widget in the Widgets page of the administration area.', 'responsive-column-widgets' ) . '</p>';
				
		// Generate the ID - Get a unique ID selector based on the combination of the sidebar ID and the parameters.
		$oID = new ResponsiveColumnWidgets_IDHandler;
		$strCallID = $oID->GetCallID( $arrParams['sidebar'], $arrParams );	// an ID based on the sidebar ID + parameters; there could be the same ID if the passed values are the same.
		$strIDSelector = $oID->GenerateIDSelector( $strCallID );	// a unique ID throughout the script load 
		unset( $oID );	// release the object for below PHP 5.3 
		
		// Retrieve the widget output buffer.
		// $strOut = '<div id="' . $strIDSelector . '" class="' . $this->arrClassSelectors['box'] . ' ' . $this->strClassSelectorBox2 . ' ' . $arrParams['sidebar'] . '">' 
		$strOut = "<div class='{$arrParams['sidebar']}'>"
				. $arrParams['before_widget_box']
				. "<div id='{$strIDSelector}' class='{$this->arrClassSelectors['box']} {$this->strClassSelectorBox2}'>"
					. $this->GetOutputWidgetBuffer( $arrParams['sidebar'], $arrParams, $strCallID, $bIsStyleScoped ) 
				. "</div>"
			. $arrParams['after_widget_box']
			. "</div>";
			
		// Done!
		$strOut = apply_filters( 'RCW_filter_widgetbox_output', $strOut );
		return $strOut . $this->GetCredit();
		
	}	
	protected function isDependencyConflict( $strSidebarID ) {	// since 1.1.7.3
		
		if ( ! isset( $this->arrSidebarHierarchies ) ) {
			// Store the sidebar hierarchy array in a property - since 1.1.7.3
			$oWO = new ResponsiveColumnWidgets_WidgetOptions;
			$this->arrSidebarHierarchies = $oWO->GetHierarchyBase();
			unset( $oWO );	// for PHP below 5.3
		}
ResponsiveColumnWidgets_Debug::DumpArray( $this->arrSidebarHierarchies, dirname( __FILE__ ) . '/sidebar_hierarchies.txt' );						
		
		$oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
		$arrDependencies = $oSH->getDependenciesOf( $strSidebarID, $this->arrSidebarHierarchies );		
		unset( $oSH );	// for PHP below 5.3.
		if ( isset( $this->arrSidebarHierarchies[''] ) || in_array( $strSidebarID, $arrDependencies ) ) 
			return true;
		
		return false;
		
	}
	protected function GetCredit() {
		
		$strCredit = defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ? 'Responsive Column Widgets Pro' : 'Responsive Column Widgets';
		$strVendor = 'miunosoft http://michaeluno.jp';
		return "<!-- Rendered with {$strCredit} by {$strVendor} -->";
		
	}
		
	/*
	 * Retrieve widget output buffers. 
	 * The followings are buffer formatting methods.
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
	protected function IsRenderable( $strSidebarID, &$arrSidebarsWidgets ) {
		
		global $wp_registered_sidebars;
		if ( empty( $arrSidebarsWidgets ) ) return false;
		if ( empty( $wp_registered_sidebars[ $strSidebarID ] ) ) return false;
		if ( !array_key_exists( $strSidebarID, $arrSidebarsWidgets ) ) return false;
		if ( !is_array( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
		if ( empty( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
		return true;
		
	}	
	protected function GetOutputWidgetBuffer( $vIndex=1, &$arrParams, $strCallID, $bIsStyleScoped ) {

		// First, check if the sidebar is renderable.
		$strSidebarID = $this->GetCorrectSidebarID( $vIndex );
		$arrSidebarsWidgets = wp_get_sidebars_widgets();
		if ( ! $this->IsRenderable( $strSidebarID, $arrSidebarsWidgets ) ) return false;
	
		// Instantiate the object to generate widget box outputs.
		$oWidgetBox = new ResponsiveColumnWidgets_WidgetBox( // this object must be instantiated every time rendering a widget box.
			$arrParams, 
			$this->oOption->SetMinimiumScreenMaxWidth(	// the max-columns array
				$this->oOption->FormatColumnArray( 
					$arrParams['columns'], 	
					$arrParams['default_media_only_screen_max_width'] 
				)		
			),
			$this->oOption->formatColSpanArray( $arrParams['colspans'] ),
			$this->arrClassSelectors
		);	
		
		// Check if the cache duration is set and if the cache is stored.
		$strCacheID = 'RCW_Cache_' . md5( $strCallID );	// since the passed call ID has the long prefix 'responsive_coluimn_widget', it needs to be shortened.
		$strBuffer = $arrParams['cache_duration'] > 0 ? $this->oDecode->decodeBase64( get_transient( $strCacheID ) ) : '';

		if ( empty( $strBuffer ) ) {
			
			// Store the output buffers into an array.
			$arrWidgetBuffers = $oWidgetBox->GetWidgetsBufferAsArray( 
				$strSidebarID, 
				$arrSidebarsWidgets,
				$this->oOption->ConvertStringToArray( $arrParams['showonly'], ',' ),
				$this->oOption->ConvertStringToArray( $arrParams['omit'], ',' ),
				$arrParams['remove_id_attributes']
			);

			// since 1.1.3 - Get the flag array indicating whether the widgets are the plugin's widget-box widget or not.
			$arrFlagsWidgetBoxWidget = $oWidgetBox->GetWidgetBoxWidgetFlagArray();
							
			// Now, $arrWidgetBuffers contains the necessary data for the output. 
			// Okay, go. Enclose the buffer output string with the tag having the class attribute of screen max-width.
			$strBuffer = '';			// $strBuffer stores the string buffer output.		
			foreach ( $arrWidgetBuffers as $intIndex => $strWidgetBuffer ) 	{
				
				$oWidgetBox->setColSpans( $intIndex + 1 ); // the widget index is one-base while the array index is zero-base.
				
				$strBuffer .= '<div class="' 
					. $oWidgetBox->GetClassAttribute() 	// returns the class attribute values calculated with the stored positions and parameters.
					. ( isset( $arrFlagsWidgetBoxWidget[ $intIndex ] ) && $arrFlagsWidgetBoxWidget[ $intIndex ] ? ' widget_box_widget' : '' )	// add no margin and no padding class
					. '">'
					.  force_balance_tags( $strWidgetBuffer )
					. '</div>';	
					
				// If the allowed number of widgets reaches the limit, escape the loop.
				// For the max-rows, it depends on the screen max-widths, so it will be dealt with the style.
				if (  $arrParams['maxwidgets'] != 0 &&  ( $intIndex + 1 ) >= $arrParams['maxwidgets'] ) break;
					
				$oWidgetBox->advancePositions();	// increments the position values stored in the object properties.
					
			}	
			
			if ( $arrParams['cache_duration'] > 0 ) 
				set_transient( $strCacheID, base64_encode( $strBuffer ), $arrParams['cache_duration'] );
			
			
		}
		
		// the CSS rules
		$strBuffer .= $this->oStyle->GetStyles( 
			$arrParams['sidebar'], 
			$strCallID, 
			$arrParams['custom_style'], 
			$oWidgetBox->GetScreenMaxWidths(), 
			$bIsStyleScoped 
		);
			
		// Done!
		unset( $oWidgetBox );	// make sure it's released for PHP below 5.3.
		return $strBuffer;
		
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