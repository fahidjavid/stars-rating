=== Stars Rating ===
Contributors: fahidjavid
Tags: comments, rating, reviews, stars, shortcode
Requires at least: 5.0
Tested up to: 6.7.1
Stable tag: 4.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin to turn comments into reviews by adding rating feature.

== Description ==

A simple and easy to use plugin that turns post, pages and custom post types comments into reviews.

Its main features are as follows:

* Turns post, pages and custom post types comments into reviews.
* Allows you to choose post types on which you want to enable Stars Rating feature.
* An option to require rating selection to leave a review.
* Also, allows you to enable/disable stars rating feature for the posts and pages individually.
* An option to display stars rating in Google search results.
* Choose from two different rating stars styles according to your site look.
* Offers a shortcode <strong>[stars_rating_avg]</strong> to display average rating anywhere in the post/page/CPTs detail or listing pages.
* Hide average rating text <strong>[stars_rating_avg show_text="no"]</strong>.
* Hide empty average rating <strong>[stars_rating_avg show_empty_rating="no"]</strong>.
* See each review stars rating on the comments page (backend).
* Enhanced SEO with structured data for standout reviews in Google with "Google Review Schema" integration.
* Preempt negativity with the "Negative Rating Alert" feature, promoting issue resolution before reviews are posted.

== Installation ==

### Method 1: WordPress Admin Interface

1. Navigate to your WordPress dashboard and go to **Plugins > Add New**.
2. In the search bar, enter **Stars Rating** and press Enter.
3. Look for the plugin in the search results and click on the **Install Now** button.
4. Once installed, click on the **Activate** button to activate the plugin.

### Method 2: FTP Upload

1. Download the plugin ZIP file and extract it.
2. Connect to your server using an FTP client (e.g., FileZilla, Cyberduck or cPanel).
3. Upload the extracted plugin folder to the `/wp-content/plugins/` directory on your server.
4. Activate the plugin through the WordPress dashboard from **Plugins > Installed Plugins** page.

<strong>Note:</strong> After installing the plugin go to the <strong>Settings > Discussion</strong> page (at the very bottom) and enable desired post types for the Stars Rating.

== Screenshots ==

1. Enable/Disable 'Stars Rating' for the posts, pages and custom post types comments globally and other settings.
2. Enable/Disable 'Stars Rating' for the posts, pages and custom post types comments individually.
3. Comments with their ratings and an average rating above comments.
4. Rating option in comment form.
5. Shortcode <strong>[stars_rating_avg]</strong> to display average rating anywhere in the post/page/CPTs
6. Stars Rating display on the comments page (backend)

== Changelog ==

= 4.0.6
* Fixed average rating display logic based on enabled/disabled custom post types (CPTs)
* Resolved issue with rating display on individual comments
* Improved performance and optimized query handling
* Refactored large portions of the codebase for better readability and maintainability
* Updated translation (.pot) file for improved localization support
* Tested compatibility with WordPress 6.8.1

= 4.0.5
* Updated language file
* Tested plugin with WordPress 6.7.1

= 4.0.4
* Added average rating text show/hide support to shortcode
* Added empty average rating show/hide support to shortcode
* Added independent settings page of the plugin
* Removed settings from "Dashboard > Settings > Discussions" page
* Updated language file
* Tested plugin with WordPress 6.6.1

= 4.0.3
* Fixed a PHP warning on comments template
* Displayed average rating only if comments are open
* Displayed empty stars for average rating if no rating found
* Improved code for average rating to work with posts/pages/CPTs list
* Updated language file
* Tested plugin with WordPress 6.5.3

= 4.0.2
* Fixed a PHP version specific fatal error on (backend) comments page
* Tested plugin with WordPress 6.4.3

= 4.0.1
* Added "Negative Rating Alert" system to foster positive feedback
* Improved "Google Review Schema" display upon reviews availability
* Updated language POT file
* Tested plugin with WordPress 6.4.2

= 4.0.0
* Improved plugin resources management
* Improved comments enable/disable compatability
* Improved plugin settings area styles
* Improved whole plugin code WRT the advance practices
* Added comments stars rating column to the comments page (dashboard)
* Updated language file
* Tested plugin with WordPress 6.4.1

= 3.5.5
* Updated language file.
* Tested plugin with WordPress 6.2

= 3.5.4
* Added average rating fallback when there is no review
* Improved average rating display markup for better control
* Updated language file.
* Tested plugin with WordPress 6.0.3

= 3.5.3
* Tested plugin with WordPress 5.9.1

= 3.5.2
* Minor sanitization functions update.

= 3.5.1
* Improved plugin from security point of view.
* Tested plugin with WordPress 5.8.2

= 3.5.0
* Tested plugin with WordPress 5.7.1

= 3.4.0
* Added translation missing strings to the translation.

= 3.3.0
* Tested with WordPress 5.4.1

= 3.2.0
* Added custom reviews type support for Google search results.

= 3.1.0
* Added latest Gutenberg editor support.
* Allowed reviews reply without rating.

= 3.0.0 =
* Added an option to choose from two different rating stars style.
* Added an option to display stars rating in Google search results.

= 2.0.0 =
* Tested plugin with WordPress 5.1.1 and PHP 7.3.3
* Added an option to require rating selection

= 1.3.1 =
* Fixed styling issue

= 1.3.0 =
* Tested with WordPress 5.0
* Fixed the default rating value

= 1.2.0 =

* Added show/hide support for average rating above comments section
* Added "[stars_rating_avg]" shortcode support to display average rating in the post/page content area or loop
* Set the rating option in the comment form to 5 stars by default

= 1.1.0 =

* Tested plugin up to WP V4.9.8

= 1.0.0 =

* Initial Release