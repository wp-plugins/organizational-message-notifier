=== Organizational Message Notifier ===
Contributors: zaantar
Tags: superadmin, multisite, organization, organizational, message
Donate link: http://zaantar.eu/financni-prispevek
Author URI: http://zaantar.eu
Plugin URI: http://wordpress.org/extend/plugins/organizational-message-notifier
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 2.0.3

Allows network admin to send organizational messages to blog admins. Includes read confirmation.

== Description ==

This plugin allows network admin to send organizational messages to blog admins within blog administration. 

Main features:

* Every blog admin sees an notification until they mark all messages as read.
* Network admin has control over these notifications - can view who has not read which message yet and can delete notifications manually
* Message contains title (with or without URL), current date  and the content (which can contain HTML)
* Notifications are shown to admins of blogs active in time of it's creation. (probably will be adjustable in the future)

Makes use of the Wordpress Logging Service.

Developed for private use, but has perspective for more extensive usage. I can't guarantee any support in the future nor further development, but it is to be expected. Kindly inform me about bugs, if you find any, or propose new features: [zaantar@zaantar.eu](mailto:zaantar@zaantar.eu?subject=[organizational-message-notifier]).

== Frequently Asked Questions ==

No questions yet.

== Changelog ==

= 2.0.3 =
* Fix: Wrong function name causing fatal error when showing list of all messages for an user.

= 2.0.2 =
* Fix: Actually fix the error addressed in 2.0.1.
* Czech translation updated.

= 2.0.1 =
* Fix: Missing backslash causing invalid callback.
* Tweak: Message can be targeted to multiple roles now.

= 2.0 =
* Almost completely rewritten, polished code.
* Note: Requires PHP >= 5.3 from now on, because of using PHP namespaces.
* Feature: Message can be targeted on all users with specified role (on multisite it is the role on primary blog).
* Tweak: Show notices and (only unread) message list also to users without "minimal capability" if they have unread messages.

= 1.5.7 =
* Fix last incorrect $wpdb->prepare() call.
* Added wishlist to readme.txt

= 1.5.6 =
* Fix $wpdb->prepare() usage to disable warning in WordPress 3.5.

= 1.5.5 =
* network admin overview: using WP_List_Table for better look
* new message target: blog administrators determined by blog "admin_email" settings
* minor bugs fixed

= 1.5.4 =
* new feature: e-mail user notification

= 1.5.3 =
* possibility to delete a message

= 1.5.2 =
* settings page; donate link, moved wls settings here
* option to notify admins or all users or selected users
* adujstable minimal capability to show messages

= 1.5.1 =
* fixed register_activation_hook

= 1.5 =
* i18zed
* submitted to wordpress.org
* code cleanup
* removed dependency on the Who is who plugin
* expanded new message content text field & other minor visual improvements
* czech translation

== Wishlist ==

* accept list of usernames as a target when creating new message
* rewrite code using PHP 5.3 features such as namespaces
* more verbose error logging
* bulk actions in message table
