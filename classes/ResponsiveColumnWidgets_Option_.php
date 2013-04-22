<?php
class ResponsiveColumnWidgets_Option_ {

	// Objects
	public $oInfo;	// stores the plugin info object.
	
	// Default Values
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
		'default_media_only_screen_max_width' => 600,	// since 1.1.1 - it mens when the browser widths gets 600px or below, the media only rules will be applied
	);
	public $arrDefaultSidebarArgs = array(	// must be public; accessed in the core object for register_sidebar()
		'description' 						=> '',
		'before_widget'						=> '<aside class="%2$s"><div class="widget">',
		'after_widget'						=> '</div></aside>',
		'before_title'						=> '<h3 class="widget-title">',
		'after_title'						=> '</h3>',
		'message_no_widget'					=> 'No widget added yet.',
		'custom_style'						=> '',		// since 1.0.6
		// since 1.0.9
		'autoinsert_enable'			=> 0,		// 0: off, 1: on
		'autoinsert_enable_areas'	=> array( 
			'the_content' => true,
			'comment_text' => false,
			'wp_footer' => false,		
		),
		'autoinsert_position'	=> 1,	// 0: above, 1: below, 2: both
		'autoinsert_enable_filters'	=> array(),
		'autoinsert_enable_actions'	=> array(),
		'autoinsert_enable_pagetypes'	=> array( 
			'is_home' => false,
			'is_archive' => false,
			'is_404' => false,
			'is_search' => false,		
		),
		'autoinsert_enable_posttypes'	=> array( 'post' => false, 'page' => false ),
		'autoinsert_enable_categories'	=> array(),	// the category ID, in most cases 1 is Uncategoriezed.
		'autoinsert_enable_post_ids'	=> array(),	
		'autoinsert_disable_pagetypes'	=> array( 
			'is_home' => false,
			'is_archive' => false,
			'is_404' => false,
			'is_search' => false,		
		),
		'autoinsert_disable_posttypes'	=> array( 'post' => false, 'page' => false ),
		'autoinsert_disable_categories'	=> array(),	
		'autoinsert_disable_post_ids'	=> array(),	
		// since 1.1.1.2
		'remove_id_attributes' => true,	
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
			'allowedhtmltags' => array(),		// e.g. array( 'noscript', 'style' ) - will be imploded when it is rendered
			'license' =>'',
			'memory_allocation' => 0,	// since 1.0.7.1 - 0 means do nothing.
			'general_css_timimng_to_load' => 0,	// since 1.1.0 - 0: head, 1 : first box
			'general_css_areas_to_load' => array(	// since 1.1.0
				'regular' => true,	
				'login' => true,
				'admin' => true,
			),
			'general_css_class_attributes' => array(	// since 1.1.0
				'box' => 'responsive_column_widgets_box',
				'row' => 'responsive_column_widgets_row',
				'column' => 'responsive_column_widgets_column',
			),
			'has_reviewed' => false,	 // since 1.1.1.2
			'time_first_option_update' => null,	// since 1.1.1.2 - set it null so that isset() can be used.
		),
	);
	function __construct( $strOptionKey, $strFilePath=null ) {
	
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
		$this->oInfo = new ResponsiveColumnWidgets_PluginInfo( $strFilePath );		
					
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
	public function ConvertOptionArrayValueToString( $vInput, $arrGlues=array( ', ', ': ' ) ) {	// must be public as the core class uses it from instantiated objects.
		
		// since 1.0.6.1
		// Converts the option value with the type of array into string.
		
		if ( ! is_array( $vInput ) ) return $vInput;
		
		return $this->ImplodeRecursive( $vInput, $arrGlues );
		
	}

	/*
	 *  Methods for format & sanitize column array. Used by the core class and the admin page class.
 	 */
	public function IsOneColumm( $arrColumns ) {		// since 1.1.1, used not only by this class but also by the admin page class. So it must be public.
	
		// Determines whether the passed column array yeilds one.

		$arrResult = array_diff( array_unique( $arrColumns ), array( 1 ) );
		return empty( $arrResult ); // if it's not empty, it means it's different. Otherwise, it's the same and therefore, it's one.
		
	}
	public function SetMinimiumScreenMaxWidth( $arrSubject ) {	// since 1.1.1

		// If the user does not specify the screen max-width, by default the format method will add 600px for it.
		// However, if the user set it by themselves but do not set the column number that is to be one, it will not be a perfect responsive design;
		// even though the browser width is diminished, the columns remain multiple.
		// There shuold be a safe guard to force the minimum number of the columns at some point. Let's make it 240px which should be narrow enough 
		// for most browsers to have mere single column.
		
		$intMinimumScreenMaxWidth = 240;
		$arrSanitize = array();
		$intLeastWidth = 0;
		$bIsThereOneColumn = false;
		
		foreach( $arrSubject as $intScreenMaxWidth => $arrColumns ) {
			
			if ( $intScreenMaxWidth == 0 ) {	// no problem
				
				$arrSanitize[0] = $arrColumns;
				$bIsThereOneColumn = $this->IsOneColumm( $arrColumns );
				continue;
				
			}
			
			if ( $intScreenMaxWidth >= $intMinimumScreenMaxWidth ) {	// no problem
				
				$arrSanitize[ $intScreenMaxWidth ] = $arrColumns;
				
				// updated the set least max-width.
				if ( $intLeastWidth >= $intScreenMaxWidth ) $intLeastWidth = $intScreenMaxWidth;
					
				$bIsThereOneColumn = $this->IsOneColumm( $arrColumns );
				continue;
				
			}
			
			// Okay, now there is a problem that the set screen max-width is too small. So make it to the minimum.
			$arrSanitize[ $intMinimumScreenMaxWidth ] = array( 1 );
			$bIsThereOneColumn = true;
			$intLeastWidth = $intMinimumScreenMaxWidth;
			
		}
		
		if ( ! $bIsThereOneColumn && $intLeastWidth != 0 && $intLeastWidth >= $intMinimumScreenMaxWidth )
			$arrSanitize[ $intMinimumScreenMaxWidth ] = array( 1 );
			
		return $arrSanitize;
		
	}	
	public function IsFormattedColumnArray( $vInput ) {	// since 1.1.1, used not only by this class itself but also by the admin page class. So must be public.
		
		// Determins whether the given value is formatted correctly for the plugin to output the widget buffers.
		// Returns true if it's okay; othewise false.
		
		if ( is_array( $vInput ) && $this->CountArrayDimension( $vInput ) == 2 ) return true;
			
		return false;
		
	}
	protected function SanitizeColumnArray( $arrColumnsInput ) {	// since 1.1.1
		
		/*
		 * Column array sanitization
		 * Step 1. each delimited element must not be empty
		 * Step 2. each column number must be within 1 to 12 and empty elements are not allowed.

			Array
			(
				[600] => Array
					(
						[0] => 1
					)
				[0] => Array
					(
						[0] => 3
						[1] => 4
						[2] => 1
					)					
			) 
		*/			
	
		foreach( $arrColumnsInput as $intScreenMaxWidth => &$arrColumns ) {
			
			if ( is_string( $arrColumns ) )
				$arrColumns = $this->ConvertStringToArray( $arrColumns );
							
			$arrColumns = $this->FixNumbers( $arrColumns, 
				$this->arrDefaultParams['columns'][0], // should be 3
				1, 
				12 
			);
			
		}	
			
		return $arrColumnsInput;
		
	}	
	public function FormatColumnArray( $vInput, $intDefaultScreenMaxWidth=600 ) {	// since 1.1.1
		
		// The returning array.
		$arrMaxColsByPixel = array();	

		/*	
		 * 	Format Validation
		 * Consider the following cases that $vInput is :
		 * 1. a new type two-dimensional array which has the | and : separators and its dimension.
		 * 2. an old type one-dimensional array which does not have the | and : separators and its dimension.
		 * 3. a string passed from the shortcode
		 */
		
		// Case 1: return the sanitized column array.
		if ( $this->IsFormattedColumnArray( $vInput ) ) return $this->SanitizeColumnArray( $vInput );
		
		// Case 2: array( 2, 5, 3 ) -> 2, 5, 3
		if ( is_array( $vInput ) )
			$vInput = $this->ConvertOptionArrayValueToString( $vInput );	// now $vInput becomes a string
		
		// Need to ensure it's a string because $vInput can be am already correctly formatted array, passed from the options.
		//	'4, 5, 1 | 480: 3, 4, 1' -> array( 0 => array( 0 => '4, 5, 1' ), 1 => array( 0 => 480, 1 => '3, 4, 1' ) )
		if ( is_string( $vInput ) ) { 	// Case 3
		
			$arrParse = $this->ConvertStringToArray( $vInput, '|', ':' );			
			
		}
		else 
			return array( 0 => array( 3 ), 600 => array( 1 ) );	// returns the default value.
	
		// If the pixel width is not set or only one set of column numbers is set whose screen max-width is less than 600px,
		// apply the default max width ( 600 pixels to one column by default set in the $intDefaultScreenMaxWidth variable ).
		// Note that at this point, the array is not formatted yet but only adding necessary elements to create necessary keys in the next steps.
		if ( count( $arrParse ) == 1 ) {	// the number of elements is one 
						
			$arrFirstElement = array_shift( array_values( $arrParse ) );
			$intCount = count( $arrFirstElement );
			
			if ( $intCount == 1 )	// this means the width is not set.
				$arrParse[] = array( 0 => $intDefaultScreenMaxWidth, 1 => 1 );	// means in 600 pixel width, the number of columns becomes one.
				
			// If only one pixel width is set, in that case, no-limit width needs to be set. Set the same column number then.
			// This happens when the value is like 800: 3, 2 and no pipe is used.
			if ( $intCount == 2 ) 
				$arrParse[] = array( 0 => $arrFirstElement[1] );	// $arrFirstElement[0] is the screen max-width.
 			
			// If the set screen max-width is greater than the default least max-width (600px), then as safe-guard add 600: 1. 
			if ( $intCount == 2 && $arrFirstElement[0] > $intDefaultScreenMaxWidth )
				$arrParse[] = array( 0 => $intDefaultScreenMaxWidth, 1 => 1 );	 // 600 : 1
				
		}
		/*	
		 * 	At this point the array structure looks like the following.
			Array
			(
				[0] => Array
					(
						[0] => 3, 4, ,1
					)

				[1] => Array
					(
						[0] => 600
						[1] => 1
					)
			)
			Now we need to make it like this:
			Array
			(
				[600] => Array
					(
						[0] => 1
					)
				[0] => Array
					(
						[0] => 3
						[1] => 4
						[2] => 1
					)					
			)
		*/

		/*
		 * Now format the array.
		*/
		
		// Add the max-width pixel size if missing
		foreach ( $arrParse as &$arrMaxCols ) {
			
			if ( count( $arrMaxCols ) == 1 )	// means the width key is missing.
				array_unshift( $arrMaxCols, 0 );	// add the zeo value to the first element.
				
			// *Applying trim() to the key is necessary for some inputs, which are not sanitized.
			$intMaxScreenWidth = trim( $arrMaxCols[0] ); 
			
			if ( ! is_numeric( $intMaxScreenWidth ) ) {	// broken input
				
				$arrMaxColsByPixel[ 0 ] = $this->ConvertStringToArray( $arrMaxCols[1] );
				$arrMaxColsByPixel[ $intDefaultScreenMaxWidth ] = array( 1 );
				continue;
				
			}
			
			$arrMaxColsByPixel[ $intMaxScreenWidth ] = $this->ConvertStringToArray( $arrMaxCols[1] );	
		
		}
			
		// Sort by descending order	
		krsort( $arrMaxColsByPixel );		
		
		return $this->SanitizeColumnArray( $arrMaxColsByPixel );
		
	}	
	
	/*
	 * Public Utilities - helper methods which can be used outside the plugin
	 * */
	public function SanitizeAttribute( $strAttr ) {	// since 1.1.1.1, moved from the core class
		
		return preg_replace( '/[^a-zA-Z0-9_\x7f-\xff\-\.]/', '_', $strAttr );
		
	}	 
	public function FindLowestKey( $arr ) {	// since 1.1.1
		
		if ( empty( $arr ) ) return 0;
		
		return min( array_keys( $arr ) ); 
		
	}	 
	public function PrependArrayElement( &$arr, $key, $v ) { 	// since 1.1.1
	
		$arr = array_reverse( $arr, true ); 
		$arr[$key] = $v; 
		return array_reverse( $arr, true ); 
		
	} 	 
	public function GetNextArrayKey( $arr, $strSubjectKey ) {	// since 1.1.1, used in the admin page class
		
		$bMatched = false;
		foreach ( $arr as $strKey => $v ) {
			
			if ( $bMatched ) return $strKey;
				
			if ( $strKey == $strSubjectKey ) $bMatched = true;
			
		}
		
	}
	public function FixNumbers( $arrNumbers, $numDefault, $numMin="", $numMax="" ) {	// since 1.1.1
		
		// An array version of FixNumber(). The array must be numerically indexed.
		
		foreach( $arrNumbers as &$intNumber )
			$intNumber = $this->FixNumber( $intNumber, $numDefault, $numMin, $numMax );
		
		return $arrNumbers;
		
	}	
	public function FixNumber( $numToFix, $numDefault, $numMin="", $numMax="" ) {	// since 1.1.1
	
		// Checks if the passed value is a number and set it to the default if not.
		// if it is a number and exceeds the set maximum number, it sets it to the max value.
		// if it is a number and is below the minimum number, it sets to the minimium value.
		// set a blank value for no limit.
		// This is useful for form data validation.
		
		if ( !is_numeric( trim( $numToFix ) ) ) return $numDefault;
			
		if ( $numMin != "" && $numToFix < $numMin) return $numMin;
			
		if ( $numMax != "" && $numToFix > $numMax ) return $numMax;

		return $numToFix;
		
	}	 
	public function CountArrayDimension( $arr ) {	// since 1.1.1
		
		// by m227(a)poczta.onet.pl at http://pt.php.net/manual/en/ref.array.php#49219
		if ( is_array( reset( $arr ) ) )
			$intCount = $this->CountArrayDimension( reset( $arr ) ) + 1;
		else 
			$intCount = 1;

		return $intCount;
				
	}
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
	public function ConvertStringToArray( $strInput, $strDelim=',', $strDelim2='' ) {
		
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
	public function UniteArraysRecursive( $arrPrecedence, $arrDefault ) {
		
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