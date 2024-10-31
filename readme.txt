=== Plugin Name ===
Contributors: meetmthosar
Donate link: http://logiccoding.com/
Tags: recurly,payment gateway
Requires at least: 3.0.1
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is basic wordpress plugin to list all plans which have been added to recurly account.

== Description ==

Steps to follow:

* At Admin side
If you have recurly account then you just need to set subdomain, API key, private key from plugin  setting page.

* Create page with [plans] shortcode. It will show all plans.

* More Shortcode
1. [plans type="days"] – To show plans which has validity in days.
2. [plans type="months"] – To show plans which has validity in months.
3. [plans type="yearly"] – To show plans which has validity in years.
4. Show selected plans: You can add shortcode like [plans type="all Or yearly Or months or days"   codes='plan_code,plan_code_1,plan_code_2']. Then it will show only mentioned plans.

* Subscribe any plan
User can select any plan for subscription. It will redirect user to checkout page where user can pay with credit card.

== Installation ==

Installation does not include any complicated steps to follow

It is simple as

1. Upload `reculry` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
1. Further detail information is in description.

== Frequently Asked Questions ==



== Screenshots ==

1. Screenshot - 1 : Enter your recurly details at admin side
2. Screenshot - 2 : Use any of shortcode in page.


== Changelog ==



== Arbitrary section ==


== A brief Markdown Example ==