<?php
/**
 * Returns plugin specific CSS rules.
 *   
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl   http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Returns plugin specific CSS rules.
 * 
 * @since       1.1.2
 * @filter      apply       RCW_filter_base_styles          applies to the base CSS rules of the plugin.
 */
class ResponsiveColumnWidgets_Styles_ {
    
    // Default Properties
    protected $strColPercentages = array(
        1 =>    100,
        2 =>    49.2,
        3 =>    32.2,
        4 =>    23.8,
        5 =>    18.72,
        6 =>    15.33,
        7 =>    12.91,
        8 =>    11.1,
        9 =>    9.68,
        10 =>    8.56,
        11 =>    7.63,
        12 =>    6.86,
    );        
    
    /**
     * Sets up properties.
     */
    function __construct( &$oOption, $arrClassSelectors ) {
        
        $this->oOption = $oOption;
        
        $this->strClassSelectorBox      = $arrClassSelectors['box'];
        $this->strClassSelectorColumn   = $arrClassSelectors['column'];
        $this->strClassSelectorRow      = $arrClassSelectors['row'];
        $this->strClassWidgetBoxWidget  = 'widget_box_widget';
    }

    /*
     * Used by hooks that embed base styles such as wp_head, login_head, admin_head.
     * 
     * @todo        examine why this is not done in the style loader class.
     * @remark      callback of wp_head, login_head, admin_head
    */
    public function AddStyleSheet() {
    
        global $arrResponsiveColumnWidgets_Flags;
        $arrResponsiveColumnWidgets_Flags['base_style'] = true;
        
        echo $this->GetBaseStyles();
        echo $this->GetUserDefinedEnqueuedStyles();
        
    }

    /**
     * Returns the CSS rules.
     * 
     * @since       1.1.2.1
     * @since       1.2.0           Moved from the _StyleLoader class as it more makes sense in the Style class.
     * Made it static anc changed the scope public from protected as the ..._Style class access it. Removed the first parameter of the core object.
     * @access      public  
     */
    static public function getStyle( $arrParams=array() ) {
        
        global $oResponsiveColumnWidgets;
        if ( ! isset( $oResponsiveColumnWidgets ) ) { return ''; }
        
        $oStyle     = $oResponsiveColumnWidgets->oStyle;
        $oOption    = $oResponsiveColumnWidgets->oOption;
        
        $arrParams = $oOption->FormatParameterArray( $arrParams );
        $oWidgetBox = new ResponsiveColumnWidgets_WidgetBox( 
            $arrParams, 
            $oOption->SetMinimiumScreenMaxWidth(    // the max-columns array
                $oOption->FormatColumnArray( 
                    $arrParams['columns'],     
                    $arrParams['default_media_only_screen_max_width'] 
                )        
            ),
            $oOption->formatColSpanArray( $arrParams['colspans'] ),
            $oResponsiveColumnWidgets->arrClassSelectors
        );    
        
        $oID = new ResponsiveColumnWidgets_IDHandler;
        return $oStyle->GetStyles( 
            $arrParams['sidebar'], 
            $oID->GetCallID( $arrParams['sidebar'], $arrParams ), 
            $arrParams['custom_style'], 
            $oWidgetBox->GetScreenMaxWidths(), 
            false    // no scoped 
        );
                                              
    }        
    
    /*
     * Returns the CSS rules.
     * 
     * @remark      Used by the methods for output widget buffers
     * @since       1.1.2       
     * @access      public      must be public as used from an instantiated object.
    */
    public function GetStyles( $strSidebarID, $strCallID, $strCSSRules, $arrScreenMaxWidths, $bIsStyleScoped ) {   
        
        $strStyles = '';
        
        // Add the base CSS rules if not loaded yet. 
        $strStyles .= $this->GetBaseStylesIfNotAddedYet( $bIsStyleScoped );    // the scoped attribute will be embedded if true is passed.
        
        // Add the user's custom CSS rules. This is common by the sidebar ID.
        $strStyles .= $this->GetCustomStyleIfNotAddedYet( $strSidebarID, $strCSSRules, $strCallID, $bIsStyleScoped );

        $strStyles .= $this->GetWidgetBoxStyleIfNotAddedYet( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsStyleScoped );
        
        return $strStyles;
        
    }
    
