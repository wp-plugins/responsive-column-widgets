<?php
class ResponsiveColumnWidgets_Admin_Page_ extends ResponsiveColumnWidgets_Admin_Page_Framework {

    // Properties
    protected $strPluginName = 'Responsive Column Widgets';
    protected $strPluginSlug = 'responsive_column_widgets';
    
    /**
     * 
     * @remark      used with the CheckKeys() method to allow missing keys when an array is validated    
     */
    protected $arrRecentlyAddedOptionKeys = array(    
        // since 1.0.8
        'insert_comment_form',                
        'insert_comment_form_positions',     
        'insert_comment_form_disable_front',
        'insert_comment_form_disable_post_ids',
        // since 1.0.9
        'autoinsert_enable',        
        'autoinsert_enable_areas',
        'autoinsert_position',
        'autoinsert_enable_filters',
        'autoinsert_enable_actions',
        'autoinsert_enable_pagetypes',
        'autoinsert_enable_posttypes',
        'autoinsert_enable_categories',
        'autoinsert_enable_post_ids',
        'autoinsert_disable_pagetypes',
        'autoinsert_disable_posttypes',
        'autoinsert_disable_categories',
        'autoinsert_disable_post_ids',
        
        // 1.1.1+
        'default_media_only_screen_max_width',
        
        // 1.1.1.2+
        'remove_id_attributes',
        
        // 1.1.5+
        'colspans',
        
        // 1.1.6+
        'cache_duration',
        'offsets',    // deprecated
        
        // 1.1.7+
        'before_widget_box',
        'after_widget_box',
        'widget_box_container_background_color',
        'widget_box_container_paddings',
        'widget_box_max_width',
        'call_id',  
        
        'widget_box_column_text_alignment', // 1.1.8.4+
        'delay_register_sidebar',   // 1.1.9+
    );
    
    /**
     * 
     * @since   1.1.1.2
     * @remark  {n} seconds * minutes * hours * days; 1209600 is 2 weeks.
     */
    protected $intIntervalToShowPleaseRate = 1209600;    
    
    // Flags
    protected $bIsNew;

    // Objects
    /**
     * Stores the option object.
     * 
     * @remark  It is set via the SetOptionObject() method.
     */
    public $oOption; 
    
