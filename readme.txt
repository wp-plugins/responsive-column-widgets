=== Responsive Columns Widgets ===
Contributors: Michael Uno, miunosoft
Donate link: http://michaeluno.jp/en/donate
Tags: miunosoft, widgets, sidebar, columns, responsive
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Creates a custom responsive column widget box.

== Description ==

Creates a custom responsive column widget box. When you want to display widgets horizontally, I know it’s such a headache to edit the theme and repeat the numerous times of trial and error. If you feel the same way, this would save the time for you.

<h4>Features</h4>
* Displays widgets in clolums - the main feature of this plugin.
* Responsive Design - when the browser width is less than 600 px, it adjusts the layout.
* Supports up to 12 columns - for example, if you have 24 registered widgets, you can displays 12 items in two rows.
* PHP code and Shortcode - use them to display the widgtes.
* The default sidebars integration - The sidebars defined by your them also can be displayed in columns.
  
== Installation ==

= Install = 
1. Upload **`amazonautolinks.php`** and other files compressed in the zip folder to the **`/wp-content/plugins/`** directory.
1. Upload **`responsive-column-widgets.php`** and other files compressed in the zip folder to the **`/wp-content/plugins/`** directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

= How to Use = 
1. Go to Appearance > Widgets. You'll see a new custom sidebar box named **Responsive Custom Widgets**.
1. Add widgtes to it.
1. Add the following code: 
* **in a theme** - PHP code. e.g. `<?php if ( function_exists( 'ResponsiveColumnWidgets' ) ) ResponsiveColumnWidgets(array('columns' => 5 )); ?>` where 5 indicates the number of columns.
* **in a page/post** - Shortcode e.g. `[responsive_column_widgets columns="3"]`  where 3 indicates the number of columns.

== Frequently asked questions ==

= How do I customize the style? =
You can add your rules to the class named `.responsive_column_widget_area`. It's defined in **responsive_column_widgets.css** but simply you can define it in your **style.css** as well.

== Screenshots ==

1. **Three Colums Example**
2. **Four Colums Example**
3. **Five Colums Example**
4. **Responsiveness**

== Changelog ==

= 1.0.0 =
* Initial Release
