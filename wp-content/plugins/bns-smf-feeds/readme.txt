=== BNS SMF Feeds ===
Contributors: cais
Donate link: http://buynowshop.com
Tags: RSS, SMF, Multiple Widgets, Option Panel
Requires at least: 3.6
Tested up to: 4.5
Stable tag: 2.1

Plugin with multi-widget functionality that builds an SMF Forum RSS feed url by user option choices; and, displays a SMF forum feed.

== Description ==

Plugin with multi-widget functionality that builds an SMF Forum RSS feed url by user option choices; and, displays a SMF forum feed. The widget includes the additional option to include in the feed: specific boards and/or specific categories. There are also check boxes to include the feed item date and the item summary, too.

NB: If updating from a version before 1.1 please make sure to re-save your widget options (check your feed type) for each instance after upgrading. This will set the feed type correctly using the new feed drop-down selection option. Thanks! ~cais

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `bns-smf-feeds.php` to the `/wp-content/plugins/` directory
2. Activate through the 'Plugins' menu.
3. Place the BNS SMF Feeds widget appropriately in the Appearance | Widgets section of the dashboard.
4. Set options to personal preferences:

* Widget Title
* Specify the full URL to the SMF Forum (e.g.: http://www.simplemachines.org/community/)
* Choose feed type from drop-down menu (RSS, RSS2, Atom, RDF)
* The default is by recent Topics, or choose to display recent posts
* The default displays all items able to be seen by a "guest".
* Choose specific boards (or categories of boards) to include only.
* Choose the maximum quantity of items to display (varies by SMF user permissions)
* Choose to display the item date and/or item summary

-- or -

1. Go to 'Plugins' menu under your Dashboard
2. Click on the 'Add New' link
3. Search for BNS SMF Feeds
4. Install.
5. Activate through the 'Plugins' menu.
6. Place the BNS SMF Feeds widget appropriately in the Appearance | Widgets section of the dashboard.
7. Set options to personal preferences:

* Widget Title
* Specify the full URL to the SMF Forum (e.g.: http://www.simplemachines.org/community/)
* Choose feed type from drop-down menu (RSS, RSS2, Atom, RDF)
* The default is by recent Topics, or choose to display recent posts
* The default displays all items able to be seen by a "guest".
* Choose specific boards (or categories of boards) to include only.
* Choose the maximum quantity of items to display (varies by SMF user permissions)
* Choose to display the item date and/or item summary

Reading this article for further assistance: http://wpfirstaid.com/2009/12/plugin-installation/

== Frequently Asked Questions ==
= How can I get support for this plugin? =
Please note, support may be available on the WordPress Support forums; but, it may be faster to visit http://buynowshop.com/plugins/bns-smf-feeds/ and leave a comment with the issue you are experiencing.

= Can I use this in more than one widget area? =
Yes, this plugin has been made for multi-widget compatibility. Each instance of the widget will display, if wanted, differently than every other instance of the widget.

= How can I style the plugin output? =
The plugin uses a variation of the WordPress RSS Output that assigns several rss related classes, such as: rssSummary, rss-date, and rsswidget. A wrapping class of 'bns-smf-feeds' has also been added to the widget for more fine tuning.

= How can I get the RSS feed url? =
Once the widget is activated and placed into a widget ready area, there will be a standard RSS icon displayed beside the feed title. This icon will have the url anchored to it, simply click on it to subscribe to the feed directly.

= How do I use the shortcode `bns_smf_feeds`; and, does it offer the same options as the widget? =
Yes, the shortcode does offer the same options as the widget. Here is a list of these options with their defaults:

* title = 'SMF Forum Feed'
* smf_forum_url = 'http://www.simplemachines.org/community/' (this is an example only not the actual default)
* smf_feed_type = 'rss2'
* smf_sub_action = false (false displays news or recent topics; true displays recent posts)
* smf_boards = '' (defaults to all)
* smf_categories = '' (defaults all)
* limit_count = '10'
* show_date = false
* show_summary = false
* blank_window = false
* feed_refresh = '43200' (value in seconds = 12 hours)

For more information you can read the following codex entries:

* http://codex.wordpress.org/Shortcode (basic usage)
* http://codex.wordpress.org/Shortcode_API (for the more technically inclined)

== Screenshots ==
1. The options panel.

== Other Notes ==
* Copyright 2009-2015  Edward Caissie  (email : edward.caissie@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2,
  as published by the Free Software Foundation.

  You may NOT assume that you can use any other version of the GPL.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

  The license for this software can also likely be found here:
  http://www.gnu.org/licenses/gpl-2.0.html

== Upgrade Notice ==
Please stay current with your WordPress installation, your active theme, and your plugins.

== Changelog ==
= 2.1 =
* Released August 2015
* Updated to use PHP5 constructor objects

= 2.0 =
* Released December 2014
* Added "In Plugin Update Message" function to display relevant `changelog` entries
* Added class `bns-smf-feeds-content` wrapper for widget output
* Changed to a "Singleton" instantiation method
* Improved code formatting to better meet WordPress Code Standards
* Optimized `esc_attr( __() )` to `esc_attr__()`
* Updated inline documentation
* Updated "Tested up to" version to 4.1
* Updated "Requires at least" version to 3.6 (for shortcode filter option)

= 1.9.4 =
* Released May 2014
* Added $feed_fresh parameter to take it out of the "global" realm
* Code reformatting to better meet WordPress Coding Standards
* Fixed selection code for feed types
* Minor option panel layout adjustments
* Update required version to WordPress 3.6 to use shortcode filter parameter
* Update Copyright years

= 1.9.3 =
* Released November 2013
* Added clarifying text to Board and Category settings
* Code clean up

= 1.9.2 =
* Released October 2013
* Minor clean up of comments

= 1.9.1 =
* Released May 2013
* Version number compatibility update

= 1.9 =
* Released February 2013
* Added code block termination comments
* Added documentation header blocks
* Moved code into class structure

= 1.8 =
* confirmed compatible with WordPress 3.5
* minor documentation updates
* code formatting and clean-up
* remove load_textdomain as redundant (see plugin header)
* remove unused Author option
* added shortcode `bns_smf_feeds`
* update 'readme' with information about the shortcode

= 1.7.2 =
* confirmed compatible with WordPress 3.4

= 1.7.1 =
* updated documentation

= 1.7 =
* released November 2011
* confirmed compatible with WordPress 3.3
* added phpDoc Style documentation
* added i18n support

= 1.6 =
* released June 2011
* confirmed compatible with WordPress version 3.2-beta2-18085
* re-sized options panel
* updated screenshot to show new size (and layout) of options panel 

= 1.5.1 =
* released December 11, 2010
* Confirm compatible with WordPress 3.1 (beta)

= 1.5 =
* released: June 13, 2010
* code clean up to meet WP Standards
* corrected error with setting quantity of items displayed from the RSS feed

= 1.4 =
* compatible with WordPress version 3.0
* updated license declaration

= 1.3.1.2 =
* compatible with WordPress version 2.9.2
* updated license declaration

= 1.3.1.1 =
* clarified the plugin's release under a GPL license

= 1.3.1 =
* tied all links in the widget to use the "option to open links in new window"

= 1.3 =
* removed unnecessary (commented out) code
* added option to open links in new window as suggested
* added new screenshot of option panel

= 1.2 =
* added feed refresh frequency (in seconds) option
* added new screenshot of option panel

= 1.1.1 =
* compatibility check for 2.9.1 completed

= 1.1 =
* added drop-down menu to option panel to choose feed type, default set to RSS2
* updated the Option Panel screen shot

= 1.0.1 =
* minor corrections to description and screenshot

= 1.0 =
* Initial Release.