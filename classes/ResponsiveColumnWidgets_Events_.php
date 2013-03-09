<?php
class ResponsiveColumnWidgets_Events_ {
	
	function __construct( &$oOption ) {
		
		$this->oOption = $oOption;
		
		add_action( 'RCWP_action_setup_transients', array( $this, 'SetUpTransients' ) );
		
	}
	function SetUpTransients() {
		
		// Prepare Classes
		// $oRC = new ResponsiveColumnWidgets_RegisterClasses( RESPONSIVECOLUMNWIDGETSDIR . '/classes/' );
		// $oRC->RegisterClasses();
		
		// Setup Transients
		$oUA = new ResponsiveColumnWidgets_UserAds();
		$oUA->SetupTransients();
				
	}
	
}


