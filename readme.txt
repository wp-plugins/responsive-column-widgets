=== Responsive Column Widgets ===
Contributors: Michael Uno, miunosoft
Donate link: http://en.michaeluno.jp/donate
Tags: widget, widgets, sidebar, columns, responsive, post, posts, page, pages, plugin, miunosoft
Requires at least: 3.2
Tested up to: 3.5.1
Stable tag: 1.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Creates a custom responsive column widget box.

== Description ==

When you want to display widgets horizontally, I know it’s such a headache to edit the theme and repeat the numerous times of trial and error. If you feel the same way, this would save the time for you.

<h4>Features</h4>
* **Displays widgets in clolums** - the main feature of this plugin.
* **Set Number of Columns per Row** - flexibily set numbers of columns in each row.
* **Responsive Design** - when the browser width is less than 600 px, it automatically adjusts the layout. This is for tablet and mobile visitors.
* **Up to 12 columns** - for example, if you have 24 registered widgets, you can displays 12 items in two rows.
* **Works in Posts/Pages** - with the shortcode, you can embed the responsive widgets in post and pages.
* **PHP code and Shortcode** - use them to display the widgtes in theme template or in posts/pages.
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

**For a theme** - PHP code. e.g.
`<?php if ( function_exists( 'ResponsiveColumnWidgets' ) ) ResponsiveColumnWidgets(array('columns' => 5 )); ?>` 
where 5 indicates the number of columns. Change the number accordingly for your need.

**For a page/post** - Shortcode e.g.
`[responsive_column_widgets columns="3,2,5"]` 
where 3 indicates the number of columns in the first row, 2 indicates 2 colums in the second, and 5 to the third. Change the numbers accordingly for your need.

= Parameters = 
* **columns** - the number of columns to show. Default: 3. If you want to specify the number of columns in each row, put the numbers separated by commas. e.g. 3, 2, 4. would display 3 columns in first row and 2 columns in the second row and four columns in the third row and so on. The rest rows fill follow the last set number.
* **sidebar** - the ID of the sidebar to show. Default: responsive_column_widgets. For the twenty-twelve theme, sidebar-1, would show the default first sidebar contents. 
* **maxwidgets** - the allowed number of widgets to display. Set 0 for no limitation. Default: 0.
* **maxrows** - the allowed number of rows to display. Set 0 for no limitation. Default: 0.
* **omit** - the numbers of the widget order of the items to omit, separated by commas. e.g. **3, 5** would skip the third and fifth registered widgtes.
* **showonly** - the numbers of the widget order of the items to show, separated by commas. e.g. **2, 7** would only show the second and seventh registered widtges. Other items will be skipped.
* **offsets** - the offsets of width percentage applied to each column. This is for the level of increase/decrease of the column number in the specified pixel width. The format is "*pixel*:*offset*, *pixel*:*offset*, ...". They consist of key-value pairs of pixel width and offset delimited by colon and separated by commas. For example, "600:4" will shift 4 column levels when the browser width is 600px and the higher the offset level gets, it takes less number of columns. e.g."800: 1, 600: 2, 480: 3, 320: 4, 240: 5".
* **label** - the label name of the widget box. Default: Responsive Column Widgets.

== Frequently Asked Questions ==

= How do I customize the style? =
You can add your rules to the classes named **.responsive_column_widget_area .widget**. It's defined in *responsive-column-widgets/css/responsive_column_widgets.css* but simply you can define it in your theme's *style.css* as well.

e.g. 
`.responsive_column_widget_area .widget {
    padding: 4px;
    line-height: 1.5em;
    background-color: #EEE;
}`