    /**
     * 
     * @since       1.1.0
     * @since       1.1.1       moved from the core method
     * @since       1.1.2       moved from the core class
     */
    public function GetBaseStylesIfNotAddedYet( $bScoped=true ) {
        
        // If the timing to load the styles is set to the first box's rendering, 
        global $arrResponsiveColumnWidgets_Flags;
        
        if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
            && $this->oOption->arrOptions['general']['general_css_timimng_to_load'] == 1 
            && ! $arrResponsiveColumnWidgets_Flags['base_style']
            ) {
            
            $arrResponsiveColumnWidgets_Flags['base_style'] = true;
            return $this->GetBaseStyles( $bScoped );    // passing true assigns the scoped attribute in the tag.
            
        }        
        
        return '';
        
    }    
    
    protected function GetWidgetBoxStyle( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped=true ) {    // since 1.1.1
                
        $strScoped = $bIsScoped ? ' scoped' : '';
        $strStyleRules = '';        
        $_iPreveousMaxWidth = 0;

        sort( $arrScreenMaxWidths, SORT_NUMERIC );        // will be parsed from the smallest max width to the largest.
        foreach ( $arrScreenMaxWidths as $intScreenMaxWidth ) {
                        
            // If the screen max-width is 0, meaning no-limit,
            if ( $intScreenMaxWidth == 0 ) {
                continue;
            }
            
            // Set the prefixes.
            $strPrefixElementOf = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth . '_element_of_';
            $strPrefixColumn = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth;
            $strPrefixRow = $this->strClassSelectorRow . '_' . $intScreenMaxWidth;
                
            // Add the rules.
            $strStyleRules .= "@media only screen and (min-width: " . ( $_iPreveousMaxWidth + 1 ) . "px) and (max-width: {$intScreenMaxWidth}px) {". PHP_EOL;    
            foreach ( $this->strColPercentages as $intElement => $strWidthPercentage )     {
                
                $strWidthPercentage = "{$strWidthPercentage}%";
                $strClearLeft = $intElement == 1 ? " clear: left;" : "";
                $strMargin = $intElement == 1 ? " margin: 1% 0 1% 0;" : "";
                $strFloat = " display: block; float:left;";
                $strStyleRules .= " .{$strSidebarID} .{$strPrefixElementOf}{$intElement} { width:{$strWidthPercentage};{$strClearLeft}{$strMargin}{$strFloat} } " . PHP_EOL;
            
            }
            
            // 1.1.5+ Add the widths for col-spans.
            $strStyleRules .= $this->getWidthsForColSpans( $strSidebarID, $strPrefixColumn . '_' );
            
            // Override the other screen max-widths clear property.
            $strStyleRules .= $this->GetClearProperties( $strSidebarID, $arrScreenMaxWidths, $intScreenMaxWidth );
            
            $strStyleRules .= " .{$strSidebarID} .{$strPrefixColumn}_hide { display: none; } " . PHP_EOL;    // the first column element
            
            $strStyleRules .= "}" . PHP_EOL;
                            
            $_iPreveousMaxWidth = $intScreenMaxWidth;
            
        }    
        
        // Add the margin-left fixer.
        $_nLargestMaxWidth = max( $arrScreenMaxWidths ) + 1;
        $_nMinWidth = $_nLargestMaxWidth + 1;
        $strStyleRules .= "@media only screen and (min-width: {$_nMinWidth}px) {
            .{$strSidebarID} .{$this->strClassSelectorColumn}_1 {
                margin-left: 0px;
            }
        }" . PHP_EOL;
        
        
        $strStyleRules = "<style type='text/css' class='style_{$strCallID}'{$strScoped}>"    // The name attribute is invalid in a scoped tag. use the class attribute to identify this call.
            . ( $this->oOption->arrOptions['general']['general_css_minify'] ? $this->minifyCSS( $strStyleRules ) : $strStyleRules )
            . '</style>';
            
        return $strStyleRules;
        
    }
    
    /**
     * 
     * @since 1.1.2
     */
    protected function GetClearProperties( $strSidebarID, $arrScreenMaxWidths, $intThisScreenMaxWidth ) { 
        
        $strStyleRules = '';
        foreach ( $arrScreenMaxWidths as $intScreenMaxWidth ) {
            
            if ( $intScreenMaxWidth == 0 ) { continue; }
            
            $strPrefixColumn = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth;
            
            if ( $intScreenMaxWidth == $intThisScreenMaxWidth ) {

                // this needs to be inserted last to override other values.
                $strOverriderOthers = " .{$strSidebarID} .{$strPrefixColumn}_1 { 
                    clear: left; 
                    margin-left: 0px; 
                } 
                " . PHP_EOL;    // the first column element
                continue;
                
            }
            
            $strStyleRules .= " .{$strSidebarID} .{$strPrefixColumn}_1 { clear: none; } " . PHP_EOL;
                
        }    
        return $strStyleRules . $strOverriderOthers;
        
    }
    
    /**
     * 
     * @access      public      called from the instantiated core class so it must be public.
     * @since       1.1.2       
     * @param       string      $strSidebarID
     * @param       string      $strCallID          This must be a unique string that represents the call of a particular widget box's rendering request.
     * @param       array       $arrScreenMaxWidths
     * @param       boolean     $bIsScoped
     */
    public function GetWidgetBoxStyleIfNotAddedYet( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped=true ) {
        
        global $arrResponsiveColumnWidgets_Flags;
        
        // If already loaded, return an empty string.
        if ( in_array( $strCallID, $arrResponsiveColumnWidgets_Flags['arrWidgetBoxRenderingCallerIDs'] ) ) {
            return '';
        }
            
        // Store the widget box's sidebar ID into the global flag array.
        $arrResponsiveColumnWidgets_Flags['arrWidgetBoxRenderingCallerIDs'][] = $strCallID;            
        
        return $this->GetWidgetBoxStyle( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped );
        
    }        
    public function GetCustomStyleIfNotAddedYet( $strSidebarID, $strCustomCSSRules, $strIDSelector, $bIsScoped=true ) {    // since 1.1.1, called from the instantiated core class so it must be public.

        // If the custom style for the widget box has not been added yet,
        global $arrResponsiveColumnWidgets_Flags;    
        
        // If already loaded, return an empty string.
        if ( in_array( $strSidebarID, $arrResponsiveColumnWidgets_Flags['arrUserCustomStyles'] ) ) {
            return '';
        }
        
        // Store the widget box's sidebar ID into the global flag array.
        $arrResponsiveColumnWidgets_Flags['arrUserCustomStyles'][] = $strSidebarID;
                
        // For the max-width, paddings, and the background color.
        if ( isset( $this->oOption->arrOptions['boxes'][ $strSidebarID ] ) ) {    // the sidebar ID can be the one that the theme provides, not the plugin. In that case, the options are not associated.
            $strCustomCSSRules .= " .{$strSidebarID} { display: inline-block; width: 100%; }" . PHP_EOL;
            $strBGColor = $this->oOption->arrOptions['boxes'][ $strSidebarID ][ 'widget_box_container_background_color' ];
            if ( $strBGColor ) {
                $strCustomCSSRules .= " .{$strSidebarID} { background-color: {$strBGColor} }" . PHP_EOL;
            }
            $strMaxWidth = $this->oOption->arrOptions['boxes'][ $strSidebarID ][ 'widget_box_max_width' ];
            if ( $strMaxWidth ) {
                $strCustomCSSRules .= " .{$strSidebarID} .{$this->strClassSelectorBox} { max-width: {$strMaxWidth}px }" . PHP_EOL;
            }
            $arrContainerPaddings = $this->oOption->arrOptions['boxes'][ $strSidebarID ][ 'widget_box_container_paddings' ];
            if ( array_filter( $arrContainerPaddings ) ) {
                $strPadding = $this->getPaddingPropertyFromArray( $arrContainerPaddings );
                $strCustomCSSRules .= " .{$strSidebarID} { padding: {$strPadding} }" . PHP_EOL;
            }
            $strColumnAlignment = $this->oOption->arrOptions['boxes'][ $strSidebarID ][ 'widget_box_column_text_alignment' ];            
            if ( $strColumnAlignment != 'left' ) {
                $strCustomCSSRules .= " .{$strSidebarID} .{$this->strClassSelectorColumn} { text-align: {$strColumnAlignment}; } ";
            }
            
        }
        
        $strCustomCSSRules = trim( $strCustomCSSRules );
        if ( empty( $strCustomCSSRules ) ) { return ''; }

        // Okay, return the custom CSS rules.
        $strIDAttribute = 'style_custom_' . $strIDSelector;
        $strScoped = $bIsScoped ? ' scoped' : '';
        return '<style type="text/css" id="' . $strIDAttribute . '"' . $strScoped . '>'
            . ( $this->oOption->arrOptions['general']['general_css_minify'] ? $this->minifyCSS( $strCustomCSSRules ) : $strCustomCSSRules )
            . '</style>' . PHP_EOL;        
        
    }
    
    /**
     * 
     * @since 1.1.7
     */
    protected function getPaddingPropertyFromArray( $arrPaddings ) {   
    
        $strPadding     = '';
        $arrPositions   = array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' );
        $arrPaddings    = $arrPaddings + $arrPositions;
        foreach( $arrPositions as $strPosition => $v ) {
            $strPadding .= $arrPaddings[ $strPosition ] ? $arrPaddings[ $strPosition ] . "px " : "0 ";
        }
        return trim( $strPadding );
        
    }
    
    // == Common methods used by multiple methods  ==
    
    /**
     *     
     * @remark      It is assumed that this method is called in the head tag ( by the methods/functions triggered with the hooks ).
     * @since       1.1.2.1
     * */
    protected function GetUserDefinedEnqueuedStyles() {
        
        // This general option stores parameters in each element.
        $strStyles = $this->GetStyleDefaultShortCode();    // the default style for an empty parameter.
        foreach( $this->oOption->arrOptions['general']['general_css_load_in_head'] as $strParams ) {    
        
            if ( trim( $strParams ) == '' ) { continue; }
            
            $strStyles .= self::getStyle( shortcode_parse_atts( $strParams ) );
            
        }
        
        // For the ones added by the ResponsiveColumnWidgets_EnqueueStyle() function.
        global $arrResponsiveColumnWidgets_Flags;    
        foreach( $arrResponsiveColumnWidgets_Flags['arrEnqueueStyleParams'] as $arrParams ) {
            
            if ( empty( $arrParams ) ) { continue; }

            $strStyles .=  self::getStyle( $arrParams );

        }        
        return $strStyles;
        
    }
    
    /**
     * 
     * @since       1.1.2.1
     */
    protected function GetStyleDefaultShortCode() {   
        return self::getStyle( array() );
    }
    
    /**
     * 
     * @since 1.1.5
     */
    protected function getWidthForColSpan( $intMaxCol, $intColSpan ) { 

        // If the both numbers are the same, it means it's as one element, one column.
        if ( $intMaxCol == $intColSpan ) { return 100; }
                
        return ( ( $this->strColPercentages[ $intMaxCol ] + 1.6 ) * $intColSpan ) - 1.6;
        
    }
    /**
     * 
     * @since       1.1.5
     */
    protected function getWidthsForColSpans( $strPrefix1='', $strPrefix2='' ) {   
        
        $strRule    = "";
        $strPrefix1 = $strPrefix1 ? ".{$strPrefix1} " : '';    
        for ( $intMaxCol = 2; $intMaxCol <= 12; $intMaxCol++ ) {
            
            for ( $intColSpan = 2; $intColSpan <= $intMaxCol; $intColSpan++ ) {
                $strWidth = $this->getWidthForColSpan( $intMaxCol, $intColSpan );
                $strRule .= " {$strPrefix1}.{$strPrefix2}element_{$intColSpan}_of_{$intMaxCol} { width: {$strWidth}%; }" . PHP_EOL;
            }
            
        }
        return $strRule;

    }
    /**
     * 
     * @since       1.1.0
     * @since       1.1.2       Moved from the core class.
     */
    protected function GetBaseStyles( $bIsScoped=false ) {    
        
        $strScoped              = $bIsScoped ? "scoped" : "";
        $strHide                = 'none';
        $strWidthsForColSpans   = $this->getWidthsForColSpans();
        $strCSS                 = "
            .{$this->strClassSelectorBox} .widget {
                padding: 4px;
                width: auto;
                height: auto;
            }

            .{$this->strClassSelectorColumn}_hide {
                display: {$strHide} !important;
            }
            
            /* REMOVE MARGINS AS ALL GO FULL WIDTH AT 240 PIXELS */
            @media only screen and (max-width: 240px) {
                .{$this->strClassSelectorColumn} { 
                    margin: 1% 0 1% 0;
                }
            }
            
            /*  GROUPING  ============================================================================= */
            .{$this->strClassSelectorBox}:before,
            .{$this->strClassSelectorBox}:after {
                content: '';
                display: table;
            }
            .{$this->strClassSelectorBox}:after {
                clear:both;
            }
            .{$this->strClassSelectorBox} {
                float: none;
                width: 100%;        
                margin-left: auto;
                margin-right: auto;
                zoom:1; /* For IE 6/7 (trigger hasLayout) */
            }

            /* GRID COLUMN SETUP  */
            .{$this->strClassSelectorColumn} {
                display: block;
                float: left;
                margin: 1% 0 1% 1.6%;                
            }     
            
            /* all browsers except IE6 and lower */
            .{$this->strClassSelectorColumn}:first-child { 
                margin-left: 0; 
            }
            
            /* 
             * Remove the left margin of the first column. This should be done after all setting margins of columns for IE8. 
             * If declared earlier and there is a rule setting left margin of first columns, then it takes effect instead in IE8.
             */
            .{$this->strClassSelectorColumn}_1 {
                margin-left: 0px;
                clear: left;
            }            
            
            /*  GRID  ============================================================================= */
            .element_of_1 { width: 100%; }
            .element_of_2 { width: 49.2%; }
            .element_of_3 { width: 32.2%; }
            .element_of_4 { width: 23.8%; }
            .element_of_5 { width: 18.72%; }
            .element_of_6 { width: 15.33%; }
            .element_of_7 { width: 12.91%; }
            .element_of_8 { width: 11.1%; }
            .element_of_9 { width: 9.68%; }
            .element_of_10 { width: 8.56%; }
            .element_of_11 { width: 7.63%; }
            .element_of_12 { width: 6.86%; }
            
            /*  GRID for Col-spans ============================================================================= */
            {$strWidthsForColSpans}            
            /* Responsive Column Widget Box Widget */
            .{$this->strClassWidgetBoxWidget} .{$this->strClassSelectorBox} {
                margin-top: 0px;
            }
            .{$this->strClassSelectorColumn}.{$this->strClassWidgetBoxWidget} { 
                margin-top: 0px;
                margin-left: 0px;
            }
            
            /* Twenty Thirteen support */
            .site-main .{$this->strClassSelectorBox}.widget-area {
                width: 100%;
                margin-right: auto;
                float: none;
            }
            .widget_box_widget div.widget {
                background:none;
            }            
            
            /* Twenty Fourteen Support */
            .responsive_column_widgets_box.content-sidebar {
                padding: 0;
            }
        ";
            
        $strIDAttr  = $this->oOption->SanitizeAttribute( "{$this->oOption->oInfo->Name} {$this->oOption->oInfo->Version}" );
        $strCSS     = apply_filters( 'RCW_filter_base_styles', $strCSS );
        $strCSS     = $this->oOption->arrOptions['general']['general_css_minify'] ? $this->minifyCSS( $strCSS ) : $strCSS;
        return "<style type='text/css' id='{$strIDAttr}' {$strScoped}>" 
                . $strCSS
            . "</style>" . PHP_EOL;
        
    }

    /**
     * Minifies CSS rules.
     * 
     * @since       1.1.5.2
     * @see         http://www.catswhocode.com/blog/3-ways-to-compress-css-files-using-php
     * @remark      Thanks to Jean-Baptiste Jung. 
     */
    protected function minifyCSS( $strCSSRules ) {
        
        // Remove comments
        $strCSSRules = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $strCSSRules );
        
        // Remove tabs, spaces, newlines, etc. 
        return str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $strCSSRules );
        
    }
    
}