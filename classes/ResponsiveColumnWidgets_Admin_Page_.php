<?php
class ResponsiveColumnWidgets_Admin_Page_ extends ResponsiveColumnWidgets_Admin_Page_Framework {

	// Properties
	protected $strPluginName = 'Responsive Column Widgets';
	protected $strPluginSlug = 'responsive_column_widgets';
	
	// Flags
	protected $bIsNew;

	// Objects
	public $oOption = null;	// stores the option object. It is set via the SetOptionObject() method.
	
	function start_ResponsiveColumnWidgets_Admin_Page() {
		
		// admin footer to add plugin version
		if ( isset( $_GET['page'] ) && $_GET['page'] == $this->strPluginSlug )
			add_filter( 'update_footer', array( $this, 'AddPluginVersionInFooter' ), 11 );
			
		$this->AddLinkToPluginDescription( $this->GetPluginDescriptionLinks() );				
		
		$this->oUserAds = new ResponsiveColumnWidgets_UserAds;
		
		if ( isset( $_GET['view'] ) )
			add_action( 'admin_init', array( $this, 'EnqueueAdminStyle' ) );
			
		$this->strGetPro = __( 'Get Pro to enabel this feature!', 'responsive-column-widgets' );

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
	function AddPluginVersionInFooter( $strText ) {	

		$strProInfo = isset( $this->oOption->arrPluginDataPro ) ? $this->oOption->arrPluginDataPro['Name'] . ' ' . $this->oOption->arrPluginDataPro['Version'] : '';
		return $strProInfo 
			. ' ' . $this->oOption->arrPluginData['Name'] 
			. ' ' . $this->oOption->arrPluginData['Version']
			. ' ' . $strText;			
	}
	
    function SetUp() {
		
		// Set the access rights to the option page.
		$numCapability = $this->oOption->arrOptions['general']['capability'];
		$this->SetCapability( $this->oOption->arrCapabilities[$numCapability ? $numCapability : 0] );

		// Build menu and pages
        $this->SetRootMenu( 'Appearence' );          // specifies to which parent menu to belong.
        $this->AddSubMenu(  
			$this->strPluginName,    // page and menu title
			$this->strPluginSlug 	// page slug
		);	 
		
		// Add in-page tabs in the third page.			
		$this->AddInPageTabs( $this->strPluginSlug,	
			array(	// slug => title
				'neworedit' 	=> '<span class="newtab">' . __( 'New', 'responsive-column-widgets' ) . '</span>&nbsp;<span class="slash">/</span>&nbsp;' . __( 'Edit', 'responsive-column-widgets' ),
				'manage'		=> __( 'Manage', 'responsive-column-widgets' ),
				'general'		=> __( 'General Options', 'responsive-column-widgets' ),
				'information'	=> __( 'Information', 'responsive-column-widgets' ),
			)
		);			
		
		// Determine which widget box it is.
		$strSidebarID = $this->DetermineCurrentSidebarToEdit();	// the returned value can be empty.

		$this->bIsNew =  empty( $strSidebarID ) || ! isset( $this->oOption->arrOptions['boxes'][$strSidebarID] )  ? true : false;
		$bIsNew = $this->bIsNew;
		
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
							'id' => 'field_label',
							'title' => __( 'Widget Box Label', 'responsive-column-widgets' ),
							'tip' => __( 'Set a unique name for the widget box.', 'responsive-column-widgets' ),
							'description' => __( 'Set a unique name for the widget box.', 'responsive-column-widgets' ),
							'error' => __( 'The label neither cannot be empty nor use the same one that already exists.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 60,
							'class' => $this->numPluginType == 0 || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? 'disabled' : '',
							'disable' => $this->numPluginType == 0 || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? true : false,
							'value' => $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['label'] : (  $bIsNew  ? '' : $this->oOption->arrOptions['boxes'][$strSidebarID]['label'] ),
							'default' => '',
						),
						array(
							'id' => 'field_sidebar',
							'title' => __( 'Widget Box Sidebar ID', 'responsive-column-widgets' ),
							'tip' => __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
							'pre_html' => '<input class="disabled" size="80" type="text" disabled="Disabled" value="' . ( $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['sidebar'] : $strSidebarID ) . '" /><br />',
							'description' => $bIsNew ? __( 'A new ID will be automatically generated.', 'responsive-column-widgets' ) 
								: __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
							'type' => 'hidden',
							'value' => $bIsNew ? '' : ( $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['sidebar'] : $strSidebarID ),
							'default' => '',
						),						
						array(
							'id' => 'field_description',
							'title' => __( 'Widget Box Description', 'responsive-column-widgets' ),
							'tip' => __( 'Additional notes for this box.', 'responsive-column-widgets' ),
							'description' => __( 'Additional notes for this box.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['description'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['description'],
							'default' => '',
						),		
						array(
							'id' => 'field_before_widget',
							'title' => __( 'Widget Beginning Tag', 'responsive-column-widgets' ),
							'tip' => __( 'Set the before_widget html opening tag.', 'responsive-column-widgets' ),
							'description' => __( 'Set the before_widget html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['before_widget'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['before_widget'],
							'default' => $this->oOption->arrDefaultSidebarArgs['before_widget'], //'<aside id="%1$s" class="%2$s"><div class="widget">',
						),
						array(
							'id' => 'field_after_widget',
							'title' => __( 'Widget Ending Tag', 'responsive-column-widgets' ),
							'tip' => __( 'Set the after_widget html opening tag.', 'responsive-column-widgets' ),
							'description' => __( 'Set the after_widget html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['after_widget'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['after_widget'],
							'default' => $this->oOption->arrDefaultSidebarArgs['after_widget'],	//'</div></aside>',
						),
						array(
							'id' => 'field_before_title',
							'title' => __( 'Starting Tag for Box Title', 'responsive-column-widgets' ),
							'tip' => __( 'Set the before_title html opening tag.', 'responsive-column-widgets' ),
							'description' => __( 'Set the before_title html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['before_title'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['before_title'],
							'default' => $this->oOption->arrDefaultSidebarArgs['before_title'], //'<h3 class="widget-title">',
						),
						array(
							'id' => 'field_after_title',
							'title' => __( 'Starting Tag for Box Title', 'responsive-column-widgets' ),
							'tip' => __( 'Set the after_title html opening tag.', 'responsive-column-widgets' ),
							'description' => __( 'Set the after_title html opening tag.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultSidebarArgs['after_title'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['after_title'],
							'default' => $this->oOption->arrDefaultSidebarArgs['after_title'], //'</h3>',
						),
						array(
							'id' => 'field_message_no_widget',
							'title' => __( 'Message for No Widget', 'responsive-column-widgets' ),
							'tip' => __( 'Set the message which appears when no widget is added so that nothing can be rendered.', 'responsive-column-widgets' ),
							'description' => __( 'Set the message which appears when no widget is added so that nothing can be rendered.', 'responsive-column-widgets' ),
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? __( 'No widgetd is added yet.', 'responsive-column-widgets' ) : $this->oOption->arrOptions['boxes'][$strSidebarID]['message_no_widget'],
							'default' => __( 'No widgetd is added yet.', 'responsive-column-widgets' ),
						),						
					),
				),
				array(
					'pageslug' => $this->strPluginSlug,
					'tabslug' => 'neworedit',
					'id' => 'section_params',
					'title' => __( 'Widget Box Options', 'responsive-column-widgets' ), 
					'fields' => array(
						array(
							'id' => 'field_columns',
							'title' => __( 'Numbers of Columns', 'responsive-column-widgets' ),
							'tip' => __( 'Set the number of columns separated by commnas. Each delimited element number corresponds to the order number of the rows.', 'responsive-column-widgets' ),
							'description' => __( 'Set the number of columns separated by commnas. Each delimited element number corresponds to the order number of the rows.', 'responsive-column-widgets' ) . ' e.g. 4, 2, 3',
							'type' => 'text',
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['columns'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['columns'],
							'default' => $this->oOption->arrDefaultParams['columns'],
						),		
						array(
							'id' => 'field_maxwidgets',
							'title' => __( 'Number of Max Widgets', 'responsive-column-widgets' ),
							'tip' => __( 'Set the number of max widgets. 0 for no limit.', 'responsive-column-widgets' ),
							'description' => __( 'Set the number of max widgets. 0 for no limit.', 'responsive-column-widgets' ) . ' e.g. 10',
							'type' => 'number',
							'min' => 0,
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['maxwidgets'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['maxwidgets'],
							'default' => $this->oOption->arrDefaultParams['maxwidgets'],
						),	
						array(
							'id' => 'field_maxrows',
							'title' => __( 'Number of Max Rows', 'responsive-column-widgets' ),
							'tip' => __( 'Set the number of max rows. 0 for no limit.', 'responsive-column-widgets' ),
							'description' => __( 'Set the number of max rows. 0 for no limit.', 'responsive-column-widgets' ) . ' e.g. 2',
							'type' => 'number',
							'min' => 0,
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['maxrows'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['maxrows'],
							'default' => $this->oOption->arrDefaultParams['maxrows'],
						),	
						array(
							'id' => 'field_omit',
							'title' => __( 'Omitting Widgets', 'responsive-column-widgets' ),
							'tip' => __( 'Set the numbers of omitting widgets separated by commas.', 'responsive-column-widgets' ),
							'description' => __( 'Set the numbers of omitting widgets separated by commas.', 'responsive-column-widgets' ) 
								. ' e.g. "2, 5, 8" ' . __( 'where the second, the fifth, and the eighth ones will be skipped.', 'responsive-column-widgets' ),
							'type' => 'text',
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['omit'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['omit'],
							'default' => $this->oOption->arrDefaultParams['omit'],
						),	
						array(
							'id' => 'field_showonly',
							'title' => __( 'Show-only Widgets', 'responsive-column-widgets' ),
							'tip' => __( 'Set the numbers of show-only widgets separated by commas.', 'responsive-column-widgets' ),
							'description' => __( 'Set the numbers of show-only widgets separated by commas.', 'responsive-column-widgets' ) 
								. ' e.g. "1, 3" ' . __( 'where only the first and the third ones will be shown.', 'responsive-column-widgets' ),
							'type' => 'text',
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['showonly'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['showonly'],
							'default' => $this->oOption->arrDefaultParams['showonly'],
						),	
						array(
							'id' => 'field_offsets',
							'title' => __( 'Width Percentage Offsets', 'responsive-column-widgets' ),
							'tip' => __( 'Set the offsets for width percentage. The higher the offset nubmer is, the less will the number of clummns be displayed.', 'responsive-column-widgets' ),
							'description' => __( 'Set the offsets for width percentage. The higher the offset nubmer is, the less will the number of clummns be displayed.', 'responsive-column-widgets' ) . ' '
								. __( 'Format', 'responsive-column-widgets' ) . ': ' . __( 'Pixel: Offset, Pixel: Offset, ....', 'responsive-column-widgets' ) . ' '
								. 'e.g. 600:3, 480:4, 400:5',
							'type' => 'text',
							'size' => 80,
							'value' => $bIsNew ? $this->oOption->arrDefaultParams['offsets'] : $this->oOption->arrOptions['boxes'][$strSidebarID]['offsets'],
							'default' => $this->oOption->arrDefaultParams['offsets'],
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
							'id' => 'field_capability',
							'title' => __( 'Access Rights', 'responsive-column-widgets' ),
							'tip' => __( 'Set the access level to the setting pages.', 'responsive-column-widgets' ),
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
							'id' => 'field_allowedhtmltags',
							'title' => __( 'Additional Allowed HTML Tags', 'responsive-column-widgets' ),
							'tip' => __( 'Specify which HTML tags are allowed to be posted in the New / Edit page to prevent them from being stripped out by the WordPress KSES filter, separated by commas. For security, many tags are not allowed by default.', 'responsive-column-widgets' ),
							'description' => __( 'Specify which HTML tags are allowed to be posted in the New / Edit page to prevent them from being stripped out by the WordPress KSES filter, separated by commas. For security, many tags are not allowed by default.', 'responsive-column-widgets' ) . ' '
								. 'e.g. "noscript, style"',
							'type' => 'text',
							'size' => 80,
							'default' => '',
						),	
						array(  // single button
							'id' => 'submit_save',
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
					'title' => __( 'Option Management', 'responsive-column-widgets' ), 
					'description' => __( 'Be carefult to perform these operations.', 'responsive-column-widgets' ),
					'fields' => array( 	// Field Arrays
						// Checkbox
						array(  
							'id' => 'field_initializeoptions',
							'title' => __( 'Initialize Options', 'responsive-column-widgets' ),
							'tip' => __( 'Clean all saved data and intialize to the default.', 'responsive-column-widgets' ),
							'description' => __( 'Clean all saved data and intialize to the default.', 'responsive-column-widgets' ),
							'type' => 'checkbox',
							'default' => 0,
							'label' => __( 'Initialize', 'responsive-column-widgets' ),
						),
						// Submit Buttons
						array(  // single button
							'id' => 'submit_perform',
							'type' => 'submit',		// the submit type creates a button
							'label' =>  __( 'Perform Checked', 'responsive-column-widgets' ),
							'class' => 'submit-buttons button button-secondary'
						),
					),
				),				
			)
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
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_label']			= $arrBoxOptions['label'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_sidebar']		= $strSidebarID;
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_description']	= $arrBoxOptions['description'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_before_widget']	= $arrBoxOptions['before_widget'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_after_widget']	= $arrBoxOptions['after_widget'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_before_title']	= $arrBoxOptions['before_title'];
		$arrAdminOptions['responsive_column_widgets']['section_sidebar']['field_after_title']	= $arrBoxOptions['after_title'];
		
		// Update the database so that the updated values will be displayed in the form fields
		update_option( RESPONSIVECOLUMNWIDGETSKEYADMIN, $arrAdminOptions );
	}	
	function IsTabNewOrEdit() {
		if ( ! isset( $_GET['tab'] ) ) return True; // new landing
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'neworedit' ) return True;
	}	
	function DetermineCurrentSidebarToEdit() {
		
		if ( $this->numPluginType == 0 ) return $this->oOption->arrDefaultParams['sidebar'];	// the default sidebar ID
		
		if ( isset( $_GET['sidebarid'] ) ) return $_GET['sidebarid'];
		
	}	
	function SetOptionObject( &$oOption ) {
		$this->oOption = $oOption;		
	}

	/*
	 * Modify the head and the foot parts
	 * */
	function head_ResponsiveColumnWidgets_Admin_Page( $strHead ) {
		$strButton = isset( $_GET['tab'] ) && $_GET['tab'] == 'manage' ? $this->GetAddNewBoxButton() : '';
		return $this->oUserAds->GetTopBanner()
			. $strHead 
			. '<div class="responsive-column-widgets-admin-body">'
			. '<table border="0" cellpadding="0" cellspacing="0" unselectable="on" width="100%">
			<tbody>
			<tr>
			<td valign="top">'
			. $this->oUserAds->GetTextAd()
			. $strButton;
	}
	function foot_ResponsiveColumnWidgets_Admin_Page( $strFoot ) {
		
		return $strFoot 
			. '<div style="float:left; margin-top: 10px" >' . $this->oUserAds->GetTextAd() . '</div>'
			. '</td>
			<td valign="top">' 
			. $this->oUserAds->GetSkyscraper() . '</td>
			</tr>
			</tbody>
			</table>'
			. '</div>';
	}
	
	/*
	 * Modify Page Body Part
	 * */
    function do_responsive_column_widgets() {  
	
		// function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		// echo get_submit_button( $text, $type, $name, $wrap, $other_attributes );		
// $str = RESPONSIVECOLUMNWIDGETSKEYADMIN;
// $bIsUpdated = delete_option( 'responsive_column_widgets_admin' );
// echo 'Admin Option Key : ' . $str . '<br />';
// echo '$bIsUpdated: ' . $bIsUpdated . '<br />';

	}	 
	function do_responsive_column_widgets_neworedit() {
		
		// Submit Button
		echo '<div style="float: right; margin-right: 10px">';
		if ( $this->numPluginType == 0 )
			$strButtonLabel = __( 'Save Changes', 'responsive-column-widgets' );
		else
			$strButtonLabel = isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ? __( 'Save Changes', 'responsive-column-widgets' ) : __( 'Add New Box', 'responsive-column-widgets' ); 
		submit_button( $strButtonLabel, 'primary' );
		echo '</div>';
	
		// debug
		// $arrOptions = ( array ) get_option( RESPONSIVECOLUMNWIDGETSKEY );
		// echo '<pre>' . htmlspecialchars( print_r( $arrOptions, true ) )  . '</pre>';
		// $arrOptionsAdmin = ( array ) get_option( RESPONSIVECOLUMNWIDGETSKEYADMIN );
		// echo '<pre>' . htmlspecialchars( print_r( $arrOptionsAdmin , true ) ) . '</pre>';		
		
	}
	// function head_responsive_column_widgets_manage( $strHead ) {
		// return $strHead;			
	// }
    function do_responsive_column_widgets_manage() {
		
		$this->RenderWidgetBoxTable();
		echo $this->GetDeleteButton();

		// debug
		// $sidebars_widgets = get_option('sidebars_widgets', array());		
		// echo '<pre>' . htmlspecialchars( print_r( $sidebars_widgets, true ) ). '</pre>';
    }
	function GetAddNewBoxButton() {
		return '<div class="submit-buttons" style="margin-bottom:20px; margin-top: 10px;"><span title="' . $this->strGetPro . '">'
			. $this->GetSubmitButton( 
				__( 'Add New Box', 'responsive-column-widgets' ),
				'button button-primary', 
				'', 
				'disabled="disabled"'
			)
			. '</span></div>';			
	}
	function GetDeleteButton() {
		return '<div class="submit-buttons" style="margin-bottom: 20px;"><span title="' . $this->strGetPro . '">'
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
	function do_responsive_column_widgets_general() {
		// echo '<div class="submit-buttons">';
		// submit_button();	
		// echo '</div>';
	}
	function do_responsive_column_widgets_information() {
		?>
		<h3><?php _e( 'Please Review', 'responsive-column-widgets' ); ?></h3>
		<p><?php _e( 'If you find the plugin useful, please <a href="http://wordpress.org/support/view/plugin-reviews/responsive-column-widgets">rate</a> it so that others can know it.', 'responsive-column-widgets' ); ?></p>
		<h3><?php _e( 'Get Pro', 'responsive-column-widgets' ); ?></h3>
		<p><?php _e( 'If you like the plugin and want more useful features, please upgrade it to <a href="http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro">Pro</a>.', 'responsive-column-widgets' ); ?></p>
		<h3><?php _e( 'Exchanges', 'responsive-column-widgets' ); ?></h4>
		<p><?php _e( 'You may receive a discount or a copy of the plugin by contributing one of the followings. A contribution does not have to be for this plugin. It can be for any product of miunosoft. Please just ask.', 'responsive-column-widgets' ); ?></p>
		<ul>
			<li><?php _e( 'Translation - submitting a localization file for an untranslated languge.', 'responsive-column-widgets' )?></li>
			<li><?php _e( 'Testimonial - with your photo, comments, and a link to your SNS page will be on the plugin site.', 'responsive-column-widgets' )?></li>
			<li><?php _e( 'Graphic Design - icons or banners for the plugin.', 'responsive-column-widgets' )?></li>
			<li><?php _e( 'Review Article - requires Google PageRank 3 or higher and the link to the product page.', 'responsive-column-widgets' )?></li>
			<li><?php _e( 'Video Tutorial - a brief instruction video tutorial with your narration.', 'responsive-column-widgets' )?></li>
			<li><?php _e( 'Something else - please ask for something else for the exchange.', 'responsive-column-widgets' )?></li>
		</ul>	
		<?php
	}
	
	/*
	 * Modify Fileds
	 * */
	function field_field_label( $strHTML ) {
		$bIsNew =  $this->bIsNew  ? 1 : 0;			
		return $strHTML . "<input type='hidden' name='isnew' value='{$bIsNew}' />";
	}
	
	/*
	 * Validate Post Data
	 * */
	function validation_responsive_column_widgets_neworedit( $arrInput ) {


		// Sanitize HTML Post Data
		$arr = array();
		foreach( $arrInput[ $this->strPluginSlug ]['section_sidebar'] as $strField => $strHTML ) 
			$arr[$strField] = $this->FilterPostHTMLCode( $arrInput[ $this->strPluginSlug ]['section_sidebar'][$strField] );
		$arrInput[ $this->strPluginSlug ]['section_sidebar'] = $arr;

		// Set the variables.
		$bIsValid = True;
		$arrErrors = array();
		$strErrors = '';
		
		// Check if the label is not empty - if the "field_label" key is not set, it means it's disabled, which occures to the default widget box.
		if ( isset( $arrInput[ $this->strPluginSlug ]['section_sidebar']['field_label'] ) ) {
			
			$arrInput[ $this->strPluginSlug ]['section_sidebar']['field_label'] = trim( $arrInput['responsive_column_widgets']['section_sidebar']['field_label'] );
			
			// Check if the label is empty
			if ( empty( $arrInput[ $this->strPluginSlug ]['section_sidebar']['field_label'] ) ) {
				$arrErrors['section_sidebar']['field_label'] = '';
				$strErrors .= '<p>' . __( 'The label cannot be empty.', 'responsive-column-widgets' ) . '</p>';
				$bIsValid = False;
			}
			
			// Check if the same label name is used.
			if ( $_POST['isnew'] == 1 && $this->IsLabelAlreadyUsed( $arrInput[ $this->strPluginSlug ]['section_sidebar']['field_label'] ) ) {
				$arrErrors['section_sidebar']['field_label'] = $arrInput[ $this->strPluginSlug ]['section_sidebar']['field_label'];
				$strErrors .= '<p>' . __( 'The same label already used.', 'responsive-column-widgets' ) . '</p>';
				$bIsValid = False;
			}
		}

		// for debug
		// add_settings_error( $_POST['pageslug'], 
			// 'can_be_any_string',  
			// '<h3>Submitted Values</h3>' 
			// . '<h4>$bIsValid:</h4><pre>' . $bIsValid . '</pre>' 
			// . '<h4>Is New:</h4><pre>' . $_POST['isnew'] . '</pre>' 
			// . '<h4>$strErrors:</h4><pre>' . $strErrors . '</pre>' 
			// . '<h4>Transient Key:</h4><pre>' . get_class( $this ) . '_' . $_POST['pageslug'] . '</pre>'
			// . '<h4>$arrInput</h4><pre>' . htmlspecialchars( print_r( $arrInput, true ) ) . '</pre>' 
			// . '<h4>$_POST</h4><pre>' . htmlspecialchars( print_r( $_POST, true ) ) . '</pre>'
			// ,'updated'
		// );			
		
		if ( !$bIsValid  ) {
			
			// This line is reached if there are invalid values.
			// Store the error array in the transient with the name of the extended class name + _ + page slug.
			set_transient( md5( get_class( $this ) . '_' . $_POST['pageslug'] ), $arrErrors, 60*5 );	// store it for 5 minutes ( 60 seconds * 5 )
			
			// This displays the error message
			add_settings_error( $_POST['pageslug'], 'can_be_any_string',  $strErrors  );	
			
			// Returning an empty array will not change options.
			return array();				
			
		}
		
		/*
		 * Sanitize Values
		 * */	 				
		/*	
			[responsive_column_widgets] => Array
					[section_sidebar] => Array
						(
							[field_label] => 
							[field_description] => 
							[field_before_widget] => 
							[field_after_widget] => 
							[field_before_title] => 
							[field_after_title] => 
					[section_params] => Array
						(
							[field_columns] => 3
							[field_maxwidgets] => 0
							[field_maxrows] => 0
							[field_omit] => 
							[field_showonly] => 
							[field_offsets] => 800: 1, 600: 2, 480: 3, 320: 4, 240: 5
						)
						
		*/		
		 // FixNumber( $numToFix, $numDefault, $numMin="", $numMax="" ) 
		$arrInput[ $this->strPluginSlug ]['section_params']['field_maxwidgets'] = $this->FixNumber( $arrInput[ $this->strPluginSlug ]['section_params']['field_maxwidgets'], 0, 0 );
		$arrInput[ $this->strPluginSlug ]['section_params']['field_maxrows'] = $this->FixNumber( $arrInput[ $this->strPluginSlug ]['section_params']['field_maxrows'], 0, 0 );

		// The data are valid. Update the box options.
		// Setup the box option array
		$this->UpdateBoxOptions( $arrInput[ $this->strPluginSlug ], $_POST['isnew'] );
		add_settings_error( 
			$_POST['pageslug'], 
			'can_be_any_string', 
			__( 'The widget box options have been saved.', 'responsive-column-widgets' ), 
			'updated' 
		);
		
		delete_transient( md5( get_class( $this ) . '_' . $_POST['pageslug'] ) );
		return $arrInput;
			
	}
	function UpdateBoxOptions( $arrInput, $bIsNew ) {
		
		$arrBoxOptions = array();		
		$arrBoxOptions['sidebar'] = ! empty( $bIsNew ) ? $this->GetAvailableSidebarID() : $arrInput['section_sidebar']['field_sidebar'];
		$arrBoxOptions['label'] = isset( $arrInput['section_sidebar']['field_label'] ) ? $arrInput['section_sidebar']['field_label'] : $this->oOption->arrOptions['boxes'][ $arrInput['section_sidebar']['field_sidebar'] ]['label'];
		$arrBoxOptions['description'] = $arrInput['section_sidebar']['field_description'];
		$arrBoxOptions['before_widget'] = $arrInput['section_sidebar']['field_before_widget'];
		$arrBoxOptions['after_widget'] = $arrInput['section_sidebar']['field_after_widget'];
		$arrBoxOptions['before_title'] = $arrInput['section_sidebar']['field_before_title'];
		$arrBoxOptions['after_title'] = $arrInput['section_sidebar']['field_after_title'];
		$arrBoxOptions['message_no_widget'] = $arrInput['section_sidebar']['field_message_no_widget'];
		$arrBoxOptions['columns'] = $arrInput['section_params']['field_columns'];
		$arrBoxOptions['maxwidgets'] = $arrInput['section_params']['field_maxwidgets'];
		$arrBoxOptions['maxrows'] = $arrInput['section_params']['field_maxrows'];
		$arrBoxOptions['omit'] = $arrInput['section_params']['field_omit'];
		$arrBoxOptions['showonly'] = $arrInput['section_params']['field_showonly'];
		$arrBoxOptions['offsets'] = $arrInput['section_params']['field_offsets'];
		
		// Update
		$this->oOption->InsertBox( $arrBoxOptions['sidebar'], $arrBoxOptions );
		$this->oOption->Update();		
		
	}
	function FixNumber( $numToFix, $numDefault, $numMin="", $numMax="" ) {
	
		// checks if the passed value is a number and set it to the default if not.
		// if it is a number and exceeds the set maximum number, it sets it to the max value.
		// if it is a number and is below the minimum number, it sets to the minimium value.
		// set a blank value for no limit
		if ( !is_numeric( trim( $numToFix ) ) ) return $numDefault;
			
		if ( $numMin != "" && $numToFix < $numMin) return $numMin;
			
		if ( $numMax != "" && $numToFix > $numMax ) return $numMax;

		return $numToFix;
		
	}			
	function IsLabelAlreadyUsed( $strLabel ) {
		// since 1.0.4
		foreach( $this->oOption->arrOptions['boxes'] as $strSidebarID => $arrBoxOptions ) 			
			if ( $arrBoxOptions['label'] == $strLabel ) return True;

	}	
	function GetAvailableSidebarID() {
		// since 1.0.4
		// return 'responsive_column_widgets_' . uniqid();

		/*
		 *  The following method turned out to be incompatible with the WordPress code structure that registers sidebars.
		 *  When a sidebar is removed, if the ID is like ..._n where n is a diget, then it remains in the WordPress database as an inactive sidebar.
		 *  So plugin must use a different name structue for the ID.
		 * */
		$numID = '';
		$arrBoxes = ( array ) $this->oOption->arrOptions['boxes'];
		$arrBoxes = array_reverse( $arrBoxes, true);	// the ID number is ascending so read from the last one.
		foreach( $arrBoxes as $strID => $v ) {
				
			preg_match( '/^(.+\D)(\d+)$/', $strID, $arrMatches );	// get the last digits
			if ( ! isset( $arrMatches[2] ) ) continue;
			
			$numID = $arrMatches[2] + 1;
			if ( ! isset( $this->oOption->arrOptions['boxes'][$arrMatches[1] . $numID] ) ) 
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
		$arrNumericAllowedHTMLTags = $this->oOption->arrOptions['general']['allowedhtmltags'];
		foreach( $arrNumericAllowedHTMLTags as $strHTMLTag ) 
			$arrAllowedHTMLTags[$strHTMLTag] = array();
		$strHTML = $this->EscapeAndFilterPostKSES( $strHTML, $arrAllowedHTMLTags );
		return $strHTML;
		
	}
	function validation_responsive_column_widgets_manage( $arrInput ) {
		// add_settings_error( $_POST['pageslug'], 
			// 'can_be_any_string',  
			// '<h3>Submitted Values</h3>' .
			// '<h4>$_POST</h4><pre>' . print_r( $_POST, true ) . '</pre>' . 
			// '<h4>$arrInput</h4><pre>' . print_r( $arrInput, true ) . '</pre>'
			// ,'updated'
		// );			
		
		/*
		 * Delete Checked Widget Box Items
		 * */
		if ( isset( $arrInput['delete'] ) ) {
			$strMsg = '';
			$bIsUnset = False;
			$arrSidebarOptions = get_option( 'sidebars_widgets', array() );
			foreach( ( array ) $arrInput['delete'] as $strSidebarID => $numValue ) {
				
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
				add_settings_error( $_POST['pageslug'], 'can_be_any_string', $strMsg, 'updated' );
				
			}
			// unless unsetting the key, it will remain in the database. 
			unset( $arrInput['delete'] );
		}
		
		return $arrInput;
	}
	function validation_responsive_column_widgets_general( $arrInput ) {
		add_settings_error( $_POST['pageslug'], 
			'can_be_any_string',  
			'<h3>Submitted Values</h3>' .
			'<h4>$arrInput</h4><pre>' . htmlspecialchars( print_r( $arrInput, true ) ) . '</pre>'
			,'updated'
		);	
			
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
			&& isset( $arrInput['responsive_column_widgets']['section_dangerzone']['field_initializeoptions'] )
			&& $arrInput['responsive_column_widgets']['section_dangerzone']['field_initializeoptions'] == 1 
		) {
			
			// Delete the plugin main options
			$this->oOption->arrOptions = null;
			$this->oOption->Update();
			
			// Delete the admin page options as well.
			return null;	
		}	
		
		// Do some validation and sanitization here.
		// field_allowedhtmltags
		$this->oOption->arrOptions['general']['capability'] = $arrInput['responsive_column_widgets']['section_general']['field_capability'];
		$this->oOption->arrOptions['general']['allowedhtmltags'] = preg_split( '/[, ]+/', $arrInput['responsive_column_widgets']['section_general']['field_allowedhtmltags'], -1, PREG_SPLIT_NO_EMPTY );
		
		// Update the value to the separate main option.
		$this->oOption->Update();
		
		return $arrInput;
	}

	function EscapeAndFilterPostKSES( $strString, $arrAllowedTags = array(), $arrDisallowedTags=array(), $arrAllowedProtocols = array() ) {
		// $arrAllowedTags : e.g. array( 'noscript' => array(), 'style' => array() );
		// $arrDisallowedTags : e.g. array( 'table', 'tbody', 'thoot', 'thead', 'th', 'tr' );

		global $allowedposttags;
		$arrAllowedHTML = array_replace_recursive( $allowedposttags, $arrAllowedTags );	// the second parameter takes over the first.
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
	function RenderWidgetBoxTable() {
		?>
		<div class="submit">
			<table class="wp-list-table widefat fixed posts responsive_column_widgets_admin" cellspacing="0" >
				<thead><?php $this->RenderWidgetBoexTableHeader(); ?></thead>
				<tbody id="the-list">
					<?php
						$this->RenderWidgetBoexTableDefaultRow();
						$this->RenderWidgetBoexTableRows();
					?>
				</tbody>
				<tfoot><?php $this->RenderWidgetBoexTableHeader(); ?></tfoot>
			</table>
			<?php 
			// $this->RenderSubmitButton( $this->pluginkey . '[remove_button]'
									// , __( 'Remove Checked', 'responsive-column-widgets' ) ); 
			?>
		</div>
		<?php	
	}	 
	function RenderWidgetBoexTableHeader() {
	?>
		<tr style="">
			<th scope="col" class="manage-column column-cb check-column" style="vertical-align:middle; padding-left:4px;" valign="middle">
				<input type="checkbox">
			</th>
			<th scope="col" class="manage-column column-label asc desc sortable" style="width:22%;">
				<span><?php _e( 'Box Label', 'responsive-column-widgets' ); ?> / <?php _e( 'Description', 'responsive-column-widgets' ); ?></span>
			</th>
			<th scope="col" class="manage-column column-label asc desc sortable" style="width:20%;">
				<span><?php _e( 'Sidebar ID', 'responsive-column-widgets' ); ?></span>
			</th>
			<th scope="col" class="manage-column column-label asc desc sortable" style="width:44%;">
				<span><?php _e( 'Shortcode', 'responsive-column-widgets' ); ?> / <?php _e( 'PHP Code', 'responsive-column-widgets' ); ?> <?php _e( 'Example', 'responsive-column-widgets' ); ?></span>
			</th>	
			<th scope="col" class="manage-column column-label asc desc sortable operation" style="width:10%;">
				<span><?php _e( 'Operation', 'responsive-column-widgets' ); ?></span>
			</th>				
		</tr>
	<?php
	}
	function RenderWidgetBoexTableDefaultRow() {
	?>
		<tr class="responsive_column_widgets_default_row" >
			<?php echo '<td align="center" class="check-column first-col" style="padding: 8px 0 8px" ></td>'; ?>
			<td>
				<ul style="margin:0;">
					<li><b><?php echo $this->oOption->arrOptions['boxes'][$this->oOption->arrDefaultParams['sidebar']]['label']; ?></b></li>
					<li><?php echo $this->oOption->arrOptions['boxes'][$this->oOption->arrDefaultParams['sidebar']]['description']; ?></li>
				</ul>
			</td>
			<td>responsive_column_widgets</td>
			<td>
				<ul style="margin:0;">
					<li>[<?php echo $this->oOption->arrOptions['boxes'][$this->oOption->arrDefaultParams['sidebar']]['sidebar']; ?>]</li>
					<li>&lt;?php ResponsiveColumnWidgets(); ?&gt;</li>
				</ul>
			</td>
			<td class="operation">
				<?php
					$strURL = admin_url( 'admin.php?page=' . $_GET['page'] . '&tab=neworedit&sidebarid=responsive_column_widgets' . '&mode=edit' );
					echo "<a href='{$strURL}'>" . __( 'Edit', 'responsive-column-widgets' ) . "</a>";
					// . "&nbsp;|&nbsp;"
					// $strURL = admin_url( 'admin.php?page=' . $_GET['page'] . '&tab=' . $_GET['tab'] . '&sidebarid=responsive_column_widgets&view=true' );
					// echo "<a href='{$strURL}'>" . __( 'View', 'responsive-column-widgets' ) . "</a>";					 
				?>
			</td>
		</tr>	
	<?php
	}
	function RenderWidgetBoexTableRows() {}
	 
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
		';
	}
	function style_responsive_column_widgets_manage( $strStyle ) {
		return $strStyle . '
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
		';
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
	protected $numPluginType = 0;
	protected $strGetPro = 'Get Pro to enabel this feature!';

}