= Is it possible to create multiple widget boxes? =
Yes, with [Pro](http://en.michaeluno.jp/responsive-column-widgets/responsive-column-widgets-pro/).

== Screenshots ==

1. ***Adding Widgets***
2. ***Three-Two-Five Colums Combination Example***
3. ***Four Column Example***
4. ***Responsiveness***


== Changelog ==

= 1.0.6.1 - 03/13/2013 =
* Raised: the required WordPress version to 3.2 from 3.0.
* Fixed: an issue that the user with a custom access level could not change the options.
* Fixed: minor typographical errors in the setting page.
* Added: the sanitization functionality for option values with delimiters including *Numbers of Columns*, *Omitting Widgets*, *Show-only Widgets*, *Width Percentage Offsets*, and *Additional Allowed HTML Tags*.
* Added: the requirement check which includes PHP version to 5.2.4 or higher.
* Tweaked: the code to load the pages faster in the admin pages.

= 1.0.6 - 03/11/2013 =
* Fixed: a bug that saving in the General Options page redirected to the Manage page in the plugin admin pages.
* Added: the ability to set custom style rules per widget box.

= 1.0.5 - 03/09/2013 =
* Added: the ability to automatically insert the widget box in the footer if the option is checked in the setting page.
* Fixed: a bug that the same ID attribute was applied to the aside tags.
* Changed: a filter name that the plugin internally uses to make the names consistent.

= 1.0.4.9 - 03/04/2013 =
* Changed: the Access Rights option not to appear for members other than administrators. 

= 1.0.4.8 - 03/04/2013 =
* Added: some base files for plugin extensions.
* Added: an action hook for plugin extensions.
* Fixed: a bug that the default widget box options could not be retrieved properly in the table of the Manage tab page when the option initialization was performed.

= 1.0.4.7 - 03/04/2013 =
* Fixed: a bug that when settings are saved in one of the pages and the other pages settings get erased introduced since supporting the alternatives of array_replace_recursive().

= 1.0.4.6 - 03/04/2013 =
* Fixed: a bug that debug output appeared after submitting the form in General Options page.

= 1.0.4.5 - 03/04/2013 =
* Fixed: typos and applied minor description changes to the parts not clear enough.

= 1.0.4.4 - 03/03/2013 = 
* Fixed: the parts using array_replace_recursive() which is incompatible with below PHP 5.3.

= 1.0.4.3 - 03/03/2013 =
* Fixed: an issue that the server below PHP 5.3 got Fatal error: Call to undefined function array_replace_recursive().

= 1.0.4.2 - 03/03/2013 =
* FIxed: the version number in the main file.

= 1.0.4.1 - 03/03/2013 =
* Fixed: a bug in the file name which contains case-mismatch with the include/require statement, which caused a fatal error during the activation.

= 1.0.4 - 03/03/2013 =
* Added: the ability to reset all saved option values.
* Added: the ability to set custom HTML tags to be posted in the setting page.
* Added: the Access Rights option in the setting pages.
* Changed: the default value of the offsets paramter to 600:12, which is 100% compatible with the versions prior to 1.0.3.
* Added: the ability to set the options without the parameters; the saved option values in the setting page will be used.
* Added: the ability to set a custom message when no widget is added.
* Added: the ability to set opening/closing HTML code in front of the widget output and title.
* Added: the ability to specify the widget box by label name.
* Added: the setting pages under the Appearance menu.

= 1.0.3 - 02/28/2013 =
* Notes: **THIS UPDATE INCLUDES POSSIBLE BREAKING CHANGES. PLEASE TEST IT FIRST ON A LOCAL SERVER BEFORE UPDATING.**
* **Changed: the default number of columns to 3 from 1.**
* Added: the ability to flexibly change the number of columns by pixel width with the offsets parameter.

= 1.0.2 - 02/23/2013 = 
* Added: an additional class attribute to the enclosing div tag named "widget-area," which helps to match the site's sidebar style.
* Added: an enclosing div tag with the class attribute named "widget," which helps to match the site's sidebar style.
* Cleaned: some code.
* Changed: the misleading class attribute name, responsive_column_widgets_newrow, to responsive_column_widgets_firstcol.

= 1.0.1 =
* Fixed a minor issue in the stylesheet.
* Added the parameter options including, maxwidgets, maxrows, omit, showonly.
* Added the ability to set the number of columns to show in each row.

= 1.0.0 =
* Initial Release
