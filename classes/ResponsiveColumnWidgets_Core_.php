<?php
/**
	Displays widgtes in multiple columns
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 * @sub-classes ResponsiveColumnWidgets_Styles, ResponsiveColumnWidgets_WidgetBox, ResponsiveColumnWidgets_IDHandler
 * 

	Todo: there is a claim that the scoped attribute is invalid in HTML5. 
	
*/
class ResponsiveColumnWidgets_Core_ {
	
	// Objects
	public $oOption;		// deals with the plugin options. Made it public in 1.1.2 to allow the AutoInsert class access this object. In 1.1.2.1 the StyleLoader class also uses it.
	public $oStyle;		// since 1.1.2 - manipulates CSS rules. It is public because the Auto-Insert class uses it. In 1.1.2.1 the StyleLoader class also uses it.
		
	// Default properties
	protected $strShortCode;
	// protected $strCSSDirURL;
	protected $strPluginName = 'responsive-column-widgets';		// used to the name attribute of the script
	protected $arrDefaultParams = array();	// will be overriden by the option object's array in the constructor.
			
	protected $strClassSelectorBox2 ='widget-area';
	public $arrClassSelectors = array(	// overriden by the option in the constructor, made it public in 1.1.2.1 to allow the StyleLoader class to access it.
		'box' => 'responsive_column_widgets_box',
		'column' => 'responsive_column_widgets_column',
		'row' => 'responsive_column_widgets_row',
	);


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
				
		// register this plugin sidebar; if already registered, it will do nothing
		$this->RegisterSidebar();	// must be called after $this->oOption is set.
		
		// add the stylesheet - set the order number to 100 which is quite low to load it after others have loaded.
		// add_action( 'wp_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );	
		// add_action( 'login_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );
		// add_action( 'admin_enqueue_scripts', array( $this, 'AddStyleSheetInHeader' ), 100 );
		
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& ! $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) {	// 0 for the header
			
			add_action( 'wp_head', array( $this->oStyle, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['login'] )
				add_action( 'login_head', array( $this->oStyle, 'AddStyleSheet' ) );
			if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['admin'] )			
				add_action( 'admin_head', array( $this->oStyle, 'AddStyleSheet' ) );
		
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
	function AddFormInDynamicSidebar( $arrSidebarArgs ) {	// since 1.0.4 - currently not used	
		
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
	
	/*
	 * The core methods to render widget boxes. RenderWidgetBox() and GetWidgetBoxOutput().
	*/
	public function RenderWidgetBox( $arrParams, $bIsStyleNotScoped=false ) {	// must be public as this is called from instantiated objects.
		
		echo $this->GetWidgetBoxOutput( $arrParams, $bIsStyleNotScoped );	// do echo, not return.
		
	}	
	public function GetWidgetBoxOutput( $arrParams, $bIsStyleNotScoped=false ) {	// since 1.0.4
		
		// The function callback for shortcode. Notice that the last part is returning the output.
		$arrParams = $this->oOption->FormatParameterArray( $arrParams );

		// If this is a callback for the shortcode, the second parameter will be false. Reverse the value.
		$bIsStyleScoped = $bIsStyleNotScoped ? false : true;

		// If nothing is registered in the given name of sidebar, return
		if ( ! is_active_sidebar( $arrParams['sidebar'] ) ) 
			return '<p>' . $arrParams['message_no_widget'] . '</p>';	
				
		// Generate the ID - Get a unique ID selector based on the sidebar ID and the parameters.
		$oID = new ResponsiveColumnWidgets_IDHandler;
		$strCallID = $oID->GetCallID( $arrParams['sidebar'], $arrParams );	// an ID based on the sidebar ID + parameters; there could be the same ID if the passed values are the same.
		$strIDSelector = $oID->GenerateIDSelector( $strCallID );	// a unique ID throughout the script load 
		
		// Retrieve the widget output buffer.
		$strOut = '<div id="' . $strIDSelector . '" class="' . $this->arrClassSelectors['box'] . ' ' . $this->strClassSelectorBox2 . ' ' . $arrParams['sidebar'] . '">' 
			. $this->GetOutputWidgetBuffer( $arrParams['sidebar'], $arrParams, $strCallID, $bIsStyleScoped ) 
			. '</div>';
			
		// Done!
		return $strOut . $this->GetCredit();
		
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
			$this->arrClassSelectors
		);	
		
		// Next, store the output buffers into an array.
		$arrWidgetBuffers = $oWidgetBox->GetWidgetsBufferAsArray( 
			$strSidebarID, 
			$arrSidebarsWidgets,
			$this->oOption->ConvertStringToArray( $arrParams['showonly'] ),
			$this->oOption->ConvertStringToArray( $arrParams['omit'] ),
			$arrParams['remove_id_attributes']
		);

		// Now, $arrWidgetBuffers contains the necessary data for the output. 
		// Okay, go. Enclose the buffer output string with the tag having the class attribute of screen max-width.
		$strBuffer = '';			// $strBuffer stores the string buffer output.		
		foreach ( $arrWidgetBuffers as $intIndex => $strWidgetBuffer ) 	{
			
			$strBuffer .= '<div class="' 
				. $oWidgetBox->GetClassAttribute() 	// returns the class attribute values calculated with the stored positions and parameters.
				. '">'
				.  force_balance_tags( $strWidgetBuffer )
				. '</div>';	
				
			// If the allowed number of widgets reaches the limit, escape the loop.
			// For the max-rows, it depends on the screen max-widths, so it will be dealt with the style.
			if (  $arrParams['maxwidgets'] != 0 &&  ( $intIndex + 1 ) >= $arrParams['maxwidgets'] ) break;
				
			$oWidgetBox->AdvancePositions();	// increments the position values stored in the object properties.
				
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