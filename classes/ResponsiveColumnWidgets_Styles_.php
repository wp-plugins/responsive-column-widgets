<?php
/**
	Returns plugin specific CSS rules
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.2
 * 
	@filters
	RCW_filter_base_styles: applies to the base CSS rules of the plugin.
	
*/

class ResponsiveColumnWidgets_Styles_ {
	
	// Default Properties
	protected $strColPercentages = array(
		1 =>	100,
		2 =>	49.2,
		3 =>	32.2,
		4 =>	23.8,
		5 =>	18.72,
		6 =>	15.33,
		7 =>	12.91,
		8 =>	11.1,
		9 =>	9.68,
		10 =>	8.56,
		11 =>	7.63,
		12 =>	6.86,
	);		
	
	// Dynamic Properties
			
	function __construct( &$oOption, $arrClassSelectors ) {
		
		$this->oOption = $oOption;
		
		$this->strClassSelectorBox = $arrClassSelectors['box'];
		$this->strClassSelectorColumn = $arrClassSelectors['column'];
		$this->strClassSelectorRow = $arrClassSelectors['row'];
		$this->strClassWidgetBoxWidget = 'widget_box_widget';
	}

	/*
	 * Used by hooks that embed base styles such as wp_head, login_head, admin_head.
	*/
	public function AddStyleSheet() {	// used by hooks
	
		global $arrResponsiveColumnWidgets_Flags;
		$arrResponsiveColumnWidgets_Flags['base_style'] = true;
		
		echo $this->GetBaseStyles();
		echo $this->GetUserDefinedEnqueuedStyles();
		
	}


	/*
	 * Used by the methods for output widget buffers
	*/
	public function GetStyles( $strSidebarID, $strCallID, $strCSSRules, $arrScreenMaxWidths, $bIsStyleScoped ) {	// since 1.1.2, must be public as used from an instantiated object.
		
		/*
		 * Retrieve the CSS rules.
		*/
		$strStyles = '';
		
		// Add the base CSS rules if not loaded yet. 
		$strStyles .= $this->GetBaseStylesIfNotAddedYet( $bIsStyleScoped );	// the scoped attribute will be embedded if true is passed.
		
		// Add the user's custom CSS rules. This is common by the sidebar ID.
		$strStyles .= $this->GetCustomStyleIfNotAddedYet( $strSidebarID, $strCSSRules, $strCallID, $bIsStyleScoped );

		$strStyles .= $this->GetWidgetBoxStyleIfNotAddedYet( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsStyleScoped );
		
		return $strStyles;
		
	}
	public function GetBaseStylesIfNotAddedYet( $bScoped=true ) {	// Since 1.1.0, moved from the core method in 1.1.1, moved from the core class in 1.1.2
		
		// If the timing to load the styles is set to the first box's rendering, 
		global $arrResponsiveColumnWidgets_Flags;
		
		if ( isset( $this->oOption->arrOptions['general']['general_css_timimng_to_load'] ) 
			&& $this->oOption->arrOptions['general']['general_css_timimng_to_load'] == 1 
			&& ! $arrResponsiveColumnWidgets_Flags['base_style']
			) {
			
			$arrResponsiveColumnWidgets_Flags['base_style'] = true;
			return $this->GetBaseStyles( $bScoped );	// passing true assigns the scoped attribute in the tag.
			
		}		
		
		return '';
		
	}	
	
