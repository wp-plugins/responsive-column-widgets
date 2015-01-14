<?php
/**
 * Displays widgets in multiple columns
 *  
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @see         http://en.michaeluno.jp/responsive-column-widgets
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Provides method to output widget boxes.
 * 
 * @since       1.0.0
 * @uses        ResponsiveColumnWidgets_Styles, ResponsiveColumnWidgets_WidgetBox, ResponsiveColumnWidgets_IDHandler
 * @filter      apply       RCW_filter_widgetbox_output         Applies to the outputs of widget boxes.
 * @filter      apply       RCW_filter_widget_output_array      Applies to the widget output array. 
 */
class ResponsiveColumnWidgets_Core_ {
    
    // Objects
    /**
     * Deals with the plugin options.
     * @since       unknown
     * @since       1.1.2           Made it public to allow the AutoInsert class access this object.
     * @since       1.1.2.1         the StyleLoader class also uses it.
     */
    public $oOption;
    
    /**
     * Handles CSS rules.
     * 
     * @access      public          It is public because the Auto-Insert class uses it. 
     * @since       1.1.2
     * @since       1.1.2.1         the StyleLoader class also uses it.
     */
    public $oStyle; 
    
    /**
     * 
     * @since       1.1.6           Decodes encrypted html contents as cache saved in a transient.
     */
    public $oDecode;
        
    /**
     * Used for the name attribute of the script.
     */
    protected $strPluginName = 'responsive-column-widgets';        
    
    /**
     * 
     * @remark      will be overridden by the option object's array in the constructor.
     */
    protected $arrDefaultParams = array();   
            
    protected $strClassSelectorBox2 ='widget-area';
    
    /**
     * 
     * @remark      overridden by the option in the constructor
     * @since       unknown
     * @since       made it public allow the StyleLoader class to access it.
     */
    public $arrClassSelectors = array(  
        'box'       => 'responsive_column_widgets_box',
        'column'    => 'responsive_column_widgets_column',
        'row'       => 'responsive_column_widgets_row',
    );
    
    /**
     * Stores the array containing hierarchy information of the sidebars selected in the plugin widget.
     */
    protected $arrSidebarHierarchies;   
    
    // Flags
    protected $bIsFormInDynamicSidebarRendered = false;

