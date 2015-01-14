<?php
/**
    Creates a widget that encapsulates a sidebar. 
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.3
 * @dependencies ResponsiveColumnWidgets_SidebarHierarchy, ResponsiveColumnWidgets_WidgetOptions
 *     
    
*/

class ResponsiveColumnWidgets_Widget_ extends WP_Widget {

    // protected $strBaseID = 'responsive_column_widget_box';
    public static $strBaseID = 'responsive_column_widget_box';
    protected $strWidgetName = 'Responsive Column Widget Box';
    protected $strClassSelectorFormSelect = 'responsive_column_widget_box_form_select';    // refered by the plugin JavaScript script to find out the container sidebar ID.
    protected $strClassSelectorFormOption = 'responsive_column_widget_box_form_option';    // refered by the plugin JavaScript script to disable cetain option tag elements.
    protected $strClassSelector_ContainerSidebarID = 'responsive_column_widget_box_form_container_sidebar_id';
    
    public static function RegisterWidget() {
    
        global $oResponsiveColumnWidgets_Options;
        if ( ! isset( $oResponsiveColumnWidgets_Options ) ) return;
        
        // Register the widget only if the option is set to Enable.
        if ( isset( $oResponsiveColumnWidgets_Options->arrOptions['general']['widget_responsive_column_widget_box'] ) 
            && $oResponsiveColumnWidgets_Options->arrOptions['general']['widget_responsive_column_widget_box'] )
            return register_widget( 'ResponsiveColumnWidgets_Widget' );    // the class name

    }    
    
    /**
     * Fixes a bug-like error with asynchronous widget updates, "Notice: Undefined index: responsive_column_widget_box-[...] in ...\wp-admin\includes\ajax-actions.php on line 1578"
     * 
     * A callback for the 'sidebar_admin_setup' action hook.
     * @since            1.1.7.3
     */
    public static function fixAsyncSaveBug() {
        
        global $wp_registered_widget_controls;
        if ( ! isset( $_POST['widget-id'], $_POST['id_base'] ) ) return;
        if ( isset( $wp_registered_widget_controls[ $_POST['widget-id'] ] ) ) return;
        if ( $_POST['id_base'] != self::$strBaseID ) return;
            
        $wp_registered_widget_controls[ $_POST['widget-id'] ] = array(
            'name' => '',
            'id' => $_POST['widget-id'],
            'callback' => "ResponsiveColumnWidgets_Widget::doNothing", //self::doNothing(),
            'params' => ''
        );                

    }
    public static function doNothing() {}
    
    public function __construct() {
        
        // Objects
        // $this->oOption = & $GLOBALS['oResponsiveColumnWidgets_Options']; // reference the option object.
        
        // Properties
        $this->strClassSelectorFormOption = self::$strBaseID . '_form_option';
        
        // Add a common JavaScript script into the head tag of the widget.php admin page.
        if ( is_admin() && $GLOBALS['pagenow'] == 'widgets.php' ) 
            add_action( 'admin_head', array( $this, 'AddJavaScript_CommonFunctions' ) );
                
        // Register the widget
        parent::__construct(
             self::$strBaseID, 
            $this->strWidgetName, 
            array( 'description' => __( 'A widget that encapsulates a sidebar.', 'responsive-column-widgets' ), ) 
        );
        
        // Enqueue styles - since 1.1.3
        add_action( 'wp_loaded', array( $this, 'EnqueueStyles' ) );
        
    }
    
    public function EnqueueStyles() {    // since 1.1.3, public as called from hooks.
        
        // This method must be loaded after the $wp_registered_widgets variable is set
        // ,after these hooks: widgets_init, register_sidebar, wp_register_sidebar_widget.
        
        // Retrieve the active registered plugin widgets.
        $oWO = new ResponsiveColumnWidgets_WidgetOptions;
        $arrWidgetOptions = $oWO->GetRegisteredWidgetOptionsByBaseID();

        // Add the parameters into the flag array so that the core object will read them and add the style in the header if the 
        // option allows it.
        global $arrResponsiveColumnWidgets_Flags;        
        foreach ( $arrWidgetOptions as $arrWidgetOption ) {
            
            // The sidebarid_selected key must be set. If not it must have something went wrong when updating the widget options in widgets.php.
            if ( ! isset( $arrWidgetOption['sidebarid_selected'] ) ) continue;
            
            // Set up the parameter array.
            $arrParams = array( 'sidebar' => $arrWidgetOption['sidebarid_selected'] );
    
            // Set it in the global flag array.
            // if ( ! in_array( $arrParams, $arrResponsiveColumnWidgets_Flags['arrEnqueueStyleParams'] ) )
                $arrResponsiveColumnWidgets_Flags['arrEnqueueStyleParams'][] = $arrParams;
            
        }
        
    }
    
