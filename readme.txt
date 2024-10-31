=== Private Suite ===
Contributors: sillybean
Tags: privacy, private, password, categories, users, roles
Donate Link: http://sillybean.net/code/wordpress/private-suite/
Requires at least: 3.3
Tested up to: 4.5
Stable tag: 2.1

Allows you to choose who can read private content and offers better control of privacy features.

== Description ==

* Optionally adds private pages to `wp_list_pages()`, `wp_page_menu()`, and the Pages widget
* Provides a separate `wp_list_private_pages()` tag (a clone of `wp_list_pages()` that accepts all the same arguments)
* Specifies private categories, in which all posts will automatically be set to private
* Lets you change the "Private:" prefix on private post/page titles
* Lets you change the "Protected:" prefix on password-protected post/page titles
* Lets you choose which user roles can read private pages and posts

Now on <a href="https://github.com/sillybean/private-suite">GitHub</a>.

= Translations =

* French (fr_FR) by Regis Lemaire
* Bulgarian (bg_BG) by <a href="http://www.siteground.com/">SiteGround</a>
* Turkish (tr_TR) by <a href="http://bijoubijouterie.com">&Ouml;mer Faruk Karabulut</a>

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/` 
1. Activate the plugin through the 'Plugins' menu in WordPress

== Notes ==

== Reading Settings ==

You must have at least one public page. Otherwise, as of 3.1, the front page settings (where you can choose a static page as your home page) will not appear on the settings page at all. As long as you have one public page, all the private pages will appear as options in the dropdown menu.

== Page Lists and Widgets ==

Adding private pages to `wp_list_pages()`, `wp_page_menu()`, and the Pages widget does not always work as it should. The private pages will be out of order, and they might appear as children of the wrong parent page. If this occurs, try using the `wp_list_private_pages()` template tag instead. It's a clone of `wp_list_pages()` and should accept all the same arguments. This plugin includes an extra Pages widget that includes private pages.

== Private Categories ==

When you mark a category as private, all the posts in that category will have their visibility set to private when they are published, even if you don't change the visibility setting on the edit screen. Only published posts are affected; your draft, pending, and scheduled posts will work as usual, except that scheduled posts will be set to private when they become active.

== Acknowledgments ==

Huge thanks to <a href="http://profiles.wordpress.org/mtekk">mtekk</a> for providing the <a href="http://core.trac.wordpress.org/ticket/8592">patch</a> that makes the page list features possible. The alternative method of listing pages was adapted from <a href="http://activecodeline.com/create-a-menu-for-private-pages-and-posts-in-wordpress">Branko Ajzele</a>.

== Translations ==

If you would like to send me a translation, please write to me through <a href="http://sillybean.net/about/contact/">my contact page</a>. Let me know which plugin you've translated and how you would like to be credited. I will write you back so you can attach the files in your reply.

== Screenshots ==

1. The options page

== Changelog ==

= 2.1 =
* Fixed bug where password-protected prefix field incorrectly displayed private prefix value.
* Category walker update.
* Widget constructor update.
= 2.0 =
* private, future, draft, and pending pages are now available as parents in page attributes and quick edit dropdowns
* private pages can be added to nav menus (but do not announce themselves as private, and will lead anonymous visitors to a 404 page)
* private pages can be added to page lists and widgets
* fixed uninstall hook
* fixed '8' in add_options_page, argh
* updated options page to use settings API
* referred role management to <a href="http://wordpress.org/extend/plugins/members/">Members</a>
* fixed save_post action to avoid database call
* using mb_ereg_replace to handle multibyte strings in Private/Protected prefixes
= 1.2.3 =
* Turkish translation
= 1.2.2 =
* Bulgarian translation
= 1.2.1 =
* Fixed a bug involving a reference to an obsolete function. (January 3, 2011)
= 1.2 =
* Added support for custom roles.
* Fixed a problem in the way roles worked.
* Fixed bug with accented characters in title prefixes. (December 16, 2010)
= 1.1 =
* Fixed list markup when forcing private pages to appear in page lists (Thanks, Tina and Sébastien!)
* French translation by Regis (August 5, 2010)
= 1.01 =
* Fixed bug that caused conflicts with other plugins' widgets.
* Fixed formatting of category checkboxes on the options page. (January 24, 2010)
= 1.0 = 
* New `wp_list_private_pages()` tag. 
* Fixed markup (somewhat) when adding private pages to existing template tags.
* Added private pages to parent dropdown when editing pages.
* Added page widget that includes private pages.
* Added private categories feature.
* Fixed prefix fields to work with HTML (e.g. <img> tags)
* Translation support. (January 16, 2010)
= 0.9 = 
* First release (October 15, 2009)

== Upgrade Notice ==
= 2.0 =
* This version requires WordPress 3.3! For earlier versions of WordPress, download 1.2.3 from the Other Versions page. PLEASE NOTE: 2.0 no longer includes options for setting privacy-related permissions. You should use the <a href="http://wordpress.org/extend/plugins/members/">Members</a> plugin in addition to this one.
