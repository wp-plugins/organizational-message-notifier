=== Organizational Message Notifier ===
Contributors: zaantar
Tags: superadmin, multisite, organization, organizational, message
Donate link: http://zaantar.eu/index.php?page=Donate
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 1.5.1

Allows network admin to send organizational messages to blog admins. Includes read confirmation.

== Description ==

This plugin allows network admin to send organizational messages to blog admins within blog administration. 

Main features:

* Every blog admin sees an notification until they mark all messages as read.
* Network admin has control over these notifications - can view who has not read which message yet and can delete notifications manually
* Message contains title (with or without URL), current date  and the content (which can contain HTML)
* Notifications are shown to admins of blogs active in time of it's creation. (probably will be adjustable in the future)

Makes use of the Wordpress Logging Service.

Developed for private use, but has perspective for more extensive usage. I can't guarantee any support in the future nor further development, but it is to be expected. Kindly inform me about bugs, if you find any, or propose new features: zaantar@zaantar.eu.

== Frequently Asked Questions ==

No questions yet.

== Changelog ==

= 1.5.1 =
* fixed register_activation_hook

= 1.5 =
* i18zed
* submitted to wordpress.org
* code cleanup
* removed dependency on the Who is who plugin
* expanded new message content text field & other minor visual improvements
* czech translation