	protected function GetWidgetBoxStyle( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped=true ) {	// since 1.1.1
				
		$strScoped = $bIsScoped ? ' scoped' : '';
		$strStyleRules = "<style type='text/css' class='style_{$strCallID}'{$strScoped}>";	// The name attribute is invalid in a scoped tag. use the class attribute to identify this call.
		
		foreach ( $arrScreenMaxWidths as $intScreenMaxWidth ) {
									
			// If the screen max-width is 0, meaning no-limit, skip, because it's already defined in the base rules.
			// We need to set style rules for no-limit screen max widths as well later at some point when the width offset option is implemented.
			// For now and for the back-ward compatibility, just skip them and leave them untouched.
			if ( $intScreenMaxWidth == 0 ) continue;
			
			// Set the prefixes.
			$strPrefixElementOf = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth . '_element_of_';
			$strPrefixColumn = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth;
			$strPrefixRow = $this->strClassSelectorRow . '_' . $intScreenMaxWidth;
				
			// okay, add the rules.
			$strStyleRules .= "@media only screen and (max-width: {$intScreenMaxWidth}px) {" . PHP_EOL;
			
			foreach ( $this->strColPercentages as $intElement => $strWidthPercentage ) 	{
				
				$strWidthPercentage = "{$strWidthPercentage}%";
				$strClearLeft = $intElement == 1 ? " clear: left;" : "";
				$strMargin = $intElement == 1 ? " margin: 1% 0 1% 0;" : "";
				$strFloat = " display: block; float:left;";
				$strStyleRules .= " .{$strSidebarID} .{$strPrefixElementOf}{$intElement} { width:{$strWidthPercentage};{$strClearLeft}{$strMargin}{$strFloat} } " . PHP_EOL;
			
			}
			
			// Add the widths for col-spans. ( since 1.1.5 )
			$strStyleRules .= $this->getWidthsForColSpans( $strSidebarID, $strPrefixColumn . '_' );
			
			// Override the other screen max-widths clear property.
			$strStyleRules .= $this->GetClearProperties( $strSidebarID, $arrScreenMaxWidths, $intScreenMaxWidth );
			
			$strStyleRules .= " .{$strSidebarID} .{$strPrefixColumn}_hide { display: none; } " . PHP_EOL;	// the first column element
			
			$strStyleRules .= "}" . PHP_EOL;
				
		}	
		return $strStyleRules . '</style>';
		
	}
	protected function GetClearProperties( $strSidebarID, $arrScreenMaxWidths, $intThisScreenMaxWidth ) {	// since 1.1.2
		
		$strStyleRules = '';
		foreach ( $arrScreenMaxWidths as $intScreenMaxWidth ) {
			
			if ( $intScreenMaxWidth == 0 ) continue;
			
			$strPrefixColumn = $this->strClassSelectorColumn . '_' . $intScreenMaxWidth;
			
			if ( $intScreenMaxWidth == $intThisScreenMaxWidth ) {
				
				// this needs to be inserted last to override other values.
				$strOverriderOthers = " .{$strSidebarID} .{$strPrefixColumn}_1 { clear: left; margin-left: 0px; } " . PHP_EOL;	// the first column element
				continue;
				
			}
			
			$strStyleRules .= " .{$strSidebarID} .{$strPrefixColumn}_1 { clear: none; } " . PHP_EOL;
				
		}	
		
		return $strStyleRules . $strOverriderOthers;
		
	}
	
	public function GetWidgetBoxStyleIfNotAddedYet( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped=true ) {	// since 1.1.2, called from the instantiated core class so it must be public.

		// $strCallID must be a unique string that represents the call of a particular widget box's rendering request.
		
		global $arrResponsiveColumnWidgets_Flags;
		
		// If already loaded, return an empty string.
		if ( in_array( $strCallID, $arrResponsiveColumnWidgets_Flags['arrWidgetBoxRenderingCallerIDs'] ) )
			return '';
			
		// Store the widget box's sidebar ID into the global flag array.
		$arrResponsiveColumnWidgets_Flags['arrWidgetBoxRenderingCallerIDs'][] = $strCallID;			
		
		return $this->GetWidgetBoxStyle( $strSidebarID, $strCallID, $arrScreenMaxWidths, $bIsScoped );
		
	}		
	public function GetCustomStyleIfNotAddedYet( $strSidebarID, $strCustomCSSRules, $strIDSelector, $bIsScoped=true ) {	// since 1.1.1, called from the instantiated core class so it must be public.

		// If the custom style for the widget box has not been added yet,
		global $arrResponsiveColumnWidgets_Flags;	
		
		// If already loaded, return an empty string.
		if ( in_array( $strSidebarID, $arrResponsiveColumnWidgets_Flags['arrUserCustomStyles'] ) )
			return '';
		
		// Store the widget box's sidebar ID into the global flag array.
		$arrResponsiveColumnWidgets_Flags['arrUserCustomStyles'][] = $strSidebarID;
		
		$strCustomCSSRules = trim( $strCustomCSSRules );
		if ( empty( $strCustomCSSRules ) ) return '';
		
		// Okay, return the custom CSS rules.
		$strIDAttribute = 'style_custom_' . $strIDSelector;
		$strScoped = $bIsScoped ? ' scoped' : '';
		return '<style type="text/css" id="' . $strIDAttribute . '"' . $strScoped . '>' 
			. $strCustomCSSRules
			. '</style>' . PHP_EOL;		
		
	}
	
