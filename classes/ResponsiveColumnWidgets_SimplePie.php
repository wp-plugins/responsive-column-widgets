<?php
if ( ! class_exists( 'ResponsiveColumnWidgets_SimplePie_' ) ) 
	require_once( dirname( __FILE__ ) . '/ResponsiveColumnWidgets_SimplePie_.php' );		//<-- very importat. Without this line, the cache setting breaks.

class ResponsiveColumnWidgets_SimplePie extends ResponsiveColumnWidgets_SimplePie_ {}