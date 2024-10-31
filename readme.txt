=== Pressbackup Express===
Contributors: infinimediainc
Tags: pressbackup, pressbackup express, backup, cron, schedule, scheduling, automatic, database ,files, wp-content
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.0.1
License: GPLv2

PressBackup Express automatically backs up your entire WordPress site to our cloud service. Requires a PressBackup.com Express membership to use.

== Description ==

PressBackup Express automatically backs up your entire WordPress site. Powered by our own cloud built on top of the leading cloud infrastructure providers, PressBackup Express will help ensure your site is properly backed up and secure. 

It can be setup in minutes and requires minimal knowledge of WordPress or server configuration.

This plugin allows your WordPress administrator to schedule backups, restore your site, and migrate your site as needed. 

PressBackup Express requires a PressBackup.com Express membership. Pricing details can be found on PressBackup.com

http://pressbackup.com


== Installation ==

* Download plugin
* Copy it into your folder: /wp-content/plugins
* Activate the plugin
* Go to Tools > PressBackup Express

  DONE :)

== Requirements == 

* Sufficient disk space to store the temporary zip of your site. 
* GZip extension or zip app via shell
* Curl extension (php safe mode off)
* A PressBackup.com Express membership

= Warnings =

To restore the backups you need to change permissions of ” themes” “plugins” and “uploads” folders to 777 (read and write for all)
then do the restore and then change back to original

Please be careful about doing a restore from a previous version of Wordpress if you have upgraded Wordpress core files between backups.
We cannot ensure a smooth transition between each upgrade.


== INCOMPATIBILITIES == 

* IIS web servers
* Some versions of LiteSpeed web servers
* web hosting from ecowebhosting.co.uk


== Changelog ==

= 1.0.1 =
* FIXED BUG ON ACTIVATION PLEASE UPGRADE

= 1.0 =
* The little brother of Pressbackup Pro!

