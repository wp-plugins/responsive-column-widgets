<?php
/**
 * Manages formatting widget box outputs.
 * 
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.2
 * @uses        ResponsiveColumnWidgets_HTMLElementReplacer
*/

class ResponsiveColumnWidgets_WidgetBox_ { 
    
    public $arrIsPluginWidgetBoxWidget = array();
    
    /*
     * This class must be instantiated per widget box as it stores the iterating positions in the properties.
    */
    
    function __construct( $arrParams, $arrMaxCols, $arrColSpans, $arrClassAttributes ) {
        
        $this->arrParams = $arrParams;
        $this->arrPositions = $this->formatPositionsArray( $arrMaxCols, $arrColSpans, $arrClassAttributes );

    }
    protected function formatPositionsArray( &$arrMaxColsByPixel, &$arrColSpansByPixel, &$arrClassAttributes ) {    // since 1.1.1, moved from the core class in 1.1.2
    
        $arrPositions = array();    // returning array
        foreach ( $arrMaxColsByPixel as $intScreenMaxWidth => $arrMaxCols ) {
            $arrPositions[ $intScreenMaxWidth ] =  array(
                'intCellIndex' => 1,    // since 1.1.5 - the index of the cell
                'intWidgetIndex' => 1,    // since 1.1.5 - the index of the widget
                'arrMaxCols' => $arrMaxCols,
                'intCurrentMaxCol' => $this->GetLowestKeyElement( $arrMaxCols ),
                'intColPosInRow' => 1,    // one-base
                'intRowPos' => 1,        // one-base
                'arrColSpans' => isset( $arrColSpansByPixel[ $intScreenMaxWidth ] ) ? $arrColSpansByPixel[ $intScreenMaxWidth ] : array(),    // since 1.1.5
                'intCurrentColSpan' => isset( $arrColSpansByPixel[ $intScreenMaxWidth ][ 1 ] ) ?  $arrColSpansByPixel[ $intScreenMaxWidth ][ 1 ] : 1,    // since 1.1.5
                'intScreenMaxWidth' => $intScreenMaxWidth,    // this is refereed from the methods that need to know the screen max-width of the passed position array.
                'strClassSelectorBox' => $intScreenMaxWidth == 0 ? $arrClassAttributes['box'] : $arrClassAttributes['box'] . '_' . $intScreenMaxWidth,
                'strClassSelectorColumn' => $intScreenMaxWidth == 0 ? $arrClassAttributes['column'] : $arrClassAttributes['column'] . '_' . $intScreenMaxWidth,
                'strClassSelectorRow' => $intScreenMaxWidth == 0 ? $arrClassAttributes['row'] : $arrClassAttributes['row'] . '_' . $intScreenMaxWidth,
            );
        }
        return $arrPositions;
        
    }
    
