=== Responsive Column Widgets ===
Contributors: Michael Uno, miunosoft
Donate link: http://en.michaeluno.jp/donate
Tags: widget, widgets, sidebar, columns, responsive, post, posts, page, pages, plugin, miunosoft
Requires at least: 3.2
Tested up to: 3.5.1
Stable tag: 1.0.9
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
* **Auto Insert Widget Box** - The widget box can be embedded automatically without the shortcode.
* and [more](http://wordpress.org/extend/plugins/responsive-column-widgets/other_notes/).
  
== Installation ==

= Install = 

1. Upload **`responsive-column-widgets.php`** and other files compressed in the zip folder to the **`/wp-content/plugins/`** directory.,
2. Activate the plugin through the 'Plugins' menu in WordPress.

= How to Use = 
Go to the [Other Notes](http://wordpress.org/extend/plugins/responsive-column-widgets/other_notes/) section.

== Basic Three Steps ==
1. Go to Appearance > Widgets. You'll see a new custom sidebar box named **Responsive Custom Widgets**.,
2. Add widgtes to it.,
3. Add the shortcode in a post `[responsive_column_widgets]`.


== More Flexible Usage ==

= Specify Different Number of Columns in Each Row = 
By default, the widgets are displayed in 3 columns. It can be changed by setting the columns parameter. 

`[responsive_column_widgets columns="4"]`  will display the widgets in 4 columns. 

Optionally, if you like to change the number of columns in each row, use sequential numbers separated by commas.

For instance, 

`[responsive_column_widgets columns="3,2,5"]` will show the widgets in 3 columns in the first row, 2 columns in the second, and 5 to the third. Change the numbers accordingly for your needs.

= Use PHP code for Themes = 
The widget box can be dispayed outside post/pages. Putting a PHP code into the theme is one way of doing it. Use the `ResponsiveColumnWidgets()` function. 
For instance, `<?php if ( function_exists( 'ResponsiveColumnWidgets' ) ) ResponsiveColumnWidgets( array( 'columns' => 5 ) ); ?>` will display the widgets in 5 columns.

= Parameters =  
There are other parameters besides *columns*.

* **columns** - the number of columns to show. Default: 3. If you want to specify the number of columns in each row, put the numbers separated by commas. e.g. 3, 2, 4. would display 3 columns in first row and 2 columns in the second row and four columns in the third row and so on. The rest rows fill follow the last set number.
* **sidebar** - the ID of the sidebar to show. Default: responsive_column_widgets. For the twenty-twelve theme, sidebar-1, would show the default first sidebar contents. 
* **maxwidgets** - the allowed number of widgets to display. Set 0 for no limitation. Default: 0.
* **maxrows** - the allowed number of rows to display. Set 0 for no limitation. Default: 0.
* **omit** - the numbers of the widget order of the items to omit, separated by commas. e.g. **3, 5** would skip the third and fifth registered widgtes.
* **showonly** - the numbers of the widget order of the items to show, separated by commas. e.g. **2, 7** would only show the second and seventh registered widtges. Other items will be skipped.
* **offsets** - the offsets of width percentage applied to each column. This is for the level of increase/decrease of the column number in the specified pixel width. The format is "*pixel*:*offset*, *pixel*:*offset*, ...". They consist of key-value pairs of pixel width and offset delimited by colon and separated by commas. For example, "600:4" will shift 4 column levels when the browser width is 600px and the higher the offset level gets, it takes less number of columns. e.g."800: 1, 600: 2, 480: 3, 320: 4, 240: 5".
* **label** - the label name of the widget box. Default: Responsive Column Widgets.

== Frequently Asked Questions ==

= Can't figure out how to show widgets in columns! ARGH! Are you there? =
Take a deep breath. Believe me, it's really simple. 90% of the time, you have not added any widget yet. Go to *Appearance > Widgets* and add widgets to the plugin's custom sidebar box. Then use the shortcode in a post. That's it. If you still cannot figure it out, take the screenshots of the *wp-admin/widgets.php* page and the page of the post you added the shortcode. And request a [support](http://wordpress.org/support/plugin/responsive-column-widgets) with the screenshots.

= How do I customize the style? =
You can add your rules to the classes named **.responsive_column_widget_box .widget**. It's defined in *responsive-column-widgets/css/responsive_column_widgets.css* but simply you can define it in your theme's *style.css* as well.

e.g. 
`.responsive_column_widget_box .widget {
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

= 1.0.9 - 04/07/2013 =
* Added: the ability to load the plugin CSS rules in login pages. To display the widget box in the login page, either use the PHP code or set the custom filter in the Auto-Insert section of the plugin admin page.
* Added: the ability to add custom filter/action hooks for the widget box auto-insertion.
* Added: the ability to restrict the widget box auto-insertion by category, post-id, post-type, and page-type.
* Added: the ability to disable the widget box auto-insertion by category, post-id, post-type ( post, page, custom post types ), and page-type ( archives, search, 404, etc. ). 
* Fixed: a bug in the Information page that the sub menu list items were displayed with bullet marks in WordPress 3.4.x.
* Renewed: (**Breaking Change**) the entire Auto-Insert section of the plugin admin page. Accordingly, the previous auto-insert options should be reconfigured.
* Removed: (**Breaking Change**) the *responsive_column_widget_area* class attribute. Those who have been using it, should simply change it to *responsive_column_widget_box* instead.

= 1.0.8.7 - 04/05/2013 =
* Tweaked: the form data sanitization functionality for the numeric sequence values with the comma delimiter.
* Changed: the class attribute name for each column from, *col*, to *responsitve_column_widgts_column*, in order to avoid being too generic which may result on conflicts with other plugins or themes.
* Fixed: a bug the *responsitve_column_widgts_column_1* class attribute was not displayed correctly introduced in 1.0.8.6.

= 1.0.8.6 - 04/04/2013 =
* Fixed: typos in plugin admin pages.
* Added: the class attributes, *responsitve_column_widgts_column_{n}* and *responsitve_column_widgts_row_{n}*, to each div tag containing a widget, where *_{n}* indicates the position of the column and row.

= 1.0.8.5 - 04/01/2013 =
* Fixed: a bug that the notification which appears when no added widget is added appeared in other plugin pages.

= 1.0.8.4 - 03/25/2013 =
* Fixed: a bug that the WordPress version 3.4.x or below got Fatal error: Cannot make non static method SimplePie::sort_items() static in class ResponsiveColumnWidgets_SimplePie_ in .../wp-content/plugins/responsive-column-widgets/classes/ResponsiveColumnWidgets_SimplePie_.php on line 21.

= 1.0.8.3 - 03/24/2013 = 
* Fixed: a typo in the plugin admin page footer.
* Updated: the information page.
* Changed: the requirement check to perform only upon the plugin activation from loading the plugin admin page.

= 1.0.8.2 - 03/23/2013 =
* Fixed: the warning that occurred in debug mode in a Not Found page, Undefined property: WP_Query::$post.

= 1.0.8.1 - 03/21/2013 =
* Fixed: a bug that the widget box was inserted below the comment form regardless of whether the option was checked or not.

= 1.0.8 - 03/20/2013 =
* Fixed: a bug that when multiple widget boxes with different sidebar IDs were present in one page, only the first widget box's custom style was loaded; the other custom styles' set to each box did not load.
* Fixed: a bug that when multiple widget boxes with different sidebar IDs were present in one page, only the first widget box was responsive; the responsive rules did not apply to the other widget boxes.
* Added: the ability to auto-insert the widget box into the comment form section.
* Fixed: the warning that occurred in some servers, "Parameter 1 to ResponsiveColumnWidgets_Startup() expected to be a reference, value given in .../wp-includes/plugin.php on line 406."

= 1.0.7.1 - 03/19/2013 = 
* Added: a warning message when there is no widget added to the widget box in the edit page.
* Added: the setting to attempt to override the memory limit for PHP set by the server.
* Added: the Japanese translation. 
* Added: the localization support with the text domain, responsive-column-widgets.

= 1.0.7 - 03/18/2013 =
* Added: the ability to disable the auto-insert option for both footer and posts/pages by post IDs and the checkbox for the front page.
* Added: the ability to auto-insert the widget box into post and pages above/below the contents.
* Fixed: a minor bug that merging with the default option values did not perform correctly when the subject option key had a null value.

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