    protected function GetContainerSidebarID() {    // since 1.1.3
                    
        foreach( get_option( 'sidebars_widgets' ) as $strSidebarID => $arrRegisteredWidgets ) 
            if ( in_array( $this->id, ( array ) $arrRegisteredWidgets ) ) 
                return $strSidebarID;
// ResponsiveColumnWidgets_Debug::DumpArray( get_option( 'sidebars_widgets' ), dirname( __FILE__ ) . '/sidebars.txt' );                                                                    
    }
    
    public function AddJavaScript_CommonFunctions() {    // since 1.1.3, public as used by hooks
    
        // This script is called from JavaScript events triggered when a plugin widget is moved.
        // This script requests another page on the site with a query and retrieves the result from the page as JSON.
        // The plugin returns the information of the saved sidebar's relationships between the added plugin widgets.
        ?>
        <script type="text/javascript" class="responsive-column-widgets-widget-registration-script">

            function ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration( update_widget ){
                
                // Defined the static variable
                if ( typeof ResponsiveColumnWidgets_Timer == 'undefined' ) {
                    
                    ResponsiveColumnWidgets_Timer = new Date().getTime();    // the variable will be static
                    
                } else  {    // the else clause helps to execute the first call.
                    
                    // Prevent multiple repeated calls too often.
                    if ( ResponsiveColumnWidgets_Timer + 200 > new Date().getTime() ) {    // 200 milliseconds have not passed.
                        console.log( '(RCW Log) The function for Ajax request has been called too early. Returning.' );
                        ResponsiveColumnWidgets_Timer = new Date().getTime();
                        return; 
                    }
                    
                }  
                
                // Here is where the request will happen
                jQuery.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    data:{
                        'action': 'get_sidebar_hierarchy',        // a custom action hook
                        'rcw_ajax_request': 'true',                // the $_GET request
                        // 'sidebarid': 'test_output_sidebarid'        // the $_GET request
                    },
                    dataType: 'JSON',
                    success: function( data ){
                    
                        // Disable the dependencies listed in the plugin's widget drop-down lists.
                        ResponsiveColumnWidgets_DisableDependencyOptionTags( data );
                        if ( typeof update_widget != 'undefined' ) {
                            console.log( '(RCW Log) Updating the widget. : ' + update_widget.attr( "id" ) );                                
                            ResponsiveColumnWidgets_PressSaveButton( update_widget );    
                        }
                        
                    },
                    error: function( errorThrown ){
                        console.log( errorThrown );
                    },
                    // async: false,
                });

            }    
            function ResponsiveColumnWidgets_DisableDependencyOptionTags( data ) {
                
                // Enable all the plugin's widget's form option tags.
                jQuery( "select.<?php echo $this->strClassSelectorFormSelect; ?> option.<?php echo $this->strClassSelectorFormOption;?>" )
                    .removeAttr( 'disabled' );                                
                    
                // Select the sidebar box container div and iterate the container div elements.
                // This will be applied to all the sidebars that contains the plugin specific class selector.
                jQuery( 'div.widgets-sortables' ).has( '.<?php echo $this->strClassSelectorFormSelect; ?>' )
                    .each( function() {
                    
                    var container_div = jQuery( this );
                    var container_sidebar_id = container_div.attr( "id" );
                    
                    // Skip the undefined id element because it means either it belongs to the inactive sidebar box / the default factory box or removed.
                    if ( typeof container_sidebar_id == 'undefined' ) return true;    // to be equivalent to the continue command in loop, return true.

                    // Prepare an array for selected IDs.
                    var selectedIDs = new Array();                    
                    
                    // Iterate the plugin's select tags.
                    container_div.find( "select.<?php echo $this->strClassSelectorFormSelect; ?>" ).each( function() {
                
                        var select = jQuery( this );        
                        
                        // Iterate the option tags which belongs to the select tag.
                        select.find( "option.<?php echo $this->strClassSelectorFormOption; ?>" )
                            .each( function() {
                                
                            // this.text : the label displayed in the option item: the sidebar label.
                            // this.value : the value set in the option tag: the sidebar ID.

                            // If the iterating option has the selected attribute, store it in the array.
                            if ( jQuery( this ).is( ':selected' ) )
                                selectedIDs.push( jQuery( this ).val() );
                            
                            // If the iterating sidebar ID is present in the dependencies, disable it.
                            if ( jQuery.inArray( this.value, data[ container_sidebar_id ] ) !== -1 )
                                jQuery( this ).attr( 'disabled', 'disabled' );    

                            // If the container sidebar ID is present in the dependencies of the iterating sidebar, disable it.
                            if ( jQuery.inArray( container_sidebar_id, data[ this.value ] ) !== -1 )
                                jQuery( this ).attr( 'disabled', 'disabled' );    

                            // If the iterating sidebar ID is the container sidebar ID, disable it.
                            if ( this.value == container_sidebar_id )
                                jQuery( this ).attr( 'disabled', 'disabled' );    
                                
                            // Remove the disabled attribute for the selected item if it's not the container sidebar ID.
                            if ( container_sidebar_id !=  this.value 
                                && jQuery.inArray( container_sidebar_id, data[ this.value ] ) === -1 
                                // && jQuery.inArray( this.value, data[ container_sidebar_id ] ) === -1 
                            ) {
                                jQuery( this ).filter( ":selected" ).removeAttr( "disabled" );
                            }
                                                        
                        });    // end of each option tag iteration.
                        
                        
                    });    // end of each sidebar box that contains a plugin widget.

                    ResponsiveColumnWidgets_EnableSelectedSidebarOptionTag( container_div, selectedIDs );
                    ResponsiveColumnWidgets_SelectAvailable( container_div.find( "select.<?php echo $this->strClassSelectorFormSelect ;?> option:selected.<?php echo $this->strClassSelectorFormOption; ?>" ) );
                    // console.log( '(RCW Log) Dependency select option items should be disabled by now.' );                    
                    
                }); // end of each sidebar box iteration.
                
                console.log( data );

            }
            function ResponsiveColumnWidgets_EnableSelectedSidebarOptionTag( container_div, selectedIDs ) {
                
                // When multiple widgets of this plugin are dropped in one sidebar box, the plugin considers the other widget's selected items
                // as one of the dependencies and disables the option tags containing the sidebar ID as its value. So we need to enable them.
                
                var container_sidebar_id = container_div.attr( "id" );
                
                // If multiple plugin widgets are not in the dropped sidebar box, return.
                if ( container_div.find( "select.<?php echo $this->strClassSelectorFormSelect ;?>" ).length <= 1 ) return;
                
                // Create a filter.
                var str_filter_selected_ids = "";
                var count = 0;    // somehow the variable, i, below does not start from 0 sometimes. So create a clean one here.
                jQuery.each( selectedIDs, function( i, sidebar_id ) {
                    if ( sidebar_id == container_sidebar_id ) return true;    // equivalent to the continue command in PHP.
                    var comma = ( count != 0  ? ', ' : '' );
                    str_filter_selected_ids += comma + "[value='" + sidebar_id + "']";
                    count++;
                });
                if ( str_filter_selected_ids === '' ) return;
                
                // Apply the filter.
                console.log( '(RCW Log) filter: ' + str_filter_selected_ids );
                var selected = container_div.find( "select.<?php echo $this->strClassSelectorFormSelect ;?> option.<?php echo $this->strClassSelectorFormOption; ?>" )
                    .filter( str_filter_selected_ids )
                    .each( function(){
                        this.disabled = false;
                    });
                console.log( "(RCW Log) The number of overall selected drop-down list items: " + selected.length );
                
            }
            function ResponsiveColumnWidgets_SelectAvailable( selected ) {
                
                // The plugin JavaScript scripts disable the plugin widget's drop-down list items based on the 
                // sidebar dependencies. There might be a case that previously selected item gets disabled
                // by it. In that case, this function is used to pick one available item from the list. If nothing is available,
                // a blank value ( no item ) will be assigned to the selected item.            
                
                selected.each( function() {
                    
                    if ( ! jQuery( this ).is( ':disabled' ) ) return true; // continue
                    
                    jQuery( this ).removeAttr( "selected" );    // deselect all the items.
                    jQuery( this ).siblings( ":not( :disabled )" ).each( function(){    
                        jQuery( this ).attr( "selected", "Selected" );
                        return false;    // pick the first item and break.
                    });
                    
                });
            }    
            
            function ResponsiveColumnWidgets_WidgetDropEvent( event, ui ) {

                var id = jQuery( ui.item ).attr( 'id' );
                if ( ! id ) return;
                
                var regex_match = id.match( /widget-[0-9]+_(.+)-(__i__|\d+)/i );
                if ( regex_match !== null && regex_match.hasOwnProperty( 1 ) ) 
                    var widget_type = regex_match[1];
                if ( widget_type != "<?php echo self::$strBaseID; ?>" ) return;

                console.log( '(RCW Log) A widget drop event occurred for the Responsive Column Widgets Box widget' );                
                ResponsiveColumnWidgets_SetContainerSidebarID( ui.item );
                                
                // If the second match element consists of all digits, that means the widget is dropped
                // from another sidebar to the other; the widget is not newly created. In that case,
                // renew(update) the widget form options by pressing the Save button.
                // This will update the widget form options.
                if ( /^\d+$/.test( regex_match[2] ) ) {    // widget moved
                    
                    // Before performing the option update, make sure newly placed widgets are not selecting disabled items.
                    ResponsiveColumnWidgets_DisableDependencyOptionTags( new Array() );    // pass an empty array
                    
                    // Update the options.
                    // wpWidgets.save( ui.item, 0, 1, 1 );
                    ResponsiveColumnWidgets_PressSaveButton( ui.item );    

                } else {    // widget added
                    
                    ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration( ui.item );
                                    
                }
                
            }
            function ResponsiveColumnWidgets_PressSaveButton( widget ) {
                
                // Select the save button element.
                var save = jQuery( widget ).find( 'input[type=submit]' );
                if ( save.length != 1 ) return;        // the save button must be only one per widget. Otherwise, something went wrong. So do nothing.
                    
                console.log( '(RCW Log) The widget Save button is going to be clicked.' );
                save.trigger( 'click' );
                
            }

            function ResponsiveColumnWidgets_SetContainerSidebarID( item ) {
                
                // Sets the container sidebar ID to the hidden input field. 
                // This is important for the update() method to update the form options.
                <?php if ( version_compare( get_bloginfo( 'version' ) , '3.6', "<" ) ) : ?>            
                    var container_div = jQuery( item ).closest( ".widgets-sortables" );
                    var container_sidebar_id = container_div.attr( "id" );                
                    if ( typeof container_sidebar_id !== 'undefined' ) 
                        container_div.find( "input.<?php echo $this->strClassSelector_ContainerSidebarID; ?>" ).val( container_sidebar_id );        
                <?php endif; ?>
                <?php if ( version_compare( get_bloginfo( 'version' ) , '3.6', ">=" ) ) : ?>
                    jQuery( "div.widgets-sortables" ).each( function() {
                        var container_div = jQuery( this ).has( "input.<?php echo $this->strClassSelector_ContainerSidebarID; ?>" );
                        var container_sidebar_id = container_div.attr( "id" );
                        if ( typeof container_sidebar_id == 'undefined' ) return;    // same as 'continue' in PHP
                        container_div
                            .find( "input.<?php echo $this->strClassSelector_ContainerSidebarID; ?>" )
                            .each( function() {
                                var input = jQuery( this );
                                input.val( container_sidebar_id );    // inputs.attr( 'value', 'XXX' );
                            });                        
                    });
                <?php endif; ?>            
            }

            function ResponsiveColumnWidgets_WidgetSaveButtonPressEvent( input ) {
                
                // the input object is the submit input field ( the save button element ).
                var container_div = jQuery( input ).closest( ".widgets-sortables" );
                var widget = container_div.find( 'div.widget' );
                var id = widget.attr( 'id' );
                if ( ! id ) return;
                
                // Check if it matches the plugin widget id.
                var regex_match = id.match( /widget-[0-9]+_(.+)-(__i__|\d+)/i );
                if ( regex_match !== null && regex_match.hasOwnProperty( 1 ) ) 
                    var widget_type = regex_match[1];
                if ( widget_type != "<?php echo self::$strBaseID; ?>" ) return;
                
                console.log( '(RCW Log) The plugin widget Save button has been pressed and its event has occurred.' );
                ResponsiveColumnWidgets_SetContainerSidebarID( widget );                
                ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
        
            }            
            
            // Hook the save button press(click) event
            // jQuery( document ).ready( function(){    // prevent multiple calls <-- this freezes the page in IE, in WordPress 3.5.x or below.
            
                // If the jQuery version is 1.8.x or below, use the live() method; otherwise, use the on() method
                // as the live() method is removed as of v1.9.
                console.log( '(RCW Log) the jQuery version is: ' + jQuery.fn.jquery );
                if ( /^1\.[012345678]\./.test( jQuery.fn.jquery ) ) {
                    
                    jQuery( 'input.widget-control-save' ).live( 'click', function(){
                        ResponsiveColumnWidgets_WidgetSaveButtonPressEvent( this );                        
                    });

                } else {    // assuming this is WordPress 3.6 or above.
                
                    jQuery( document ).ready( function() {    // the ready method works fine here in IE as well for some reasons. ( Could be that it cannot be used with the live method in IE. )
                        jQuery( 'input.widget-control-save' ).on( 'click', function(){
                            ResponsiveColumnWidgets_WidgetSaveButtonPressEvent( this );                        
                        });            
                        jQuery( "div.widgets-sortables" ).on( 'sortstop sortreceive', function( event, ui ){
                            
                            console.log( '(RCW Log) the sortstop or sortreceive event has been fired.' );                        
                            ResponsiveColumnWidgets_WidgetDropEvent( event, ui );
                            
                        });    
                            
                    });
                    
                }
            // });                
        </script>
        <?php
    }
        
    public function widget( $arrArgs, $arrInstance ) {    // must be public, the protected scope will cause fatal error.
        
        // Set up variables.
        $arrInstance = $arrInstance + array(
            'sidebarid_selected' => null,
            'sidebarid_parent' =>    null,        
        );    
        
        // Start rendering the widget.
        echo $arrArgs['before_widget']; 
                
        // First, check if there is a dependency conflict.
        $oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
        $arrDependencies = $oSH->GetDependencies( false );    
        if ( isset( $arrDependencies[ $arrInstance['sidebarid_selected'] ] ) && in_array( $arrInstance['sidebarid_parent'], $arrDependencies[ $arrInstance['sidebarid_selected'] ] )  ) {
            
            echo '<strong>Responsive Column Widgets:</strong> ' . __( 'A dependency conflict occurred. Please reselect a child widget in the Widgets page of the administration area.', 'responsive-column-widgets' );
            echo $arrArgs['after_widget'];
            return;
            
        }
        if ( empty( $arrInstance['sidebarid_selected'] ) ) {

            echo '<strong>Responsive Column Widgets:</strong> ' . __( 'No sidebar is selected.', 'responsive-column-widgets' );
            echo $arrArgs['after_widget'];
            return;
            
        }
        
        // Draw the contents of the selected widget box.
        $oCore = $GLOBALS['oResponsiveColumnWidgets'];
        $oCore->RenderWidgetBox( array( 'sidebar' => $arrInstance['sidebarid_selected'] ) );

        echo $arrArgs['after_widget'];
        
    }
    
    public function form( $arrInstance ) {

        /*
         * Variables - use uniqid() to generate a unique ID to avoid the same ID being inserted among other widgets dropped onto different sidebar boxes.
        */
        // Aboid undefined index warnings.
        $arrInstance = $arrInstance + array(
            'sidebarid_selected' => null,
            'sidebarid_parent' =>    null,
        );
    
        // For the select tag.
        $strID_SidebarIDSelected = $this->get_field_id( 'sidebarid_selected' );    
        $strName_SidebarIDSelected = $this->get_field_name( 'sidebarid_selected' );    // the string "sidebarid_selected" will be the key for the field and passed as the input array key to the update() method.

        // For the option tag.
        $strID_Selector = $strID_SidebarIDSelected . '_' . uniqid();    
        $strClass_Option = $strID_Selector;
        
        // For the hidden input tag that indicates the sidebar ID of the one to which the selected sidebar belongs.
        $strID_SidebarParent = $this->get_field_id( 'sidebarid_parent' ) . '_' . uniqid();    // the string "sidebarid" will be the key for the field and passed as the input array key to the update() method.
        $strName_SidebarParent = $this->get_field_name( 'sidebarid_parent' );    // the string "sidebarid" will be the key for the field and passed as the input array key to the update() method.
            
        // Create the array for the hierarchy reference.
        $oSH = new ResponsiveColumnWidgets_SidebarHierarchy;
        $arrDependencies = $oSH->GetDependencies();
        unset( $oSH ); // make sure the object is released.
        
// echo 'Sidebar Hierarchy: <br />'
    // . $this->DumpArray( $arrDependencies );            

        $oWO = new ResponsiveColumnWidgets_WidgetOptions;
        $arrWidgetOptions = $oWO->GetRegisteredWidgetOptionsByBaseID();
        unset( $oWO ); // make sure the object is released.
        
// echo 'Widget Options: <br />'
    // . '<div style="font-size: 80%; margin-left: 0px; padding-left: 0px">'
    // . $this->DumpArray( $arrWidgetOptions )
    // . '</div>';

// echo 'This Widget ID: ' . $this->id . '<br />';

        // Set the container sidebar ID - note that this will not work ( a null will be assigned ) when the widget is dropped for the first time. ( works after saving the form options. ) 
        $this->strContainerSidebarID = $this->GetContainerSidebarID();
// echo 'Parent Sidebar ID: ' . $this->strContainerSidebarID . '<br />';

        ?>
        <p>    
    
            <input type="hidden" name="<?php echo $strName_SidebarParent; ?>" id="<?php echo $strID_SidebarParent; ?>" class="<?php echo $this->strClassSelector_ContainerSidebarID; ?>" value="<?php echo $this->strContainerSidebarID; ?>" />
            <label for="<?php echo $strID_Selector; ?>">
                <?php _e( 'Select Sidebar', 'responsive-column-widgets' ); ?>:
            </label>
            <br />
            <select name="<?php echo $strName_SidebarIDSelected; ?>" id="<?php echo $strID_Selector; ?>" class="<?php echo $this->strClassSelectorFormSelect; ?>">
                <?php 
                // the default non-select item.
                echo '<option class="' . $strClass_Option . ' ' . $this->strClassSelectorFormOption . '" value="">'
                    . '-------- ' . __( 'Select a sidebar', 'responsive-column-widgets' ) . ' --------'
                    . '</option>';

                foreach( $GLOBALS['wp_registered_sidebars'] as $arrSidebar ) {
                    
                    $bDisabled = false;
                    
                    // wp_inactive_widgets is for the Inactive Widgets section box in the admin widget page. So do nothing.
                    if ( $arrSidebar['id'] == 'wp_inactive_widgets' ) continue;    
                    if ( in_array( 'inactive-sidebar', explode( ' ', $arrSidebar['class'] ) ) ) continue;
                    
                    // If the parsing sidebar ID is the same as the container sidebar ID, disable it.
                    if ( $arrSidebar['id'] == $this->strContainerSidebarID ) 
                        $bDisabled = true;
                    
                    // If the parsing sidebar ID matches this widget's parent (container) sidebar ID, disable it.
                    // The $this->id property is defined in the WP_Widget class ( the parent class of this class ) and the id properties is the widget id of this widget instance.
                    if ( isset( $arrWidgetOptions[ $this->id ]['sidebarid_parent'] ) && $arrWidgetOptions[ $this->id ]['sidebarid_parent'] == $arrSidebar['id'] ) 
                        $bDisabled = true;
                    
                    // If the parsing sidebar ID ( the candidate to be a child sidebar ) has this container sidebar as its child, then disable it.
                    if ( isset( $arrDependencies[ $arrSidebar['id'] ] ) && in_array( $this->strContainerSidebarID, $arrDependencies[ $arrSidebar['id'] ] ) )
                        $bDisabled = true;
                        
                    // If the container sidebar    ID has the parsing sidebar ID as its dependency, disable it.
                    // This should not be commented out but currently the JavaScript scripts of this plugin are not able to enable this item
                    // when the position of widget is changed. Until the script to fix it is added, let it being commented out.
                    // if ( isset( $arrDependencies[ $this->strContainerSidebarID ] ) && in_array( $arrSidebar['id'], $arrDependencies[ $this->strContainerSidebarID ] ) )
                        // $bDisabled = true;
                    
                    echo '<option class="' . $strClass_Option . ' ' . $this->strClassSelectorFormOption . '" value="' 
                        . esc_attr( $arrSidebar['id'] )
                        . '" '
                        . ( $arrSidebar['id'] == $arrInstance['sidebarid_selected'] ? 'selected="Selected"' : '' )
                        . ( $bDisabled ? ' disabled=Disabled' : '' ) 
                        . '>'
                        . ucwords( $arrSidebar['name'] )
                        . '</option>';
                        
                }
                ?>
            </select>
        </p>
        
        <?php if ( version_compare( get_bloginfo( 'version' ) , '3.6', "<" ) ) : ?>
        <script type="text/javascript" class="responsive-column-widgets-widget-registration-script" >

            var select = jQuery( "select#<?php echo $strID_Selector; ?>" );    
            var container_sidebar_id = select.closest( ".widgets-sortables" ).attr( "id" ); 
            var selects = jQuery( "select.<?php echo $this->strClassSelectorFormSelect; ?>" );

            console.log( '(RCW Log) The plugin widget form has been rendered.' );
            if ( typeof container_sidebar_id !== 'undefined' && container_sidebar_id != 'wp_inactive_widgets' ) {
                
                console.log( 
                    '(RCW Log) Container Sidebar ID: ' + container_sidebar_id + '\n' +
                    'Widget ID: <?php echo $this->id; ?>'                 
                );
                
                // Set the container sidebar ID to the hidden input field. This is important for the update() method to update the form options.
                jQuery( "input#<?php echo $strID_SidebarParent;?>" ).val( container_sidebar_id );    
                
                ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
                
            }
                        
            // When the widget is drag'n-dropped,
            var container_divs = selects.closest( "div.widgets-sortables" );
        
            // (Re)Hook the widget drop event.                
            // Somehow the live() method causes syntax errors so use the bind method.
            container_divs.unbind( 'sortstop sortreceive' );    // unbind previous event hooks.
            container_divs.bind( 'sortstop sortreceive', function( event, ui ){
                ResponsiveColumnWidgets_WidgetDropEvent( event, ui );
            });
    
        </script>
        <?php endif; ?>
        <?php if ( version_compare( get_bloginfo( 'version' ) , '3.6', ">=" ) ) : ?>
        <script type="text/javascript" class="responsive-column-widgets-widget-registration-script" >
        
            var select = jQuery( "select#<?php echo $strID_Selector; ?>" );        
            var container_sidebar_id = select.closest( ".widgets-sortables" ).attr( "id" ); 
            var selects = jQuery( "select.<?php echo $this->strClassSelectorFormSelect; ?>" );

            console.log( '(RCW Log) The plugin widget form has been rendered.' );
            if ( typeof container_sidebar_id !== 'undefined' && container_sidebar_id != 'wp_inactive_widgets' ) {
                
                console.log( 
                    '(RCW Log) Container Sidebar ID: ' + container_sidebar_id + '\n' +
                    'Widget ID: <?php echo $this->id; ?>'                 
                );
                
                // Set the container sidebar ID to the hidden input field. This is important for the update() method to update the form options.
                jQuery( "input#<?php echo $strID_SidebarParent;?>" ).val( container_sidebar_id );    
                
                ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
                
            }        
                    
        </script>
        <?php endif;    
        
    }
    
    public function update( $arrNewInstance, $arrOldInstance ) {
        
        if ( empty( $arrNewInstance['sidebarid_selected'] ) )
            return $arrOldInstance;
        
        return $arrNewInstance + array(
            'sidebarid_selected' => null,
            'sidebarid_parent' =>    null,
        );
    
    }
    
    /*
     * Methods for Debug
     * */
    function DumpArray( $arr ) {
        
        return '<pre>' . esc_html( print_r( $arr, true ) ) . '</pre>';
        
    }    
}



