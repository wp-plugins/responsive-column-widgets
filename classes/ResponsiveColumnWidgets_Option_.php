<?php
class ResponsiveColumnWidgets_Option_ {

	public $arrDefaultParams = array(	// must be public; accessed in the core object.
		'columns' => array( 3 ),		// set the default to 3 since 1.0.3; the type changed to array from string since 1.0.6.1
		'sidebar' => 'responsive_column_widgets',
		'label' => 'Responsive Column Widgets',
		'maxwidgets' => 0,
		'maxrows' => 0,
		'omit' => array(),				// the type changed to array from string since 1.0.6.1
		'showonly' => array(),			// the type changed to array from string since 1.0.6.1
		'offsets' => array( 			// the type changed to array from string since 1.0.6.1
			array( 600, 12 ),
		),	//'600: 12', // e.g. '800: 1, 600: 2, 480: 3, 320: 4, 240: 5',	// added since 1.0.3
	);
	public $arrDefaultSidebarArgs = array(	// must be public; accessed in the core object for register_sidebar()
		'description' 						=> '',
		'before_widget'						=> '<aside id="%1$s" class="%2$s"><div class="widget">',
		'after_widget'						=> '</div></aside>',
		'before_title'						=> '<h3 class="widget-title">',
		'after_title'						=> '</h3>',
		'message_no_widget'					=> 'No widget added yet.',
		'insert_footer'						=> false,	// since 1.0.5
		'insert_footer_disable_front'		=> false,	// since 1.0.7
		'insert_footer_disable_ids'			=> array(),		// since 1.0.7
		'custom_style'						=> '',		// since 1.0.6
		'insert_posts'						=> array(	// since 1.0.7
			'post' => false,
			'page' => false,
		),	
		'insert_posts_positions'			=> array(	// since 1.0.7
			'above' => false,
			'below' => true,
		),		
		'insert_posts_disable_front'		=> false,	// since 1.0.7
		'insert_posts_disable_ids'			=> array(),		// since 1.0.7
	);	
	public $arrCapabilities = array(	// used in the drop-down list of the General Options page.
		0 => 'manage_options',
		1 => 'edit_theme_options',
		2 => 'publish_posts',
		3 => 'edit_posts',
		4 => 'read'
	);
	protected $arrDefaultOptionStructure = array(
		'boxes' => array(),
		'general' => array(
			'capability' => 0,
			'allowedhtmltags' => array(),		// e.g. array( 'noscript', 'style' )	// will be imploded when it is rendered
			'license' =>'',
			'memory_allocation' => 0,	// since 1.0.7.1 - 0 means do nothing.
		),
	);
	function __construct( $strOptionKey ) {
	
		$this->strOptionKey = $strOptionKey;
		$this->arrOptions = ( array ) get_option( $strOptionKey );
		
		// Merge with the default values.
		$this->arrDefaultSidebarArgs['description'] = __( 'The default widget box of Responsive Column Widgets.', 'responsive-column-widgets' );	// cannot be declared as the default property because it needs to use a custom function.
				
		// wp_parse_args(), array() + array(), array_merge() - do not work with multi-dimensional arrays
		// array_replace_recursive() - does not support PHP below 5.3.0
		$this->arrOptions = $this->UniteArraysRecursive( $this->arrOptions, $this->arrDefaultOptionStructure );	// $this->arrOptions = $this->array_replace_recursive( $this->arrDefaultOptionStructure, $this->arrOptions );
		
		// $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] = isset( $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] ) ? $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] + $arrDefaultBoxParams : $arrDefaultBoxParams;
		// $this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] = wp_parse_args( $this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ], $arrDefaultBoxParams );
		
		$arrDefaultBoxParams = $this->arrDefaultSidebarArgs + $this->arrDefaultParams;
		$arrCurrentDefaultBoxParams = isset( $this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] ) ? $this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] : array();
		$this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] = $this->UniteArraysRecursive( 
			$arrCurrentDefaultBoxParams, 
			$arrDefaultBoxParams 
		);
	
		// store plugin data
		if ( !function_exists( 'get_plugin_data' )  ) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );	
		$this->arrPluginData = get_plugin_data( RESPONSIVECOLUMNWIDGETSFILE, false );
		if ( defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ) 
			$this->arrPluginDataPro = get_plugin_data( RESPONSIVECOLUMNWIDGETSPROFILE, false );
			
		// if the attempt to override the memory allocation option is set,
		if ( ! empty( $this->arrOptions['general']['memory_allocation'] ) ) 		
			$this->SetMemoryLimit( $this->arrOptions['general']['memory_allocation'] );
			
	}
	
	function Update() {
		
		update_option( $this->strOptionKey, $this->arrOptions );
		
	}
	
	function InsertBox( $strSidebarID, $arrBoxOptions ) {
		
		$this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] = $arrBoxOptions;
		
	}
	
	function GetDefaultValue( $strKey, $bConvertToString=True, $arrGlues=array( ', ', ': ' )) {
		
		// Since 1.0.6.1
		// Returns the default value of the given key from the default option array for the default Widget Box
		// If the value is an array it will convert it to string. ( this is useful to display in a form field )
		// If the array to string convertsion is on, it uses $strDelim1 and $strDelim2 to implode() the array.
		// Up to the second dimension is supported for multi-dimensional arrays.
		$arrDefaultBoxParams = $this->arrDefaultSidebarArgs + $this->arrDefaultParams;
		
		$vValue = isset( $arrDefaultBoxParams[ $strKey ] ) ? $arrDefaultBoxParams[ $strKey ] : null;
		
		if ( ! $bConvertToString ) return $vValue;
		
		return $this->ConvertOptionArrayValueToString( $vValue, $arrGlues );
				
	}
	function ConvertOptionArrayValueToString( $vInput, $arrGlues=array( ', ', ': ' ) ) {
		
		// since 1.0.6.1
		// Converts the option value with the type of array into string.
		
		if ( ! is_array( $vInput ) ) return $vInput;
		
		return $this->ImplodeRecursive( $vInput, $arrGlues );
		
	}
	
	/*
	 * Utilities - helper methods which can be used outside the plugin
	 * */
	function EchoMemoryLimit() {
		
		// since 1.0.7.1
		echo $this->arrOptions['general']['memory_allocation'] . '<br />';
		echo $this->GetMemoryLimit();
		
	}
	function GetMemoryLimit() {
		
		// since 1.0.7.1
		// if ( ! function_exists( 'memory_get_usage' ) ) return;
		if ( ! function_exists( 'ini_get' ) ) return;		// some servers disable ini_get()
		return @ini_get( 'memory_limit' );		// returns the string with the traling M characeter. e.g. 128M

	}
	function SetMemoryLimit( $numMegabytes ) {
		
		// since 1.0.7.1
		// unlike GetMemoryLimit() the passed value should not contain the M character at the end.
		// if ( ! function_exists( 'memory_get_usage' ) ) return;		
		if ( ! function_exists( 'ini_set' ) ) return;		// some servers disable ini_set()
		@ini_set( 'memory_limit', rtrim( $numMegabytes, 'M' ) . 'M' );
		
	}	 
	function ImplodeRecursive( $arrInput, $arrGlues ) {
		
		// since 1.0.6.1
		// Implodes the given multi-dimensional array.
		// $arrGlues should be an array nummerically indexed with the values of glue. 
		// Each element should represent the glue of the dimension corresponding to the depth of the array.
		// 	e.g. array( ',', ':' ) will glue the elements of first dimension with comma and second dimension with colon.
		
		$arrGlues_ = ( array ) $arrGlues;
		array_shift( $arrGlues_ );

		foreach( $arrInput as $k => &$vElem ) {
			
			if ( ! is_array( $vElem ) ) continue;
				
			$vElem = $this->ImplodeRecursive( $vElem, ( ( array ) $arrGlues_[0] ) );
		
		}
		
		return implode( $arrGlues[0], $arrInput );

	}	
	function ConvertStringToArray( $strInput, $strDelim=',', $strDelim2='' ) {
		
		// Since 1.0.6.1
		// explodes the given array into string and it supports up tp the second dimension.
		
		if ( is_array( $strInput ) ) return $strInput;
		
		// converts the given string into array by the given delimiter
		// e.g. 
		// 3, 7, 4 --> array( 3, 7, 4 )
		// 740:3, 600: 2, 300: 1 -->  array( array( 740, 3 ), array( 600, 2 ), array( 300, 1 ) )
		$arrElems = preg_split( "/[{$strDelim}]\s*/", trim( $strInput ), 0, PREG_SPLIT_NO_EMPTY );

		$arrInput = $arrElems;
		
		if ( !empty( $strDelim2 ) )
			foreach( $arrElems as $numIndex => $strElem )
				$arrInput[ $numIndex ] = preg_split( "/[{$strDelim2}]\s*/", trim( $strElem ), 0, PREG_SPLIT_NO_EMPTY );
				
		return $arrInput;
		
	}		
	function UniteArraysRecursive( $arrPrecedence, $arrDefault ) {
		
		if ( is_null( $arrPrecedence ) )
			$arrPrecedence = array();
		
		if ( !is_array( $arrDefault ) || !is_array( $arrPrecedence ) ) return $arrPrecedence;
			
		foreach( $arrDefault as $strKey => $v ) {
			
			// If the precedence does not have the key, assign the default's value.
			if ( ! array_key_exists( $strKey, $arrPrecedence ) )
				$arrPrecedence[ $strKey ] = $v;
			else {
				
				// if the both are arrays, do the recursive process.
				if ( is_array( $arrPrecedence[ $strKey ] ) && is_array( $v ) ) 
					$arrPrecedence[ $strKey ] = $this->UniteArraysRecursive( $arrPrecedence[ $strKey ], $v );			
			
			}
		}
		
		return $arrPrecedence;
		
	}			
	
	/*
	 * Methods for Debug
	 * */
	function DumpArray( $arr ) {
		
		return '<pre>' . esc_html( print_r( $arr, true ) ) . '</pre>';
		
	}	
}