<?php
/**
    Manages sidebar hierarchy.
    
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl    http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.1.3
 * 
    
*/

class ResponsiveColumnWidgets_SidebarHierarchy_ { 


    public function DumpSidebarHierarchyAsJSON() {    // since 1.1.3
        
        // Outputs the hierarchical relationship of the given sidebar and its children as JSON.
        $arrHierarchy = $this->GetDependencies();
        $vOut = json_encode( $arrHierarchy );
        die( is_array( $vOut ) ? print_r( $vOut, true ) : $vOut );
        
    }

    public function DumpSidebarHierarchy() {    // since 1.1.3
        
        $arrHierarchy = $this->GetDependencies();
        die( '<pre class="dump-array">' . esc_html( print_r( $arrHierarchy, true ) ) . '</pre>' );
        
    }
    
    public function GetDependencies( $bIncludeSelfID=true ) {    // since 1.1.3, public as called from an instantiated object.
        
        // Generate the base hierarchy array from the widget options.
        $oWO = new ResponsiveColumnWidgets_WidgetOptions;
        $arrHierarchyBase = $oWO->GetHierarchyBase();        
        
        $arrSidebarHierarchy = array();            
        foreach ( $GLOBALS['wp_registered_sidebars'] as $arrSidebar ) {
            
            // it can be null indicating an error.
            $arrDependencies = $this->GetFlatternChildWidgetBoxes( $arrSidebar['id'], $arrHierarchyBase );
            if ( is_null( $arrDependencies ) )    // If null, a dependency conflict occurred. So add the parsing sidebar ID to the parent sidebar.
                $arrDependencies = array( $arrSidebar['id'] );
            $arrDependencies = $bIncludeSelfID ? array_merge( array( $arrSidebar['id'] ), $arrDependencies ) : $arrDependencies;
            $arrDependencies = array_unique( $arrDependencies );
            $arrSidebarHierarchy[ $arrSidebar['id'] ] = $arrDependencies; 

        }
        unset( $oWO );    // for PHP below 5.3
        return $arrSidebarHierarchy;
        
    }
    public function getDependenciesOf( $strSidebarID, $arrHierarchyBase=null ) {    // since 1.1.7.2
        
        // Similar to the above GetDependencies() method but this one only checks the dependencies of the given sidebar. 
        // This is used by the core class before it renders the widget box output in case a dependency conflict is happening.
        
        // Generate the base hierarchy array from the widget options.
        if ( is_null( $arrHierarchyBase ) ) {
            $oWO = new ResponsiveColumnWidgets_WidgetOptions;
            $arrHierarchyBase = $oWO->GetHierarchyBase();    
            unset( $oWO );    // for PHP below 5.3
        }
        
        $arrDependencies = $this->GetFlatternChildWidgetBoxes( $strSidebarID, $arrHierarchyBase );
        if ( is_null( $arrDependencies ) )    // If null, a dependency conflict occurred. So add the parsing sidebar ID to the parent sidebar.
            $arrDependencies = array( $strSidebarID );
        
        return $arrDependencies;
        
    }
    protected function GetFlatternChildWidgetBoxes( $strSidebarID, &$arrHierarchyBase, $intDepth=0 ) {    // since 1.1.3
        
        // Returns an array consisting of values of all sidebar IDs that belongs to the given sidebar.
        // This is used to check if a selected sidebar contains a particular sidebar in the children in the hierarchical relationships.
        // Called from the above form() method.
        $arrChildSidebarIDs = array();
        $intDepth++;
            
        if ( $intDepth > 20 ) return null;    // this is a recursive function so avoid stack overflow by setting the depth limit.
        if ( ! isset( $arrHierarchyBase[ $strSidebarID ] ) ) return $arrChildSidebarIDs;        
        if ( empty( $arrHierarchyBase[ $strSidebarID ] ) ) return $arrChildSidebarIDs;
        
        foreach( $arrHierarchyBase[ $strSidebarID ] as $strChildID ) {
            
            $arrGrandChild = $this->GetFlatternChildWidgetBoxes( $strChildID, $arrHierarchyBase, $intDepth );
            if ( is_null( $arrGrandChild ) ) return null;    // indicates an error occurred.
            
            $arrChildSidebarIDs = array_merge( $arrGrandChild , $arrChildSidebarIDs, array( $strChildID ) );
            $arrChildSidebarIDs = array_unique( $arrChildSidebarIDs );
            
        }
        
        return $arrChildSidebarIDs;
        
    }
    
}