	/*
	 *	 Common methods used by multiple methods. 
	 * */
	protected function GetUserDefinedEnqueuedStyles() {	// since 1.1.2.1
		
		// It is assumed that this method is called in the head tag ( by the methods/functions triggered with the hooks ).
		
		// This general option stores parameters in each element.
		$strStyles = $this->GetStyleDefaultShortCode();	// the default style for an empty parameter.
		foreach( $this->oOption->arrOptions['general']['general_css_load_in_head'] as $strParams ) {	
			
			$oStyleLoader = new ResponsiveColumnWidgets_StyleLoader( 
				shortcode_parse_atts( $strParams ), 
				array() 		// pass empty arrays to disable hooks.
			);	
			$strStyles .= $oStyleLoader->AddStyleInHead();
			unset( $oStyleLoader );	// ensure it's released for PHP below 5.3.
			
		}
		
		// For the ones added by the ResponsiveColumnWidgets_EnqueueStyle() function.
		global $arrResponsiveColumnWidgets_Flags;	
		foreach( $arrResponsiveColumnWidgets_Flags['arrEnqueueStyleParams'] as $arrParams ) {
			
			$oStyleLoader = new ResponsiveColumnWidgets_StyleLoader( 
				$arrParams, 
				array() 		// pass empty arrays to disable hooks.
			);	
			$strStyles .= $oStyleLoader->AddStyleInHead();
			unset( $oStyleLoader );	// ensure it's released for PHP below 5.3.
			
		}
		
		return $strStyles;
		
	}
	protected function GetStyleDefaultShortCode() {	// since 1.1.2.1
	
		$oStyleLoader = new ResponsiveColumnWidgets_StyleLoader( array(), array() );	// pass empty arrays.
		return $oStyleLoader->AddStyleInHead();
		
	}
	protected function getWidthForColSpan( $intMaxCol, $intColSpan ) {	// since 1.1.5

		// If the both numbers are the same, it means it's as one element, one column.
		if ( $intMaxCol == $intColSpan ) return 100 - 1.6;	// 98.4%. 1.6% is the left margin.
		
		return ( ( $this->strColPercentages[ $intMaxCol ] + 1.6 ) * $intColSpan ) - 1.6;
		
	}
	protected function getWidthsForColSpans( $strPrefix1='', $strPrefix2='' ) {	// since 1.1.5
		
		$strRule = "";
		$strPrefix1 = $strPrefix1 ? ".{$strPrefix1} " : '';	
		for ( $intMaxCol = 2; $intMaxCol <= 12; $intMaxCol++ ) {
			
			for ( $intColSpan = 2; $intColSpan <= $intMaxCol; $intColSpan++ ) {
				$strWidth = $this->getWidthForColSpan( $intMaxCol, $intColSpan );
				$strRule .= " {$strPrefix1}.{$strPrefix2}element_{$intColSpan}_of_{$intMaxCol} { width: {$strWidth}%; }" . PHP_EOL;
			}
		}
		return $strRule;

	}
	protected function GetBaseStyles( $bIsScoped=false ) {	// since 1.1.0, moved from the core class in 1.1.2.
		
		$strScoped = $bIsScoped ? "scoped" : "";
		$strHide = 'none';
		$strWidthsForColSpans = $this->getWidthsForColSpans();
		$strCSS = "
			.{$this->strClassSelectorBox} .widget {
				padding: 4px;
				width: auto;
				height: auto;
			}
			.{$this->strClassSelectorColumn}_1 {
				margin-left: 0px !important;
				clear: left;
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
				content:'';
				display:table;
			}
			.{$this->strClassSelectorBox}:after {
				clear:both;
			}
			.{$this->strClassSelectorBox} {
				float: none;
				width: 100%;		
				zoom:1; /* For IE 6/7 (trigger hasLayout) */
			}

			.{$this->strClassSelectorColumn}:first-child { margin-left: 0; } /* all browsers except IE6 and lower */

			/* GRID COLUMN SETUP  */
			.{$this->strClassSelectorColumn} {
				display: block;
				float:left;
				margin: 1% 0 1% 1.6%;
			}					
			
			/*  GRID  ============================================================================= */
			.element_of_1 { width: 100%; }
			.element_of_2 {	width: 49.2%; }
			.element_of_3 {	width: 32.2%; }
			.element_of_4 {	width: 23.8%; }
			.element_of_5 {	width: 18.72%; }
			.element_of_6 {	width: 15.33%; }
			.element_of_7 {	width: 12.91%; }
			.element_of_8 { width: 11.1%; }
			.element_of_9 {	width: 9.68%; }
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
			}
		";
		
		/*
			ROWS  ============================================================================= 
			.{$this->strClassSelectorRow} {
				clear: both;
				padding: 0px;
				margin: 0px;
			}
	

		 */
			/*  GO FULL WIDTH AT LESS THAN 600 PIXELS 
			@media only screen and (max-width: 600px) {
				.element_of_2,
				.element_of_3,
				.element_of_4,
				.element_of_5,
				.element_of_6,
				.element_of_7,
				.element_of_8,
				.element_of_9,
				.element_of_10,
				.element_of_11,
				.element_of_12	
				{	width: 100%;  }
			}
			*/			
		$strIDAttr = $this->oOption->SanitizeAttribute( "{$this->oOption->oInfo->Name} {$this->oOption->oInfo->Version}" );
		return "<style type='text/css' id='{$strIDAttr}' {$strScoped}>" 
			. apply_filters( 'RCW_filter_base_styles', $strCSS )
			. "</style>" . PHP_EOL;
		
	}		
}