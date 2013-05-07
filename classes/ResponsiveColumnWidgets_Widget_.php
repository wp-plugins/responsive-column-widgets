<?php
/**
	Creates a widget that encapsulates a sidebar. 
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.3
 * @dependencies ResponsiveColumnWidgets_SidebarHierarchy, ResponsiveColumnWidgets_WidgetOptions
 * 	
	
*/

class ResponsiveColumnWidgets_Widget_ extends WP_Widget {

	protected $strBaseID = 'responsive_column_widget_box';
	protected $strWidgetName = 'Responsive Column Widget Box';
	protected $strClassSelectorFormSelect = 'responsive_column_widget_box_form_select';	// refered by the plugin JavaScript script to find out the container sidebar ID.
	protected $strClassSelectorFormOption = 'responsive_column_widget_box_form_option';	// refered by the plugin JavaScript script to disable cetain option tag elements.
	
	public static function RegisterWidget() {
	
		return register_widget( 'ResponsiveColumnWidgets_Widget' );	// class name

	}	
	public function __construct() {
		
		// Objects
		// Do not store objects in object properties as it seems PHP stores callbacks including referenced objects in the properties with their total sizes, which causes this object to be huge in size.
		// $this->oOption = & $GLOBALS['oResponsiveColumnWidgets_Options']; // reference the option object.
		
		// Proeprties
		$this->strClassSelectorFormOption = $this->strBaseID . '_form_option';
		
		// Add a common JavaScript script into the head tag of the widget.php admin page.
		if ( $GLOBALS['pagenow'] == 'widgets.php' ) 
			add_action( 'admin_head', array( $this, 'AddJavaScript_CommonFunctions' ) );
				
		// Register the widget
		parent::__construct(
	 		$this->strBaseID, 
			$this->strWidgetName, 
			array( 'description' => __( 'A widget that encapsulates a sidebar.', 'responsive-column-widgets' ), ) 
		);
	
	}
	
		
	protected function GetContainerSidebarID() {	// since 1.1.3
					
		foreach( get_option('sidebars_widgets') as $strSidebarID => $oRegisteredWidgets ) 
			if ( in_array( $this->id, ( array ) $oRegisteredWidgets ) ) 
				return $strSidebarID;

	}
	