    /*
     * Used to generate tag class selector names based on the given widget position.
    */
    public function setColSpans( $intWidgetIndex ) {    // since 1.1.5
        
        // This should be performed prior to GetClassAttribute().
        foreach ( $this->arrPositions as &$arrPosition )  {
            
            $arrPosition['intWidgetIndex'] = $intWidgetIndex; // the overall index of the widgets. One-base.    
            $arrPosition['intCurrentColSpan'] = $this->getCurrentColSpan( $arrPosition );
            
        }
    }
    public function advancePositions() {    // since 1.1.2, must be public as called from an instantiated object

        foreach ( $this->arrPositions as &$arrPosition ) {
            $intColsToMove = $arrPosition['intCurrentColSpan'];
            // $intColsToMove = $this->getCurrentColSpan( $arrPosition );
            for ( $i = 1; $i <= $intColsToMove; $i++ )
                $arrPosition = $this->advancePosition( $arrPosition );    
        }
        
    }
    protected function advancePosition( $arrPosition ) {    // since 1.1.2
        
        // Called from the above advancePositions() method.
    
        $arrPosition['intCellIndex']++;     // the overall index of the cell.
        $arrPosition['intColPosInRow']++;    // one-base.

        // If the current column position can be divided without any surplus by the maximum number of allowed columns, it means it's the last item in the row.
        if ( ( ( $arrPosition['intColPosInRow'] - 1 ) % $arrPosition['intCurrentMaxCol'] ) == 0 ) {
            
            $arrPosition['intRowPos']++;                // increment the row position
            $arrPosition['intColPosInRow'] = 1;        // reset the column position
            
        }    
        
        $arrPosition['intCurrentMaxCol'] = $this->getCurrentMaxColumns( $arrPosition );
                
        return $arrPosition;
        // $this->strClassSelectorColumnFirst = $arrPosition['intColPosInRow'] == 1 ? " {$this->strClassSelectorColumn}_first" : "";
        
    }
    protected function getCurrentMaxColumns( $arrPosition ) {
        
        // A position array must be formatted to use this method. For the necessary keys, see formatPositionsArray().
        
        $intColIndex = $arrPosition[ 'intRowPos' ] - 1;    // minus 1 because arrays are zero-base and the position we use is one-base.
        return ( isset( $arrPosition['arrMaxCols'][ $intColIndex ] ) )     // array is zero-base
            ? $arrPosition['arrMaxCols'][ $intColIndex ] : $arrPosition['intCurrentMaxCol'];
        
    }
    
    protected function getCurrentColSpan( $arrPosition ) {    // since 1.1.5
            
        $intWidgetIndex = $arrPosition['intWidgetIndex'];
            
        // If the index key is not set, return 1, which is default.
        if ( ! isset( $arrPosition['arrColSpans'][ $intWidgetIndex ] ) ) return 1;
        $intColSpan = $arrPosition['arrColSpans'][ $intWidgetIndex ];
        
        // If the current column position + the specified col-span does not exceed the set max-column, return the col-span.
        if ( $arrPosition['intColPosInRow'] + $intColSpan - 1 <= $arrPosition['intCurrentMaxCol'] )
            return $intColSpan;

        // Otherwise, reduce the col-span to fit in the row.
        return $arrPosition['intCurrentMaxCol'] - $arrPosition['intColPosInRow'] + 1;
    }
    
    public function GetClassAttribute() {    // since 1.1.2, called from an object instance so it must be public.

        $arrClassSelectors = array();
        foreach ( $this->arrPositions as &$arrPosition ) 
            $arrClassSelectors[] = $this->GetClassSelectors( $arrPosition );
        
        $arrClassSelectors = array_unique( $arrClassSelectors );
        return implode( ' ', $arrClassSelectors );
        
    }
    protected function GetClassSelectors( &$arrPosition ) {    // since 1.1.2
        
        // Called from the above GetClassAttribute method.
        
        // Set the col span selector sub string.
        $strColSpan = $arrPosition['intCurrentColSpan'] == 1 ? '' : $arrPosition['intCurrentColSpan'] . '_';
        
        $strElementOf = ( $arrPosition['intScreenMaxWidth'] == 0 ? "" : "{$arrPosition['strClassSelectorColumn']}_" )
            . "element_{$strColSpan}of_{$arrPosition['intCurrentMaxCol']} ";
        
        // responsive_column_widgets_column element_of_5 responsive_column_widgets_column_1 responsive_column_widgets_row_1
        return "{$arrPosition['strClassSelectorColumn']} "
            . $strElementOf
            . "{$arrPosition['strClassSelectorColumn']}_element_{$strColSpan}of_{$arrPosition['intCurrentMaxCol']} "
            . "{$arrPosition['strClassSelectorColumn']}_{$arrPosition['intColPosInRow']} "
            . "{$arrPosition['strClassSelectorRow']}_{$arrPosition['intRowPos']}"
            // If the number of rows exceeds the set max-rows, hide the element so that it will be invisible.
            . ( ( $this->arrParams['maxrows'] != 0 && $arrPosition['intRowPos'] > $this->arrParams['maxrows'] ) ? " {$arrPosition['strClassSelectorColumn']}_hide" : "" );        
        
    }
    
