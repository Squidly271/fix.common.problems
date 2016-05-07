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

</strong>
";
echo Markdown($checkList);
?>