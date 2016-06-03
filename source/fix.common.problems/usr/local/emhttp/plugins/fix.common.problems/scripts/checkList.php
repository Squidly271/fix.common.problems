<?
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/dynamix/include/Markdown.php");

$checkList = "
<h2> Normal Scans </h2>

* Implied Cache Only shares do not have files / folders stored outside the cache drive
* Cache Only shares do not have files / folders stored outside the cache drive
* Array Only shares do not have files / folders stored on the cache drive
* Dynamix checking for plugin updates
* Dynamix checking for docker updates
* Community Applications Installed - Only because of its plugin auto update feature
* Community Applications set to auto update itself
* Dynamix WebUI set to auto update (via Community Applications)
* This plugin set to auto update itself (via Community Applications)
* Powerdown plugin installed
* Ability for the server to communicate to the outside world (ping github.com)
* Ability to write a file to each drive in array and cache
* Ability to write a file to the flash drive
* Ability to write a file to the docker image
* Similar named shares only differing by case (eg: MyShare and myshare)
* Default appdata storage location is set to /mnt/cache/....
* Default appdata storage location is a cache only share
* Look for disabled disks
* Look for missing disks
* Look for read errors
* Look for file system errors
* Look for SSD's within the Array
* Check if plugins are up to date (and ignore if autoupdate settings are enabled in CA)
* Check for 32 bit packages in /boot/extra and /boot/packages
* Check for docker applications updates available
* Check individual docker application's /config mappings set to /mnt/user (should be /mnt/cache)
* Check for /var/log greater than 50% full
* Check for tmpfs greater than 75 % full
* Check for docker image file greater than 80% full
* Check for Date and Time to be within 5 minutes of current
* Check for scheduled parity checks
* Check for shares with included and excluded disks both set
* Check for shares with both included and excluded disks having overlaps
* Check for global share settings both included and excluded disks set
* Check for global share settings with included and excluded disks having overlaps
* Check for docker containers having UD mounted volumes not being passed with slave option
* Check for only supported file system types (reiserFS, xfs, btrfs) on array / cache devices
* Check for flash drive formatted as fat32
* Check for built-in FTP server running
* Check for destination set for Alert level notifications
* Check for destination set for Warning level notifications 
& Check for email server and recipient addresses set if email notifications are selected
* Check for plugins installed being blacklisted
* Check for plugins installed not being known to Community Applications (implies incompatible)
* Check for docker applications installed and users changing Container ports from author default
* Check for ad blocker's interfering with unRaid
* Check for illegal characters in share names
* Check for docker applications not running in the network mode template author specifies
* Check for HPA on drives  (Error on parity, other warning for all other drives)
* Check for illegal suffixes on cacheFloor settings
* Check for cache free space less than cacheFloor
* Check for cache floor greater than cache total space
* Check for permissions of 0777 on shares
* Check for unclean shutdowns of server
* Check for Hack Attacks on your server
* Check for Moderated / Blacklisted docker applications
* Check for plugins incompatible for your unRaid version
* Check for changed webUI on docker applications
* Check for cache only shares, but no cache drive
* Check for user shares named the same as a disk share
* Check for CPU Scaling Driver installed

<h2>Troubleshooting Mode</h2>

* Continuously 'tails' syslog to /boot/logs/syslog.txt

<b>Every 10 Minutes</b>

* var/log filling up
* rootfs filling up
* logs sysload
* logs free memory
* logs ps aux (CPU % > 0)

<b>Every 30 Minutes</b>

* runs unRaid diagnostics
</strong>
";
echo Markdown($checkList);
?>