    public function getWidgetsBufferAsArray( $strSidebarID, $arrSidebarsWidgets, $arrShowOnlys, $arrOmits, $bRemoveIDAttributes ) {    // since 1.1.1, moved from the core class in 1.1.2        
    
        global $wp_registered_sidebars, $wp_registered_widgets;
        
        // Variables
        $arrWidgetBuffer = array();    // stores the returning widget buffer outputs, one key for one widget.
        $arrSidebarInfo = isset( $wp_registered_sidebars[ $strSidebarID ] ) ? $wp_registered_sidebars[ $strSidebarID ] : array();    
        /*
            $arrSidebarInfo contains the following keys ( the values are as an example ):
            [name] => Responsive Column Widgets
            [id] => responsive_column_widgets
            [description] => The default widget box of Responsive Column Widgets.
            [class] => 
            [before_widget] => <aside id="%1$s" class="%2$s"><div class="widget">
            [after_widget] => </div></aside>
            [before_title] => <h3 class="widget-title">
            [after_title] => </h3>            
        */

        $numWidgetOrder = 0;    // for the omit parameter        
        $bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;    // if showonly is set, render only the specified widget id.
        $this->arrIsPluginWidgetBoxWidget = array();
        
        // Objects
        $oReplace = new ResponsiveColumnWidgets_HTMLElementReplacer();
        
        foreach ( ( array ) $arrSidebarsWidgets[ $strSidebarID ] as $strWidgetID ) {
            
            if ( ! isset( $wp_registered_widgets[ $strWidgetID ] ) ) continue;        
            if ( in_array( ++$numWidgetOrder, $arrOmits ) ) continue;                    // if the omit ids match, skip
            if ( $bShowOnly && !in_array( $numWidgetOrder, $arrShowOnlys ) ) continue;    // if the show-only orders match, skip,
            
            $arrParams = array_merge(
                array(    
                    array_merge( 
                        $arrSidebarInfo, 
                        array(
                            'widget_id' => $strWidgetID, 
                            'widget_name' => $wp_registered_widgets[ $strWidgetID ]['name'] 
                        ) 
                    )
                ),
                ( array ) $wp_registered_widgets[ $strWidgetID ]['params']
            );

            // Substitute HTML id and class attributes into before_widget
            $strClassName = '';
            foreach ( ( array ) $wp_registered_widgets[ $strWidgetID ]['classname'] as $cn ) {
                
                if ( is_string( $cn ) )
                    $strClassName .= '_' . $cn;
                elseif ( is_object( $cn ) )
                    $strClassName .= '_' . get_class( $cn );
                    
            }
            $strClassName = ltrim( $strClassName, '_' );
            $arrParams[0]['before_widget'] = sprintf( $arrParams[0]['before_widget'], '', $strClassName );    // the second parameter is for the backward compatibility.
            // $arrParams[0]['before_widget'] = sprintf( $arrParams[0]['before_widget'], $strWidgetID, $strClassName );
                
            $arrParams = apply_filters( 'dynamic_sidebar_params', $arrParams );
            $vCallback = $wp_registered_widgets[ $strWidgetID ]['callback'];
            do_action( 'dynamic_sidebar', $wp_registered_widgets[ $strWidgetID ] );
            
            // since 1.1.3 - stores an array to check if the widget is the plugin widget-box widget that is added in v1.1.3.
            // This will store true/false ( boolean ) in the array with the index that is same as the array storing the buffer.
            // This flag array will be passed to a filter so that it can be captured from other places.
            $this->arrIsPluginWidgetBoxWidget[] = ( isset( $arrParams[0]['widget_id'] ) && preg_match( '/^responsive_column_widget_box-\d+/', $arrParams[0]['widget_id'] ) );
                
            ob_start();
            if ( is_callable( $vCallback ) ) {        
            
                call_user_func_array( $vCallback, $arrParams );        // will echo the widget.
                $arrWidgetBuffer[] = $bRemoveIDAttributes ? $oReplace->RemoveIDAttributes( ob_get_contents() ) : ob_get_contents();    // deletes the ID attributes here.
                
            }
            ob_end_clean();
            
        } // end of foreach()
        
        return $arrWidgetBuffer;
        
    }    
    
