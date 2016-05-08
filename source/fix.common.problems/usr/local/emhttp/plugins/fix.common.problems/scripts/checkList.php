<?
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/dynamix/include/Markdown.php");

$checkList = "
* Implied Cache Only shares do not have files / folders stored outside the cache drive
* Cache Only shares do not have files / folders stored outside the cache drive
* Array Only shares do not have files / folders stored on the cache drive
* Dynamix checking for plugin updates
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
</strong>
";
echo Markdown($checkList);
?>