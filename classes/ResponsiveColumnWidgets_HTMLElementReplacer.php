<?php
/**
	
	Replaces HTML elements
	
 * @package     Responsive Column Widgets
 * @copyright   Copyright (c) 2013, Michael Uno
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.1.0

*/

class ResponsiveColumnWidgets_HTMLElementReplacer  {

	public $strImageQuery = 'responsive_column_widgets_image';
	public $strLinkQuery = 'responsive_column_widgets_link';		
	
	protected $strCharEncoding = '';	// stores the character set of the site.

	function __construct( $strCharEncoding=null ) {
	
		$this->strCharEncoding = $strCharEncoding ? $strCharEncoding : get_bloginfo( 'charset' ); 	
	
		$this->bImageCache = ( class_exists( 'DOMDocument' ) && function_exists( 'imagecreatefromstring' ) );
	
	}
				
	public function Perform( $strHTML ) {	
		
		// Performs replacements. This is Responsive Column Widgets specific method.
		// replaces a tag's href values <a href="http://something"> -> <a href="http://siteurl?responsive_column_widgets_link=encodedstring">
		// replaces img tag's src values <img src="http://something" /> -> <img src="http://siteurl?responsive_column_widgets_link=encodedstring">
		
		if ( ! $this->bImageCache ) return $strHTML;	// if the server does not support necessary libraries, do not perform replacements.
		
		$strHTML = $this->ReplaceAHrefs( $strHTML, array( $this, 'ReplaceAHrefsCallback' ) ); 
		$strHTML = $this->ReplaceSRCs( $strHTML, array( $this, 'ReplaceSRCsCallback' ) ); 	// works for iframe and img tags.
		// $strHTML = $this->ReplaceIframeSRCs( $strHTML, array( $this, 'ReplaceIframeSRCsCallback' ) ); 
		
		return $strHTML;
		
	}
	public function ReplaceIframeSRCsCallback( $strSRC ) {
// echo $strSRC . '<br />';			
		return site_url() . "?{$this->strLinkQuery}=" . base64_encode( $strSRC );
		
	}
	public function ReplaceAHrefsCallback( $strHref ) {
// echo $strHref . '<br />';			
		return site_url() . "?{$this->strLinkQuery}=" . base64_encode( $strHref );
		
	}
	public function ReplaceSRCsCallback( $strSRC ) {
		
		$strPath = parse_url( $strSRC, PHP_URL_PATH );
		$arrPathInfo = pathinfo( $strPath );

		// Iframe src values are also passed, - iframe url does not work with the redireced url so just return the given url.
		if ( ! isset(  $arrPathInfo['extension'] ) ) 
			return $strSRC;
			// return site_url() . "?{$this->strLinkQuery}=" . base64_encode( $strSRC );
					
		// Only jpeg, jpg, png, and gif are supported. Otherwise, return the passed string, which does not perform replacement.
		if ( ! in_array( $arrPathInfo['extension'], array( 'jpeg', 'jpg', 'png', 'gif' ) ) ) 
			return $strSRC;
			// return site_url() . "?{$this->strLinkQuery}=" . base64_encode( $strSRC );
		
		return site_url() . "?{$this->strImageQuery}=" . base64_encode( $strSRC );
		
	}
	protected function GetAttributeReplacementArrayWithRegex( $strHTML, $strAttribute, $vReplaceCallbackFunc ) {

		$arrReplacements = array( 
			'search' => array(), 
			'replace' => array(),
		);		
		
		$intCount = preg_match_all( '/' . $strAttribute . '=(["\'])(.*?)\1/', $strHTML, $arrMatches );	//'
		
		$bIsCallable = is_callable( $vReplaceCallbackFunc );

// echo '<pre>' . print_r( $arrMatches, true ) . '</pre>';		
// echo '<pre>' . esc_html( $strHTML, true ) . '</pre>';
		$i = 0;
		While ( $i < $intCount ) {
			
			$strAttr = $arrMatches[2][ $i ];
			$arrReplacements['search'][] = $strAttr;
			$arrReplacements['replace'][] = $bIsCallable ? call_user_func_array( $vReplaceCallbackFunc , array( &$strAttr ) ) : $strAttr;
			$i++;
			
		}
		
		return $arrReplacements;
		
	}
	protected function GetAttributeReplacementArrayWithDOM( $nodes, $strAttribute, $vReplaceCallbackFunc ) {
		
		$arrReplacements = array( 
			'search' => array(), 
			'replace' => array(),
		);
		
		foreach( $nodes as $node ){
			
			$strAttr = $node->getAttribute( $strAttribute );
			$strReplacement = is_callable( $vReplaceCallbackFunc ) ? call_user_func_array( $vReplaceCallbackFunc , array( &$strAttr ) ) : $strAttr;

// if ( $strAttribute == 'src' ) {
	// echo '<pre>' 
		// . $strReplacement . '<br />'
		// . $strAttr 
		// . '</pre>';
// }
			
			if ( $strAttr == $strReplacement ) continue;	// if the replacement is the same, no need to add it to the array.
			
			$arrReplacements['search'][] = $strAttr;
			$arrReplacements['replace'][] = $strReplacement;
				
		}			
				
		return $arrReplacements;
		
	}	
	protected function ReplaceSRCs( $strHTML, $vCallback ) {
		
		$arrReplacements = $this->GetAttributeReplacementArrayWithRegex( $strHTML, 'src', $vCallback );
		return str_replace( $arrReplacements['search'], $arrReplacements['replace'], $strHTML );

	}	
	protected function ReplaceAHrefs( $strHTML, $vCallback ) {

		$arrReplacements = $this->GetAttributeReplacementArrayWithRegex( $strHTML, 'href', $vCallback );
		return str_replace( $arrReplacements['search'], $arrReplacements['replace'], $strHTML );
		
	}
	protected function ReplaceAHrefsWithDOM( $strHTML, $vCallback ) {

		$bErrorFlag = libxml_use_internal_errors( true );
		$oDOM = $this->LoadDomFromHTML( $strHTML );
		$nodeAs = $oDOM->getElementsByTagName( 'a' );
		$arrReplacements = $this->GetAttributeReplacementArrayWithDOM( $nodeAs, 'href', $vCallback );
		$strHTML = str_replace( $arrReplacements['search'], $arrReplacements['replace'], $strHTML );
		libxml_use_internal_errors( $bErrorFlag );		
		return $strHTML;			
		
	}
	protected function ReplaceIframeSRCsWithDOM( $strHTML, $vCallback ) {
		
		$bErrorFlag = libxml_use_internal_errors( true );
		
		$oDOM = $this->LoadDomFromHTML( $strHTML );
		$nodeIframe = $oDOM->getElementsByTagName( 'iframe' );
		$arrReplacements = $this->GetAttributeReplacementArrayWithDOM( $nodeIframe, 'src', $vCallback );
		$strHTML = str_replace( $arrReplacements['search'], $arrReplacements['replace'], $strHTML );

		libxml_use_internal_errors( $bErrorFlag );
		
		return $strHTML;
		
	}
	protected function ReplaceIMGSRCsWithDOM( $strHTML, $vCallback ) {	
		
		$bErrorFlag = libxml_use_internal_errors( true );
		
		$oDOM = $this->LoadDomFromHTML( $strHTML );
		$nodeImgs = $oDOM->getElementsByTagName( 'img' );
		$arrReplacements = $this->GetAttributeReplacementArrayWithDOM( $nodeImgs, 'src', $vCallback );
		$strHTML = str_replace( $arrReplacements['search'], $arrReplacements['replace'], $strHTML );

		libxml_use_internal_errors( $bErrorFlag );
		
		return $strHTML;
		
	}
	public function LoadDomFromHTML( $strHTML ) {
		
		// $oDOM = new DOMDocument( '1.0', $this->strCharEncoding );
		$oDOM = new DOMDocument( '1.0' );
		// $oDOM->preserveWhiteSpace = false;
		// $oDOM->formatOutput = true;	
		// $strHTML = '<div>' . $strHTML . '</div>';		// this prevents later when using saveXML() from inserting the comment <!-- xml version .... -->
		$oDOM->loadhtml( $strHTML );
		return $oDOM;
		
	}	
	public function ReplaceLinks( $strHTML ) {	// since 1.1.0
		
		return $strHTML;
	}
	
}