    /**
     * Sets up properties and hooks.
     */
    function __construct( $oOption ) {
                
        // Properties
        $this->arrDefaultParams     = $oOption->arrDefaultParams + $oOption->arrDefaultSidebarArgs;
        $this->arrClassSelectors    = array( 
            'box'        => $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['box'] ),
            'column'     => $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['column'] ),
            'row'        => $oOption->SanitizeAttribute( $oOption->arrOptions['general']['general_css_class_attributes']['row'] ),
        );

        // Objects
        $this->oOption  = $oOption;
        $this->oStyle   = new ResponsiveColumnWidgets_Styles( 
            $oOption, 
            $this->arrClassSelectors
        );
        $this->oDecode  = new ResponsiveColumnWidgets_Decoder;
                        
        // Add the stylesheet    
        if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
            && ! $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) {    // 0 for the header
            
            add_action( 'wp_head', array( $this->oStyle, 'AddStyleSheet' ) );
            if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['login'] ) {
                add_action( 'login_head', array( $this->oStyle, 'AddStyleSheet' ) );
            }
            if ( $this->oOption->arrOptions['general']['general_css_areas_to_load']['admin'] ) {
                add_action( 'admin_head', array( $this->oStyle, 'AddStyleSheet' ) );
            }        
        }
           
        // Compatibility Fixes
        add_action( 'init', array( $this, 'supportTwentyFourteen' ) );
        
    }
        /**
         * Support Twenty Fourteen 
         */
        public function supportTwentyFourteen() {
            if ( function_exists( 'twentyfourteen_content_width' ) ) {
                $this->strClassSelectorBox2 .= ' content-sidebar';            
            }
        }
    
    /*
     * The core methods to render widget boxes. RenderWidgetBox() and getWidgetBoxOutput().
     * 
     * @access      public      This is called from instantiated objects.
    */
    public function RenderWidgetBox( $arrParams, $arrOutput=array(), $bIsStyleNotScoped=false ) { 
        echo $this->getWidgetBoxOutput( $arrParams, $arrOutput, $bIsStyleNotScoped ); 
    }    
    
    /**
     * Returns the widget box output.
     * 
     * @remark      This method can be the callback for shortcode or manually called by the front-end function.
     * @since       1.0.4
     * @since       1.2.0       Changed the name from 'GetWidgetBoxOutput'.
     * @return      string      The widget box HTML block output.
     * @access      public      Called from instantiated objects such as the shortcode class's.
     */
    public function getWidgetBoxOutput( $arrParams, $arrOutput=array(), $bIsStyleNotScoped=false ) {

        $arrParams = $this->oOption->FormatParameterArray( $arrParams );

        // for shortcode callbacks , it needs to be converted to array. Note that array( '' ) is evaluated not true so if this is an empty string, '', this line helps to make it empty array.
        $arrOutput = empty( $arrOutput ) ? array() : $arrOutput;    
        
        // If this is a callback for the shortcode, the third parameter will be false. Reverse the value.
        $bIsStyleScoped = $bIsStyleNotScoped ? false : true;

        // Check sidebar dependency conflicts
        if ( $this->isDependencyConflict( $arrParams['sidebar'] ) ) {
            return '<p class="error">'
                    . '<strong>Responsive Column Widget</strong>: '
                    . __( 'A dependency conflict occurred. Please reselect a child widget in the Widgets page of the administration area.', 'responsive-column-widgets' ) 
                . '</p>';
        }
                
        // Generate the ID - Get a unique ID selector based on the combination of the sidebar ID and the parameters.
        $oID            = new ResponsiveColumnWidgets_IDHandler;
        $strCallID      = $oID->GetCallID( $arrParams['sidebar'], $arrParams );    // an ID based on the sidebar ID + parameters; there could be the same ID if the passed values are the same.
        $strIDSelector  = $oID->GenerateIDSelector( $strCallID );    // a unique ID throughout the script load 
        unset( $oID );    // for PHP below 5.3
        
        // Retrieve the widget output.
        $strOut = "<div class='{$arrParams['sidebar']}'>"
                . $arrParams['before_widget_box']
                . "<div id='{$strIDSelector}' class='{$this->arrClassSelectors['box']} {$this->strClassSelectorBox2}'>"
                    . $this->getOutputWidgetBuffer( $arrOutput, $arrParams, $strCallID, $bIsStyleScoped ) 
                . "</div>"
            . $arrParams['after_widget_box']
            . "</div>";
            
        return apply_filters( 'RCW_filter_widgetbox_output', $strOut ) . $this->GetCredit();
        
    }    
    /**
     * Check sidebar dependency conflicts.
     * 
     * @since       1.1.7.3
     */
    protected function isDependencyConflict( $strSidebarID ) { 
        
        if ( ! isset( $this->arrSidebarHierarchies ) ) {
            // 1.1.7.3+ Store the sidebar hierarchy array in a property.
            $oWO = new ResponsiveColumnWidgets_WidgetOptions;
            $this->arrSidebarHierarchies = $oWO->GetHierarchyBase();
            unset( $oWO );    // for PHP below 5.3
        }
       
        $oSH = new ResponsiveColumnWidgets_SidebarHierarchy();
        $arrDependencies = $oSH->getDependenciesOf( $strSidebarID, $this->arrSidebarHierarchies );
        unset( $oSH );    // for PHP below 5.3.
        if ( isset( $this->arrSidebarHierarchies[''] ) || in_array( $strSidebarID, $arrDependencies ) ) {
            return true;
        }
        
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
    protected function getCorrectSidebarID( $vIndex ) {
        
        global $wp_registered_sidebars;
        if ( is_int( $vIndex ) ) { 
            return "sidebar-$vIndex";
        }

        $vIndex = sanitize_title( $vIndex );
        foreach ( ( array ) $wp_registered_sidebars as $strKey => $arrValue ) {
            if ( sanitize_title( $arrValue['name'] ) == $vIndex ) {
                return $strKey;
            }
        }
        return $vIndex;
        
    }
    protected function isRenderable( $strSidebarID, &$arrSidebarsWidgets ) {
        
        global $wp_registered_sidebars;
        if ( empty( $arrSidebarsWidgets ) ) return false;
        if ( empty( $wp_registered_sidebars[ $strSidebarID ] ) ) return false;
        if ( !array_key_exists( $strSidebarID, $arrSidebarsWidgets ) ) return false;
        if ( !is_array( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
        if ( empty( $arrSidebarsWidgets[ $strSidebarID ] ) ) return false;
        return true;
        
    }    
    
    /**
     * Returns the widget box output.
     * 
     */
    protected function getOutputWidgetBuffer( $arrOutput, &$arrParams, $strCallID, $bIsStyleScoped ) {
        
        // Check if the cache duration is set and if the cache is stored.
        $strCacheID = 'RCW_Cache_' . md5( $strCallID );    // since the passed call ID has the long prefix 'responsive_coluimn_widget', it needs to be shortened.
        $strBuffer = $arrParams['cache_duration'] > 0 ? $this->oDecode->decodeBase64( get_transient( $strCacheID ) ) : '';

        // Instantiate the object to generate widget box outputs.
        $oWidgetBox = new ResponsiveColumnWidgets_WidgetBox( // this object must be instantiated every time rendering a widget box.
            $arrParams, 
            $this->oOption->SetMinimiumScreenMaxWidth(    // the max-columns array
                $this->oOption->FormatColumnArray( 
                    $arrParams['columns'],     
                    $arrParams['default_media_only_screen_max_width'] 
                )        
            ),
            $this->oOption->formatColSpanArray( $arrParams['colspans'] ),
            $this->arrClassSelectors
        );    
        $strSidebarID = $this->getCorrectSidebarID( $arrParams['sidebar'] );

        if ( empty( $strBuffer ) ) {
            
            // First, retrieve the filtered output array.
            $arrOutputBuffer = ( array ) apply_filters( 'RCW_filter_widget_output_array', $arrOutput, $arrParams );
            $fIsEmpty = empty( $arrOutputBuffer );
            
            // check if the sidebar is renderable.            
            $arrSidebarsWidgets = wp_get_sidebars_widgets();
            if ( $fIsEmpty ) {
                
                // If nothing is registered in the given name of sidebar, return
                if ( ! is_active_sidebar( $strSidebarID ) )
                    return '<p>' . $arrParams['message_no_widget'] . '</p>';                
                
                if ( ! $this->isRenderable( $strSidebarID, $arrSidebarsWidgets ) ) 
                    return '<p>' . __( 'The responsive box is not renderable.', 'responsive-column-widgets' ) . '</p>';
                    
            }        
            
            // Store the output buffers into an array.
            $arrWidgetBuffers = $fIsEmpty
                ? $oWidgetBox->getWidgetsBufferAsArray( 
                    $strSidebarID, 
                    $arrSidebarsWidgets,
                    $this->oOption->ConvertStringToArray( $arrParams['showonly'], ',' ),
                    $this->oOption->ConvertStringToArray( $arrParams['omit'], ',' ),
                    $arrParams['remove_id_attributes']
                )
                : $arrOutputBuffer;

            // since 1.1.3 - Get the flag array indicating whether the widgets are the plugin's widget-box widget or not.
            $arrFlagsWidgetBoxWidget = $fIsEmpty
                ? $oWidgetBox->GetWidgetBoxWidgetFlagArray()
                : array();
                            
            // Now, $arrWidgetBuffers contains the necessary data for the output. 
            // Enclose the buffer output string with the tag having the class attribute of screen max-width.    
            foreach ( $arrWidgetBuffers as $intIndex => $strWidgetBuffer )     {
                
                $oWidgetBox->setColSpans( $intIndex + 1 ); // the widget index is one-base while the array index is zero-base.
                
                $strBuffer .= '<div class="' 
                    . $oWidgetBox->GetClassAttribute()     // returns the class attribute values calculated with the stored positions and parameters.
                    . ( isset( $arrFlagsWidgetBoxWidget[ $intIndex ] ) && $arrFlagsWidgetBoxWidget[ $intIndex ] ? ' widget_box_widget' : '' )    // add no margin and no padding class
                    // . " content-sidebar"    // for Twenty Fourteen
                    . '">'
                    .  force_balance_tags( $strWidgetBuffer )
                    . '</div>';     
                    
                // If the allowed number of widgets reaches the limit, escape the loop.
                // For the max-rows, it depends on the screen max-widths, so it will be dealt with the style.
                if (  $arrParams['maxwidgets'] != 0 &&  ( $intIndex + 1 ) >= $arrParams['maxwidgets'] ) break;
                    
                $oWidgetBox->advancePositions();    // increments the position values stored in the object properties.
                    
            }    
            
            if ( $arrParams['cache_duration'] > 0 ) {
                set_transient( $strCacheID, base64_encode( $strBuffer ), $arrParams['cache_duration'] );
            }
                
        }
            
        // the CSS rules
        $strBuffer .= $this->oStyle->GetStyles( 
            $strSidebarID, 
            $strCallID, 
            $arrParams['custom_style'], 
            $oWidgetBox->GetScreenMaxWidths(), 
            $bIsStyleScoped 
        );
            
        unset( $oWidgetBox );    // for PHP below 5.3.
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