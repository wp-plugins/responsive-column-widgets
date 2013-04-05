<?php
class ResponsiveColumnWidgets_Admin_Page_ extends ResponsiveColumnWidgets_Admin_Page_Framework {

	// Properties
	protected $strPluginName = 'Responsive Column Widgets';
	protected $strPluginSlug = 'responsive_column_widgets';
	protected $arrRecentlyAddedOptionKeys = array(	// used with the CheckKeys() method to allow missing keys when an array is validated	
		'insert_comment_form',					// since 1.0.8
		'insert_comment_form_positions', 		// since 1.0.8
		'insert_comment_form_disable_front',	// since 1.0.8
		'insert_comment_form_disable_post_ids',	// since 1.0.8
	);
	
	// Flags
	protected $bIsNew;

	// Objects
	public $oOption;	// stores the option object. It is set via the SetOptionObject() method.
	protected $oWidgetPage;
	
	function start_ResponsiveColumnWidgets_Admin_Page() {
							
		$this->Localize();
		
		$this->AddLinkToPluginDescription( $this->GetPluginDescriptionLinks() );				
		
		$this->oUserAds = new ResponsiveColumnWidgets_UserAds;
		
		if ( isset( $_GET['view'] ) )
			add_action( 'admin_init', array( $this, 'EnqueueAdminStyle' ) );
			
		$this->strGetPro = __( 'Get Pro to enabel this feature!', 'responsive-column-widgets' );
		$this->strGetProNow = __( 'Get Pro Now!', 'responsive-column-widgets' );
		
		
	}
	function Localize() {
		
		$this->bLoadedTextDomain = load_plugin_textdomain( 
			'responsive-column-widgets', 
			false, 
			dirname( plugin_basename( RESPONSIVECOLUMNWIDGETSFILE ) ) . '/lang/'
		);
		$this->bLoadedTextDomain = load_plugin_textdomain( 
			'admin-page-framework', 
			false, 
			dirname( plugin_basename( RESPONSIVECOLUMNWIDGETSFILE ) ) . '/lang/'
		);		
		
	}	
	
