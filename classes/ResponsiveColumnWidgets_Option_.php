<?php
class ResponsiveColumnWidgets_Option_ {

	public $arrDefaultParams = array(		// must be public; accessed in the core object.
		'columns' => 3,		// set the default to 3 since 1.0.3
		'sidebar' => 'responsive_column_widgets',
		'label' => 'Responsive Column Widgets',
		'maxwidgets' => 0,
		'maxrows' => 0,
		'omit' => '',
		'showonly' => '',
		'offsets' => '600: 12', //'800: 1, 600: 2, 480: 3, 320: 4, 240: 5',	// added since 1.0.3
	);
	public $arrDefaultSidebarArgs = array(	// must be public; accessed in the core object for register_sidebar()
		'description' 		=> '',
		'before_widget'		=> '<aside id="%1%s" class="%2$s"><div class="widget">',
		'after_widget'		=> '</div></aside>',
		'before_title'		=> '<h3 class="widget-title">',
		'after_title'		=> '</h3>',
		'message_no_widget'	=> 'No widget added yet.',
	);	
	public $arrCapabilities = array(	// used in the drop-down list of the General Options page.
		0 => 'manage_options',
		1 => 'edit_pages',
		2 => 'publish_posts',
		3 => 'edit_posts',
		4 => 'read'
	);
	protected $arrDefaultOptionStructure = array(
		'boxes' => array(),
		'general' => array(
			'capability' => 0,
			'allowedhtmltags' =>'',
		),
	);
	function __construct( $strOptionKey ) {
	
		$this->strOptionKey = $strOptionKey;
		$this->arrOptions = ( array ) get_option( $strOptionKey );
		
		// Merge with the default values.
		$this->arrDefaultSidebarArgs['description'] = __( 'The default widget box of Responsive Column Widgets.', 'responsive-column-windgets' );	// cannot be declared as the default property because it needs to use a custom function.
		
		
		// $this->arrOptions = $this->arrOptions + $this->arrDefaultOptionStructure;	// $this->arrOptions = $this->array_replace_recursive( $this->arrDefaultOptionStructure, $this->arrOptions );
		$this->arrOptions = wp_parse_args( $this->arrOptions, $this->arrDefaultOptionStructure );	// $this->arrOptions = $this->array_replace_recursive( $this->arrDefaultOptionStructure, $this->arrOptions );
		
		$arrDefaultBoxParams = $this->arrDefaultSidebarArgs + $this->arrDefaultParams;
		// $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] = isset( $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] ) ? $this->arrOptions['boxes'][$this->arrDefaultParams['sidebar']] + $arrDefaultBoxParams : $arrDefaultBoxParams;
		$this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] = wp_parse_args( $this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ], $arrDefaultBoxParams );
	
		// store plugin data
		if ( !function_exists( 'get_plugin_data' )  ) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );	
		$this->arrPluginData = get_plugin_data( RESPONSIVECOLUMNWIDGETSFILE, false );
		if ( defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ) 
			$this->arrPluginDataPro = get_plugin_data( RESPONSIVECOLUMNWIDGETSPROFILE, false );
			
	}
	
	function Update() {
		
		update_option( $this->strOptionKey, $this->arrOptions );
		
	}
	
	function InsertBox( $strSidebarID, $arrBoxOptions ) {
		
		$this->arrOptions['boxes'][ $this->arrDefaultParams['sidebar'] ] = $arrBoxOptions;
		
	}
		
}