    function start_ResponsiveColumnWidgets_Admin_Page() {
                                    
        if ( is_admin() && isset( $GLOBALS['pagenow'] ) && 'plugins.php' === $GLOBALS['pagenow'] ) {
            $this->AddLinkToPluginDescription(
                array(
                    '<a href="http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro/?lang=' . esc_attr( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en' ) . '">' . __( 'Get Pro', 'responsive-column-widgets' ) . '</a>',
                    '<a href="http://en.michaeluno.jp/donate">' . __( 'Donate', 'responsive-column-widgets' ) . '</a>',
                    '<a href="http://en.michaeluno.jp/contact/custom-order/?lang=' . esc_attr( defined( 'WPLANG') && WPLANG ? WPLANG : 'en' ) . '">' . __( 'Order custom plugin', 'responsive-column-widgets' ) . '</a>',
                )        
            );                
        }

        // In the plugin setting page.
        if ( isset( $_GET['page'] ) && $_GET['page'] == $this->strPluginSlug ) {            
        
            // Objects
            $this->oUserAds = new ResponsiveColumnWidgets_UserAds;

            // Add color picker script. Since 1.1.7
            add_action( 'admin_init', array( $this, 'addColorPickerScript' ) );
            
        }

        // Properties
        $this->strGetPro = __( 'Get Pro to enable this feature!', 'responsive-column-widgets' );
        $this->strGetProNow = __( 'Get Pro Now!', 'responsive-column-widgets' );

        
    }
    
    /**
     * 
     * @since       1.1.7
     * @see         http://www.sitepoint.com/upgrading-to-the-new-wordpress-color-picker/
     */
    public function addColorPickerScript(){
             
        // If the WordPress version is greater than or equal to 3.5, then load the new WordPress color picker.
        if ( version_compare( $GLOBALS['wp_version'], '3.5', '>=' ) ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
            return;
        }
        
        // Otherwise, load the older farbtasic color picker.
        wp_enqueue_style( 'farbtastic' );
        wp_enqueue_script( 'farbtastic' );   
        
    }    

    /**
     * Sets up admin pages.
     */
    function setUp() {
        
        // Set the access rights to the option page.
        $numCapability = $this->oOption->arrOptions['general']['capability'];
        $this->SetCapability( $this->oOption->arrCapabilities[ $numCapability ? $numCapability : 0 ] );

        // Build menu and pages
        $this->SetRootMenu( 'Appearance' );          // specifies to which parent menu to belong.
        $this->AddSubMenu(  
            $this->oOption->oInfo->Name,    // page and menu title
            $this->strPluginSlug     // page slug
        );     
        
        // Add in-page tabs.        
        $this->AddInPageTabs( $this->strPluginSlug,    
            array(    // slug => title
                // 'widgets'        => __( 'Widgets', 'responsive-column-widgets' ),
                'neworedit'     => '<span class="newtab">' . __( 'New', 'responsive-column-widgets' ) . '</span>&nbsp;<span class="slash">/</span>&nbsp;' . __( 'Edit', 'responsive-column-widgets' ),
                'manage'        => __( 'Manage', 'responsive-column-widgets' ),
                'general'        => __( 'General', 'responsive-column-widgets' ),
                'information'    => __( 'Information', 'responsive-column-widgets' ),
                'getpro'        => __( 'Get Pro!', 'responsive-column-widgets' ),
            )
        );            
        
        // Determine which widget box it is.
        $strSidebarID = $this->DetermineCurrentSidebarToEdit();    // the returned value can be empty.

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
                            'id'            => 'label',
                            'title'         => __( 'Widget Box Label', 'responsive-column-widgets' ),
                            'description'   => __( 'Set a unique name for the widget box.', 'responsive-column-widgets' ),
                            'error'         => __( 'The label neither cannot be empty nor use the same one that already exists.', 'responsive-column-widgets' ),
                            'type'          => 'text',
                            'size'          => 40,
                            'class'         => 0 == $this->numPluginType || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? 'disabled' : '',
                            'disable'       => 0 == $this->numPluginType || isset( $_GET['sidebarid'] ) && $_GET['sidebarid'] == 'responsive_column_widgets' ? true : false,
                            'value'         => 0 == $this->numPluginType 
                                ? $this->oOption->arrDefaultParams['label'] 
                                : (  
                                    $bIsNew  
                                        ? '' 
                                        : $this->oOption->arrOptions['boxes'][ $strSidebarID ]['label'] 
                                ),
                            'post_html'     => "<input type='hidden' name='isnew' value='{$bIsNew}' />",
                        ),
                        array(
                            'id'            => 'sidebar',
                            'title'         => __( 'Widget Box Sidebar ID', 'responsive-column-widgets' ),
                            'tip'           => __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
                            'description'   => $bIsNew 
                                ? __( 'A new ID will be automatically generated.', 'responsive-column-widgets' ) 
                                : __( 'The sidebar ID associated with this widget box.', 'responsive-column-widgets' ),
                            'type'          => 'text',
                            'size'          => 40,
                            'readonly'      => true,
                            'class'         => 'disabled',
                            'value'         => $bIsNew 
                                ? '' 
                                : ( $this->numPluginType == 0 ? $this->oOption->arrDefaultParams['sidebar'] : $strSidebarID ),
                        ),                        
                        array(
                            'id' => 'description',
                            'title' => __( 'Widget Box Description', 'responsive-column-widgets' ),
                            'description' => __( 'Additional notes for this box.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'size' => 40,
                            'value' => $arrWidgetBoxOptions['description'],
                        ),    
                        array(
                            'id' => 'before_widget_box',
                            'title' => __( 'Widget Box Beginning Tag', 'responsive-column-widgets' ),
                            'description' => __( 'Set the html opening tag that will be inserted before the widget box output.', 'responsive-column-widgets' ) 
                                . ' e.g. <code>&lt;div class="my-custom-class-selector"&gt;</code>',
                            'type' => 'text',
                            'size' => 40,
                            'value' => $arrWidgetBoxOptions['before_widget_box'],
                        ),
                        array(
                            'id' => 'after_widget_box',
                            'title' => __( 'Widget Box Ending Tag', 'responsive-column-widgets' ),
                            'description' => __( 'Set the html closing tag that will be inserted after the widget box output.', 'responsive-column-widgets' )
                                . ' e.g. <code>&lt;/div&gt;</code>',
                            'type' => 'text',
                            'size' => 40,
                            'value' => $arrWidgetBoxOptions['after_widget_box'],
                        ),                        
                        array(
                            'id' => 'before_widget',
                            'title' => __( 'Widget Beginning Tag', 'responsive-column-widgets' ),
                            'description' => __( 'Set the html opening tag that will be inserted before each widget output.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'size' => 40,
                            'value' => $arrWidgetBoxOptions['before_widget'],
                        ),
                        array(
                            'id' => 'after_widget',
                            'title' => __( 'Widget Ending Tag', 'responsive-column-widgets' ),
                            'description' => __( 'Set the html closing tag that will be inserted after each widget output.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'size' => 40,
                            'value' => $arrWidgetBoxOptions['after_widget'],
                        ),
                        array(
                            'id'            => 'before_title',
                            'title'         => __( 'Starting Tag for Widget Title', 'responsive-column-widgets' ),
                            'description'   => __( 'Set the before_title html opening tag.', 'responsive-column-widgets' ),
                            'type'          => 'text',
                            'size'          => 40,
                            'value'         => $arrWidgetBoxOptions['before_title'],
                        ),
                        array(
                            'id'            => 'after_title',
                            'title'         => __( 'Ending Tag for Widget Title', 'responsive-column-widgets' ),
                            'description'   => __( 'Set the after_title html closing tag.', 'responsive-column-widgets' ),
                            'type'          => 'text',
                            'size'          => 40,
                            'value'         => $arrWidgetBoxOptions['after_title'],
                        ),
                        array(
                            'id' => 'message_no_widget',
                            'title' => __( 'Message for No Widget', 'responsive-column-widgets' ),
                            'description' => __( 'Set the message which appears when no widget is added; thus, nothing can be rendered.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'size' => 40,
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
                            'size' => 40,
                            'title' => __( 'Numbers of Columns', 'responsive-column-widgets' ),
                            'value' => $this->GetColumnStringValueForInput( 
                                $bIsNew ? $this->oOption->GetDefaultValue( 'columns' ) : $this->oOption->arrOptions['boxes'][ $strSidebarID ]['columns'], 
                                $bIsNew ? $this->oOption->GetDefaultValue( 'default_media_only_screen_max_width' ) : $this->oOption->arrOptions['boxes'][ $strSidebarID ]['default_media_only_screen_max_width'] 
                            ),
                            'pre_field' => $this->GetColumnValueDetails( $bIsNew ? $this->oOption->arrDefaultParams['columns'] : $this->oOption->arrOptions['boxes'][ $strSidebarID ]['columns'] ),
                            'post_field' => '<p class="description">' . __( 'Set the number of columns separated by commas. Each delimited element number corresponds to the order number of the rows.', 'responsive-column-widgets' ) . '&nbsp;'
                                . __( 'Min', 'responsive-column-widgets' ) . ' 1 ' . __( 'Max', 'responsive-column-widgets' ) . ' 12 '
                                . __( '( for each row )', 'responsive-column-widgets' ) . '</p>e.g. <code>4, 2, 3</code>'
                                . '<p class="description">' . __( 'To set the number of columns by screen max-width, use the colon(:) character after the width in pixel, and use the pipe (|) character to delimit each set of number of columns. If the pixel is omitted, it is considered no limit. If the pipe delimiter is not present, the plugin will add 600: 1 internally by default.', 'responsive-column-widgets' ) 
                                . '</p>'
                                . '<p class="description">' . __( 'Format', 'responsive-column-widgets' ) . ': <code>' . __( 'column value | pixel: column value | pixel: column value | ...', 'responsive-column-widgets' ) . '</code><br />'
                                . __( 'The following example displays widgets in 5 column when the browser width is greater than 800, and four when the width is 601 to 800, and three when the width is 481 to 600, and one when the width is 1 to 480.', 'responsive-column-widgets' ) . '</p>e.g. <code>5 | 800: 4 | 600: 3 |480: 1</code>',
                            'type' => 'text',    // must not be number because it's a string containing a sequence of numbers with commas.
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
                            'size' => 40,
                            'description' => __( 'Set the numbers of omitting widgets separated by commas.', 'responsive-column-widgets' ) 
                                . ' e.g. "2, 5, 8" ' . __( 'where the second, the fifth, and the eighth ones will be skipped.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'omit' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['omit'] ),
                        ),    
                        array(
                            'id' => 'showonly',
                            'title' => __( 'Show-only Widgets', 'responsive-column-widgets' ),
                            'size' => 40,
                            'description' => __( 'Set the numbers of show-only widgets separated by commas.', 'responsive-column-widgets' ) 
                                . ' e.g. "1, 3" ' . __( 'where only the first and the third ones will be shown.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'showonly' ) : $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['showonly'] ),
                        ),            
                        array(
                            'id' => 'colspans',
                            'title' => __( 'Column Spans', 'responsive-column-widgets' ),
                            'size' => 40,
                            'description' => __( 'Set the column spans separated by comma and dash.', 'responsive-column-widgets' ) . '<br />'
                                . __( 'Format', 'responsive-column-widgets' ) . ': <code>widget index - column span, widget index - column span, widget index - column span, ...</code><br />' 
                                . 'e.g. "<code>1-3, 4-2, 7-4</code>", ' . __( 'where the first widget takes 3 columns and the fourth widget takes two and the seventh takes four. Unspecified widget items will have one column span.', 'responsive-column-widgets' ) . '<br />'
                                . __( 'To set them by screen max-width, use the colon(:) character after the width in pixel, and use the pipe (|) character to delimit each set of column spans. If the pixel is omitted, it is considered no limit. These widths need to correspond to the above Number of Columns parameter.', 'responsive-column-widgets' ) . '<br />'
                                . ' e.g. <code>1-3, 4-2, 7-4 | 600: 1-2, 3-2, 7-3 | 480: 1-2</code>',
                            'type' => 'text',
                            'value' => $bIsNew ? $this->getColSpanValueForInput( $arrWidgetBoxOptions['colspans'] ) : $this->getColSpanValueForInput( $this->oOption->arrOptions['boxes'][ $strSidebarID ]['colspans'] ),
                        ),        
                        array(  
                            'id' => 'cache_duration',
                            'title' => __( 'Cache Duration', 'responsive-column-widgets' ),
                            'type' => 'number',
                            'min' => 0,
                            'size' => 10,
                            'pre_field' => '',
                            'post_field' => ' ' . __( 'seconds', 'responsive-column-widgets' ),
                            'description' => __( 'This sets how long the cache for the widget box\'s output remains. Set 0 to disable this feature.', 'responsive-column-widgets' ) . '&nbsp;' . __( 'Default', 'responsive-column-widgets' ) . ': ' . 0 . '<br />' // '
                                . 'e.g. <code>3600</code>',
                            'value' => $arrWidgetBoxOptions['cache_duration'],
                        ),                            
                        array(  // single button
                            // 'pre_html' => '<div class="text-info">' . ( isset( $this->oUserAds ) ? $this->oUserAds->GetTextAd() : '' ) . '</div>',
                            'id' => 'submit_save_neworedit_middle',
                            'type' => 'submit',        // the submit type creates a button
                            'label' => $this->numPluginType == 0 || isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ? __( 'Save Changes', 'responsive-column-widgets' ) : __( 'Add New Box', 'responsive-column-widgets' ),
                            'class' => 'neworedit-button submit-buttons button button-primary',
                            'pre_field' => '<div class="neworedit-button">',
                            'post_field' => '</div>',                            
                            'redirect' => $this->numPluginType == 0 || ( isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ) ? null : admin_url( "admin.php?page={$this->strPluginSlug}&tab=manage&updated=true" ),
                        ),                            
                    ),                    
                ),
                // array(    // since 1.1.6
                    // 'pageslug' => $this->strPluginSlug,
                    // 'tabslug' => 'neworedit',
                    // 'id' => 'section_cache', 
                    // 'title' => __( 'Cache', 'responsive-column-widgets' ), 
                    // 'fields' => array(        
                        // array(  
                            // 'id' => 'cache_duration',
                            // 'title' => __( 'Cache Duration', 'responsive-column-widgets' ),
                            // 'type' => 'number',
                            // 'min' => 0,
                            // 'size' => 10,
                            // 'pre_field' => '',
                            // 'post_field' => ' ' . __( 'seconds', 'responsive-column-widgets' ),
                            // 'description' => __( 'This sets how long the cache for the widget box\'s output remains. Set 0 to disable this feature.', 'responsive-column-widgets' ) . '&nbsp;' . __( 'Default', 'responsive-column-widgets' ) . ': ' . 0 . '<br />'
                                // . 'e.g. 3600',
                            // 'value' => $arrWidgetBoxOptions['cache_duration'],
                        // ),                                
                    // ),
                // ),                
                array(
                    'pageslug' => $this->strPluginSlug,
                    'tabslug' => 'neworedit',
                    'id'    => 'section_autoinsert',
                    'title'    => __( 'Auto-Insert', 'responsive-column-widgets' ), 
                    'fields' => array(
                        array(
                            'id' => 'autoinsert_enable',
                            'title' => __( 'Enable Auto-insert', 'responsive-column-widgets' ),
                            'description' => __( 'Insert the widget box automatically. If this is Off, the below settings in this section do not take effects.', 'responsive-column-widgets' ),
                            'type' => 'radio',
                            'value' => $arrWidgetBoxOptions['autoinsert_enable'],
                            'label' => array( 
                                1 => __( 'On', 'responsive-column-widgets' ),
                                0 => __( 'Off', 'responsive-column-widgets' ),
                            ),
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            'post_html' => '<hr />',
                        ),
                        array(
                            'id' => 'autoinsert_enable_areas',
                            'title' => __( 'Areas ( hook )', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'value' => $arrWidgetBoxOptions['autoinsert_enable_areas'],
                            'label' => array(
                                'the_content' => __( 'Post / Page Content ( the_content )', 'responsive-column-widgets' ),
                                'wp_footer' => __( 'Footer ( wp_footer )', 'responsive-column-widgets' ),
                                'comment_text' => __( 'Comment Text ( comment_text )', 'responsive-column-widgets' ),
                            ),
                            'description' => __( 'Check where the auto-insertion should be performed.', 'responsive-column-widgtes' ),
                        ),    
                        array(    
                            'id' => 'autoinsert_enable_filters',
                            'type' => 'text',
                            'size' => 40,
                            'title' => __( 'Filters', 'responsive-column-widgets' ),
                            'description' => sprintf( __( 'Enter the WordPress <a href="%1$s">filters</a> with which the auto-insertion is performed, separated by commas.', 'responsive-column-widgets' ), 'http://codex.wordpress.org/Plugin_API/Filter_Reference' )
                                . '<br />e.g. the_excerpt, my_custom_filter, other_plugin_filter',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'autoinsert_enable_filters' ) : $this->oOption->ConvertOptionArrayValueToString( $arrWidgetBoxOptions['autoinsert_enable_filters'] ),    
                        ),                            
                        array(
                            'id' => 'autoinsert_position',
                            'title' => __( 'Position', 'responsive-column-widgets' ),
                            'type' => 'radio',
                            'value' => $arrWidgetBoxOptions['autoinsert_position'],
                            'label' => array(
                                __( 'Above', 'responsive-column-widgets' ),
                                __( 'Below', 'responsive-column-widgets' ),
                                __( 'Both', 'responsive-column-widgets' ),
                            ),
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            'description' => __( 'This option determines whether the widet box should be placed before or after ( above or below ) the provided contents by the hooking filters. This does not take effects for action hooks such as wp_footer.', 'responsive-column-widgets' ),
                        ),
                        array(    
                            'id' => 'autoinsert_enable_actions',
                            'type' => 'text',
                            'size' => 40,
                            'title' => __( 'Actions', 'responsive-column-widgets' ),
                            'description' => sprintf( __( 'Enter the WordPress <a href="%1$s">actions</a> with which the auto-insertion is performed, separated by commas.', 'responsive-column-widgets' ), 'http://codex.wordpress.org/Plugin_API/Action_Reference' )
                                . '<br />e.g. login_footer, comment_form_before, comment_form_after, my_custom_action, other_plugin_action',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'autoinsert_enable_actions' ) : $this->oOption->ConvertOptionArrayValueToString( $arrWidgetBoxOptions['autoinsert_enable_actions'] ),    
                            'post_html' => '<hr />',
                        ),                            
                        array(
                            'id' => 'autoinsert_enable_pagetypes',
                            'title' => __( 'Limit Auto-Insert to Checked Page Types', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'value' => $arrWidgetBoxOptions['autoinsert_enable_pagetypes'],
                            'label' => array(
                                'is_home' => __( 'Home / Front Page', 'responsive-column-widgets' ),
                                'is_archive' => __( 'Archives', 'responsive-column-widgets' ),
                                'is_404' => __( '404 Page', 'responsive-column-widgets' ),
                                'is_search' => __( 'Search Results', 'responsive-column-widgets' ),
                            ),
                            'description' => __( 'Check the page types that the auto-insertion should be performed to restrict it to certain page types. Leave them all unchecked if you want the auto-insertion to perform in all types of pages.', 'responsive-column-widgets' ),                            
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                        ),                            
                        array(
                            'id' => 'autoinsert_enable_posttypes',
                            'title' => __( 'Limit Auto-Insert to Checked Post Types', 'responsive-column-widgets' ),
                            'description' => __( 'Check the post types that the auto-insertion should be performed to restrict it to certain post types. Leave them all unchecked if you want the auto-insertion to perform in all types of posts.', 'responsive-column-widgets' ),
                            'type' => 'posttype',
                            'value' => $arrWidgetBoxOptions['autoinsert_enable_posttypes'],
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                        ),                            
                        array(
                            'id' => 'autoinsert_enable_categories',
                            'title' => __( 'Limit Auto-Insert to Checked Categories', 'responsive-column-widgets' ),
                            'description' => __( 'Check the categories that the auto-insertion should be performed. This only applies to posts. Leave them all unchecked to aplly the auto-insertion to all posts.', 'responsive-column-widgets' ),
                            'type' => 'category',
                            'value' => $arrWidgetBoxOptions['autoinsert_enable_categories'],
                        ),    
                        array(    
                            'id' => 'autoinsert_enable_post_ids',
                            'type' => 'text',
                            'size' => 40,
                            'title' => __( 'Limit Auto-Insert to Certain Post / Page IDs', 'responsive-column-widgets' ),
                            'description' => __( 'Enter the post IDs where the auto-insertion should be performed, separated by commas. Set empty to apply the auto-insertion to all posts.', 'responsive-column-widgets' )
                                . '<br />e.g. 98, 76, 5',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'autoinsert_enable_post_ids' ) : $this->oOption->ConvertOptionArrayValueToString( $arrWidgetBoxOptions['autoinsert_enable_post_ids'] ),    
                        ),    
                        array(
                            'id' => 'autoinsert_disable_pagetypes',
                            'title' => __( 'Page Types to Disable Auto-Insert', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'value' => $arrWidgetBoxOptions['autoinsert_disable_pagetypes'],
                            'label' => array(
                                'is_home' => __( 'Home / Front Page', 'responsive-column-widgets' ),
                                'is_archive' => __( 'Archives', 'responsive-column-widgets' ),
                                'is_404' => __( '404 Page', 'responsive-column-widgets' ),
                                'is_search' => __( 'Search Results', 'responsive-column-widgets' ),
                            ),
                            'description' => __( 'Check the page types that the auto-insertion should be disabled. Leave them all unchecked if you want the auto-insertion to perform in all types of pages.', 'responsive-column-widgets' ),                            
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                        ),                            
                        array(
                            'id' => 'autoinsert_disable_posttypes',
                            'title' => __( 'Post Types to Disable Auto-Insert', 'responsive-column-widgets' ),
                            'description' => __( 'Check the post types that the auto-insertion should be disabled. Leave them all unchecked if you want the auto-insertion to perform in all types of posts.', 'responsive-column-widgets' ),
                            'type' => 'posttype',
                            'value' => $arrWidgetBoxOptions['autoinsert_disable_posttypes'],
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                        ),                            
                        array(
                            'id' => 'autoinsert_disable_categories',
                            'title' => __( 'Categories to Disable Auto-Insert', 'responsive-column-widgets' ),
                            'description' => __( 'Check the categories that the auto-insertion should be disabled. This only applies to posts.', 'responsive-column-widgets' ),
                            'type' => 'category',
                            'value' => $arrWidgetBoxOptions['autoinsert_disable_categories'],
                        ),                        
                        array(    
                            'id' => 'autoinsert_disable_post_ids',
                            'type' => 'text',
                            'size' => 40,
                            'title' => __( 'Post / Page ID to Disable Auto-Insert', 'responsive-column-widgets' ),
                            'description' => __( 'Enter the post IDs where the auto-insertion should be disabled, separated by commas. Set empty not to disable the auto-insertion in any post.', 'responsive-column-widgets' )
                                . '<br />e.g. 98, 76, 5',
                            'value' => $bIsNew ? $this->oOption->GetDefaultValue( 'autoinsert_disable_post_ids' ) : $this->oOption->ConvertOptionArrayValueToString( $arrWidgetBoxOptions['autoinsert_disable_post_ids'] ),    
                        ),                            
                    ),
                ),
                // HTML 
                array(
                    'pageslug' => $this->strPluginSlug,
                    'tabslug' => 'neworedit',
                    'id' => 'section_html',
                    'title' => __( 'HTML', 'responsive-column-widgets' ), 
                    'fields' => array(    // since 1.1.1.2
                        array(
                            'id' => 'remove_id_attributes',
                            'title' => __( 'Remove ID Attributes', 'responsive-column-widgets' ),
                            'type' => 'radio',
                            'label' => array(
                                1 => __( 'Yes', 'responsive-column-widgets' ),
                                0 => __( 'No', 'responsive-column-widgets' ),
                            ),
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            'value' => $arrWidgetBoxOptions['remove_id_attributes'],
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
                            'id' => 'widget_box_container_background_color',
                            'title' => __( 'Container Background Color', 'responsive-column-widgets' ),
                            'value' => $strBGColor = empty( $arrWidgetBoxOptions['widget_box_container_background_color'] ) ? 'transparent' : $arrWidgetBoxOptions['widget_box_container_background_color'],
                            'description' => __( 'Set the background color for the widget box container. Set <code>transparent</code> for no color.', 'responsive-column-widgets' )
                                . ' ' . __( 'Default', 'responsive-column-widgets' ) . ': <code>transparent</code>.' . '<br />'
                                . ( ! empty( $strSidebarID ) ? __( 'This sets the CSS rule with the following class selector and the property:', 'responsive-column-widgets' ) . " <code>.{$strSidebarID} { background-color: [" . __( 'the set value comes here', 'responsive-column-widgets' ) . "] }</code>"  : '' ),
                            'type' => 'text',
                            'size' => 20,
                            'class' => 'input_color color_change',
                            'post_field' => "<div class='colorpicker' rel='section_custom_style_widget_box_container_background_color'></div>",
                            'pre_html' => "<div id='widget_box_container_bgcolor' >"
                                ."<img id='widget_box_container_background_color_image' class='color_change' style='background-color:{$strBGColor};' color='{$strBGColor}' src='"
                                . RESPONSIVECOLUMNWIDGETSURL . "/asset/image/settings-container-bgcolor.gif"
                                . "' /></div>",
                        ),
                        array(
                            'id' => 'widget_box_container_paddings',
                            'title' => __( 'Container Paddings', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'size' => 10,
                            'label' => array(
                                'top' => __( 'Top', 'responsive-column-widgets' ), 
                                'right' => __( 'Right', 'responsive-column-widgets' ), 
                                'bottom' => __( 'Bottom', 'responsive-column-widgets' ), 
                                'left' => __( 'Left', 'responsive-column-widgets' ), 
                            ),
                            'post_field' => array(
                                'top' => '&nbsp;&nbsp;px',
                                'right' => '&nbsp;&nbsp;px',
                                'bottom' => '&nbsp;&nbsp;px',
                                'left' => '&nbsp;&nbsp;px',                            
                            ),
                            'pre_html' => '<div id="widget_box_container_paddings_image"></div>',
                            'value' => $arrWidgetBoxOptions['widget_box_container_paddings'],
                            'description' => __( 'Set the paddings for the widget box container. Leave them empty for no padding.', 'responsive-column-widgets' ) . '<br />'
                                . ( ! empty( $strSidebarID ) ? __( 'This sets the CSS rule with the following class selector and the property:', 'responsive-column-widgets' ) . " <code>.{$strSidebarID} { padding: [" . __( 'the set values come here', 'responsive-column-widgets' ) . "] }</code>"  : '' ),
                        ),                        
                        array(
                            'id' => 'widget_box_max_width',
                            'title' => __( 'Widget Box Maximum Width', 'responsive-column-widgets' ),
                            // 'min' => 0,    // it's okay to be minus 
                            'type' => 'number',
                            'size' => 10,
                            'post_field' => '&nbsp;&nbsp;px',
                            'pre_html' => '<div id="widget_box_max_width_image"></div>',
                            'value' => $arrWidgetBoxOptions['widget_box_max_width'],
                            'description' => __( 'Set the maximum width of the widget box. Leave it empty or 0 for no maximum width.', 'responsive-column-widgets' ) . '<br />'
                                . ( ! empty( $strSidebarID ) 
                                    ? __( 'This sets the CSS rule with the following class selectors and the property:', 'responsive-column-widgets' ) . " <code>.{$strSidebarID} .{$this->oOption->arrOptions['general']['general_css_class_attributes']['box']} { max-width: [" . __( 'the set value comes here', 'responsive-column-widgets' ) . "]px }</code> " . __( 'So if you need to use the unit other than px, leave this empty and use the CSS Rule box below.', 'responsive-column-widgets' )
                                    : '' 
                                ),
                        ),        
                        // array(
                            // 'id' => 'widget_box_column_text_alignment',
                            // 'title' => __( 'Widget Box Column Text Alignment', 'responsive-column-widgets' ),
                            // 'type' => 'radio',
                            // 'label' => array(
                                // 'left' => __( 'Left', 'responsive-column-widgets' ) . "<div class='widget_box_column_text_alignment widget_box_column_text_alignment_left'><img src='" . RESPONSIVECOLUMNWIDGETSURL . '/asset/image/setting-column-text-align-left.jpg' . "' /></div>", 
                                // 'center' => __( 'Center', 'responsive-column-widgets' ) . "<div class='widget_box_column_text_alignment widget_box_column_text_alignment_center'><img src='" . RESPONSIVECOLUMNWIDGETSURL . '/asset/image/setting-column-text-align-center.jpg' . "' /></div>",
                                // 'right' => __( 'Right', 'responsive-column-widgets' ) . "<div class='widget_box_column_text_alignment widget_box_column_text_alignment_right'><img src='" . RESPONSIVECOLUMNWIDGETSURL . '/asset/image/setting-column-text-align-right.jpg' . "' /></div>",
                            // ),
                            // 'delimiter' => PHP_EOL,
                            // 'value' => $arrWidgetBoxOptions['widget_box_column_text_alignment'],
                            
                        // ),                            
                        array(
                            'id' => 'custom_style',
                            'title' => __( 'CSS Rule', 'responsive-column-widgets' ),
                            'description' => __( 'Define your custom CSS rules here.', 'responsive-column-widgets' ) . '<br />'
                                . 'e.g. ' . esc_html( '.responsive_column_widgets_box .widget { padding: 0 20px 0 20px; }' ),
                            'type' => 'textarea',
                            'cols' => 70,
                            'rows' => 6,
                            'value' => $arrWidgetBoxOptions['custom_style'],
                        ),
                        array(  // single button
                            // 'pre_html' => isset( $this->oUserAds ) ? $this->oUserAds->GetTextAd() : '',
                            'id' => 'submit_save_neworedit_bottom',
                            'type' => 'submit',        // the submit type creates a button
                            'label' => $this->numPluginType == 0 || isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ? __( 'Save Changes', 'responsive-column-widgets' ) : __( 'Add New Box', 'responsive-column-widgets' ),
                            'class' => 'submit-buttons button button-primary',
                            'pre_field' => '<div class="neworedit-button">',
                            'post_field' => '</div>',
                            'redirect' => $this->numPluginType == 0 || ( isset( $_GET['mode'] ) && $_GET['mode'] == 'edit' ) ? null : admin_url( "admin.php?page={$this->strPluginSlug}&tab=manage&updated=true" ),
                        ),                            
                    ),
                ),        
                array(  
                    'pageslug' => $this->strPluginSlug,
                    'tabslug' => 'general',
                    'id' => 'section_css_general', 
                    'capability' => 'manage_options',
                    'title' => __( 'CSS', 'responsive-column-widgets' ), 
                    'fields' => array(     // Field Arrays
                        array(  
                            'id' => 'general_css_timimng_to_load',
                            'title' => __( 'Timing to Load', 'responsive-column-widgets' ),
                            'description' => __( 'Select the timing to load the plugin\'s CSS base rules. If this is set the second item, the below Area to Load option does not take effect.', 'responsive-column-widgets' ),    //'
                            'type' => 'radio',
                            'label' => array( 
                                __( 'When the head tab is loaded. ( Default )', 'responsive-column-widgets' ),
                                __( 'When the first widget box is loaded.', 'responsive-column-widgets' ),
                            ),
                            'value' => $this->oOption->arrOptions['general']['general_css_timimng_to_load'],
                        ),                    
                        array(  
                            'id' => 'general_css_areas_to_load',
                            'title' => __( 'Areas to Load', 'responsive-column-widgets' ),
                            'description' => __( 'Select the areas to load the plugin CSS rules besides the front end pages.', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'label' => array( 
                                'regular' => __( 'Regular Pages', 'responsive-column-widgets' ),
                                'login' => __( 'Login Page', 'responsive-column-widgets' ),
                                'admin' => __( 'Administration Panel', 'responsive-column-widgets' ),
                            ),
                            'disable' => array(
                                'regular' => true,
                                'login' => false,
                                'admin' => false,
                            ),
                            'value' => $this->oOption->arrOptions['general']['general_css_areas_to_load'],
                        ),    
                        array(  
                            'id' => 'general_css_class_attributes',
                            'title' => __( 'Class Selector Names', 'responsive-column-widgets' ),
                            'pre_html' => '<span title="' . $this->strGetPro . '">',
                            'post_html' => '</span>',
                            'description' => __( 'Set the custom class selector names for the class attributes which is applied to the widget boxes. This cannot be empty.', 'responsive-column-widgets' ),
                            'type' => 'text',
                            'pre_field' => '<span class="text-input-field">',
                            'post_field' => '</span>',
                            'label' => array( 
                                'box' => __( 'Box', 'responsive-column-widgets' ),
                                'row' => __( 'Row', 'responsive-column-widgets' ),
                                'column' => __( 'Column', 'responsive-column-widgets' ),
                            ),                        
                            'disable' => true,
                            'class' => 'disabled',
                            'size' => 40,
                            'value' => $this->oOption->arrOptions['general']['general_css_class_attributes'],
                            'delimiter' => '<br />',
                        ),    
                        array(  // since 1.1.2.1
                            'id' => 'general_css_load_in_head',
                            'title' => __( 'Enqueue Styles in Head Tag', 'responsive-column-widgets' ),
                            'tip' => __( "This is for those who use the shortcode or the PHP function, <code>ResponsiveColumnWidgets()</code>, directly into posts or the theme but do not want the style tag to be embedded in the body tag.", 'responsive-column-widgets' ) . ' '
                                . __( "The plugin creates CSS rules based on the passed parameters. So you need to tell the plugin which parameters you used.", 'responsive-column-widgets' ),
                            'pre_field' => $this->GetRegisteredStyles( $this->oOption->arrOptions['general']['general_css_load_in_head'] ),
                            'post_html' => '<p class="description">' . __( "This is for those who use the shortcode or the PHP function, <code>ResponsiveColumnWidgets()</code>, directly into posts or the theme but do not want the style tag to be embedded in the body tag.", 'responsive-column-widgets' ) . ' '
                                . __( "The plugin creates CSS rules based on the passed parameters. So you need to tell the plugin which parameters you used.", 'responsive-column-widgets' ) . ' '
                                . '</p>'
                                . '<p class="description">' . __( "Specify the parameters used in the shorcode or the PHP function per line.", 'responsive-column-widgets' ) . ' '
                                . __( 'They need to be entered in the format of shortcode. If you use <code>array( \'label\' => \'my_widget_box\', \'columns\' => \'3, 4, 1\' )</code> in the PHP function, change it to, <code>label="my_widget_box" columns="3, 4, 1"</code>.', 'responsive-column-widgets' ) . ' '
                                . __( "The one with a blank parameter is already registered by default.", 'responsive-column-widgets' )
                                . '</p>'
                                . '<p class="description">e.g.<br />'
                                . 'label="my_widget_box" columns="3, 4, 1"<br/>'
                                . 'columns="5 | 800: 4 | 600: 1"'
                                . '</p>',
                            'type' => 'textarea',
                            'cols' => 70,
                            'rows' => 8,                            
                            'value' => $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['general']['general_css_load_in_head'], array( PHP_EOL ) ),
                        ),    
                        array(  // since 1.1.5.2
                            'id' => 'general_css_minify',
                            'title' => __( 'Minify CSS Code', 'responsive-column-widgets' ),
                            'label' => __( 'Compress the size of CSS code that the plugin generates.', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'value' => $this->oOption->arrOptions['general']['general_css_minify'],
                        ),                                            
                    ),
                ),                
                // General Options
                array(  
                    'pageslug' => $this->strPluginSlug,
                    'tabslug' => 'general',
                    'id' => 'section_general', 
                    'title' => __( 'General Options', 'responsive-column-widgets' ), 
                    // 'description' => __( 'test', 'responsive-column-widgets' ),
                    'fields' => array(     // Field Arrays
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
                            'size' => 40,
                            'value' => $this->oOption->ConvertOptionArrayValueToString( $this->oOption->arrOptions['general']['allowedhtmltags'] ), 
                        ),
                        array(    // since 1.1.4
                            'capability' => 'manage_options',
                            'id' => 'debug_mode',
                            'title' => __( 'Debug Mode', 'responsive-column-widgets' ),
                            'description' => __( 'This is mostly for developers. General users may occasionally need to turn this on when reporting the plugin behavior for a bug report.', 'responsive-column-widgets' ),
                            'type' => 'radio',
                            'label' => array(
                                1 => __( 'On', 'responsive-column-widgets' ),
                                0 => __( 'Off', 'responsive-column-widgets' ),
                            ),
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            'value' => $this->oOption->arrOptions['general']['debug_mode'],
                        ),    
                        array(    // since 1.1.4.1
                            'capability' => 'manage_options',
                            'id' => 'widget_responsive_column_widget_box',
                            'title' => __( 'Responsive Column Widget Box Widget', 'responsive-column-widgets' ),
                            'description' => __( 'Enable / disable the plugin widget that encapsulates a sidebar in the Widgets page.', 'responsive-column-widgets' ),
                            'type' => 'radio',
                            'label' => array(
                                1 => __( 'Enable', 'responsive-column-widgets' ),
                                0 => __( 'Disable', 'responsive-column-widgets' ),
                            ),
                            'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            'value' => $this->oOption->arrOptions['general']['widget_responsive_column_widget_box'],
                        ),    
                        array(  // since 1.1.5.3
                            'capability' => 'manage_options',
                            'id' => 'execute_shortcode_in_widgets',
                            'title' => __( 'Execute Shortcodes in Widget Boxes', 'responsive-column-widgets' ),
                            'label' => array( 
                                0 => __( 'No (Default)', 'responsive-column-widgets' ),
                                1 => __( 'Execute shortcodes in text widgets.', 'responsive-column-widgets' ),
                                2 => __( 'Execute shortcodes in whole widget boxes.', 'responsive-column-widgets' ),
                            ),
                            'type' => 'radio',
                            'value' => $this->oOption->arrOptions['general']['execute_shortcode_in_widgets'],
                        ),    
                        // @deprecated 1.2.0+ 
                        // array(    // since 1.1.1.2
                            // 'if' => isset( $this->oOption->arrOptions['general']['time_first_option_update'] ) && ( time() > $this->oOption->arrOptions['general']['time_first_option_update'] + $this->intIntervalToShowPleaseRate ),
                            // 'id' => 'has_reviewed',
                            // 'title' => __( 'Have You Rated the Plugin?', 'responsive-column-widgets' ),
                            // 'description' => sprintf( __( 'If you have <a href="%1$s">rated</a> the plugin, set it Yes.', 'responsive-column-widgets' ), 'http://wordpress.org/support/view/plugin-reviews/responsive-column-widgets' ),
                            // 'type' => 'radio',
                            // 'label' => array(
                                // 1 => __( 'Yes', 'responsive-column-widgets' ),
                                // 0 => __( 'No', 'responsive-column-widgets' ),
                            // ),
                            // 'delimiter' => '&nbsp;&nbsp;&nbsp;',
                            // 'value' => $this->oOption->arrOptions['general']['has_reviewed'],
                        // ),    
                        array(  // since 1.1.9
                            'id' => 'delay_register_sidebar',
                            'title' => __( 'Delay Sidebar Registration', 'responsive-column-widgets' ),
                            'label' => __( 'Wait until theme sidebars are registered to create the plugin custom sidebar.', 'responsive-column-widgets' ),
                            'type' => 'checkbox',
                            'value' => $this->oOption->arrOptions['general']['delay_register_sidebar'],
                        ),                            
                        array(  // single button
                            // 'pre_html' => '<div class="text-info">' . ( isset( $this->oUserAds ) ? $this->oUserAds->GetTextAd() : '' ) . '</div>',
                            'id' => 'submit_save_2',
                            'type' => 'submit',        // the submit type creates a button
                            'label' => __( 'Save Changes', 'responsive-column-widgets' ),
                            'pre_field' => '<div class="update-button">',
                            'post_field' => '</div>',                    
                            'class' => 'submit-buttons button button-primary',
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
                    'fields' => array(     // Field Arrays
                        // Checkbox
                        array(  
                            'id' => 'memory_allocation',
                            'title' => __( 'Attempt to Override Allocated Memory Size', 'responsive-column-widgets' ),
                            'description' => __( 'If the error, "Allowed memory size of ... bytes exhausted" occurs, try increasing the memory size allocated for PHP. Set 0 to use the server\'s setting.', 'responsive-column-widgets' ) . '<br />'    //'
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
                        array(  // since 1.1.6
                            'id' => 'clear_widget_box_caches',
                            'title' => __( 'Clear Caches', 'responsive-column-widgets' ),
                            'label' => __( 'Delete widget boxes\' caches.', 'responsive-column-widgets' ),    //'
                            'type' => 'checkbox',
                            'value' => 0,    // this should be always unchecked.
                        ),                                
                        // Submit Button
                        array(  // single button
                            'id' => 'submit_perform',
                            'type' => 'submit',        // the submit type creates a button
                            'label' =>  __( 'Perform', 'responsive-column-widgets' ),
                            'class' => 'submit-buttons button button-secondary'
                        ),
                    ),
                ),                
            )
        );
        $this->AddFormSections(
            //' Section Arrays
            array(                 
                // Manage Options
                array(  
                    'pageslug' => $this->strPluginSlug,
                    'tabslug' => 'manage',
                    'id' => 'section_buttons', 
                    'title' => '', //__( 'Pro Settings', 'responsive-column-widgets' ), 
                    // 'description' => __( '', 'responsive-column-widgets' ),
                    'fields' => array(     // Field Arrays
                        array(  // single button
                            'id' => 'submit_create_new',
                            'type' => 'submit',        // the submit type creates a button
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
                            'type' => 'submit',        // the submit type creates a button
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

        // Display the memory usage in other admin pages besides the plugin's if the plugin debug mode is on.
        if ( 
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
            && ( ! isset( $_GET['page'] ) || $_GET['page'] != $this->strPluginSlug )
        ) 
            add_action( 'update_footer', array( $this, 'PrintDebugInfo' ) );
                
            
    }    
    
    /*
     *  Custom Methods
     */
            
    protected function RemoveDefaultOmittingColumnElement( $arrColumnInput, $intDefaultScreenMaxWidth ) {    // since 1.1.1
        
        // This is just used for a displaying the option value purpose. So do not use it for modifyint the option values to save and update the data.
        
        // Find the lowest key.
        $arrHaystack = $arrColumnInput;
        if ( isset( $arrHaystack[0] ) ) unset( $arrHaystack[0] );
        $intLowestKey = $this->oOption->FindLowestKey( $arrHaystack );
        
        if ( $intDefaultScreenMaxWidth == $intLowestKey && $this->oOption->IsOneColumm( $arrColumnInput[ $intLowestKey ] ) )
            unset( $arrColumnInput[ $intLowestKey ] );
        
        return $arrColumnInput;
        
    }

    protected function GetRegisteredStyles( $arrParamLines ) {    // since 1.1.2.1

        $strTable = '<table class="responsive-column-widgets-column-registered-styles" border="0">'
            . '<tbody>'
            . '<tr>'
            . '<td>' . __( 'Registered Codes', 'responsive-column-widgets' ) . '</td>'
            . '<td>' . $this->GetListFromArray( $arrParamLines ) . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';
        return $strTable;

    }
    protected function GetListFromArray( $arr, $strParentTag='ul' ) {    // since 1.1.2.1
    
        $strList = "<li>[" . RESPONSIVECOLUMNWIDGETSKEY . "]</li>";    // the default empty parameter.
        foreach( $arr as $strElem ) {
            if ( empty( $strElem ) ) continue;
            $strList .= "<li>[" 
                . RESPONSIVECOLUMNWIDGETSKEY 
                . ' ' . $strElem 
                . "]</li>";
        }
        return "<{$strParentTag}>" . $strList . "</{$strParentTag}>";
        
    }
    
    protected function getColSpanValueForInput( $vInput ) {    // since 1.1.5
        
        $strColSpans = '';
        $vInput = is_array( $vInput ) ? $vInput : array();
        foreach ( $vInput as $intScreenMaxWidth => &$vColSpans ) {
    
            // now $vColSpans becomes a string.
            $strWidth = $intScreenMaxWidth == 0 ? '' : $intScreenMaxWidth . ': ';
            
            $strColSpans = "{$strWidth}";
            foreach( $vColSpans as $intWidgetIndex => $intColSpan )  
                $strColSpans .= "{$intWidgetIndex}-{$intColSpan}, ";
            $vColSpans = rtrim( $strColSpans, ', ' );
            // $vColSpans = ' ' . $strWidth . $this->oOption->ConvertOptionArrayValueToString( $vColSpans, array( '-', ', '  ) ) . ' ';
            
        }
        $strColSpans = $this->oOption->ConvertOptionArrayValueToString( $vInput, array( ' | ' ) );
        return trim( $strColSpans );    // since white spaces are added around the each string element, remove them.
    
    }
    
    protected function GetColumnStringValueForInput( $arrColumnInput, $intDefaultScreenMaxWidth=600 ) {    // since 1.1.1
        
        // This is used to get a string value for the user input field.
        // Converts 
        //         array( 800 => array( 4, 3, 2 ), 600 => array( 3, 2, 1 ), 480 => array( 1) )
        // to
        //         800: 4, 3, 2 | 600: 3, 2, 1 | 400: 1

        // The processing value must be an array.
        $arrColumnInput = ( array ) $arrColumnInput;

        /*    
         * Consider the following cases that $arrColumnInput is :
         * 1. a new type two-dimensional array which was made up with the | and : separators and the corresponsding array dimensions.
         * 2. an old type one-dimensional array which was made up with the commma(,) separator.
         */
        
        // Case 2
        if ( ! $this->oOption->IsFormattedColumnArray( $arrColumnInput ) )
            return $this->oOption->ConvertOptionArrayValueToString( $arrColumnInput );    // now $vInput becomes a string
            
        // Case 1 - formatted correctly. Note that it is sorted by descending order.
    
        // The default omitting element array( 600 => array( 1 ) ) should be omitted if it is the least screen-max width.
        $arrColumnInput = $this->RemoveDefaultOmittingColumnElement( $arrColumnInput, $intDefaultScreenMaxWidth );
        
        // We need to put the 0 key value to the beginning of the array without resorting the entire array.
        $arrKeyZero = $arrColumnInput[0];
        unset( $arrColumnInput[0] );
        $arrColumnInput = $this->oOption->PrependArrayElement( $arrColumnInput, 0, $arrKeyZero );    // array_unshift will resort the array so avoid using that.
        
        foreach ( $arrColumnInput as $intScreenMaxWidth => &$arrColumn ) {
    
            // now $arrColumn becomes a string.
            $strWidth = $intScreenMaxWidth == 0 ? '' : $intScreenMaxWidth . ': ';
            $arrColumn = ' ' . $strWidth . $this->oOption->ConvertOptionArrayValueToString( $arrColumn, array( ', ' ) ) . ' ';
            
        }

        $strReturn = $this->oOption->ConvertOptionArrayValueToString( $arrColumnInput, array( '|' ) );
        return trim( $strReturn );    // since white spaces are added around the each string element, remove them.
        
    }    
    protected function GetColumnValueDetails( $arrColumnArray ) {    // since 1.1.1
        
        // Displays the max-column value description text.
        // $arrColumnArray is either one dimensinal array ( old format prior to v1.1.1 ) or two dimensional array ( new one after v1.1.1 )
        $arrMaxColumnInfo = $this->oOption->FormatColumnArray( $arrColumnArray );
                
// echo $this->DumpArray( $arrMaxColumnInfo );        
        $intLagestWidth = 0;        
        $strTable = '<table class="responsive-column-widgets-column-details" border="0">'
            . '<tbody>'
            . '<tr>'
            . '<th class="screen-max-width">' . __( 'Screen Max Width (pixel)', 'responsive-column-widgets' ) . '</th>'
            . '<th class="number-of-columns">' . __( 'Number of Columns', 'responsive-column-widgets' ) . '</th>'
            . '</tr>';
        
        $arrDetailRows = array();
        foreach( $arrMaxColumnInfo as $intScreenMaxWidth => $arrColumns ) {
            
            if ( $intScreenMaxWidth == 0 ) continue;
            
            $intLagestWidth = $intScreenMaxWidth > $intLagestWidth ? $intScreenMaxWidth : $intLagestWidth;
            
            $intNextScreenMaxWidth = $this->oOption->GetNextArrayKey( $arrMaxColumnInfo, $intScreenMaxWidth );
            
            $arrDetailRows[] = '<tr><td>' . $intScreenMaxWidth . ' - ' . ( $intNextScreenMaxWidth + 1 ) . '</td><td>' . $this->oOption->ConvertOptionArrayValueToString( $arrColumns ) . '</td></tr>';
            
        }
        array_unshift( $arrDetailRows, '<tr><td>' . '- ' . ( $intLagestWidth + 1 ) . '</td><td>' . $this->oOption->ConvertOptionArrayValueToString( $arrMaxColumnInfo[0] ) ) . '</td></tr>';

        foreach( $arrDetailRows as $strRow )
            $strTable .= $strRow;
        
        return  $strTable . '</tbody></table>';
                
    }
    function UpdateFieldValuesToBeDisplayed( $strSidebarID ) {

        if ( ! isset( $this->oOption->arrOptions['boxes'][$strSidebarID] ) ) return;
        $arrBoxOptions = $this->oOption->arrOptions['boxes'][$strSidebarID];        
        $arrAdminOptions = ( array ) get_option( RESPONSIVECOLUMNWIDGETSKEYADMIN );
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['label']            = $arrBoxOptions['label'];
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['sidebar']        = $strSidebarID;
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['description']    = $arrBoxOptions['description'];
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['before_widget']    = $arrBoxOptions['before_widget'];
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['after_widget']    = $arrBoxOptions['after_widget'];
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['before_title']    = $arrBoxOptions['before_title'];
        $arrAdminOptions['responsive_column_widgets']['section_sidebar']['after_title']    = $arrBoxOptions['after_title'];
        
        // Update the database so that the updated values will be displayed in the form fields
        update_option( RESPONSIVECOLUMNWIDGETSKEYADMIN, $arrAdminOptions );
        
    }    
    function IsTabNewOrEdit() {
        
        if ( ! isset( $_GET['tab'] ) ) return True; // new landing
        if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'neworedit' ) return True;
        
    }    
    function DetermineCurrentSidebarToEdit() {
        
        if ( $this->numPluginType == 0 ) return $this->oOption->arrDefaultParams['sidebar'];    // the default sidebar ID
        
        if ( isset( $_GET['sidebarid'] ) ) return trim( $_GET['sidebarid'] );
        
    }    
    function SetOptionObject( &$oOption ) {
        
        $this->oOption = $oOption;        
        
    }

    /*
     * Modify the page output.
     * */
     
    function do_before_ResponsiveColumnWidgets_Admin_Page() {
        $this->PrintDebugInfo();    // 1.1.4+
    }
    
    function head_ResponsiveColumnWidgets_Admin_Page( $strHead ) {
        
        return '' // $this->oUserAds->GetTopBanner()
            . $strHead 
            . '<div class="responsive-column-widgets-admin-body">'
                . '<table border="0" cellpadding="0" cellspacing="0" unselectable="on" width="100%">
                    <tbody>
                        <tr>
                        <td valign="top">'
            ;
            
    }
    function foot_ResponsiveColumnWidgets_Admin_Page( $strFoot ) {
        
        $numItems = 12;
        if ( isset( $_GET['tab'] ) ) {
            switch ( $_GET['tab'] ) {
                case 'neworedit':
                    $numItems = defined( 'WPLANG' ) && WPLANG == 'ja' ? 15 : 20;
                    break;
                case 'manage':
                case 'information':
                    $numItems = 2;
                    break;
                case 'general':
                case 'getpro':
                    $numItems = 6;
                    break;
                default:
                    $numItems = 4;
                    break;
            }    
        }
        
        return $strFoot 
                                . '<div style="float:left; margin-top: 10px" ></div>' 
                            . '</td>'
                            . '<td valign="top" rowspan="2">' 
                            . '</td>'
                        . '</tr>'
                        . '<tr>'
                            .'<td valign="bottom" align="center">'
                            . '</td>'
                        . '</tr>'
                    . '</tbody>'
                . '</table>'
            . '</div>';
            
    }

    public function do_after_ResponsiveColumnWidgets_Admin_Page() {
        
        if ( ! (
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
        ) ) { return; }
        
        $this->PrintDebugInfo();
        echo ResponsiveColumnWidgets_Debug::DumpArray( $this->oOption->arrOptions );
        
    }    
    public function do_after_responsive_column_widgets_general() {

        if ( ! (
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
        ) ) return;
    
        echo ResponsiveColumnWidgets_Debug::DumpArray( $this->oOption->arrOptions['general'] );
        
    }
    public function do_after_responsive_column_widgets_neworedit() {
        return;
        if ( ! (
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
        ) ) return;
    
        echo ResponsiveColumnWidgets_Debug::DumpArray( $this->oOption->arrOptions['boxes'] );
        
    }    
    public function PrintDebugInfo() {    // also used by hooks

        if ( ! (
            isset( $this->oOption->arrOptions['general']['debug_mode'] ) && $this->oOption->arrOptions['general']['debug_mode'] 
            && defined( 'WP_DEBUG' ) && WP_DEBUG == true 
        ) ) return;
    
        echo '<p>Memory Usage: ' . ResponsiveColumnWidgets_Debug::GetMemoryUsage( 1 ) . '</p>';
        echo '<p>Memory Peak Usage: ' . ResponsiveColumnWidgets_Debug::GetMemoryUsage( 2 ) . '</p>';
        
    }
    
    /*
     * Modify Page Body Part
     * */
    function do_responsive_column_widgets_manage() {

        // if ( WP_DEBUG )
            // echo $this->DumpArray( $this->oOption->arrOptions['boxes'] );
        
    }
    function do_responsive_column_widgets_general() {    
    
        // if ( WP_DEBUG )
            // echo $this->DumpArray( $this->oOption->arrOptions );
    
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
                array( 'type' => 'text', 'value' => __( 'Auto-insert', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
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
        echo $this->GetComparisionTableTR( 
            array( 
                array( 'type' => 'text', 'value' => __( 'CSS Custom Class Attribute Names', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
                array( 'type' => 'image', 'value' => False, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'second-col' ),
                array( 'type' => 'image', 'value' => True, 'align' => 'center', 'width' => 32, 'height' => 32, 'class' => 'third-col' ),                
            )             
        );
        echo $this->GetComparisionTableTR( 
            array( 
                array( 'type' => 'text', 'value' => __( 'Premium Support', 'responsive-column-widgets' ), 'align' => 'center', 'class' => 'first-col' ),
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
                'align'     => null,
                'width'     => null,
                'height'    => null,
                'class'        => null,
            );
            
            if ( $arrInfo['type'] == 'text' ) {
                
                $strOut .= "<td align='{$arrInfo['align']}' class='{$arrInfo['class']}'>" .  $arrInfo['value'] . "</td>";
                continue;
                
            }
            
            // means it's an image
            $strOut .= "<td align='{$arrInfo['align']}' class='{$arrInfo['class']}'>"
                . '<img src="' . RESPONSIVECOLUMNWIDGETSURL . '/asset/image/' . ( $arrInfo['value'] ? 'available.gif' : 'unavailable.gif' ) . '" '
                . 'title="' . ( $arrInfo['value'] ? __( 'Available', 'responsive-column-widgets' ) : __( 'Unavailable', 'responsive-column-widgets' ) ) . '" />'
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
        $strImgBuyNow = RESPONSIVECOLUMNWIDGETSURL . '/asset/image/buynowbutton.gif';
    
        $strOut  = '<div style="padding:' . $strPadding . ';">';
        $strOut .= '<div style="float:' . $strFloat . ';">';
        $strOut .= '<a href="' . $strLink . '?lang=' . esc_attr(  defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en' ) . '" title="' . esc_attr( $this->strGetProNow ) . '">';
        $strOut .= '<img src="' . esc_url( $strImgBuyNow ) . '" />';
        $strOut .= '</a>';
        $strOut .= '</div>';
        $strOut .= '</div>';
        return $strOut;
    }
    
    
    /*
     * Validate Post Data
     * */
    public function validation_responsive_column_widgets_neworedit( $arrInput ) {

        // Sanitize the values
        // - section_sidebar (HTML Post Data)
        $arr = array();
        foreach( $arrInput[ $this->strPluginSlug ]['section_sidebar'] as $strField => $strHTML ) 
            $arr[ $strField ] = $this->FilterPostHTMLCode( $arrInput[ $this->strPluginSlug ]['section_sidebar'][ $strField ] );
        $arrInput[ $this->strPluginSlug ]['section_sidebar'] = $arr;

        // - section_custom_style
        if ( isset( $arrInput['responsive_column_widgets']['section_custom_style'] ) )
            $arrInput['responsive_column_widgets']['section_custom_style'] = $this->sanitizeSectionCustomStyle( $arrInput['responsive_column_widgets']['section_custom_style'] );
    
// $this->DumpArray( $arrInput, dirname( __FILE__ ) . '/input.txt' );
        
        // Set the variables.
        $bIsValid = True;
        $arrErrors = array();
        $strErrors = '';
        
        // Check if the label is not empty - if the "field_label" key is not set, it means it's disabled and this occurs to the default widget box.
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
        
        if ( ! $bIsValid  ) {    // This line is reached if there are invalid values.
                        
            // Set the field error array.
            $this->SetFieldErrors( $arrErrors );
            
            // This displays the error message
            $this->SetSettingsNotice( $strErrors );    
            
            // Returning an empty array will not change options.
            return array();                
            
        }
        
        // Reconstruct the submitted array to omit the sections - make it flat to consist of fields
        $arrBoxOptions = array();
        foreach ( $arrInput[ $this->strPluginSlug ] as $arrFields ) 
            $arrBoxOptions = $arrBoxOptions + $arrFields;         
        
        // Please review.
        $this->PleaseReview();
        
        // The data are valid. Update the box options.
        $this->UpdateBoxOptions( $arrBoxOptions, $_POST['isnew'] );
        $this->SetSettingsNotice( __( 'The widget box options have been saved.', 'responsive-column-widgets' ), 'updated' );
        
        return $arrInput;
            
    } 
    protected function sanitizeSectionCustomStyle( $arrSection ) {    // since 1.1.7

        if ( isset( $arrSection['widget_box_container_background_color'] ) && trim( strtolower( $arrSection['widget_box_container_background_color'] ) ) == 'transparent' )
            $arrSection['widget_box_container_background_color'] = '';
        
        if ( isset( $arrSection['widget_box_container_paddings'] ) && array_filter( $arrSection['widget_box_container_paddings'] ) )
            $arrSection['widget_box_container_paddings'] = $this->oUtil->FixNumbers( $arrSection['widget_box_container_paddings'], '' );
            
        if ( isset( $arrSection['widget_box_max_width'] ) && $arrSection['widget_box_max_width'] ) 
            $arrSection['widget_box_max_width'] = $this->oUtil->FixNumber( $arrSection['widget_box_max_width'], '' );
            
   

        return $arrSection;
    }
    protected function PleaseReview() {    // since 1.1.1.2
        
        // Stores the current time. The option array will be updated by the following UpdateBoxOptions() method.
        if ( ! isset( $this->oOption->arrOptions['general']['time_first_option_update'] ) )
            $this->oOption->arrOptions['general']['time_first_option_update'] = time();
        
    }
    protected function CleanOldVersionBoxOptions( $arrBoxOptions ) {    // since 1.0.9
        
        // for 1.0.5
        $arrBoxOptions['autoinsert_enable_areas']['wp_footer'] = isset( $arrBoxOptions['insert_footer'] ) ? $arrBoxOptions['insert_footer'] : $arrBoxOptions['autoinsert_enable_areas']['wp_footer'];    
        return $arrBoxOptions;
        
    }
    function UpdateBoxOptions( $arrInput, $bIsNew ) {
        
// $this->DumpArray( $arrInput, dirname( __FILE__ )    . '/input.txt' );                

        // Sanitisation for the first two sections.
        $arrInput['maxwidgets'] = $this->oUtil->FixNumber( $arrInput['maxwidgets'], 0, 0 );
        $arrInput['maxrows'] = $this->oUtil->FixNumber( $arrInput['maxrows'], 0, 0 );
        $arrInput['sidebar'] = ! empty( $_POST['isnew'] ) ? $this->GetAvailableSidebarID() : $arrInput['sidebar'];
        $arrInput['label'] = isset( $arrInput['label'] ) ? $arrInput['label'] : $this->oOption->arrOptions['boxes'][ $arrInput['sidebar'] ]['label'];
        $arrInput['omit'] = $this->SanitizeNumericSequenceToArray( $arrInput['omit'] );
        $arrInput['showonly'] = $this->SanitizeNumericSequenceToArray( $arrInput['showonly'] );        
        $arrInput['colspans'] = $this->oOption->formatColSpanArray( $arrInput['colspans'] );        // since 1.1.5    

        // Sanitize the column array.
        $arrInput['columns'] = $this->SanitizeColumnInput( $arrInput['columns'] );

        // Sanitization for the auto-insert section.
        $arrInput['autoinsert_enable_filters'] = $this->SanitizeStringToArray( $arrInput['autoinsert_enable_filters'] );
        $arrInput['autoinsert_enable_actions'] = $this->SanitizeStringToArray( $arrInput['autoinsert_enable_actions'] );
        $arrInput['autoinsert_enable_post_ids'] = $this->SanitizeNumericSequenceToArray( $arrInput['autoinsert_enable_post_ids'] );
        $arrInput['autoinsert_disable_post_ids'] = $this->SanitizeNumericSequenceToArray( $arrInput['autoinsert_disable_post_ids'] );
        
        // Update
        $this->oOption->InsertBox( $arrInput['sidebar'], $arrInput );
        $this->oOption->Update();        
        
    }    
    /**
     * Sanitizes the column input.
     * 
     * This takes care of the backward compatibility when the widgets are going to be displayed.
     * 
     * @since       1.1.1
     */
    protected function SanitizeColumnInput( $strColumnInput ) {
        return $this->oOption->FormatColumnArray( $strColumnInput );    

    }    
    
    /**
     * Converts the given string into array and performs sanitization.
     * 
     * @since 1.0.9
     */
    function SanitizeStringToArray( $str, $bValueUnique=true, $strDelim1=',', $strDelim2='' ) {       
        
        $arr = $this->oOption->ConvertStringToArray( $str, $strDelim1, $strDelim2 );    // comma delimited
        $arr = $this->oUtil->UnsetEmptyArrayElements( $arr );
        if ( $bValueUnique ) { 
            $arr = array_unique( $arr ); 
        }
        return $arr;
        
    }
    
    /**
     * Converts the given string into array and performs sanitization to be a numeric sequence.
     * 
     * Fole Example,
     * 3, 4, 63  --> array( 3, 4, 63 )
     * ada, 9,, 4 --> array( 9, 4 ) 
     * 
     * @since            1.0.9
     */ 
    function SanitizeNumericSequenceToArray( $str, $intDefault=null, $intMin=1, $intMax='', $bValueUnique=true ) {
                
        $arr = $this->oOption->ConvertStringToArray( $str, ',' );    // comma delimited
        $arr = $this->oUtil->FixNumbers( $arr, $intDefault, $intMin, $intMax );
        $arr = $this->oUtil->UnsetEmptyArrayElements( $arr );
        if ( $bValueUnique ) { 
            $arr = array_unique( $arr ); 
        }
        return $arr;
        
    }
    
    /**
     * @since       1.0.4
     */
    function IsLabelAlreadyUsed( $strLabel ) {
        foreach( $this->oOption->arrOptions['boxes'] as $strSidebarID => $arrBoxOptions ) {
            if ( $arrBoxOptions['label'] == $strLabel ) { 
                return true; 
            }
        }
    }    
    
    /**
     * 
     * @since       1.0.4
     */
    function GetAvailableSidebarID() {

        $numID = '';
        $arrBoxes = ( array ) $this->oOption->arrOptions['boxes'];
        $arrBoxes = array_reverse( $arrBoxes, true );    // the ID number is ascending so read from the last one.
        foreach( $arrBoxes as $strID => $v ) {
                
            preg_match( '/^(.+\D)(\d+)$/', $strID, $arrMatches );    // get the last digits
            if ( ! isset( $arrMatches[2] ) ) continue;
            
            $numID = $arrMatches[2] + 1;
            if ( ! isset( $this->oOption->arrOptions['boxes'][ $arrMatches[1] . $numID ] ) ) 
                return $arrMatches[1] . $numID;
                
        }
        
        // what happens if an available ID could not be generated? 
        if ( array_key_exists( 'responsive_column_widgets_2', $arrBoxes ) )  {
            return 'responsive_column_widgets_' . uniqid();
        }
            
        return 'responsive_column_widgets_2'; 
    
    }    
    
    /**
     * 
     * @since       1.0.4
     */
    function FilterPostHTMLCode( $strHTML ) {
        
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
        if ( isset( $arrInput['responsive_column_widgets']['section_buttons']['submit_delete'] ) ) {        // the 'Delete Checked' submit button
            
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
            
        // Reconstruct the submitted array to omit(merge) the sections - make it flat to consist of fields
        $arrValidate = array();
        foreach ( $arrInput[ $this->strPluginSlug ] as $arrSections ) 
            $arrValidate = $arrValidate + $arrSections;

        /*
         * Danger Zone
         * */     
        if ( isset( $arrValidate['submit_perform'], $arrValidate['initializeoptions'] ) && $arrValidate['initializeoptions'] == 1 ) {
            
            // Delete the plugin main options
            $this->oOption->arrOptions = null;
            $this->oOption->Update();
            
            // Delete the admin page options as well.
            return null;
            
        }    
        
        /*
         * Cache clear checkbox
         */         
        if ( isset( $arrValidate['clear_widget_box_caches'] ) && $arrValidate['clear_widget_box_caches'] ) {
            ResponsiveColumnWidgets_Cleaner::CleanTransients( array( 'RCW_Cache' ) );
        }
        
        // Format and sanitize the values
        if ( ! isset( $arrValidate['general_css_class_attributes'] ) || empty( $arrValidate['general_css_class_attributes'] ) ) {
            $arrValidate['general_css_class_attributes'] = $this->oOption->arrOptions['general']['general_css_class_attributes'];
        }
        
        foreach ( $arrValidate['general_css_class_attributes'] as $strKey => &$strElem ) {
            
            $strElem = $this->oOption->SanitizeAttribute( trim( $strElem ) );
            $strElem = ! empty( $strElem ) ? $strElem : $this->oOption->arrOptions['general']['general_css_class_attributes'][ $strKey ];    // 'general_css_class_attributes' cannot be empty.
            
        }
            
        // For disabled fields 
        if ( 0 == $this->numPluginType ) {
            $arrValidate['general_css_areas_to_load']['regular'] = 1;    // Since this field is disabled, it becomes 0 when updating. So make it true.
        }         
        
            
        $arrValidate['allowedhtmltags'] = $this->oOption->ConvertStringToArray( $arrValidate['allowedhtmltags'], ',' );         
        
        $arrValidate['general_css_load_in_head'] = preg_split( "/[\r]\s*/", $arrValidate['general_css_load_in_head'] );     // since 1.1.2.1
        foreach( $arrValidate['general_css_load_in_head'] as $intIndex => $strParams ) {
            
            if ( trim( $strParams ) == '' ) continue;    // allows an empty line, which yeilds no parameters.
            
            if ( strpos( $strParams, '=' ) === false )     // if an equal sign is missing, do not process it.
                unset( $arrValidate['general_css_load_in_head'][ $intIndex ] );
                
        }
        $arrValidate['general_css_load_in_head'] = array_unique( $arrValidate['general_css_load_in_head'] );
        
        // Memory Allocation since 1.0.7.1
        $this->oOption->arrOptions['general']['memory_allocation'] = empty( $arrValidate['memory_allocation'] ) ? 0 
            : $this->oUtil->FixNumber( $arrValidate['memory_allocation'], 
                intval( 0 ),
                32     // minimum
            );

        // Please review
        $arrValidate['time_first_option_update'] = isset( $this->oOption->arrOptions['general']['time_first_option_update'] ) ? $this->oOption->arrOptions['general']['time_first_option_update'] : time();
        
        // There are hidden option values that are not sent from the admin page ( the data sent as $arrInput ), the input data need to be merged with the previous option values.
        // $this->oOption->arrOptions['general'] = $arrValidate + $this->oOption->arrOptions['general'];
        // $this->oOption->arrOptions['general'] = $this->oOption->UniteArraysRecursive( $arrValidate, $this->oOption->arrDefaultOptionStructure['general'] );
        $this->oOption->arrOptions['general'] = $this->oOption->UniteArraysRecursive( $arrValidate, $this->oOption->arrOptions['general'] );
 
        // Update the value to the separate main option.
        $this->oOption->Update();
        
        return $arrInput;
        
    }

    function EscapeAndFilterPostKSES( $strString, $arrAllowedTags = array(), $arrDisallowedTags=array(), $arrAllowedProtocols = array() ) {
        // $arrAllowedTags : e.g. array( 'noscript' => array(), 'style' => array() );
        // $arrDisallowedTags : e.g. array( 'table', 'tbody', 'thoot', 'thead', 'th', 'tr' );

        global $allowedposttags;
        // $arrAllowedHTML = array_replace_recursive( $allowedposttags, $arrAllowedTags );    // the second parameter takes over the first.
        // $arrAllowedHTML = wp_parse_args( $arrAllowedTags, $allowedposttags );    // the first parameter takes over the second.
        $arrAllowedHTML = $this->oUtil->UniteArraysRecursive( $arrAllowedTags, $allowedposttags );    // the first parameter takes over the second.
    
        foreach ( $arrDisallowedTags as $strTag )         
            if ( isset( $arrAllowedHTML[$strTag] ) ) unset( $arrAllowedHTML[$strTag] );
        
        if ( empty( $arrAllowedProtocols ) )
            $arrAllowedProtocols = wp_allowed_protocols();            
        $strString = addslashes( $strString );                    // the original function call was doing this - could be redundant but haven't fully tested it
        $strString = stripslashes( $strString );                    // wp_filter_post_kses()
        $strString = wp_kses_no_null( $strString );                // wp_kses()
        $strString = wp_kses_js_entities( $strString );            // wp_kses()
        $strString = wp_kses_normalize_entities( $strString );    // wp_kses()
        $strString = wp_kses_hook( $strString, $arrAllowedHTML, $arrAllowedProtocols ); // WP changed the order of these funcs and added args to wp_kses_hook
        $strString = wp_kses_split( $strString, $arrAllowedHTML, $arrAllowedProtocols );        
        $strString = addslashes( $strString );                // wp_filter_post_kses()
        $strString = stripslashes( $strString );                // the original function call was doing this - could be redundant but haven't fully tested it
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
            . "<a href='{$strURL}' title='" . __( 'Edit', 'responsive-column-widgets' ) . "'><img src='" . RESPONSIVECOLUMNWIDGETSURL . "/asset/image/edit16x16.gif' /></a>" 
            . '</td>'
            . '</tr>';
    }
    function GetWidgetBoexTableRows() {}
     
    /*
     * Modify Script
     * */
    public function script_responsive_column_widgets_neworedit( $strScript ) {
        //Setup the color pickers to work with our text input field
        return $strScript . " jQuery(document).ready(function(){
          'use strict';
          
          //This if statement checks if the color picker widget exists within jQuery UI
          //If it does exist then we initialize the WordPress color picker on our text input field
          if( typeof jQuery.wp === 'object' && typeof jQuery.wp.wpColorPicker === 'function' ){
            
            var myOptions = {
                // you can declare a default color here,
                // or in the data-default-color attribute on the input
                defaultColor: false,
                // a callback to fire whenever the color changes to a valid color
                change: function(event, ui){
                    // reference : http://automattic.github.io/Iris/
                    // update the image element as well
                    // event = standard jQuery event, produced by whichever control was changed.
                    // ui = standard jQuery UI object, with a color member containing a Color.js object

                    // change the headline color
                    jQuery( '#widget_box_container_background_color_image' ).css( 'background-color', ui.color.toString());    
                    
                },
                // a callback to fire when the input is emptied or an invalid color
                clear: function() {
                    jQuery( '#widget_box_container_background_color_image' ).css( 'background-color', 'transparent' );    
                    
                },
                // hide the color picker controls on load
                hide: true,
                // show a group of common colors beneath the square
                // or, supply an array of colors to customize further
                palettes: true
            };            
            jQuery( '.input_color' ).wpColorPicker( myOptions );
          }
          
          else {
            //We use farbtastic if the WordPress color picker widget doesn't exist
            jQuery( '.colorpicker' ).farbtastic( '.color_change' );
          }
        });        
        ";
    }
    
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
            .update-button {
                margin-top: 12px;
                margin-bottom: 12px;                
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
            input.disabled {
                background-color: #F1F1F1;
            }    
            .text-label    {
                min-width: 80px;
                display: inline-block;
            }
            .text-input-field input {
                margin-bottom: 8px;
            }
            .nostrong {
                font-weight: normal;
            }
            div#setting-error-please-rate.error {
                background-color: rgb(232, 255, 245);
                border-color: rgb(0, 204, 122);    
            }
        "; 
    }
    function style_responsive_column_widgets_neworedit( $strStyle ) {
        $strWidgetBoxMaxWidthImageURL = RESPONSIVECOLUMNWIDGETSURL . '/asset/image/settings-widget-box-max-width.jpg';
        $strWidgetBoxContainerPaddingsImageURL = RESPONSIVECOLUMNWIDGETSURL . '/asset/image/settings-container-box-paddings.jpg';
        return $strStyle . "
            .neworedit-button {
                margin-top: 12px;
                margin-bottom: 12px;
            }            
            .wrap hr {
                border: 0; 
                height: 1px; 
                background: #DDD;                
                width: 96%;
            }
            table.responsive-column-widgets-column-details {
                margin-bottom: 4px;
                width: 100%;
            }
            .responsive-column-widgets-column-details th,
            .responsive-column-widgets-column-details td {
                vertical-align: baseline;
                padding: 0px;
            }
            th.screen-max-width {
                min-width: 32px;                
                width: 30%;
            }
            th.number-of-columns {
                width: 70%;        
            }
            #widget_box_container_bgcolor {
                float: right;
                margin-left: 20px;
                margin-bottom: 20px;
            }
            #widget_box_container_bgcolor img {
                border: 1px solid #D6D6D6;
            }
            #widget_box_container_paddings_image {
                float: right;
                width: 258px;
                height: 134px;
                background-image: url( '{$strWidgetBoxContainerPaddingsImageURL}' );
                margin-left: 20px;
                margin-bottom: 20px;
            }
            #widget_box_max_width_image {
                float: right;
                width: 258px;
                height: 134px;
                background-image: url( '{$strWidgetBoxMaxWidthImageURL}' );
                margin-left: 20px;
                margin-bottom: 20px;
            }
            #section_custom_style_widget_box_column_text_alignment span {
                width: 33.3%;
                float: left;
                display: inline-block;
            }
            .widget_box_column_text_alignment {
                margin-top: 4px;
                margin-bottom: 4px;
            }
            .widget_box_column_text_alignment_left, 
            .widget_box_column_text_alignment_center, 
            .widget_box_column_text_alignment_right {
                margin-left: 4%;
            }
            .widget_box_column_text_alignment img {
                float: left;
                width: 100%;
                max-width: 258px;
            }
            @media only screen and (max-width: 600px) {
                #section_custom_style_widget_box_column_text_alignment span {
                    width: 100%;
                    margin-bottom: 4px;
                }        
                .widget_box_column_text_alignment_left, 
                .widget_box_column_text_alignment_center, 
                .widget_box_column_text_alignment_right {
                    margin-left: 0%;
                }
            }
        ";
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
    function style_responsive_column_widgets_general( $strStyle ) {
        return $strStyle . '
            table.responsive-column-widgets-column-registered-styles {

            }
            .responsive-column-widgets-column-registered-styles th,
            .responsive-column-widgets-column-registered-styles td {
                vertical-align: top;
                padding: 0px 10px 0px 0px;
            }    
            .responsive-column-widgets-column-registered-styles ul {
                padding: 0px 10px 0px 20px;
                margin-top: 0px;
            }                
        ';
    }
    function style_responsive_column_widgets_information( $strStyle ) {
        return $strStyle . '
            .wrap p { 
                margin-left: 20px;
            }
            .wrap ul {
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
                height:    30px; 
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