	public function AddJavaScript_CommonFunctions() {	// since 1.1.3, public as used by hooks
	
		// This script is called from JavaScript events triggered when a plugin widget is moved.
		// This script requests another page on the site with a query and retrieves the result from the page as JSON.
		// The plugin returns the information of the saved sidebar's relationships between the added plugin widgets.
		?>
		<script type="text/javascript" class="responsive-column-widgets-widget-registration-script">
			var global_responsive_column_widgets_dependencies = new Array();

			function ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration(){
				
				// Defined the static variable
				if ( typeof ResponsiveColumnWidgets_Timer == 'undefined' ) {
					
					ResponsiveColumnWidgets_Timer = new Date().getTime();	// will be static
					
				} else  {	// the else clause helps to execute the first call.
					
					// Prevent multiple repeated calls too often.
					if ( ResponsiveColumnWidgets_Timer + 200 > new Date().getTime() ) {	// 200 milliseconds have not passed.
						ResponsiveColumnWidgets_Timer = new Date().getTime();
						return; 
					}
					
				}  
				
				// here is where the request will happen
				var dependencies = jQuery.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					data:{
						'action': 'get_sidebar_hierarchy',		// a custom action hook
						'rcw_ajax_request': 'true',				// the $_GET request
						// 'sidebarid': 'test_output_sidebarid'		// the $_GET request
					},
					dataType: 'JSON',
					success: function( data ){
						// set the data into the global variable.						
						ResponsiveColumnWidgets_DisableDependencyOptionTags( data );
						// console.log( data );
					},
					error: function( errorThrown ){
						console.log( errorThrown );
					}
				});

			}	
			
			function ResponsiveColumnWidgets_DisableDependencyOptionTags( data ) {

				// Remove all disable attributes assigned to the plugin's widget's form option tags.
				jQuery( "select option.<?php echo $this->strClassSelectorFormOption;?>" )
					.removeAttr( 'disabled' );
				
				// Iterate the plugin's select tags.
				jQuery( "select.<?php echo $this->strClassSelectorFormSelect; ?>" ).each( function() {
					
					var select = jQuery( this );
					var container_sidebar_id = select.closest( ".widgets-sortables" ).attr( "id" );
					
					// Skip the undefined id element because it means either it belongs to the inactive sidebar box / the default factory box or removed.
					if ( typeof container_sidebar_id == 'undefined' ) return true;	// to be equevalent to the continue command in loop, return true.
					
					// Iterate the option tags which belongs to the select tag.
					var option = select.find( "option.<?php echo $this->strClassSelectorFormOption; ?>" );
					option.each( function() {
					
						// this.text : the label displayed in the option item.
						// this.value : the value set in the option tag.
						// alert( this.text + ' ' + this.value );	
						// If the iterating sidebar ID is present in the dependencies, disable it.
						if ( jQuery.inArray( this.value, data[ container_sidebar_id ] ) != -1 )
							jQuery( this ).attr( 'disabled', 'disabled' );	
						
					});
					option.filter( ":selected" ).removeAttr( 'disabled' );	// remove the disabled attribute for the selected item.
					// alert( container_sidebar_id + ': ' + this.value );	
					
				});								

				console.log( data );
				
			}	
					
		</script>
		<?php
	}
		
	public function widget( $arrArgs, $arrInstance ) {	// must be public, the protected scope will case fatal error.
		
		echo $arrArgs['before_widget']; 
		
		// First, check if there is a dependency conflict.
		$oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
		$arrDependencies = $oSH->GetDependencies( false );		
		if ( isset( $arrDependencies[ $arrInstance['sidebarid_selected'] ] ) && in_array( $arrInstance['sidebarid_parent'], $arrDependencies[ $arrInstance['sidebarid_selected'] ] )  ) {
			
			_e( 'A dependency conflict occurred. Please reselect a child widget in the Widgets page of the administration area.', 'responsive-column-widgets' );
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
			'sidebarid_parent' =>	null,
		);
	
		// For the select tag.
		$strID_SidebarIDSelected = $this->get_field_id( 'sidebarid_selected' );	
		$strName_SidebarIDSelected = $this->get_field_name( 'sidebarid_selected' );	// the string "sidebarid_selected" will be the key for the field and passed as the input array key to the update() method.

		// For the option tag.
		$strID_Selector = $strID_SidebarIDSelected . '_' . uniqid();	
		$strClass_Option = $strID_Selector;
		
		// For the hidden input tag that indicates the sidebar ID of the one to which the selected sidebar belongs.
		$strID_SidebarBelong = $this->get_field_id( 'sidebarid_parent' ) . '_' . uniqid();	// the string "sidebarid" will be the key for the field and passed as the input array key to the update() method.
		$strName_SidebarBelong = $this->get_field_name( 'sidebarid_parent' );	// the string "sidebarid" will be the key for the field and passed as the input array key to the update() method.
		
		// The registered sidebar arrays.
		$arrRegisteredSidebars = $GLOBALS['wp_registered_sidebars'];
		
		// Create the array for the hierarchy reference.
		$oSH = new ResponsiveColumnWidgets_SidebarHierarchy;
		$arrDependencies = $oSH->GetDependencies();

// echo 'Sidebar Hierarchy: <br />'
	// . $this->DumpArray( $arrDependencies );			

		$oWO = new ResponsiveColumnWidgets_WidgetOptions;
		$arrWidgetOptions = $oWO->GetRegisteredWidgetOptionsByBaseID();
		
// echo 'Widget Options: <br />'
	// . '<div style="font-size: 80%; margin-left: 0px; padding-left: 0px">'
	// . $this->DumpArray( $arrWidgetOptions )
	// . '</div>';

// echo 'This Widget ID: ' . $this->id . '<br />';

		// Set the container sidebar ID - note that this will not work when the widget is dropped for the first time. ( works after saving the form options. ) 
		$this->strContainerSidebarID = $this->GetContainerSidebarID();
// echo 'Parent Sidebar ID: ' . $this->strContainerSidebarID . '<br />';


		?>
		<p>	
	
			<input type="hidden" name="<?php echo $strName_SidebarBelong; ?>" id="<?php echo $strID_SidebarBelong; ?>" value="" />
			<label for="<?php echo $strID_SidebarIDSelected; ?>">
				<?php _e( 'Select Sidebar', 'responsive-column-widgets' ); ?>:
			</label>
			<br />
			<select name="<?php echo $strName_SidebarIDSelected; ?>" id="<?php echo $strID_Selector; ?>" class="<?php echo $this->strClassSelectorFormSelect; ?>">
				<?php 
				foreach( $arrRegisteredSidebars as $arrSidebar ) {
					
					// wp_inactive_widgets is for the Inactive Widgets section box in the admin widget page. So do nothing.
					if ( $arrSidebar['id'] == 'wp_inactive_widgets' ) continue;	
					
					// If the parsing sidebar ID is the same as the container sidebar ID, skip.
					if ( $arrSidebar['id'] == $this->strContainerSidebarID ) continue;
					
					// If the parsing sidebar ID matches this widget's parent (container) sidebar ID, skip.
					// The $this->id property is defined in the WP_Widget class ( the parent class of this class ) and the id properties is the widget id of this widget instance.
					if ( isset( $arrWidgetOptions[ $this->id ]['sidebarid_parent'] ) && $arrWidgetOptions[ $this->id ]['sidebarid_parent'] == $arrSidebar['id'] ) 
						continue;
					
					// If the parsing sidbar ID ( the candidate to be a child sidebar ) already has this container sidebar as its child, then skip.
					if ( isset( $arrDependencies[ $arrSidebar['id'] ] ) && in_array( $this->strContainerSidebarID, $arrDependencies[ $arrSidebar['id'] ] ) )
						continue;
											
					echo '<option class="' . $strClass_Option . ' ' . $this->strClassSelectorFormOption . '" value="' 
						. esc_attr( $arrSidebar['id'] )
						. '" '
						. ( $arrSidebar['id'] == $arrInstance['sidebarid_selected'] ? 'selected="Selected"' : '' )
						. '>'
						. ucwords( $arrSidebar['name'] )
						. '</option>';
						
				}
				?>
			</select>
		</p>
		
<?php // echo '<p id="test" class="warning">Sidebar ID: </p>'; ?>
		<script type="text/javascript" class="responsive-column-widgets-widget-registration-script" >
// jQuery( "p.warning" ).css( "color", "red" );	// debug
			var container_sidebar_id = jQuery( "select#<?php echo $strID_Selector; ?>" ).closest( ".widgets-sortables" ).attr( "id" );
// jQuery( 'p#test' ).append( sidebar_id + '<br />' );		// debug
			// jQuery( "select#<?php echo $strID_Selector; ?> option.<?php echo $strClass_Option;?>").filter( "[value='" + sidebar_id + "']" ).attr( 'disabled', 'disabled' ).siblings().removeAttr( 'disabled' );
			
			jQuery( "input#<?php echo $strID_SidebarBelong;?>" ).val( container_sidebar_id );	// set the container sidebar ID to the hidden input field.
			// if ( typeof container_sidebar_id != 'undefined' ) {
			// if ( container_sidebar_id != 'available-widgets' ) {
				jQuery( document ).ready( function(){	// prevent multiple calls
					ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
				});			
			// }
		</script>
		<?php 
			$this->AddJavaScript_Events( $strID_Selector, $strClass_Option ); 
		
    }
	protected function AddJavaScript_Events( $strIDSelector, $strClassSelector ) {	// since 1.1.3
		?>
		<script type="text/javascript" class="responsive-column-widgets-widget-registration-script" >
		
			// When the plugin widget's Save button is pressed				
			// jQuery( "select#<?php echo $strIDSelector; ?>" ).closest( "form" ).find( 'input[type=submit]' ).click( function( event ) {			
				// ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration( function( data ) {

					// jQuery( "p.warning" ).css( "color", "green" );	
					
					// for( var key in data ) {
						// if( ! data[ key ] || data[ key ] == '' ) continue; 	// if the value is empty, skip.		
						// alert( data[ key ] );
						// console.log( data[ key ] );
					// }
					
				// });
			// });
		
			// When the widget is drag'n-dropped,
			jQuery( function() {
				var $widget = jQuery( "select#<?php echo $strIDSelector; ?>" ).closest( "div.widgets-sortables" );
				$widget.bind( 'sortstop', function( event, ui ){
					var id = jQuery( ui.item ).attr( 'id' );
					if ( id ) {
						var widget_type = id.match(/widget-[0-9]+_(.+)-[0-9]+/i)[1];
console.log( 'called: sortstop' );						
						if ( widget_type == 'responsive_column_widget_box' ) {	// alert( 'dropped: sortstop' );							
							ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
						}
					}
				})
				$widget.bind( 'sortreceive', function( event, ui ){
					var id = jQuery( ui.item ).attr( 'id' );
					var widget_type = id.match(/widget-[0-9]+_(.+)-__i__/i)[1];
console.log( 'called: sortreceive' );											
					if ( widget_type == 'responsive_column_widget_box' ) {					
						ResponsiveColumnWidgets_DoAjaxRequestForWidgetRegistration();
					}
				})
			});	
		
		</script>
		<?php
	}
	
	public function update( $arrNewInstance, $arrOldInstance ) {

// ResponsiveColumnWidgets_Debug::DumpArray( $_POST, dirname( __FILE__ ) . '/info.txt' );		
// ResponsiveColumnWidgets_Debug::DumpArray( $arrNewInstance, dirname( __FILE__ ) . '/info.txt' );		


		// If the 'sidebarid_parent' element is blank, that means the client's browser disables JavaScript.
		// If so, do not update the passed value because it could contain the parent sidebar ID as the selected one which would cause infinite recursive includsion.
		// if ( empty( $arrNewInstance['sidebarid_parent'] ) ) return $arrOldInstance;
			
		// Save the hierarchial relationship into the option array. Each key has the name of sidebar ID and the element holds the values of the sidebar IDs that is embedded in.
		// $oOption = & $GLOBALS['oResponsiveColumnWidgets_Options'];
		// $strSidebarID_Parent = $arrNewInstance['sidebarid_parent'];
		// if ( ! in_array( $arrNewInstance['sidebarid_selected'], $oOption->arrOptions['hierarchy'][ $strSidebarID_Parent ] ) )
			// $oOption->arrOptions['hierarchy'][ $strSidebarID_Parent ][] = $arrNewInstance['sidebarid_selected'];
		// $oOption->Update();
		
		// $arrNewInstance = $arrNewInstance + $arrOldInstance;
				
        return $arrNewInstance;
	
    }
	
	/*
	 * Methods for Debug
	 * */
	function DumpArray( $arr ) {
		
		return '<pre>' . esc_html( print_r( $arr, true ) ) . '</pre>';
		
	}	
}