    // public function getFilteredBufferArray( $arrParams, $arrShowOnlys, $arrOmits, $fRemoveIDAttributes ) {    // since 1.1.8
        
        // $arrArray = apply_filters( $arrParams['filter'], $arrParams['array'], $arrParams );
        
        // $oReplace = new ResponsiveColumnWidgets_HTMLElementReplacer();
        
        // $numWidgetOrder = 0;
        // $bShowOnly = ( count( $arrShowOnlys ) > 0 ) ? True : False;    // if showonly is set, render only the specified widget id.
        // $arrWidgetBuffer = array();
        // foreach( $arrArray as $vElem ) {
            
            // if ( in_array( ++$numWidgetOrder, $arrOmits ) ) continue;                    // if the omit ids match, skip
            // if ( $bShowOnly && !in_array( $numWidgetOrder, $arrShowOnlys ) ) continue;    // if the show-only orders match, skip,
    
            // $arrWidgetBuffer[] = $fRemoveIDAttributes ? $oReplace->RemoveIDAttributes( $vElem ) : $vElem;
        
        // }
        // $this->arrIsPluginWidgetBoxWidget = array();
        // return $arrWidgetBuffer;
        
    // }
    
    public function GetScreenMaxWidths() {    // since 1.1.2
        
        // Returns a numerically index array consisting of the values that are keys of the position array.
        return array_keys( $this->arrPositions );
        
    }    
    
    public function GetWidgetBoxWidgetFlagArray(){    // since 1.1.3
        
        // Returns the flag array indicates wether the widget is the plugin's widget-box widget 
        // or not, with the same index(key) to the widget output array.
        return $this->arrIsPluginWidgetBoxWidget;
        
    }

    /*
     *  Currently Not Used
    */
    protected function GenerateAvailableID( $arrExistingIDs=array(), $strID='' ) {    // since 1.1.2
    
        // A utility function to generate a unique name.
        // $arrExistingIDs should be numerically indexed one-dimensional array.
        $strID = empty( $strID ) ? uniqid() : $strID;
        
        if ( ! in_array( $strID, $arrExistingIDs ) )
            return $strID;
        
        // Get the last digits
        preg_match( '/^(.+\D)(\d+)$/', $strID, $arrMatches );    
        if ( ! isset( $arrMatches[2] ) ) 
            $strID .= '_2';
        else
            $strID = $arrMatches[1] . ( $arrMatches[2] + 1 );

        // Do recursively
        return $this->GenerateAvailableID( $arrExistingIDs, $strID );
        
    }
    protected function GenerateUniqueID( $strID='' ) {    // since 1.1.2
        
        global $arrResponsiveColumnWidgets_Flags;
    
        $strID = $this->GenerateAvailableID( $arrResponsiveColumnWidgets_Flags['arrWidgetIDAttributes'], $strID );
    
        $arrResponsiveColumnWidgets_Flags['arrWidgetIDAttributes'][] = $strID;

        return $strID;
            
    }
    
    /*
     * Utilitles
    */
    function GetLowestKeyElement( $arr ) {
        
        if ( ! is_array( $arr ) ) return;
        return $arr[ min( array_keys( $arr ) ) ];
        
    }
    
    /*
     * Methods for Debug
     * */
    function DumpArray( $arr ) {
        
        return '<pre>' . esc_html( print_r( $arr, true ) ) . '</pre>';
        
    }        
}