	function EnqueueAdminStyle() {
		
		wp_enqueue_style( 'responsive_column_widgets_enqueue_style', RESPONSIVECOLUMNWIDGETSURL . '/css/responsive_column_widgets.css' );
	
	}
	function GetPluginDescriptionLinks() {
		return array(
			'<a href="http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __( 'Get Pro', 'responsive-column-widgets' ) . '</a>',
			'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=J4UJHETVAZX34">' . __( 'Donate', 'responsive-column-widgets' ) . '</a>',
			'<a href="http://en.michaeluno.jp/contact/custom-order/?lang=' . ( WPLANG ? WPLANG : 'en' ) . '">' . __( 'Order custom plugin', 'responsive-column-widgets' ) . '</a>',
		);
	}

	
    function SetUp() {
		
		// Set the access rights to the option page.
		$numCapability = $this->oOption->arrOptions['general']['capability'];
		$this->SetCapability( $this->oOption->arrCapabilities[ $numCapability ? $numCapability : 0 ] );

		// if ( WP_DEBUG )
			// $this->SetCapability( 'read' );
		
		// Build menu and pages
        $this->SetRootMenu( 'Appearance' );          // specifies to which parent menu to belong.
        $this->AddSubMenu(  
			$this->strPluginName,    // page and menu title
			$this->strPluginSlug 	// page slug
		);	 
		
		// Add in-page tabs in the third page.			
		$this->AddInPageTabs( $this->strPluginSlug,	
			array(	// slug => title
				// 'widgets'		=> __( 'Widgets', 'responsive-column-widgets' ),
				'neworedit' 	=> '<span class="newtab">' . __( 'New', 'responsive-column-widgets' ) . '</span>&nbsp;<span class="slash">/</span>&nbsp;' . __( 'Edit', 'responsive-column-widgets' ),
				'manage'		=> __( 'Manage', 'responsive-column-widgets' ),
				'general'		=> __( 'General Options', 'responsive-column-widgets' ),
				'information'	=> __( 'Information', 'responsive-column-widgets' ),
				'getpro'		=> __( 'Get Pro!', 'responsive-column-widgets' ),
			)
		);			
		
		// Determine which widget box it is.
		$strSidebarID = $this->DetermineCurrentSidebarToEdit();	// the returned value can be empty.

		// Setup the box options - in case new keys are added in newer version and old saved data do not have them, merge the array keys.
		if ( isset( $this->oOption->arrOptions['boxes'][ $strSidebarID ] ) ) {		
		
			$arrDefaultBoxParams = $this->oOption->arrDefaultSidebarArgs + $this->oOption->arrDefaultParams; 
			$this->oOption->arrOptions['boxes'][ $strSidebarID ] = $this->oOption->arrOptions['boxes'][ $strSidebarID ] + $arrDefaultBoxParams;
	
		}
				
		// Determine whether it is the New or Edit page.
		$this->bIsNew =  empty( $strSidebarID ) || ! isset( $this->oOption->arrOptions['boxes'][ $strSidebarID ] )  ? true : false;
		$bIsNew = $this->bIsNew;
		$arrWidgetBoxDefaultOptions = $this->oOption->arrDefaultSidebarArgs + $this->oOption->arrDefaultParams;
		$arrWidgetBoxDefaultOptions['message_no_widget'] = __( 'No widgetd is added yet.', 'responsive-column-widgets' ); 
		$arrWidgetBoxOptions = $bIsNew ? $arrWidgetBoxDefaultOptions : $this->oUtil->UniteArraysRecursive( $this->oOption->arrOptions['boxes'][ $strSidebarID ], $arrWidgetBoxDefaultOptions );
			
		// Add the form elements.
		$this->AddFormSections(
			// Section Arrays
			array( 				
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_sidebar',
					'title' => $bIsNew ? __( 'Add New Widget Box', 'responsive-column-widgets' ) : __( 'Edit Widget Box', 'responsive-column-widgets' ), 
					'fields' => array(
						array(
							'id' => 'label',
							'title' => __( 'Widget Box Label', 'responsive-column-widgets' ),
							'description' => __( 'Set a unique name for the widget box.', 'responsive-column-widgets' ),
							'error' => __( 'The label neither cannot be empty nor use the same one that already exists.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 60,
							'class' => $this->numPluginType == 0 || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? 'disabled' : '',
							'disable' => $this->numPluginType == 0 || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? true : false,
							'value' => $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['label'] : (  $bIsNew  ? '' : $this->oOption->arrOptions['boxes'][ $strSidebarID ]['label'] ),
							'post_html' => "<input type='hidden' name='isnew' value='{$bIsNew}' />",
						),
						array(
							'id' => 'sidebar',
							'title' => __( 'Widget Box Sidebar ID', 'responsive-column-widgets' ),
							'tip' => __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
							'description' => $bIsNew 
								? __( 'A new ID will be automatically generated.', 'responsive-column-widgets' ) 
								: __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 60,
							'readonly' => true,
							'class' => 'disabled',
							'value' => $bIsNew 
								? '' 
								: ( $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['sidebar'] : $strSidebarID ),
						),						
						array(
							'id' => 'description',
							'title' => __( 'Widget Box Description', 'responsive-column-widgets' ),
							'description' => __( 'Additional notes for this box.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['description'],
						),		
						array(
							'id' => 'before_widget',
							'title' => __( 'Widget Beginning Tag', 'responsive-column-widgets' ),
							'description' => __( 'Set the before_widget html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['before_widget'],
						),
						array(
							'id' => 'after_widget',
							'title' => __( 'Widget Ending Tag', 'responsive-column-widgets' ),
							'description' => __( 'Set the after_widget html closing tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['after_widget'],
						),
						array(
							'id' => 'before_title',
							'title' => __( 'Starting Tag for Box Title', 'responsive-column-widgets' ),
							'description' => __( 'Set the before_title html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['before_title'],
						),
						array(
							'id' => 'after_title',
							'title' => __( 'Ending Tag for Box Title', 'responsive-column-widgets' ),
							'description' => __( 'Set the after_title html closing tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['after_title'],
						),
						array(
							'id' => 'message_no_widget',
							'title' => __( 'Message for No Widget', 'responsive-column-widgets' ),
							'description' => __( 'Set the message which appears when no widget is added; thus, nothing can be rendered.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 100,
							'value' => $arrWidgetBoxOptions['message_no_widget'], 
						),						
					),
				),
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_params',
					'title' => __( 'Widget Box Parameter Values', 'responsive-column-widgets' ), 
					'fields' => array(
						array(
							'id' => 'columns',
							'title' => __( 'Numbers of Columns', 'responsive-column-widgets' ),
							'description' => __( 'Set the number of columns separated by commnas. Each delimited element number corresponds to the order number of the rows.', 'responsive-column-widgets' ) 
								. __( 'Min', 'responsive-column-widgets' ) . ' 1 '
								. __( 'Min', 'responsive-column-widgets' ) . ' 12 '
								. __( '( for each row )', 'responsive-column-widgets' ) . ' '
								. 'e.g. 4, 2, 3',
							'type' => 'text',	// must not be number because it's a string containing a sequence of numbers with commas.
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'columns' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['columns'] ),
						),		
						array(
							'id' => 'maxwidgets',
							'title' => __( 'Max Number of Widgets', 'responsive-column-widgets' ),
							'description' => __( 'Set the max number of widgets. 0 for no limit.', 'responsive-column-widgets' ) . ' e.g. 10',
							'type' => 'number',
							'min' => 0,
							'value' => $arrWidgetBoxOptions['maxwidgets'],
						),	
						array(
							'id' => 'maxrows',
							'title' => __( 'Max Number of Rows', 'responsive-column-widgets' ),
							'description' => __( 'Set the max number of rows. 0 for no limit.', 'responsive-column-widgets' ) . ' e.g. 2',
							'type' => 'number',
							'min' => 0,
							'value' => $arrWidgetBoxOptions['maxrows'], 
						),	
						array(
							'id' => 'omit',
							'title' => __( 'Omitting Widgets', 'responsive-column-widgets' ),
							'size' => 100,
							'description' => __( 'Set the numbers of omitting widgets separated by commas.', 'responsive-column-widgets' ) 
								. ' e.g. "2, 5, 8" ' . __( 'where the second, the fifth, and the eighth ones will be skipped.', 'responsive-column-widgets' ),
							'type' => 'text',
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'omit' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['omit'] ),
						),	
						array(
							'id' => 'showonly',
							'title' => __( 'Show-only Widgets', 'responsive-column-widgets' ),
							'size' => 100,
							'description' => __( 'Set the numbers of show-only widgets separated by commas.', 'responsive-column-widgets' ) 
								. ' e.g. "1, 3" ' . __( 'where only the first and the third ones will be shown.', 'responsive-column-widgets' ),
							'type' => 'text',
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'showonly' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['showonly'] ),
						),	
						array(
							'id' => 'offsets',
							'title' => __( 'Width Percentage Offsets', 'responsive-column-widgets' ),
							'description' => __( 'Set the offsets for width percentage. The higher the offset nubmer is, the less will the number of clummns be displayed.', 'responsive-column-widgets' ) . ' '
								. __( 'Format', 'responsive-column-widgets' ) . ': ' . __( 'Pixel: Offset, Pixel: Offset, ....', 'responsive-column-widgets' ) . ' '
								. 'e.g. 600:3, 480:4, 400:5',
							'type' => 'text',
							'size' => 100,
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'offsets' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['offsets'] ),
							
						),							
						array(  // single button
							'pre_html' => '<div class="text-info">' . $this->oUserAds->GetTextAd() . '</div>',
							'id' => 'submit_save_neworedit_middle',
							'type' => 'submit',		// the submit type creates a button
							'label' => $this->numPluginType == 0 || isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ? __( 'Save Changes', 'responsive-column-widgets' ) : __( 'Add New Box', 'responsive-column-widgets' ),
							'class' => 'neworedit-button submit-buttons button button-primary',
							'pre_field' => '<div class="neworedit-button">',
							'post_field' => '</div>',							
							'redirect' => admin_url( "admin.php?page={$this->strPluginSlug}&tab=manage&updated=true" ),
						),							
					),					
				),
				// Insert Widget Box into Footer
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_insert_footer',
					'title' => __( 'Insert Widget Box into Footer', 'responsive-column-widgets' ), 
					'fields' => array(
						array(
							'id' => 'footer',
							'title' => __( 'Footer', 'responsive-column-widgets' ),
							'tip' => __( 'Insert the widget box into the footer.', 'responsive-column-widgets' ),
							'label' => __( 'Insert the widget box into the footer.', 'responsive-column-widgets' ),
							'type' => 'checkbox',
							'value' => $arrWidgetBoxOptions['insert_footer'],
							'post_html' => '<p><span class="description">' . __( 'The below options will not take effect unless this is checked.', 'responsive-column-widgets' ) . '</span></p>',
						),
						array(	// since 1.0.7
							'id' => 'insert_footer_disable_front',
							'type' => 'checkbox',
							'title' => __( 'Disable the Widget Box in', 'responsive-column-widgets' ),
							'label' => __( 'Home / Front Page', 'responsive-column-widgets' ),
							'pre_html' => '<span title="' . $this->strGetPro . '">',
							'post_html' => '</span>',											
							'value' => $arrWidgetBoxOptions['insert_footer_disable_front'], 
						),							
						array(	// since 1.0.7
							'id' => 'insert_footer_disable_ids',
							'type' => 'text',
							'size' => 100,
							'title' => __( 'Post / Page ID to Disable the Widget Box', 'responsive-column-widgets' ),
							'description' => __( 'Enter the post IDs where the widget box should not be displayed, separated by commas. This will take effects if the above checkbox option at the top is checked in this section.', 'responsive-column-widgets' )
								. '<br />e.g. 98, 76, 5',
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'insert_footer_disable_ids' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['insert_footer_disable_ids'] ),							
							'pre_html' => '<span title="' . $this->strGetPro . '">',
							'post_html' => '</span>',								
						),		
					)
				),
				// Insert Widget Box into Posts and Pages
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_insert_posts',
					'title' => __( 'Insert Widget Box into Posts and Pages', 'responsive-column-widgets' ), 
					'fields' => array(				
						array(	// since 1.0.7
							'id' => 'insert_posts',
							'title' => __( 'Posts and Pages', 'responsive-column-widgets' ),
							'tip' => __( 'Insert the widget box into posts and pages.', 'responsive-column-widgets' ),
							'label' => array(
								'post' => __( 'Insert the widget box into posts.', 'responsive-column-widgets' ),
								'page' => __( 'Insert the widget box into pages.', 'responsive-column-widgets' ),
							),
							'type' => 'checkbox',
							'value' => $arrWidgetBoxOptions['insert_posts'],
							'post_html' => '<p><span class="description">' . __( 'The below options will not take effect unless one of these is checked.', 'responsive-column-widgets' ) . '</span></p>',
						),	
						array(	// since 1.0.7
							'id' => 'insert_posts_positions',
							'type' => 'checkbox',
							'title' => __( 'Position', 'responsive-column-widgets' ),
							'label' => array(
								'above' => __( 'Above Content', 'responsive-column-widgets' ),
								'below' => __( 'Below Content', 'responsive-column-widgets' ),
							),
							'delimiter' => '&nbsp;&nbsp;&nbsp;',
							'value' => $arrWidgetBoxOptions['insert_posts_positions'],							
						),	
						array(	// since 1.0.7
							'id' => 'insert_posts_disable_front',
							'type' => 'checkbox',
							'title' => __( 'Disable the Widget Box in', 'responsive-column-widgets' ),
							'label' => __( 'Home / Front Page', 'responsive-column-widgets' ),
							'value' => $arrWidgetBoxOptions['insert_posts_disable_front'],						
						),							
						array(	// since 1.0.7
							'id' => 'insert_posts_disable_ids',
							'type' => 'text',
							'title' => __( 'Post / Page ID to Disable the Widget Box', 'responsive-column-widgets' ),
							'size' => 100,
							'description' => __( 'Enter the post IDs where the widget box should not be displayed, separated by commas. This will take effects if the above checkbox option at the top is checked in this section.', 'responsive-column-widgets' )
								. '<br />e.g. 98, 76, 5',
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'insert_posts_disable_ids' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['insert_posts_disable_ids'] ),						
						),							
					),
				),
				// Insert Widget Box into Comment Form Section - since 1.0.8
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_insert_comment_form',
					'title' => __( 'Insert Widget Box into Comment Form Section', 'responsive-column-widgets' ), 
					'fields' => array(				
						array(	// since 1.0.8
							'id' => 'insert_comment_form',
							'title' => __( 'Comment Form Section', 'responsive-column-widgets' ),
							'tip' => __( 'Insert the widget box into the comment form section.', 'responsive-column-widgets' ),
							'label' => __( 'Insert widget box into the comment form section.', 'responsive-column-widgets' ),
							'type' => 'checkbox',
							'value' => $arrWidgetBoxOptions['insert_comment_form'],
							'post_html' => '<p><span class="description">' . __( 'The below options will not take effect unless this is checked.', 'responsive-column-widgets' ) . '</span></p>',
						),	
						array(	// since 1.0.8
							'id' => 'insert_comment_form_positions',
							'type' => 'checkbox',
							'title' => __( 'Position', 'responsive-column-widgets' ),
							'label' => array(
								'above' => __( 'Above Comment Form', 'responsive-column-widgets' ),
								'below' => __( 'Below Comment Form', 'responsive-column-widgets' ),
							),
							'delimiter' => '&nbsp;&nbsp;&nbsp;',
							'value' => $arrWidgetBoxOptions['insert_comment_form_positions'],							
						),	
						array(	// since 1.0.8
							'id' => 'insert_comment_form_disable_front',
							'type' => 'checkbox',
							'title' => __( 'Disable the Widget Box in', 'responsive-column-widgets' ),
							'label' => __( 'Home / Front Page', 'responsive-column-widgets' ),
							'value' => $arrWidgetBoxOptions['insert_comment_form_disable_front'],						
						),							
						array(	// since 1.0.8
							'id' => 'insert_comment_form_disable_post_ids',
							'type' => 'text',
							'title' => __( 'Post / Page ID to Disable the Widget Box', 'responsive-column-widgets' ),
							'size' => 100,
							'description' => __( 'Enter the post IDs where the widget box should not be displayed, separated by commas. This will take effects if the above checkbox option at the top is checked in this section.', 'responsive-column-widgets' )
								. '<br />e.g. 98, 76, 5',
							'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'insert_comment_form_disable_post_ids' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['insert_comment_form_disable_post_ids'] ),						
						),							
					),
				),				
				// Custom Style
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_custom_style',
					'title' => __( 'Custom Style', 'responsive-column-widgets' ), 
					'fields' => array(
						array(
							'id' => 'custom_style',
							'title' => __( 'CSS Rule', 'responsive-column-widgets' ),
							'description' => __( 'Define your custom CSS rules here.', 'responsive-column-widgets' ) . '<br />'
								. 'e.g. ' . esc_html( '.responsive_column_widgets_box .widget { padding: 0 20px 0 20px; }' ),
							'type' => 'textarea',
							'cols' => 120,
							'rows' => 6,
							'value' => $arrWidgetBoxOptions['custom_style'],
							// 'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['custom_style'] : ( isset( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['custom_style'] ) ? $this->oOption->arrOptions['boxes'][ $strSidebarID ]['custom_style'] : ''  ),
						),
						array(  // single button
							'pre_html' => $this->oUserAds->GetTextAd(),
							'id' => 'submit_save_neworedit_bottom',
							'type' => 'submit',		// the submit type creates a button
							'label' => $this->numPluginType == 0 || isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ? __( 'Save Changes', 'responsive-column-widgets' ) : __( 'Add New Box', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-primary',
							'pre_field' => '<div class="neworedit-button">',
							'post_field' => '</div>',
							'redirect' => admin_url( "admin.php?page={$this->strPluginSlug}&tab=manage&updated=true" ),
						),							
					),
				),				
				// General Options
				array(  
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'general',
					'id' => 'section_general', 
					'title' => __( 'General Options', 'responsive-column-widgets' ), 
					// 'description' => __( '', 'responsive-column-widgets' ),
					'fields' => array( 	// Field Arrays
						// Dropdown List
						array(  
							'capability' => 'manage_options',
							'id' => 'capability',
							'title' => __( 'Access Rights', 'responsive-column-widgets' ),
							'description' => __( 'Set the access level to this setting pages.', 'responsive-column-widgets' ),
							'type' => 'select',
							'default' => 0,
							'label' => array( 
								__( 'Administrator', 'responsive-column-widgets' ),
								__( 'Editor', 'responsive-column-widgets' ),
								__( 'Author', 'responsive-column-widgets' ),
								__( 'Contributor', 'responsive-column-widgets' ),
								__( 'Subscriber', 'responsive-column-widgets' ),
							)
						),
						array(
							'capability' => 'manage_options',
							'id' => 'allowedhtmltags',
							'title' => __( 'Additional Allowed HTML Tags', 'responsive-column-widgets' ),
							'description' => __( 'Specify which HTML tags are allowed to be posted in the New / Edit page to prevent them from being stripped out by the WordPress KSES filter, separated by commas. For security, many tags are not allowed by default.', 'responsive-column-widgets' ) . ' '
								. 'e.g. "noscript, style"',
							'type' => 'text',
							'size' => 100,
							'value' => $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['general']['allowedhtmltags'] ), 
						),	
						array(  // single button
							'id' => 'submit_save_2',
							'type' => 'submit',		// the submit type creates a button
							'label' => __( 'Save Changes', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-primary'
						),						
					),
				),
				array(  
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'general',
					'id' => 'section_dangerzone', 
					'capability' => 'manage_options',
					'title' => __( 'Option Management', 'responsive-column-widgets' ), 
					'description' => __( 'Be carefult to perform these operations.', 'responsive-column-widgets' ),
					'fields' => array( 	// Field Arrays
						// Checkbox
						array(  
							'id' => 'memory_allocation',
							'title' => __( 'Attempt to Override Allocated Memory Size', 'responsive-column-widgets' ),
							'description' => __( 'If the error, "Allowed memory size of ... bytes exhausted" occurs, try increasing the memory size allocated for PHP. Set 0 to use the server\'s setting.', 'responsive-column-widgets' ) . '<br />'
								. __( 'The current memory limit set by the server:', 'responsive-column-widgets' ) . ' ' . $this->oOption->GetMemoryLimit() . '<br />'
								. ( ! function_exists( 'memory_get_usage' ) || ! function_exists( 'ini_get' ) ? '<span class="error">' . __( 'The necessary functions are disabled by the server.', 'responsive-column-widgets' ) . '</span>' : '' ),
							'type' => 'number',
							'min' => 0,
							'size' => 10,
							'pre_field' => '',
							'post_field' => ' M',
							'value' => $this->oOption->arrOptions['general']['memory_allocation'],
							'disable' => ! function_exists( 'memory_get_usage' ) || ! function_exists( 'ini_get' ) ? true : false,
							'label' => __( 'Initialize', 'responsive-column-widgets' ),
						),									
						// Checkbox
						array(  
							'id' => 'initializeoptions',
							'title' => __( 'Initialize Options', 'responsive-column-widgets' ),
							'description' => __( 'Clean all saved data and intialize to the default.', 'responsive-column-widgets' ),
							'type' => 'checkbox',
							'default' => 0,
							'label' => __( 'Initialize', 'responsive-column-widgets' ),
						),
						// Submit Button
						array(  // single button
							'id' => 'submit_perform',
							'type' => 'submit',		// the submit type creates a button
							'label' =>  __( 'Perform', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-secondary'
						),
					),
				),				
			)
		);
		$this->AddFormSections(
			// Section Arrays
			array( 				
				// Manage Options
				array(  
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'manage',
					'id' => 'section_buttons', 
					'title' => '', //__( 'Pro Settings', 'responsive-column-widgets' ), 
					// 'description' => __( '', 'responsive-column-widgets' ),
					'fields' => array( 	// Field Arrays
						array(  // single button
							'id' => 'submit_create_new',
							'type' => 'submit',		// the submit type creates a button
							'label' => __( 'Add New Box', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-primary',
							'pre_html' => '<span title="' . $this->strGetPro . '">',
							'post_html' => '</span>',							
							'disable' => true,
						),
						array(  // single button
							'id' => 'checkbox_table',
							'type' => 'custom',	
							'pre_html' => $this->GetWidgetBoxTable(),
						),						
						array(  // single button
							'id' => 'submit_delete',
							'type' => 'submit',		// the submit type creates a button
							'label' => __( 'Delete Checked', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-secondary',
							'pre_html' => '<span title="' . $this->strGetPro . '">',
							'post_html' => '</span>',
							'disable' => true,
						),	
						array(  // single button
							'id' => 'export_box_options',
							'type' => 'export',	
							'file_name' => RESPONSIVECOLUMNWIDGETSKEY . '_' . date("Ymd") . '.txt',
							'label' => array(
								__( 'Export All', 'responsive-column-widgets' ),
								__( 'Export Checked', 'responsive-column-widgets' ),
							),
							'delimiter' => '',
							'class' => 'export-button submit-buttons button button-primary',
							'pre_html' => '<span class="export" title="' . $this->strGetPro . '">',
							'post_html' => '</span>',
							'disable' => true,							
						),	
						array(  // single button
							'id' => 'import_box_options',
							'type' => 'import',	
							'label' => __( 'Import Widget Boxes', 'responsive-column-widgets' ),
							// 'class' => 'import-disabled', //'submit-buttons button button-primary',
							'pre_html' => '<span class="import" title="' . $this->strGetPro . '">',
							'post_html' => '</span>',
							'delimiter' => '',
							'disable' => true,							
						),							
					),
				),				
			)
		);	

		// If this is an edit page, check if a widget is added to this widget box; otherwise, show a warning message.
		if ( 
			isset( $_GET['page'] ) && ( $_GET['page'] == $this->strPluginSlug ) 
			&& isset( $_GET['tab'] ) && ( $_GET['tab'] == 'neworedit' ) 
			&& ! $bIsNew && ! is_active_sidebar( $strSidebarID ) 
		) 
			$this->SetSettingsNotice( 
				__( 'No widget has been added to this widget box yet.', 'responsive-column-widgets' ) . ' ' 
				. sprintf( __( "You need to add widgets in the <a href='%s'>Widgets</a> page to the widget box.", 'responsive-column-widgets' ), admin_url( 'widgets.php' ) )
			);			
    }
	
	/*
	 *  Custom Methods
	 */
	function UpdateFieldValuesToBeDisplayed( $strSidebarID ) {

    // [responsive_column_widgets] => Array
            // [section_sidebar] => Array
                // (
                    // [field_label] => 
                    // [field_description] => 
                    // [field_before_widget] => 
                    // [field_after_widget] => 
                    // [field_before_title] => 
                    // [field_after_title] => 
		if ( ! isset( $this->oOption->arrOptions['boxes'][$strSidebarID] ) ) return;
		$arrBoxOptions = $this->oOption->arrOptions['boxes'][$strSidebarID];		
		$arrAdminOptions = ( array ) get_option( RESPONSIVECOLUMNWIDGETSKEYADMIN );
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['label']			= $arrBoxOptions['label'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['sidebar']		= $strSidebarID;
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['description']	= $arrBoxOptions['description'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['before_widget']	= $arrBoxOptions['before_widget'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['after_widget']	= $arrBoxOptions['after_widget'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['before_title']	= $arrBoxOptions['before_title'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['after_title']	= $arrBoxOptions['after_title'];
		
		// Update the database so that the updated values will be displayed in the form fields
		update_option( RESPONSIVECOLUMNWIDGETSKEYADMIN, $arrAdminOptions );
		
	}	
	function IsTabNewOrEdit() {
		
		if ( ! isset( $_GET['tab'] ) ) return True; // new landing
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'neworedit' ) return True;
		
	}	
	function DetermineCurrentSidebarToEdit() {
		
		if ( $this->numPluginType == 0 ) return $this->oOption->arrDefaultParams['sidebar'];	// the default sidebar ID
		
		if ( isset( $_GET['sidebarid'] ) ) return trim( $_GET['sidebarid'] );
		
	}	
	function SetOptionObject( &$oOption ) {
		
		$this->oOption = $oOption;		
		
	}

	/*
	 * Modify the head and the foot parts
	 * */
	function head_ResponsiveColumnWidgets_Admin_Page( $strHead ) {
		
		// $strButton = isset( $_GET['tab'] ) && $_GET['tab'] == 'manage' ? $this->GetAddNewBoxButton() : '';
		return $this->oUserAds->GetTopBanner()
			. $strHead 
			. '<div class="responsive-column-widgets-admin-body">'
			. '<table border="0" cellpadding="0" cellspacing="0" unselectable="on" width="100%">
			<tbody>
			<tr>
			<td valign="top">'
			. $this->oUserAds->GetTextAd();
			// . $strButton;
			
	}
	function foot_ResponsiveColumnWidgets_Admin_Page( $strFoot ) {
		
		$numItems = isset( $_GET['tab'] ) && $_GET['tab'] == 'neworedit' ? 4 : 2;
		$numItems = isset( $_GET['tab'] ) && $_GET['tab'] == 'manage' ? 1 : $numItems;
		$numItems = isset( $_GET['tab'] ) && $_GET['tab'] == 'getpro' ? 2 : $numItems;
		return $strFoot 
			. '<div style="float:left; margin-top: 10px" >' 
			. $this->oUserAds->GetTextAd() 
			. '</div>'
			. '</td>
			<td valign="top" rowspan="2">' 
			. $this->oUserAds->GetSkyscraper( $numItems ) 
			. '</td>
			</tr>
			<tr>
				<td valign="bottom" align="center">'
			. $this->oUserAds->GetBottomBanner() 
			. '</td>
			</tr>
			</tbody>
			</table>'
			. '</div>';
			
	}
	
	/*
	 * Modify Page Body Part
	 * */
	function do_responsive_column_widgets_widgets() {
		
		// global $wp_registered_sidebars;
		// require_once( ABSPATH . 'wp-admin/widgets.php' );
		$this->oWidgetPage->RenderWidgetPage();
		
	}
	function do_responsive_column_widgets_manage() {

		// if ( WP_DEBUG )
			// echo $this->DumpArray( $this->oOption->arrOptions['boxes'] );
		
	}
	function do_responsive_column_widgets_general() {	
	}
	
	function GetAddNewBoxButton() {
		
		return '<div class="submit-buttons" style=""><span title="' . $this->strGetPro . '">'
			. $this->GetSubmitButton( 
				__( 'Add New Box', 'responsive-column-widgets' ),
				'button button-primary', 
				'', 
				'disabled="disabled"'
			)
			. '</span></div>';	
			
	}
	function GetDeleteButton() {
		
		return '<div class="submit-buttons" style=""><span title="' . $this->strGetPro . '">'
			. $this->GetSubmitButton( 
				__( 'Delete Checked', 'responsive-column-widgets' ),
				'button button-secondary', 
				'delete', 
				'disabled="disabled"'
			)
			. '</span></div>';		
			
	}
	function GetSubmitButton( $strValue, $strClass, $strName, $strDisable ) {
		
		return "<input type='submit' class='{$strClass}' name='{$strName}' value='{$strValue}' {$strDisable} />";
	
	}

	function do_responsive_column_widgets_information() {
		?>
		<h3><?php _e( 'Please Review', 'responsive-column-widgets' ); ?></h3>
		<p><?php _e( 'If you find the plugin useful, please <a href="http://wordpress.org/support/view/plugin-reviews/responsive-column-widgets">rate</a> it so that others can know it.', 'responsive-column-widgets' ); ?></p>
		<?php if ( ! defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ) : ?>
		<h3><?php _e( 'Get Pro', 'responsive-column-widgets' ); ?></h3>
		<p><?php _e( 'If you like the plugin and want more useful features, please upgrade it to <a href="http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro">Pro</a>.', 'responsive-column-widgets' ); ?></p>
		<?php endif; ?>
		<h3><?php _e( 'Exchanges', 'responsive-column-widgets' ); ?></h3>
		<p><?php _e( 'You may receive a discount or a copy of the plugin by contributing one of the followings. A contribution does not have to be for this plugin. It can be for any product of miunosoft. Please just ask.', 'responsive-column-widgets' ); ?></p>
		<ul>
			<li><?php 
				_e( '<strong>Testing Development Version</strong> - If the development version is greater than the current stable version, tell the developer that the development version works fine or not in your environment. Then you will get a 20% off coupon for miunosoft products.', 'responsive-column-widgets' );
				echo '&nbsp;';
				printf( __( 'The development version number can be confirmed <a href="%1$s">here</a>.', 'responsive-column-widgets' ), 'http://plugins.svn.wordpress.org/responsive-column-widgets/trunk/responsive-column-widgets.php' );
				echo '&nbsp;';
				printf( __( 'It can be downloaded <a href="%1$s">here</a>.', 'responsive-column-widgets' ), 'http://downloads.wordpress.org/plugin/responsive-column-widgets.zip' );
				?>
			</li>
			<li><?php _e( '<strong>Translation</strong> - submitting a localization file for an untranslated languge. With a plugin called <a href="http://wordpress.org/extend/plugins/codestyling-localization/stats/">Codestyling Localization</a> no programming skill is required to create a language file.', 'responsive-column-widgets' ); ?></li>
			<li><?php _e( '<strong>Testimonial</strong> - with your photo, comments, and a link to your SNS page will be on the plugin site.', 'responsive-column-widgets' ); ?></li>
			<li><?php _e( '<strong>Graphic Design</strong> - icons, banners etc. for the plugin.', 'responsive-column-widgets' ); ?></li>
			<li><?php _e( '<strong>Review Article</strong> - requires Google PageRank 3 or higher and the link to the product page.', 'responsive-column-widgets' ); ?></li>
			<li><?php _e( '<strong>Video Tutorial</strong> - a brief instruction video tutorial with your narration.', 'responsive-column-widgets' ); ?></li>
			<li><?php _e( 'Something else - please ask for something else for the exchange.', 'responsive-column-widgets' ); ?></li>
		</ul>	
		<h3><?php _e( 'Contanct Info', 'responsive-column-widgets' ); ?></h3>
		<p><?php echo( 'wpplugins@michaeluno.jp' ); ?></p>
		<?php
	}
	
	function do_responsive_column_widgets_getpro() {
		
		echo "<h3>{$this->strGetProNow}</h3>";
		echo "<p>" . __( 'Please consider upgrading to the Pro version if you like the plugin and want more useful features.', 'responsive-column-widgets' ) . "</p>";
		echo $this->GetBuyNowButton();
		echo "<h3>" . __( 'Supported Features', 'responsive-column-widgets' ) . "</h3>";
		echo '<div align="center" style="margin-top:30px;">';
		echo '<table class="comparison-table" cellspacing="0" cellpadding="10" width="600" align="center">';
		echo '<tbody>';
		echo '<tr>';
		echo '<th class="first-col" >&nbsp;</th>';
		echo '<th align="center">';
		echo __( 'Standard', 'responsive-column-widgets' );
		echo '</th>';
		echo '<th align="center">';
		echo __( 'Pro', 'responsive-column-widgets' );
		echo '</th>';
		echo '</tr>';
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Multiple Columns', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);	
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Edit and Save Parameter Values', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);			
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Auto-insert into Footer, Posts, and Pages', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);			
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Custom Style', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);		
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Multiple Widget Boxes', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => False, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);		
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Export / Import Widget Boxes Options', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => False, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);		
		echo $this->GetComparisionTableTR( 
			array( 
				array( 'type' => 'text', 'value' => __( 'Ad Removal', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
				array( 'type' => 'image', 'value' => False, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
				array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),				
			) 			
		);				
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		
		echo $this->GetBuyNowButton();
	}
	function GetComparisionTableTR( $arrColumns ) {

		$strOut = '<tr>';
		foreach( $arrColumns as $i => $arrInfo ) {
			
			// Avoid undefined index warnings.
			$arrInfo = $arrInfo + array(
				'align' 	=> null,
				'width' 	=> null,
				'height'	=> null,
				'class'		=> null,
			);
			
			if ( $arrInfo['type'] == 'text' ) {
				
				$strOut .= "<td align='{$arrInfo['align']}' class='{$arrInfo['class']}'>" .  $arrInfo['value'] . "</td>";
				continue;
				
			}
			
			// means it's an image
			$strOut .= "<td align='{$arrInfo['align']}' class='{$arrInfo['class']}'>"
				. '<img src="' . RESPONSIVECOLUMNWIDGETSURL . '/img/' . ( $arrInfo['value'] ? 'available.gif' : 'unavailable.gif' ) . '" '
				. 'title="' . ( $arrInfo['value'] ? __( 'Available', 'responsive-column-widgets' ) : __( 'Unavailable', 'responsive-column-widgets' ) ) . '"'
				. '</td>';
				
		}
		$strOut .= '</tr>';
		return $strOut;
		
	}
	function GetBuyNowButton() {
		$strFloat='right';
		$strPadding='10px 5em 20px';
		$type=1;
		$strLink='http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro';
		$strImgBuyNow = RESPONSIVECOLUMNWIDGETSURL . '/img/buynowbutton.gif';
	
		$strOut = '<div style="padding:' . $strPadding . ';">';
		$strOut .= '<div style="float:' . $strFloat . ';">';
		$strOut .= '<a href="' . $strLink . '?lang=' . ( WPLANG ? WPLANG : 'en' ) . '" title="' . $this->strGetProNow . '">';
		$strOut .= '<img src="' . $strImgBuyNow . '" />';
		$strOut .= '</a>';
		$strOut .= '</div>';
		$strOut .= '</div>';
		return $strOut;
	}
	
	
	/*
	 * Validate Post Data
	 * */
	function validation_responsive_column_widgets_neworedit( $arrInput ) {

		// Sanitize HTML Post Data
		$arr = array();
		foreach( $arrInput[ $this->strPluginSlug ]['section_sidebar'] as $strField => $strHTML ) 
			$arr[$strField] = $this->FilterPostHTMLCode( $arrInput[ $this->strPluginSlug ]['section_sidebar'][ $strField ] );
		$arrInput[ $this->strPluginSlug ]['section_sidebar'] = $arr;

		// Set the variables.
		$bIsValid = True;
		$arrErrors = array();
		$strErrors = '';
		
		// Check if the label is not empty - if the "field_label" key is not set, it means it's disabled, which occures to the default widget box.
		if ( isset( $arrInput[ $this->strPluginSlug ]['section_sidebar']['label'] ) ) {
			
			$arrInput[ $this->strPluginSlug ]['section_sidebar']['label'] = trim( $arrInput['responsive_column_widgets']['section_sidebar']['label'] );
			
			// Check if the label is empty
			if ( empty( $arrInput[ $this->strPluginSlug ]['section_sidebar']['label'] ) ) {
				
				$arrErrors['section_sidebar']['label'] = '';
				$strErrors .=  __( 'The label cannot be empty.', 'responsive-column-widgets' );
				$bIsValid = False;
			
			}
			
			// Check if the same label name is used.
			if ( $_POST['isnew'] == 1 && $this->IsLabelAlreadyUsed( $arrInput[ $this->strPluginSlug ]['section_sidebar']['label'] ) ) {
				
				$arrErrors['section_sidebar']['label'] = $arrInput[ $this->strPluginSlug ]['section_sidebar']['label'];
				$strErrors .= '<p>' . __( 'The same label already used.', 'responsive-column-widgets' ) . '</p>';
				$bIsValid = False;
			
			}
		}
		
		if ( ! $bIsValid  ) {	// This line is reached if there are invalid values.
						
			// Set the field error array.
			$this->SetFieldErrors( $arrErrors );
			
			// This displays the error message
			$this->SetSettingsNotice( $strErrors  );	
			
			// Returning an empty array will not change options.
			return array();				
			
		}
		
		/*
		 * Reconstruct the submitted array to omit the sections - make it flat to consist of fields
		 * */
		$arrBox = array();
		foreach ( $arrInput[ $this->strPluginSlug ] as $arrFields ) 
			$arrBox = $arrBox + $arrFields;		 
				
		// The data are valid. Update the box options.
		// Setup the box option array
		$this->UpdateBoxOptions( $arrBox, $_POST['isnew'] );
		$this->SetSettingsNotice( __( 'The widget box options have been saved.', 'responsive-column-widgets' ), 'updated' );
		
		return $arrInput;
			
	}
	function UpdateBoxOptions( $arrInput, $bIsNew ) {
		
		$arrInput['maxwidgets'] = $this->oUtil->FixNumber( $arrInput['maxwidgets'], 0, 0 );
		$arrInput['maxrows'] = $this->oUtil->FixNumber( $arrInput['maxrows'], 0, 0 );
		$arrInput['sidebar'] = ! empty( $_POST['isnew'] ) ? $this->GetAvailableSidebarID() : $arrInput['sidebar'];
		$arrInput['label'] = isset( $arrInput['label'] ) ? $arrInput['label'] : $this->oOption->arrOptions['boxes'][ $arrInput['sidebar'] ]['label'];
		
		$arrInput['columns'] = $this->SanitizeNumericSequenceToArray( 
			$arrInput['columns'], 	// subject value
			$this->oOption->arrDefaultParams['columns'][0],		// default
			1,	// min
			12 	// max
		);

		$arrInput['omit'] = $this->SanitizeNumericSequenceToArray( $arrInput['omit'] );
		$arrInput['omit'] = array_unique( $arrInput['omit'] );
		$arrInput['showonly'] = $this->SanitizeNumericSequenceToArray( $arrInput['showonly'] );
		$arrInput['showonly'] = array_unique( $arrInput['showonly'] );
		$arrInput['offsets'] = $this->oOption->ConvertStringToArray( $arrInput['offsets'], ',', ':' );
		$arrInput['offsets'] = $this->oUtil->UnsetEmptyArrayElements( $arrInput['offsets'] );	
		$arrInput['offsets'] = empty( $arrInput['offsets'] ) ? $this->oOption->arrDefaultParams['offsets'] : $arrInput['offsets'];
		$arrInput['insert_footer_disable_ids'] = $this->SanitizeNumericSequenceToArray( $arrInput['insert_footer_disable_ids'] );
		$arrInput['insert_footer_disable_ids'] = array_unique( $arrInput['insert_footer_disable_ids'] );
		$arrInput['insert_posts_disable_ids'] = $this->SanitizeNumericSequenceToArray( $arrInput['insert_posts_disable_ids'] );
		$arrInput['insert_posts_disable_ids'] = array_unique( $arrInput['insert_posts_disable_ids'] );
		$arrInput['insert_comment_form_disable_post_ids'] = $this->SanitizeNumericSequenceToArray( $arrInput['insert_comment_form_disable_post_ids'] );
		$arrInput['insert_comment_form_disable_post_ids'] = array_unique( $arrInput['insert_comment_form_disable_post_ids'] );

		// Update
		$this->oOption->InsertBox( $arrInput['sidebar'], $arrInput );
		$this->oOption->Update();		
		
	}		
	function SanitizeNumericSequenceToArray( $str, $intDefault=null, $intMin=1, $intMax=null ) {
		
		// Converts the given string into array and performs sanitization.
		// e.g. 3, 4, 63  --> array( 3, 4, 63 )
		// e.g. ada, 9,, 4 --> array( 9, 4 ) 
		
		$arr = $this->oOption->ConvertStringToArray( $str );	// comma delimited
		$arr = $this->oUtil->FixNumbers( $arr, $intDefault, $intMin, $intMax );
		$arr = $this->oUtil->UnsetEmptyArrayElements( $arr );
		return $arr;
		
	}
	
	function IsLabelAlreadyUsed( $strLabel ) {
		
		// since 1.0.4
		foreach( $this->oOption->arrOptions['boxes'] as $strSidebarID => $arrBoxOptions ) 			
			if ( $arrBoxOptions['label'] == $strLabel ) return True;

	}	
	function GetAvailableSidebarID() {

		// since 1.0.4
		$numID = '';
		$arrBoxes = ( array ) $this->oOption->arrOptions['boxes'];
		$arrBoxes = array_reverse( $arrBoxes, true );	// the ID number is ascending so read from the last one.
		foreach( $arrBoxes as $strID => $v ) {
				
			preg_match( '/^(.+\D)(\d+)$/', $strID, $arrMatches );	// get the last digits
			if ( ! isset( $arrMatches[2] ) ) continue;
			
			$numID = $arrMatches[2] + 1;
			if ( ! isset( $this->oOption->arrOptions['boxes'][ $arrMatches[1] . $numID ] ) ) 
				return $arrMatches[1] . $numID;
				
		}
		
		// what happens if an available ID could not be generated? 
		if ( array_key_exists( 'responsive_column_widgets_2', $arrBoxes ) ) 
			return 'responsive_column_widgets_' . uniqid();
			
		return 'responsive_column_widgets_2'; 
	
	}	
	function FilterPostHTMLCode( $strHTML ) {
		
		// since 1.0.4
		$arrAllowedHTMLTags = array();
		$arrNumericAllowedHTMLTags = is_array( $this->oOption->arrOptions['general']['allowedhtmltags'] ) ?
			$this->oOption->arrOptions['general']['allowedhtmltags'] 
			: preg_split( '/[, ]+/', $this->oOption->arrOptions['general']['allowedhtmltags'], -1, PREG_SPLIT_NO_EMPTY );
		foreach( ( array ) $arrNumericAllowedHTMLTags as $strHTMLTag ) 
			$arrAllowedHTMLTags[$strHTMLTag] = array();
		$strHTML = $this->EscapeAndFilterPostKSES( $strHTML, $arrAllowedHTMLTags );
		return $strHTML;
		
	}
	function validation_responsive_column_widgets_manage( $arrInput ) {
		
		
		/*
		 * Delete Checked Widget Box Items
		 * */ 
		if ( isset( $arrInput['responsive_column_widgets']['section_buttons']['submit_delete'] ) ) {		// the 'Delete Checked' submit button
			
			$strMsg = '';
			$bIsUnset = False;
			$arrSidebarOptions = get_option( 'sidebars_widgets', array() );
			foreach( ( array ) $arrInput['checked_boxes'] as $strSidebarID => $numValue ) {
				
				// If broken
				if ( $strSidebarID == '' ) {
					$strMsg .= __( 'There was a broken item and it has been removed.', 'responsive-column-widgets' ) . ' ';
					unset( $this->oOption->arrOptions['boxes'][''] );
					$bIsUnset = True;				
				}
				
				// If not checked
				if ( $numValue != 1 ) continue;	
				
				// Unset
				unset( $this->oOption->arrOptions['boxes'][$strSidebarID] );
				if ( isset( $arrSidebarOptions[ $strSidebarID ] ) )
					unset( $arrSidebarOptions[ $strSidebarID ] );
				
				$bIsUnset = True;
				
			}
			if ( $bIsUnset ) {
				
				$this->oOption->Update();
				update_option( 'sidebars_widgets', $arrSidebarOptions );
				$strMsg .= __( 'The selected widget boxes have been deleted.', 'responsive-column-widgets' );
				$this->SetSettingsNotice( $strMsg, 'updated' );
				
			}
			// unless unsetting the key, it will remain in the database. 
			unset( $arrInput['checked_boxes'] );
			unset( $arrInput['responsive_column_widgets']['section_buttons']['submit_delete'] );
			
		}
		
		return $arrInput;
		
	}
	function validation_responsive_column_widgets_general( $arrInput ) {
			
		/*
		 * Danger Zone
		 * */
		/*
		[section_dangerzone] => Array
		(
			[field_initializeoptions] => 0
			[submit_perform] => Perform Checked
		)
		*/
		 
		if ( 
			isset( $arrInput['responsive_column_widgets']['section_dangerzone']['submit_perform'] )
			&& isset( $arrInput['responsive_column_widgets']['section_dangerzone']['initializeoptions'] )
			&& $arrInput['responsive_column_widgets']['section_dangerzone']['initializeoptions'] == 1 
		) {
			
			// Delete the plugin main options
			$this->oOption->arrOptions = null;
			$this->oOption->Update();
			
			// Delete the admin page options as well.
			return null;
			
		}	
		
		// Do the validations and sanitizations here.
		$this->oOption->arrOptions['general']['capability'] = $arrInput['responsive_column_widgets']['section_general']['capability'];
		$this->oOption->arrOptions['general']['allowedhtmltags'] = $this->oOption->ConvertStringToArray( $arrInput['responsive_column_widgets']['section_general']['allowedhtmltags'] ); 
		
		// Memory Allocation since 1.0.7.1
		$this->oOption->arrOptions['general']['memory_allocation'] = empty( $arrInput['responsive_column_widgets']['section_dangerzone']['memory_allocation'] ) ? 0 
			: $this->oUtil->FixNumber( $arrInput['responsive_column_widgets']['section_dangerzone']['memory_allocation'], 
				intval( $strCurrentLimit ),
				32 	// minimum
			);
		
		// Update the value to the separate main option.
		$this->oOption->Update();
		
		return $arrInput;
		
	}

	function EscapeAndFilterPostKSES( $strString, $arrAllowedTags = array(), $arrDisallowedTags=array(), $arrAllowedProtocols = array() ) {
		// $arrAllowedTags : e.g. array( 'noscript' => array(), 'style' => array() );
		// $arrDisallowedTags : e.g. array( 'table', 'tbody', 'thoot', 'thead', 'th', 'tr' );

		global $allowedposttags;
		// $arrAllowedHTML = array_replace_recursive( $allowedposttags, $arrAllowedTags );	// the second parameter takes over the first.
		// $arrAllowedHTML = wp_parse_args( $arrAllowedTags, $allowedposttags );	// the first parameter takes over the second.
		$arrAllowedHTML = $this->oUtil->UniteArraysRecursive( $arrAllowedTags, $allowedposttags );	// the first parameter takes over the second.
	
		foreach ( $arrDisallowedTags as $strTag ) 		
			if ( isset( $arrAllowedHTML[$strTag] ) ) unset( $arrAllowedHTML[$strTag] );
		
		if ( empty( $arrAllowedProtocols ) )
			$arrAllowedProtocols = wp_allowed_protocols();			
		$strString = addslashes( $strString );					// the original function call was doing this - could be redundant but haven't fully tested it
		$strString = stripslashes( $strString );					// wp_filter_post_kses()
		$strString = wp_kses_no_null( $strString );				// wp_kses()
		$strString = wp_kses_js_entities( $strString );			// wp_kses()
		$strString = wp_kses_normalize_entities( $strString );	// wp_kses()
		$strString = wp_kses_hook( $strString, $arrAllowedHTML, $arrAllowedProtocols ); // WP changed the order of these funcs and added args to wp_kses_hook
		$strString = wp_kses_split( $strString, $arrAllowedHTML, $arrAllowedProtocols );		
		$strString = addslashes( $strString );				// wp_filter_post_kses()
		$strString = stripslashes( $strString );				// the original function call was doing this - could be redundant but haven't fully tested it
		return $strString;
	}		
	
	/*
	 * Table
	 * */
	function GetWidgetBoxTable() {
		return '<div class="submit">'
			. '<table class="wp-list-table widefat fixed posts responsive_column_widgets_admin" cellspacing="0" >'
			. '<thead>' . $this->GetWidgetBoexTableHeader() . '</thead>'
			. '<tbody id="the-list">'
			. $this->GetWidgetBoexTableDefaultRow()
			. $this->GetWidgetBoexTableRows()
			. '</tbody>'
			. '<tfoot>' . $this->GetWidgetBoexTableHeader() . '</tfoot>'
			. '</table>'
			. '</div>';
	}	 
	function GetWidgetBoexTableHeader() {
		return '<tr style="">'
			. '<th scope="col" class="manage-column column-cb check-column" style="vertical-align:middle; padding-left:4px;" valign="middle">'
			. '<input type="checkbox">'				
			. '</th>'
			. '<th scope="col" class="manage-column column-label asc desc sortable" style="width:22%;">'
			. '<span>' . __( 'Box Label', 'responsive-column-widgets' ) . ' / ' . __( 'Description', 'responsive-column-widgets' ) . '</span>'
			. '</th>'
			. '<th scope="col" class="manage-column column-label asc desc sortable" style="width:20%;">'
			. '<span>' . __( 'Sidebar ID', 'responsive-column-widgets' ) . '</span>'
			. '</th>'
			. '<th scope="col" class="manage-column column-label asc desc sortable" style="width:44%;">'
			. '<span>' . __( 'Shortcode', 'responsive-column-widgets' ) . ' / ' . __( 'PHP Code', 'responsive-column-widgets' ) . ' ' . __( 'Example', 'responsive-column-widgets' ) . '</span>'
			. '</th>'
			. '<th scope="col" class="manage-column column-label asc desc sortable operation" style="width:10%;">'
			. '<span>' . __( 'Operation', 'responsive-column-widgets' ) . '</span>'
			. '</th>'
			. '</tr>';
	}
	function GetWidgetBoexTableDefaultRow() {
		
		$strURL = admin_url( 'admin.php?page=' . ( isset( $_GET['page'] ) ? $_GET['page'] : '' ) . '&tab=neworedit&sidebarid=' . $this->oOption->arrDefaultParams['sidebar'] . '&mode=edit' );
		return '<tr class="responsive_column_widgets_default_row" >'
			. '<td align="center" class="check-column first-col" style="padding: 8px 0 8px" ></td>'
			. '<td>'
			. '<ul style="margin:0;">'
			. '<li><b>' . $this->oOption->arrOptions['boxes'][ $this->oOption->arrDefaultParams['sidebar'] ]['label'] . '</b></li>'
			. '<li>' . $this->oOption->arrOptions['boxes'][ $this->oOption->arrDefaultParams['sidebar'] ]['description'] . '</li>'
			. '</ul>'
			. '</td>'
			. '<td>' . $this->oOption->arrDefaultParams['sidebar'] . '</td>'
			. '<td>'
			. '<ul style="margin:0;">'
			. '<li>[' . $this->oOption->arrOptions['boxes'][ $this->oOption->arrDefaultParams['sidebar'] ]['sidebar'] . ']</li>'
			. '<li>&lt;?php ResponsiveColumnWidgets(); ?&gt;</li>'
			. '</ul>'
			. '</td>'
			. '<td class="operation">'
			. "<a href='{$strURL}'>" . __( 'Edit', 'responsive-column-widgets' ) . "</a>"
			. '</td>'
			. '</tr>';
	}
	function GetWidgetBoexTableRows() {}
	 
	/*
	 * Modify Style
	 * */
	function style_ResponsiveColumnWidgets_Admin_Page( $strStyle ) {
		
		$strRuleTabNew = $this->numPluginType == 0 ? ' .newtab{ color: #AAA; }' : '';
		$strRuleTabSlash = $this->numPluginType == 0 ? ' .slash{ color: #AAA; }' : '';
		return $strStyle . "
			h3 .nav-tab {
				padding: 4px 10px 6px;
				font-weight: 200;
				font-size: 20px;
				line-height: 24px;				
			}
			{$strRuleTabNew}
			{$strRuleTabSlash}
			.submit-buttons {
				float: right; 
				clear: both;
			}
			.wp-core-ui .button, .wp-core-ui .button-primary, .wp-core-ui .button-secondary {
				margin-left: 10px;
			}
			.submit-buttons p {
				padding: 4px;
				text-align: right;
			}
			table.responsive_column_widgets_admin {
				clear: none; 
				width: 100%; 			
			}
			.responsive-column-widgets-admin-body {				
			}
			.admin-page-framework-container {		
				width: auto;
				min-width: 600px;
			}
			table.fixed {
				table-layout: auto;				
			}
		"; 
	}
	function style_responsive_column_widgets_neworedit( $strStyle ) {
		return $strStyle . '
			input.disabled {
				background-color: #F1F1F1;
			}
			.neworedit-button {
				margin-top: 12px;
				margin-bottom: 12px;
			}			
		';
	}
	function style_responsive_column_widgets_manage( $strStyle ) {
		$strInputFileFontColor = defined( 'RESPONSIVECOLUMNWIDGETSPROFILE' ) ? '#555' : '#DDD';
		return $strStyle . "
			.responsive_column_widgets_default_row {
				background-color:#F1F1F1;
			} 
			.responsive_column_widgets_default_row td {		
				border-top-color: #F1F1F1;
				border-bottom-color: #F1F1F1;
			}
			.widefat th.sortable, .widefat th.sorted {
				padding: 10px;
			}
			.operation {
				text-align: center;				
			}
			.form-table tbody tr th {
				width: 0px;
				padding: 0px;
			}
			.submit {
				padding: 0px;				
			}
			input.export-button {
				clear:none;
			}
			.import {
				float: right;
			}
			.import input {
				color: {$strInputFileFontColor};
				background-color: inherit;
			}
		";
	}
	function style_responsive_column_widgets_information( $strStyle ) {
		return $strStyle . '
			p { 
				margin-left: 20px;
			}
			ul {
				list-style: square;
				margin-left: 44px;
			}			
		';
		
	}
	function style_responsive_column_widgets_getpro( $strStyle ) {
		return $strStyle . '
			table.comparison-table {
				border: 1px solid #E2E2E2;
				font-size: 1.2em;
				margin-bottom: 28px;
			}
			.comparison-table th, 
			.comparison-table td {
				border-top: 1px solid #E2E2E2;
				height:	30px; 
				padding: 28px;
			}
			.comparison-table th.first-col , 
			.comparison-table td.first-col {
				width: 40%;
			}
			.comparison-table th.second-col , 
			.comparison-table td.second-col {
				width: 30%;
			}
			.comparison-table th.third-col , 
			.comparison-table td.third-col {
				width: 30%;
			}			
			.comparison-table th {
				border-top: 0px;
				background-color:#F5F5F5;
			}
			.comparison-table td img {
				border-bottom: 0px;
				border-left: 0px;
				display: inline;
				border-top: 0px;
				border-right: 0px;
				margin: 0 auto;
				display: block;	
			}		
		';	
	}
	
	protected $numPluginType = 0;
	protected $strGetPro = 'Get Pro to enabel this feature!';
	protected $strGetProNow = 'Get Pro now!';
	function __NoteProStrings() {	
		__( '<a href="http://wordpress.org/extend/plugins/responsive-column-widgets/other_notes/">Responsive Column Widgets</a> needs to be installed and activated.', 'responsive-column-widgets' );
		__( 'Add New Box', 'responsive-column-widgets' );
		__( 'Delete Checked', 'responsive-column-widgets' );
		__( 'Edit', 'responsive-column-widgets' );
		__( 'Export All', 'responsive-column-widgets' );
		__( 'Export Checked', 'responsive-column-widgets' );
		__( 'Failed to validate the license key.', 'responsive-column-widgets' );	
		__( 'Import Widget Boxes', 'responsive-column-widgets' );
		__( 'License Key', 'responsive-column-widgets' );
		__( 'License Status', 'responsive-column-widgets' );					
		__( 'Not Verified', 'responsive-column-widgets' );
		__( 'Nothing could be exporeted.', 'responsive-column-widgets' ) ;
		__( 'Pro Settings', 'responsive-column-widgets' );
		__( 'Set the license key provided by miunosoft written in the purchase receipt.', 'responsive-column-widgets' );
		__( 'The current status of the license of this plugin.', 'responsive-column-widgets' );
		__( 'The license key has been verified.', 'responsive-column-widgets' );
		__( 'The main plugin\'s version must be at least 1.0.4.8.', 'responsive-column-widgets' );
		__( 'The plugin, Responsive Column Widgets Pro, was deactivated.', 'responsive-column-widgets' );
		__( 'There was %d box(es) with borken options that were unable to be imported.', 'responsive-column-widgets' );
		__( 'Validate', 'responsive-column-widgets' );
		__( 'View', 'responsive-column-widgets' );
	}
	
}