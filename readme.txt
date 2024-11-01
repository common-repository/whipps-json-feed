=== Plugin Name ===
Contributors: whipps.org
Donate link: 
Tags: Whipps, iPhone, gallery, photo, image, mobile, feed
Requires at least: 2.7.0
Tested up to: 2.9.2
Stable tag: trunk

Plugin required to enable Whipps - free iPhone photo viewer, on your WordPress blog.

== Description ==

<p><a href="http://www.whipps.org/download-whipps">Whipps</a> is a free native iPhone application for viewing YOUR WordPress photo galleries. `Basically it's your own iPhone app`!</p>
<p>This plugin will make your WordPress installation compatible with Whipps, making yout photo galleries available to all iPhone users. Integration with existing WordPress blog is easy as installing whipps-json-feed.</p>
<p>This plugin was built upon the Chris Northwood's <a href="http://www.pling.org.uk">Json-feed plugin</a> and some parts of Paul Menard's <a href="http://www.codehooligans.com/2008/04/27/simply-exclude-plugin/">Simply Exclude plugin</a>.</p>

== Installation ==

1. Upload extracted `whipps` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Check "Whipps options" in Settings menu - set blog info page, duration of "new" posts and hide some categories from <a href="http://www.whipps.org">Whipps</a> if you need to

Make sure your
<ul>
    <li>posts you wish to show have a photo attached</li>
    <li>permalinks start with %category%</li>
    <li>categories containing subcategories do not have any posts assigned to them</li>
</ul>

Add your blog to <a href="http://www.whipps.org/download-whipps">Whipps</a> on your iPhone and congratulate yourself - you now have your own iPhone app!

== Frequently Asked Questions ==

= How do I set Whipps "info" screen for my photographs? =

<p>Your posts will need additional custom keys: 'designed-by' for photographer and 'web-url' for link. These keys are optional.</p>

= How can I hide some categories from the Whipps app? = 

<p>See "Whipps options" of the Settings menu and select which categories to hide from your iPhone users.</p>

= My "hidden" posts show through Whipps app search =

<p>If you also wish to stop some categories from showing up in "Search" panel just use <a href="http://www.codehooligans.com/2008/04/27/simply-exclude-plugin/">Simply Exclude plugin</a> to completely exclude categories from feed.</p>

= I need help with integration! =

<p>Please contact us at <a href="http://www.whipps.org">whipps.org</a>.</p>

== Screenshots ==

1. Whipps Options panel
2. Whipps application sample

== Troubleshooting == 

<p>Install <a href="http://benhollis.net/software/jsonview/">JSONView plugin</a> for FireFox for troubleshooting your feeds and follow tests given below.</p>

= How can I tell if the plugin is working correctly? =

<p>Whipps app will look for the following URLs on your blog:</p>
<ul>
    <li>http://www.yourblog/?whipps=on - list of all photos</li>
	<li>http://www.yourblog/?whipps=on&paged=0 - root category listing</li>
    <li>http://www.yourblog/my-photo-category/?whipps=on - list of category photos</li>
	<li>http://www.yourblog/?whipps=on&version=check - your plugin version info</li>
</ul>

<p>Make sure that these URLs work on your blog. Your permalinks structure should start with %category% (for example: /%category%/%postname%/).</p>

<p>To check all photos displayed within Whipps app open  http://www.yourblog/?whipps=on and you should see visible categories feed in JSON format (<a href="http://www.whipps.org/?whipps=on" target="_blank">example</a>).</p>

<p>To check categories displayed within Whipps app open http://www.yourblog/?whipps=on&paged=0 and you should see visible categories feed in JSON format (<a href="http://www.whipps.org/?whipps=on&amp;paged=0" target="_blank">example</a>).</p>

<p>To check posts Whipps app will show for your-category open http://www.yourblog/your-category/?whipps=on and you should see posts with images for your-category in JSON feed format (<a href="http://www.whipps.org/black-and-white/?whipps=on" target="_blank">example</a>).</p>

<p>Get latest info at <a href="http://www.whipps.org">www.whipps.org</a>.</p>

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
Initial release, options panel added.