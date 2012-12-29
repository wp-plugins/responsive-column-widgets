=== Responsive Columns Widgets ===
Contributors: Michael Uno, miunosoft
Donate link: http://michaeluno.jp/en/donate
Tags: miunosoft, widget, widgets, sidebar, columns, responsive, post, posts, page, pages, plugin
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Creates a custom responsive column widget box.

== Description ==

When you want to display widgets horizontally, I know it’s such a headache to edit the theme and repeat the numerous times of trial and error. If you feel the same way, this would save the time for you.

<h4>Features</h4>
* **Displays widgets in clolums** - the main feature of this plugin.
* **Set Number of Columns per Row** - flexibily set numbers of clolumns in each row.
* **Responsive Design** - when the browser width is less than 600 px, it automatically adjusts the layout.
* **Upto 12 columns** - for example, if you have 24 registered widgets, you can displays 12 items in two rows.
* **Work in Posts/Pages** - with the shortcode, you can embed the responsive widgets in post and pages.
* **PHP code and Shortcode** - use them to display the widgtes in thene template or in posts/pages.
* **Default Sidebars Integration** - The sidebars defined by your theme also can be displayed in columns.
* and [more](http://wordpress.org/extend/plugins/responsive-column-widgets/other_notes/).
  
== Installation ==

= Install = 

1. Upload **`responsive-column-widgets.php`** and other files compressed in the zip folder to the **`/wp-content/plugins/`** directory.,
2. Activate the plugin through the 'Plugins' menu in WordPress.

= How to Use = 
Go to the [Other Notes](http://wordpress.org/extend/plugins/responsive-column-widgets/other_notes/) section.

== Usage ==
= How to Use = 
1. Go to Appearance > Widgets. You'll see a new custom sidebar box named **Responsive Custom Widgets**.,
2. Add widgtes to it.,
3. Add the following code: 

**in a theme** - PHP code. e.g.
`<?php if ( function_exists( 'ResponsiveColumnWidgets' ) ) ResponsiveColumnWidgets(array('columns' => 5 )); ?>` 
where 5 indicates the number of columns.

**in a page/post** - Shortcode e.g.
`[responsive_column_widgets columns="3,2,5"]` 
where 3 indicates the number of columns in the first row, 2 indicates 2 colums in the second, and 5 to the third.

= Parameters = 
* **columns** - the number of columns to show. Default: 1. If you want to specify the number of columns in each row, put the numbers separated by commas. e.g. 3, 2, 4. would display 3 columns in first row and 2 columns in the second row and four columns in the third row and so on. The rest rows fill follow the last set number.
* **sidebar** - the ID of the sidebar to show. Default: responsive_column_widgets. For the twenty-twelve theme, sidebar-1, would show the default first sidebar contents. 
* **maxwidgets** - the allowed number of widgets to display. Set 0 for no limitation. Default: 0.
* **maxrows** - the allowed number of rows to display. Set 0 for no limitation. Default: 0.
* **omit** - the numbers of the widget order of the items to omit, separated by commas. e.g. **3, 5** would skip the third and fifth registered widgtes.
* **showonly** - the numbers of the widget order of the items to show, separated by commas. e.g. **2, 7** would only show the second and seventh registered widtges. Other items will be skipped.

== Frequently Asked Questions ==

= How do I customize the style? =
You can add your rules to the classes named `.responsive_column_widget_area .widget`. It's defined in *responsive-column-widgets/css/responsive_column_widgets.css* but simply you can define it in your theme's *style.css* as well.
e.g. 
`.responsive_column_widget_area .widget {
	padding: 4px;
	line-height: 1.5em;
}`

== Screenshots ==

1. ***Three-Two-Five Colums Combination Example***
2. ***Four Columns Example***
3. ***Responsiveness***

== Changelog ==

= 1.0.1 =
* Fixed a minor issue in the stylesheet.
* Added the parameter options including, maxwidgets, maxrows, omit, showonly.
* Added the ability to set the number of columns to show in each row.

= 1.0.0 =
* Initial Release
