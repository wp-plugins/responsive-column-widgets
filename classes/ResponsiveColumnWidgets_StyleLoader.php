<?php
/**
	Loads a widget box style in the head tag. 
	This class is not designed to be extensible as it is also used earlier than the auto-lader, which requres direct inclusion.
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @authorurl	http://michaeluno.jp
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.2.1
 
*/

class ResponsiveColumnWidgets_StyleLoader {
	
	// This class must be instantiated per a combination of request of rendering widget box and the parameters
	// as it stores those info into the object properties.	

	function __construct( $arrParams, $arrHooks=array( 'wp_head', 'login_head', 'admin_head' ) ) {
		
		// Properties
		$this->arrParams = $arrParams;
		
		// Hooks
		foreach( $arrHooks as $strHook )
			add_action( $strHook, array( $this, 'AddStyleInHead' ) );	
			
	}
		
	public function AddStyleInHead() {	// since 1.1.2.1, public, used by hooks.
		
		// Loaded with the head hook. At this point, all the necessary classes should be loaded 
		// including the core class and the option class.
		
		global $oResponsiveColumnWidgets;
		if ( ! isset( $oResponsiveColumnWidgets ) ) return;
			
		echo $this->GetStyle( $oResponsiveColumnWidgets, $this->arrParams ); 
		
	}
	protected function GetStyle( &$oCore, $arrParams=array() ) {	// since 1.1.2.1
		
		$oStyle = $oCore->oStyle;
		$oOption = $oCore->oOption;
		
		$arrParams = $oOption->FormatParameterArray( $arrParams );
	
		$oWidgetBox = new ResponsiveColumnWidgets_WidgetBox( 
			$arrParams, 
			$oOption->SetMinimiumScreenMaxWidth(	// the max-columns array
				$oOption->FormatColumnArray( 
					$arrParams['columns'], 	
					$arrParams['default_media_only_screen_max_width'] 
				)		
			),
			$oCore->arrClassSelectors
		);	
		
		$oID = new ResponsiveColumnWidgets_IDHandler;

		return $oStyle->GetStyles( 
			$arrParams['sidebar'], 
			$oID->GetCallID( $arrParams['sidebar'], $arrParams ), 
			$arrParams['custom_style'], 
			$oWidgetBox->GetScreenMaxWidths(), 
			false	// no scoped 
		);
					
		
	}	
		
}
