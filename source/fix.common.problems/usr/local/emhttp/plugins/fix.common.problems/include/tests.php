<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2020, Andrew Zawadzki    #
#                                                             #
###############################################################

/*

With the exception of the following global variables, all functions / tests should be 100% self contained and not
have any dependencies from another test

$fixPaths                       - various static local / remote paths
$fixSettings                    - the user defined settings for this plugin
$autoUpdateOverride             - set if auto-update errors should generate a warning instead
$developerMode                  - a flag to signal certain tests to not run when the user is a developer of various items for unRaid (so that they are not bugged)
$communityApplicationsInstalled - True if CA is installed
$dockerRunning                  - True if docker is running
$ignoreList                     - List of errors which are currently ignored (can be safely ignored unless there's a very valid reason to not actually run the test if its ignored, as ignored items will not generate a notification)
$shareList                      - List of unRaid's user shares
$unRaidVersion                  - Currently installed version of unRaid
*/

###########################
# Check for array started #
###########################

# NOTE: This sets the global variables $shareList so it needs to be run first of all the tests

function isArrayStarted() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_dir("/mnt/user") ) {
		$shareList = array_diff(scandir("/mnt/user"),array(".",".."));
	} else {
		$shareList = array();
		$unRaidVars = my_parse_ini_file($fixPaths['var.ini']);
		if ( $unRaidVars['mdState'] != "STARTED" ) {
			addError("Array is not started","Most (but not all) tests require the array to be started in order to run.  There may be more errors / warnings than what is listed here");
		}
	}
}

#############################################################
# Check for implied array only but files / folders on cache #
#############################################################

function impliedArrayFilesOnCache() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( startsWith($share,".") ) { continue; }
		if ( ! is_file("/boot/config/shares/$share.cfg") ) {
			if ( is_dir("/mnt/cache/$share") ) {
				$shareURL = str_replace(" ","+",$share);
				addWarning("Share <b>$share</b> is an implied <em>array-only</em> share, but files / folders exist on the cache","Set <b><em>Use Cache</em></b> appropriately, then rerun this analysis. ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
			}
		}
	}
}

############################################################
# Check for cache only share, but files / folders on array #
############################################################

function cacheOnlyFilesOnArray() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( startsWith($share,".") ) { continue; }
		if ( is_file("/boot/config/shares/$share.cfg") ) {
			$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
			if ( $shareCfg['shareUseCache'] == "only" ) {
				if (is_dir("/mnt/user0/$share") ) {
					$contents = array_diff(scandir("/mnt/user0/$share"),array(".",".."));
					if ( ! empty($contents) ) {
						$shareURL = str_replace(" ","+",$share);
						addWarning("Share <b>$share</b> set to <em>cache-only</em>, but files / folders exist on the array","You should change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL")." or use the dolphin / krusader docker applications to move the offending files accordingly.  Note that there are some valid use cases for a set up like this.  In particular: <a href='https://lime-technology.com/forum/index.php?topic=40777.msg385753' target='_blank'>THIS</a>");
					}
				}
			}
		}
	}
}

#######################################################
# Check for don't use cache, but files on cache drive #
#######################################################

function arrayOnlyFilesOnCache() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( startsWith($share,".") ) { continue; }
		if ( is_file("/boot/config/shares/$share.cfg") ) {
			$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
			if ( $shareCfg['shareUseCache'] == "no" ) {
				if ( is_dir("/mnt/cache/$share") ) {
					$contents = array_diff(scandir("/mnt/cache/$share"),array(".",".."));
					if ( ! empty($contents) ) {
						$shareURL = str_replace(" ","+",$share);
						addWarning("Share <b>$share</b> set to <em>not use the cache</em>, but files / folders exist on the cache drive","You should change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL")."or use the dolphin / krusader docker applications to move the offending files accordingly.  Note that there are some valid use cases for a set up like this.  In particular: <a href='https://lime-technology.com/forum/index.php?topic=40777.msg385753' target='_blank'>THIS</a>");
					}
				}
			}
		}
	}
}

##############################################
# Check for Dynamix to perform plugin checks #
##############################################

function pluginUpdateCheck() {
	global $fixPaths, $communityPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	
	if ( is_file("/boot/config/plugins/ca.update.applications/plugin_update.cron") ) { return; }

	if ( ! is_file("/boot/config/plugins/dynamix/plugin-check.cron") ) {
		if ( $autoUpdateOverride ) {
			$func = "addWarning";
		} else {
			$func = "addError";
		}
		$func("<b>Plugin Update Check</b> not enabled","Highly recommended to have dynamix check for plugin updates (including for the webUI".addLinkButton("Notification Settings","/Settings/Notifications"));
	}
}

#####################################################
# Check for Dynamix to perform docker update checks #
#####################################################

function dockerUpdateCheck() {
	global $fixPaths, $communityPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.7.4","<") ) return;

	if ( is_file("/boot/config/plugins/ca.update.applications/docker_update.cron") ) { return; }

	if ( $dockerRunning ) {
		if ( ! is_file("/boot/config/plugins/dynamix/docker-update.cron") ) {
			addWarning("<b>Docker Update Check</b> not enabled","Recommended to enable update checks for docker applications".addLinkButton("Notification Settings","/Settings/Notifications"));
		}
	}
}

###############################################
# Check for CA to auto update certain plugins #
###############################################

function autoUpdateCheck() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;
	global $communityPaths;

	if ( $communityApplicationsInstalled ) {
		$autoUpdateSettings = readJsonFile($communityPaths['autoUpdateSettings']);
		if ( ! $autoUpdateSettings) {
			$autoUpdateSettings['community.applications.plg'] = "true";
			$autoUpdateSettings['fix.common.problems.plg'] = "true";
		}
		if ( $autoUpdateSettings['Global'] != "true" ) {
			if ( $autoUpdateSettings['fix.common.problems.plg'] != "true" ) {
				if ( $autoUpdateOverride ) {
					$func = "addWarning";
				} else {
					$func = "addWarning";
				}
				$func("This plugin <b>(Fix Common Problems)</b> not set to auto update",addLinkButton("Auto Update Settings","/Settings/AutoUpdate")."Recommended to enable auto updates for this plugin to enable further problem solving / fixes");
			}
		}
	}
}

#############################################################
# Check for shares spelled the same but with different case #
#############################################################

function sameShareDifferentCase() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ( $shareList as $share ) {
		$dupShareList = array_diff(scandir("/mnt/user/"),array(".","..",$share));
		foreach ($dupShareList as $dup) {
			if ( strtolower($share) === strtolower($dup) ) {
				addError("Same share ($share) exists in a different case","This will confuse SMB shares.  Manual intervention required.  Use the dolphin / krusader docker app to combine the shares into one unified spelling");
				break;
			}
		}
	}
}

########################################
# Check for powerdown plugin installed #
########################################

function powerdownInstalled() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.1.9",">=") ) { return; }
	if ( ! is_file("/var/log/plugins/powerdown-x86_64.plg") ) {
		$suggestion = $communityApplicationsInstalled ? "Install either through ".addLinkButton("Community Applications","/Apps")." or via" : "";
		addWarning("<b>Powerdown</b> plugin not installed","Highly recommended to install this plugin.  Install via $suggestion the instructions <a href='http://lime-technology.com/forum/index.php?topic=31735.0' target='_blank'>HERE</a>");
	}
}

################################################
# Check for communication to the outside world #
################################################

function outsideCommunication() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( ! @file_get_contents($fixPaths['moderation']) || ! strlen(@file_get_contents($fixPaths['moderation'])) ) {
		addError("Unable to communicate with GitHub.com","Reset your modem / router or try again later, or set your ".addLinkButton("DNS Settings","/Settings/NetworkSettings")." to 8.8.8.8 and 8.8.4.4  Also make sure that you have a Gateway address set up (Your Router's IP address).  Additionally, this may also mean that GitHub is currently down.  This error means that you may not be able to update your OS or plugins");
	}
}

###############################################################
# Check for inability to write to drives, flash, docker image #
###############################################################

function writeToDriveTest() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$availableDrives = array_diff(scandir("/mnt/"),array(".","..","user","user0","disks","RecycleBin"));
	$disksIni = my_parse_ini_file($fixPaths['disks.ini'],true);

	foreach ($availableDrives as $drive) {
		if ( $fixSettings['disableSpinUp'] == "true" ) {
			if ( stripos($disksIni[$drive]['color'],"blink") ) {
				$spunDown .= " $drive ";
				continue;
			}
		}
		if ( is_file("/mnt/$drive") ) {
			addError("File $drive present within /mnt","Generally speaking, most times when files get created within /mnt it is a result of an improperly configured application.  This error may or may not cause issues for you");
			continue;
		}
		$diskpos = strpos($drive,"disk");
		if ( $diskpos !== false ) {
			$validTest = str_replace("disk","",$drive);
			if ( ! is_numeric($validTest) ) {
				addError("Invalid folder $drive contained within /mnt","Generally speaking, most times when other folders get created within /mnt it is a result of an improperly configured application.  This error may or may not cause issues for you");
				continue;
			}
		}
		$cachepos = strpos($drive,"cache");
		if ( ($cachepos !== false) && ($drive != "cache") ) {
			addError("Invalid folder $drive contained within /mnt","Generally speaking, most times when other folders get created within /mnt it is a result of an improperly configured application.  This error may or may not cause issues for you");
			continue;
		}
		if ( $diskpos === false && $drive != "cache" ) {
			addError("Invalid folder $drive contained within /mnt","Generally speaking, most times when other folders get created within /mnt it is a result of an improperly configured application.  This error may or may not cause issues for you");
			continue;
		}
		$filename = randomFile("/mnt/$drive");

		@file_put_contents($filename,"test");
		$result = @file_get_contents($filename);
		if ( $result != "test" ) {
			addError("Unable to write to $drive","Drive mounted read-only or completely full.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
		}
		@unlink($filename);
	}



	if ( $dockerRunning ) {
		$filename = randomFile("/var/lib/docker/tmp");
		@file_put_contents($filename,"test");
		$result = @file_get_contents($filename);

		if ( $result != "test" ) {
			addError("Unable to write to <b>Docker Image</b>","Docker Image either full or corrupted.  Investigate Here: ".addLinkButton("Docker Settings","/Settings/DockerSettings"));
		}
		@unlink($filename);
	}
}

###############################################################################
# check for default docker appdata location to be cache or directly on a disk #
###############################################################################

function dockerImageOnDiskShare() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.2.0-rc3",">=") || version_compare($unRaidVersion,"6.2",">=") ) { return; }

	if ( is_dir("/mnt/cache") ) {
		$dockerOptions = @my_parse_ini_file("/boot/config/docker.cfg");
		if ( startsWith($dockerOptions['DOCKER_APP_CONFIG_PATH'],"/mnt/user/") ) {
			addWarning("<b>docker appdata location</b> is stored within /mnt/user","Many (if not most) docker applications will have issues (weird results, not starting, etc) if their appdata is stored within a user share.  You should constrain the appdata share to a <b>single</b>disk or to the cache drive.  This is true even if the appdata share is a <em>Cache-Only</em> share.  Change the default here: ".addLinkButton("Docker Settings","/Settings/DockerSettings"));
		}
	}
}

####################################################################
# check for default docker appdata location to be cache only share #
####################################################################

function dockerAppdataCacheOnly() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_dir("/mnt/cache") ) {
		$dockerOptions = @my_parse_ini_file("/boot/config/docker.cfg");
		if ( ! startsWith($dockerOptions['DOCKER_APP_CONFIG_PATH'],"/mnt/user/") ) {
			return;
		}
		$folders = explode("/",str_replace("/mnt/user/","",$dockerOptions['DOCKER_APP_CONFIG_PATH']));
		$sharename = $folders[0]; # get the actual sharename from the path;
		if ( is_file("/boot/config/shares/$sharename.cfg") ) {
			$shareSettings = my_parse_ini_file("/boot/config/shares/$sharename.cfg");
			if ( ( $shareSettings['shareUseCache'] != "only" ) && ( $shareSettings['shareUseCache'] != "prefer" ) ) {
				addError("<b>Default docker appdata</b> location is not a cache-only share","If the appdata share is not set to be cache-only, then the mover program will cause your docker applications to become inoperable when it runs (6.1.x). Under 6.2+, this setting should not affect the operation of the application, but it will definitely impact significantly the performance of the application.  Fix it via ".addLinkButton("$sharename Settings","/Shares/Share?name=$sharename"));
			}
		}
	}
}

###########################
# look for disabled disks #
###########################

function disabledDisksPresent() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);

	foreach ($disks as $disk) {
		if ( startsWith($disk['status'],'DISK_DSBL') ) {
			addError("<b>".$disk['name']." (".$disk['id'].")</b> is disabled","Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
		}
	}
}

##########################
# look for missing disks #
##########################

function missingDisksPresent() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);
	foreach ($disks as $disk) {
		if ( ( $disk['status'] == "DISK_NP") || ( $disk['status'] == "DISK_NP_DSBL" ) ) {
			if ( $disk['id'] ) {
				addError("<b>".$disk['name']." (".$disk['id'].")</b> is missing","unRaid believes that your hard drive is not connected to any SATA port.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main")."  And also look at the ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
			}
		}
	}
}

########################
# look for read errors #
########################

function readErrorsPresent() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);
	foreach ($disks as $disk) {
		if ( $disk['numErrors'] ) {
			addError("<b>".$disk['name']." (".$disk['id'].")</b> has read errors","If the disk has not been disabled, then unRaid has successfully rewritten the contents of the offending sectors back to the hard drive.  It would be a good idea to look at the S.M.A.R.T. Attributes for the drive in questionBegin Investigation Here: ".addLinkButton($disk['name']." Settings","/Main/Device?name=".$disk['name']));
		}
	}
}

###############################
# look for file system errors #
###############################

function fileSystemErrors() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);
	foreach ( $disks as $disk ) {
		if ( $disk['fsError'] ) {
			addError("<b>".$disk['name']." (".$disk['id'].")</b> has file system errors (".$disk['fsError'].")","If the disk if XFS / REISERFS, stop the array, restart the Array in Maintenance mode, and run the file system checks.  If the disk is BTRFS, then see <a href='https://forums.lime-technology.com/topic/46802-faq-for-unraid-v6/?page=2&tab=comments#comment-543490' target='_blank'>this post</a>.  <b>If the disk is listed as being unmountable, and it has data on it, whatever you do do not hit the format button.  Seek assistance <a href='http://lime-technology.com/forum/index.php?board=71.0' target='_blank'>HERE</a>");
		}
	}
}

###################################
# look for SSD's within the Array #
###################################

function SSDinArray() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);
	foreach ( $disks as $disk ) {
		if ( $disk['rotational'] == "0" ) {
			if ( startsWith($disk['name'],"disk") ) {
				addWarning("<b>".$disk['name']." (".$disk['id'].")</b> is an SSD.","SSD's are not currently supported within the array, and their background garbage collection *may* impact your ability to rebuild a disk");
			}
		}
	}
}

###################################
# look for plugins not up to date #
###################################

function pluginsUpToDate() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;
	global $communityPaths, $unRaidVersion;

	$autoUpdateSettings = readJsonFile($communityPaths['autoUpdateSettings']);
	if ( $autoUpdateSettings['Global'] != "true" ) {
		$installedPlugins = array_diff(scandir("/var/log/plugins"),array(".",".."));
		foreach ($installedPlugins as $Plugin) {
			if ( $Plugin == "community.applications.plg" ) {
				continue;
			}
			if ( $autoUpdateSettings[$Plugin] != "true" ) {
				if ( is_file("/var/log/plugins/$Plugin") ) {
					if ( strtolower(pathinfo($Plugin, PATHINFO_EXTENSION)) == "plg" ) {
						if ( checkPluginUpdate($Plugin) ) {
							if ( $Plugin == "fix.common.problems.plg" ) {
								addError("Plugin <b>$Plugin</b> is not up to date","Upgrade the plugin here: ".addLinkButton("Plugins","/Plugins"));
							} else {
								if ( $Plugin == "unRAIDServer.plg" ) {
									$uptodateVersion = pluginVersion("/tmp/plugins/$Plugin");
									addWarning("<b>unRaid OS</b> not up to date","You are currently running <b>".unRaidVersion()."</b> and the latest version is <b>$uptodateVersion</b>.  It is recommended to upgrade here: ".addLinkButton("UpdateOS","/Tools/Update")." and review the release notes <a href='http://lime-technology.com/forum/index.php?board=1.0' target='_blank'>HERE</a>");
								} else {
									addWarning("Plugin <b>$Plugin</b> is not up to date","Upgrade the plugin here: ".addLinkButton("Plugins","/Plugins"));
								}
							}
						}
					}
				}
			}
		}
	}
}

###############################################################
# check for 32 bit packages in /boot/extra and /boot/packages #
###############################################################

function incompatiblePackagesPresent() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_dir("/boot/extra") ) {
		$files = array_diff(scandir("/boot/extra"),array(".",".."));
		foreach ($files as $file) {
			if ( strpos($file,"i386") || strpos($file,"i486") ) {
				addError("Probable 32 Bit package <b>$file</b> found on the flash drive in the <b>extra</b> folder","32 Bit packages are incompatible with unRaid 6.x and need to be removed - Using your desktop, navigate to the <em>flash</em> share (extra folder) and delete the offending file");
			}
		}
	}
	if ( is_dir("/boot/packages") ) {
		$files = array_diff(scandir("/boot/packages"),array(".",".."));
		foreach ($files as $file) {
			if ( strpos($file,"i386") || strpos($file,"i486") ) {
				addError("Probable 32 Bit package <b>$file</b> found on the flash drive in the <b>packages</b> folder","32 Bit packages are incompatible with unRaid 6.x and need to be removed - Using your desktop, navigate to the <em>flash</em> share (packages folder) and delete the offending file");
			}
		}
	}
}

##########################################
# Check if docker containers not updated #
##########################################

function dockerUpToDate() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.7.4","<") ) return;

	if ( $dockerRunning ) {
		$DockerClient = new DockerClient();
		$info = $DockerClient->getDockerContainers();
		$updateStatus = readJsonFile($fixPaths['dockerUpdateStatus']);

		foreach ($info as $docker) {
			if ( $updateStatus[$docker['Image']]['status'] == 'false' ) {
				addWarning("Docker Application <b>".$docker['Name']."</b> has an update available for it","Install the updates here: ".addLinkButton("Docker","/Docker"));
			}
		}
	}
}

######################################################################
# Check for docker application's config folders pointed at /mnt/user #
######################################################################

function dockerConfigUserShare() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.2.0-rc3",">=") || version_compare($unRaidVersion,"6.2",">=") ) { return; }


	if ( $dockerRunning ) {
		$DockerClient = new DockerClient();
		$info = $DockerClient->getDockerContainers();

		foreach ($info as $docker) {
			$appData = findAppData($docker['Volumes']);
			if ( startsWith($appData,"/mnt/user") ) {
				addWarning("<b>".$docker['Name']."</b> docker application has its /config folder set to <b>$appData</b>","Many (if not most docker applications) will not function correctly if their appData folder is set to a user share.  Ideally, they should be set to a disk share.  Either /mnt/cache/... or /mnt/diskX/...  Fix it here: ".addLinkButton("Docker Settings","/Docker"));
			}
		}
	}
}

#################################
# Check for /var/log filling up #
#################################

function varLogFilled() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;
	global $troubleshooting;

	unset($output);
	exec("df /var/log",$output);
	$statusLine = preg_replace('!\s+!', ' ', $output[1]);
	$status = explode(" ",$statusLine);
	$used = str_replace("%","",$status[4]);

	if ( $troubleshooting ) {
		logger("Fix Common Problems: /var/log currently $used % full");
	}

	if ( $used > 80 ) {
		addError("<b>/var/log</b> is getting full (currently $used % used)","Either your server has an extremely long uptime, or your syslog could be potentially being spammed with error messages.  A reboot of your server will at least temporarily solve this problem, but ideally you should seek assistance in the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
	} else {
		if ( $used > 50 ) {
			addWarning("<b>/var/log</b> is getting full (currently $used % used)","Either your server has an extremely long uptime, or your syslog could be potentially being spammed with error messages.  A reboot of your server will at least temporarily solve this problem, but ideally you should seek assistance in the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
		}
	}
}

#######################################
# Check for docker image getting full #
#######################################

function dockerImageFull() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	unset($output);
	if ( $dockerRunning ) {
		exec("df /var/lib/docker",$output);
		$statusLine = preg_replace('!\s+!', ' ', $output[1]);
		$status = explode(" ",$statusLine);
		$used = str_replace("%","",$status[4]);

		if ( $used > 90 ) {
			addError("<b>Docker image</b> file is getting full (currently $used % used)","You should either increase the available image size to the docker image here ".addLinkButton("Docker Settings","/Settings/DockerSettings")."or investigate the possibility of docker applications storing completed downloads / incomplete downloads / etc within the actual docker image here: ".addLinkButton("Docker","/Docker"));
		} else {
			if ( $used > 80 ) {
				addWarning("<b>Docker image</b> file is getting full (currently $used % used)","You should either increase the available image size to the docker image here ".addLinkButton("Docker Settings","/Settings/DockerSettings")."or investigate the possibility of docker applications storing completed downloads / incomplete downloads / etc within the actual docker image here: ".addLinkButton("Docker","/Docker"));
			}
		}
	}
}

#################################
# Check for rootfs getting full #
#################################

function rootfsFull() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;
	global $troubleshooting;

	unset($output);
	if ( is_dir("/") ) {
		exec("df /",$output);
		$statusLine = preg_replace('!\s+!', ' ', $output[1]);
		$status = explode(" ",$statusLine);
		$used = str_replace("%","",$status[4]);

		if ( $troubleshooting ) {
			logger("Fix Common Problems: rootfs (/) currently $used % full");
		}

		if ( $used > 90 ) {
			addError("<b>Rootfs</b> file is getting full (currently $used % used)","Possibly an application is storing excessive amount of data in /tmp.  Seek assistance on the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
		} else {
			if ( $used > 75 ) {
				addWarning("<b>Rootfs</b> file is getting full (currently $used % used)","Possibly an application is storing excessive amount of data in /tmp.  Seek assistance on the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
			}
		}
	}
}

##############################################
# Check if the server's time is out to lunch #
##############################################

function dateTimeOK() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$filename = randomFile("/tmp/fix.common.problems");
	download_url("http://currentmillis.com/time/minutes-since-unix-epoch.php",$filename);
	$actualTime = @file_get_contents($filename);
	if (intval($actualTime) > 24377381 ) { # current time as of this code being written as a check for complete download_url
		$serverTime = intval(time() / 60);
		$timeDifference = abs($serverTime - intval($actualTime));

		if ( $timeDifference > 5 ) {
			addWarning("Your server's <b>current time</b> differs from the actual time by more than 5 minutes.  Currently out by approximately $timeDifference minutes","Either set your date / time manually, or set up the server to use an NTP server to automatically update the date and time".addLinkButton("Date and Time Settings","/Settings/DateTime"));
		}
	}
	@unlink($filename);
}

#####################################
# Check for scheduled parity checks #
#####################################

function scheduledParityChecks() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_file("/boot/config/plugins/dynamix/dynamix.cfg") ) {
		$dynamixSettings = my_parse_ini_file("/boot/config/plugins/dynamix/dynamix.cfg",true);

		if ( $dynamixSettings['parity']['mode'] == "0" ) {
			addWarning("Scheduled <b>Parity Checks</b> are not enabled","It is highliy recommended to schedule parity checks for your system (most users choose monthly).  This is so that you know if unRaid has the ability to rebuild a failed drive if it needs to.  Set the schedule here: ".addLinkButton("Scheduler","/Settings/Scheduler"));
		}
	}
}

################################################################
# Check for shares having both included and excluded disks set #
################################################################

function shareIncludeExcludeSet() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( is_file("/boot/config/shares/$share.cfg") ) {
			$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
			if ( $shareCfg['shareInclude'] && $shareCfg['shareExclude'] ) {
				$shareURL = str_replace(" ","+",$share);
				addWarning("Share <b>$share</b> is set for both included (".$shareCfg['shareInclude'].") and excluded (".$shareCfg['shareExclude'].") disks","While if you're careful this isn't a problem, there is absolutely no reason ever to specify BOTH included and excluded disks.  It is far easier and safer to only set either the included list or the excluded list.  Fix it here: ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
			}
		}
	}
	# Check for global share settings having both included and exluded disks set

	if ( is_file("/boot/config/share.cfg") ) {
		$shareCfg = my_parse_ini_file("/boot/config/share.cfg");
		if ( $shareCfg['shareUserInclude'] && $shareCfg['shareUserExclude'] ) {
			addWarning("<b>Global Share Settings</b> is set for both included (".$shareCfg['shareUserInclude'].") and excluded (".$shareCfg['shareUserExclude'].") disks","While if you're careful this isn't a problem, there is absolutely no reason ever to specify BOTH included and excluded disks.  It is far easier and safer to only set either the included list or the excluded list.  Fix it here: ".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
		}
	}
}

#########################################################################
# Check for shares having duplicated disks within included and excluded #
#########################################################################

function shareIncludeExcludeSameDisk() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( is_file("/boot/config/shares/$share.cfg") ) {
			$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
			if ( ! $shareCfg['shareInclude'] ) { continue; }
			if ( ! $shareCfg['shareExclude'] ) { continue; }
			$shareInclude = explode(",",$shareCfg['shareInclude']);
			$shareExclude = explode(",",$shareCfg['shareExclude']);
			foreach ($shareInclude as $included) {
				foreach ($shareExclude as $excluded) {
					if ( $included == $excluded ) {
						$shareURL = str_replace(" ","+",$share);
						addError("Share <b>$share</b> has the same disk ($included) set to be both included and excluded","The same disk cannot be both included and excluded.  There is also no reason to ever set both the included and excluded disks for a share.  Use one or the other.  Fix it here:".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
					}
				}
			}
		}
	}

# Check for having duplicated disks within global share included / excluded

	if ( is_file("/boot/config/share.cfg") ) {
		$shareCfg = my_parse_ini_file("/boot/config/share.cfg");
		if ( ( $shareCfg['shareUserExclude'] ) && ( $shareCfg['shareUserInclude'] ) ) {
			$shareInclude = explode(",",$shareCfg['shareUserInclude']);
			$shareExclude = explode(",",$shareCfg['shareUserExclude']);
			foreach ($shareInclude as $included) {
				foreach ($shareExclude as $excluded) {
					if ( $included == $excluded ) {
						$shareURL = str_replace(" ","+",$share);
						addError("Share <b>Global Share Settings</b> has the same disk ($included) set to be both included and excluded","The same disk cannot be both included and excluded.  There is also no reason to ever set both the included and excluded disks for a share.  Use one or the other.  Fix it here:".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
					}
				}
			}
		}
	}
}

##############################################################################
# Check for UD assigned disks not being passed as slave to docker containers #
##############################################################################

function UDmountedSlaveMode() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( $dockerRunning ) {
		if ( version_compare(unRaidVersion(),"6.2",">=") ) {
			$DockerClient = new DockerClient();
			$info = $DockerClient->getDockerContainers();
			foreach ($info as $docker) {
				if ( is_array($docker['Volumes']) ) {
					foreach ($docker['Volumes'] as $volume) {
						$volumePassed = explode(":",$volume);
						if ( startsWith($volumePassed[0],"/mnt/disks/") ) {
							if ( ! stripos($volumePassed[2],"slave") ) {
								addError("Docker application <b>".$docker['Name']."</b> has volumes being passed that are mounted by <em>Unassigned Devices</em>, but they are not mounted with the slave option","To help with a trouble free experience with this application, you need to pass any volumes mounted with Unassigned Devices using the slave option.  Fix it here: ".addLinkButton("Docker","/Docker"));
							}
						}
					}
				}
			}
		}
	}
}

##############################################
# Check for only supported file system types #
##############################################

function supportedFileSystemCheck() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);
	foreach ($disks as $disk) {
		if ( ($disk['fsType'] != "reiserfs") && ($disk['fsType'] != "xfs") && ($disk['fsType'] != "btrfs") && ($disk['size'] != "0") && ($disk['fsType'] != "auto") && ($disk['fsType'] != "reiserfs") && ($disk['fsType'] != "xfs") && ($disk['fsType'] != "btrfs") && ($disk['fsType'] != "luks:reiserfs") && ($disk['fsType'] != "luks:xfs") && ($disk['fsType'] != "luks:btrfs")) {
			if ( ( startsWith($disk['name'],"cache") ) && ( $disk['name'] != "cache" ) ) {
				continue;
			}
			if ( $disk['name'] == "flash" ) {
				if ( $disk['fsType'] != "vfat" ) {
					addError("unRaid <b>USB Flash Drive</b> is not formatted as FAT32","Strange results can happen if the flash drive is not formatted as FAT32.  Note that if your flash drive is > 32Gig, you must jump through some hoops to format it as FAT32.  Seek assistance in the formums if this is the case");
				}
			} else {
				if ( ($disk['name'] != "parity") && ($disk['name'] != "parity2") ) {
					addError("Disk <b>".$disk['name']."</b> is formatted as ".$disk['fsType'],"The only supported file systems are ReiserFS, btrFS, XFS.  This error should only happen if you are setting up a new array and the disk already has data on it.  <font color='red'><b>Prior to with a fix, you should seek assistance in the forums as the disk may simply be unmountable.  Whatever you do, do not hit the format button on the unRaid main screen as you will then lose data");
				}
			}
		}
	}
}

#########################################
# Check for unRaid's ftp server running #
#########################################

function FTPrunning() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	unset($output);
	exec("cat /etc/inetd.conf | grep vsftpd",$output);
	foreach ($output as $line) {
		if ($line[0] != "#") {
			if ( is_file("/boot/config/vsftpd.user_list") ) {
				addWarning("unRaid's built in <b>FTP server</b> is running","Opening up your unRaid server directly to the internet is an extremely bad idea. - You <b>will</b> get hacked.  If you require an FTP server running on your server, use one of the FTP docker applications instead.  They will be more secure than the built in one".addLinkButton("FTP Server Settings","/Settings/FTP")." If you are only using the built in FTP server locally on your network you can ignore this warning, but ensure that you have not forwarded any ports from your router to your server.  Note that there is a bug in unRaid 6.1.9 and 6.2b21 where if you disable the service, it will come back alive after a reboot.  This check is looking at whether you have users authenticated to use the ftp server");
			}
			break;
		} else {
			if ( is_file("/boot/config/vsftpd.user_list") ) {
				addWarning("unRaid's built in <b>FTP server</b> is currently disabled, but users are defined","There is a &quot;feature&quot; within 6.1.9 and 6.2 beta 21 where after the server is reset, the FTP server will be automatically re-enabled regardless if you want it to be or not.  Remove the users here".addLinkButton("FTP Settings","/Settings/FTP"));
			}
		}
	}
}

########################################################
# Check for destination for Alert levels notifications #
########################################################

function checkNotifications() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$dynamixSettings = my_parse_ini_file("/boot/config/plugins/dynamix/dynamix.cfg",true);

	if ( $dynamixSettings['notify']['alert'] == "0" ) {
		addWarning("No destination (browser / email / agents set for <b>Alert level notifications</b>","Without a destination set for alerts, you will not know if any issue requiring your immediate attention happens on your server.  Fix it here:".addLinkButton("Notification Settings","/Settings/Notifications"));
	}
# Check for destination for Warning level notifications

	if ( $dynamixSettings['notify']['warning'] == "0" ) {
	 addWarning("No destination (browser / email / agents set for <b>Warning level notifications</b>","Without a destination set for alerts, you will not know if any issue requiring your attention happens on your server.  Fix it here:".addLinkButton("Notification Settings","/Settings/Notifications"));
	}

# Check for destination email address

	$notificationsSet = $dynamixSettings['notify']['normal'] | $dynamixSettings['notify']['warning'] | $dynamixSettings['notify']['alert'];
	$emailSelected = ($notificationsSet & 2) == 2;

	if ( $emailSelected ) {
		if ( ( ! $dynamixSettings['ssmtp']['RcptTo'] ) || ( ! $dynamixSettings['ssmtp']['server'] ) ) {
			addWarning("<b>Email</b> selected as a notification destination, but not properly configured","Either deselect email as a destination for notifications or properly configure it here: ".addLinkButton("Notification Settings","/Settings/Notifications")."  Note That this test does NOT test to see if you can actually send mail or not");
		}
	}
}

###########################################
# Check for blacklisted plugins installed #
###########################################

function blacklistedPluginsInstalled() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$caModeration = readJsonFile($fixPaths['moderation']);
	if ( $caModeration ) {
		$pluginList = array_diff(scandir("/var/log/plugins"),array(".",".."));
		foreach ($pluginList as $plugin) {
			if ( ! is_file("/var/log/plugins/$plugin") ) {
				continue;
			}
			$pluginURL = getRedirectedURL(exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin pluginURL /var/log/plugins/$plugin"));
			if ( $caModeration[$pluginURL]['Blacklist'] ) {
				addError("Blacklisted plugin <b>$plugin</b>","This plugin has been blacklisted and should no longer be used due to the following reason(s): <em><b>".$caModeration[$pluginURL]['ModeratorComment']."</b></em>  You should remove this plugin as its continued installation may cause adverse effects on your server.".addLinkButton("Plugins","/Plugins"));
			}
			if ( $caModeration[$pluginURL]['Deprecated'] ) {
				addWarning("Deprecated plugin <b>$plugin</b>","This plugin has been deprecate and should no longer be used due to the following reason(s): <em><b>".$caModeration[$pluginURL]['ModeratorComment']."</b></em>  While this plugin should still be functional, it is no recommended to continue to use it.".addLinkButton("Plugins","/Plugins"));
			}
		}
	} else {
		addOther("Could not check for <b>blacklisted</b> plugins","The download of the blacklist failed");
	}
}

##################################
# Check for non CA known plugins #
##################################

function unknownPluginInstalled() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$templates = readJsonFile($fixPaths['templates']);
	if ( ! $developerMode ) {
		$pluginList = array_diff(scandir("/var/log/plugins"),array(".",".."));

		if ( is_array($templates['applist']) ) {
			foreach ($templates['applist'] as $template) {
				if ($template['Plugin']) {
					$allPlugins[] = $template;
				}
			}
			if ( ! $allPlugins ) { return; }
			foreach ($pluginList as $plugin) {

				if ( is_file("/boot/config/plugins/$plugin") ) {
					if ( ( $plugin == "fix.common.problems.plg") || ( $plugin == "dynamix.plg" ) || ($plugin == "unRAIDServer.plg") || ($plugin == "community.applications.plg") ) {
						continue;
					}
					$pluginURL = exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin pluginURL /var/log/plugins/$plugin");
					$flag = false;
					foreach ($allPlugins as $checkPlugin) {
						if ( is_array($checkPlugin['PluginURL']) ) {                  # due to coppit
							$checkPlugin['PluginURL'] = $checkPlugin['PluginURL'][1];
						}
						if ( $plugin == basename($checkPlugin['PluginURL']) ) {
							$flag = true;
							break;
						}
					}
					if ( ! $flag ) {
						addWarning("The plugin <b>$plugin</b> is not known to Community Applications and is possibly incompatible with your server","As a <em>general</em> rule, if the plugin is not known to Community Applications, then it is not compatible with your server.  It is recommended to uninstall this plugin ".addLinkButton("Plugins","/Plugins"));
					}
				}
			}
		} else {
			addOther("Could not perform <b>unknown plugins</b> installed checks","The download of the application feed failed.");
		}
	}
}

###################################################################################################################
# check for docker applications installed but with changed container ports from what the author specified         #
# concurrently check for docker applications running in a different network mode than what the template specifies #
###################################################################################################################

function dockerAppsChangedPorts() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( $dockerRunning ) {
		$templates = readJsonFile($fixPaths['templates']);
		$dockerClient = new DockerClient();
		$info = $dockerClient->getDockerContainers();

		if ( is_array($templates['applist']) ) {
			$allApps = $templates['applist'];

			foreach ($info as $dockerInstalled) {
				$dockerImage = $dockerInstalled['Image'];
				if ( strtolower($dockerInstalled['NetworkMode']) != "host" && strtolower($dockerInstalled['NetworkMode']) != "bridge" ) {
					continue;
				}
				foreach ($allApps as $app) {
					$support = $app['Support'] ? $app['Support'] : $app['Forum'];
					if ( ($app['Repository'] === str_replace(":latest","",$dockerImage) ) || ($app['Repository'] === $dockerImage) ) {
						if ( ! $app['Networking']['Mode'] ) {   # Fix for some lsio templates
							$app['Networking']['Mode'] = $app['Network'];
						}
						if ( is_array($app['Networking']['Mode']) ) {
							$app['Networking']['Mode'] = "Indeterminate";
						}
						$mode = strtolower($app['Networking']['Mode']);
						if ( $mode != strtolower($dockerInstalled['NetworkMode']) ) {
							addError("Docker Application <b>".$dockerInstalled['Name']."</b> is currently set up to run in <b>".$dockerInstalled['NetworkMode']."</b> mode","The template for this application specifies that the application should run in $mode mode.  <a href='$support' target='_blank'>Application Support Thread</a>  ".addLinkButton("Docker","/Docker"));
						}
						if ( $mode == "host" ) { continue;}

						if ( ! @is_array($app['Networking']['Publish'][0]['Port']) ) { continue; }

						$allPorts = $app['Networking']['Publish'][0]['Port'];

						foreach ($allPorts as $port) {
							if ( ! $port['ContainerPort'] ) { continue; }
							$flag = false;
							foreach ($dockerInstalled['Ports'] as $containerPort) {
								if ( $containerPort['PrivatePort'] == $port['ContainerPort']) {
									$flag = true;
									break;
								}
							}
							if ( ! $flag ) {
								addError("Docker Application <b>".$dockerInstalled['Name'].", Container Port ".$port['ContainerPort']."</b> not found or changed on installed application","When changing ports on a docker container, you should only ever modify the HOST port, as the application in question will expect the container port to remain the same as what the template author dictated.  Fix this here: ".addLinkButton("Docker","/Docker")."<a href='$support' target='_blank'>Application Support Thread</a>");
							}
						}
					}
				}
			}
		} else {
			addOther("Could not perform <b>docker application port</b> tests","The download of the application feed failed.");
		}
	}
}

##############################################
# test for illegal characters in share names #
##############################################

function illegalShareName() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
		if ( strpos($share, '\\') != false ) {
			addError("Share <b>$share</b> contains the \\ character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,"/") != false ) {
			addError("Share <b>$share</b> contains the / character which is an illegal character on Windows / Linux systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums.  You probably also have some disk corruption, as this folder should be impossible to create");
		}
		if ( strpos($share,":") ) {
			addError("Share <b>$share</b> contains the : character which is an illegal character on Windows / MAC systems.","You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,"*") != false ) {
			addError("Share <b>$share</b> contains the * character which is an illegal character on Windows systems.","You may also run into issues with non-Windows systems when using this character.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,"?") != false ) {
			addError("Share <b>$share</b> contains the ? character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,'"') != false ) {
			addError("Share <b>$share</b> contains the \" character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,"<") != false ) {
			addError("Share <b>$share</b> contains the < character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share  You may also run into issues with non-Windows systems when using this character.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,">") != false ) {
			addError("Share <b>$share</b> contains the > character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( strpos($share,"|") != false ) {
			addError("Share <b>$share</b> contains the | character which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( trim($share) == "" ) {
			addError("Share <b>\"$share\"</b> contains only spaces which is illegal Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share/  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
		if ( substr($share, -1) == "." ) {
			addError("Share <b>$share</b> ends with the . character which is an illegal character to end a file / folder name  on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		}
	# control characters in file names are a standard part of OSX
	/*   if ( ! ctype_print($share) ) {
			addError("Share <b>$share</b> contains control character which should be illegal characters on any OS.","You may run into issues with programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
		} */
	}
}

#######################################################################
# check for HPA (addOther if on data drives, addError if parity disk) #
#######################################################################

function HPApresent() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);

	foreach ($disks as $disk) {
		if ( ! $disk['device'] ) { continue; }
		if ( $disk['name'] == "flash" ) { continue; }
		if ( ! startsWith($disk['name'],"parity") ) { continue; }  # only test for parity disks

		if ( $fixSettings['disableSpinUp'] == "true" ) {
			if ( stripos($disk['color'],"blink") ) {
				continue;
			}
		}
		$deviceID = $disk['device'];
		$command = "/sbin/hdparm -N /dev/$deviceID 2>&1";
		unset($output);
		exec($command,$output);
		foreach ($output as $line) {
			if ( strpos($line,"bad/missing") ) { break; }
			if ( strpos($line,"questionable") ) { break; }
			if ( strpos($line,"incorrect") ) { break; }
			if ( strpos($line,"HPA is enabled") ) {
				if ( $disk['name'] == "parity" || $disk['name'] == "parity2" ) {
					$func = "addError";
				} else {
					$func = "addOther";
				}
				$func("Disk <b>".$disk['name']."</b> has an HPA partition enabled on it","If this is your parity disk, then you <b>must</b> remove the HPA partition, because its presence will impact the ability (<b>as in you may not be able to do it</b>) rebuild a disabled drive and/or expand your array.  It is not so important if this is present on a data/cache disk.  See assistance on unRaid's forums for help with the commands to fix this issue.  <a href='http://lime-technology.com/wiki/index.php/UnRAID_Topical_Index#HPA' target='_blank'>Sample of forum posts</a>  This issue mainly affects hard drives that are currently installed in, or have been in a system with a Gigabyte motherboard");
				break;
			}
		}
	}
}

####################################
# Check for flash drive filling up #
####################################

function flashDriveFull() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$flashFree = disk_free_space("/boot");
	$flashAvail = disk_total_space("/boot");

	$percentage = ($flashFree / $flashAvail) * 100;

	if ( $percentage < 10 ) {
		addWarning("<b>Flash Drive</b> is > 90% full","As very little information is stored on the flash drive in a properly configured system, you may have an improperly configured application which is storing an excessive amount of data onto the flash drive.  On a properly configured system with no extraneous files on the flash drive, it should only use at most 1G");
	}
}

#####################################################
# Check for improper entry into cacheFloor Settings #
#####################################################

function cacheFloorTests() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$vars = my_parse_ini_file($fixPaths['var.ini']);
	$suffix = strtolower(preg_replace('/[0-9]+/', '', $vars['shareCacheFloor']));

	if ( ( $suffix!= "" ) && ($suffix != "kb") && ($suffix != "mb") && ($suffix != "gb") && ($suffix != "tb") ) {
		addError("An improper suffix <b>$suffix</b> was use in the cache floor settings","The only valid suffixes allowed are KB, MB, GB, TB.  Fix it here: ".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
	}

# Check for cache drive exceeding its floor space ( and cache floor larger than cache drive )

	if ( is_dir("/mnt/cache") ) {
		$vars = my_parse_ini_file($fixPaths['var.ini']);
		$cacheFloor = $vars['shareCacheFloor'];

		$cacheFloorSuffix = strtolower(preg_replace('/[0-9]+/', '', $vars['shareCacheFloor']));
		$cacheFloor = str_replace($cacheFloorSuffix,"",$cacheFloor);
		$cacheFloor = $cacheFloor ? $cacheFloor : 0;
		switch ( $cacheFloorSuffix ) {
			case "":
				$multiplier = 1024;
				break;
			case "kb":
				$multiplier = 1000;
				break;
			case "mb":
				$multiplier = 1000000;
				break;
			case "gb":
				$multiplier = 1000000000;
				break;
			case "tb":
				$multiplier = 1000000000000;
				break;
			default:
				$multiplier = 1;
				break;
		}

		$cacheFloor = $cacheFloor * $multiplier;
		$cacheFree = disk_free_space("/mnt/cache");
		$cacheSize = disk_total_space("/mnt/cache");

		if ( $cacheFloor > $cacheSize ) {
			addError("<b>Cache Floor Size</b> (calculated to $cacheFloor bytes) is larger than your cache drive ($cacheSize bytes)","Change your cache floor settings here: ".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
		} else {
			if ( $cacheFree < $cacheFloor ) {
				addWarning("<b>Cache Disk</b> free space is less than the cache floor setting","All writes to your cache enabled shares are being redirected to your array.  If this is a transient situation, you can ignore this, otherwise adjust your cache floor settings here:".addLinkButton("Global Share Settings","/Settings/ShareSettings")." or adjust the frequency of the mover running:".addLinkButton("Scheduler Settings","/Settings/Scheduler")." or purchase a larger cache drive");
			}
		}
	}
}

####################################################
# Check for standard permissions of 0777 on shares #
####################################################

function sharePermission() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	foreach ($shareList as $share) {
	if ( ! is_dir("/mnt/user/$share") ) { continue; }
		$sharePermission = substr(sprintf("%o",fileperms("/mnt/user/$share")),-4);

		if ( $sharePermission != "0777" ) {
			addWarning("Share <b>$share</b> has non-standard permissions set","The permission on the share is currently set to <b>$sharePermission</b> (standard permissions are <b>0777</b>).  You may have trouble accessing this share locally and/or over the network due to this issue.  You should run the ".addLinkButton("New Permissions","/Tools/DockerSafeNewPerms")."tool to fix this issue.  (Don't know what these numbers mean?  Look <a href='http://permissions-calculator.org/decode/' target='_blank'>HERE</a>  NOTE that if this is your appdata share then you will need to manually run the command to fix this.  Ask for assistance on the forums");
		}
	}
}

###############################
# Check for unclean shutdowns #
###############################

function uncleanReboot() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_file($fixPaths['uncleanReboot']) ) {
		addError("<b>unclean shutdown</b> detected of your server",addButton("Acknowledge Error","acknowledgeUncleanReboot(this.id);")."Your server has performed an unclean shutdown.  You need to investigate adding a UPS (if this was due to a power failure) or if one is already present, properly setting up its settings".addLinkButton("UPS Settings","/Settings/UPSsettings")."  If this is a recurring issue (ie: random resets / crashes, etc) then you should run memtest from unRaid's boot menu for <b>at least</b> one complete pass.  If there are no memory issues, then you might want to look at putting this plugin into <b>troubleshooting mode</b> before posting for support on the unRaid forums.  Note: if you do not acknowledge this error you will continually get this notification.");
	}
}

##################################
# Check for out of memory errors #
##################################

function outOfMemory() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$output = exec("cat /var/log/syslog | grep -i 'Out of memory'");
	if ( is_file($fixPaths['OOMacknowledge']) ) {
		return;
	}
	if ($output) {
		addError("<b>Out Of Memory</b> errors detected on your server",addButton("Acknowledge Error","acknowledgeOOM(this.id);")."Your server has run out of memory, and processes (potentially required) are being killed off.  You should post your diagnostics and ask for assistance on the unRaid forums");
	}
}

###############################
# Check for out of mce errors #
###############################

function mceCheck() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$output = exec("cat /var/log/syslog | grep -i 'Machine check events logged'");
	if ( is_file($fixPaths['MCEacknowledge']) ) {
		return;
	}
	if ($output) {
		addError("<b>Machine Check Events</b> detected on your server",addButton("Acknowledge Error","acknowledgeMCE(this.id);")."Your server has detected hardware errors.  You should install mcelog via the NerdPack plugin, post your diagnostics and ask for assistance on the unRaid forums.  The output of mcelog (if installed) has been logged");
		if ( is_file("/usr/sbin/mcelog") ) {
			$filename = randomFile("/tmp");
			exec("mcelog > $filename 2>&1",$mcelog);
			$mcelog = explode("\n",file_get_contents($filename));
			foreach ($mcelog as $line) {
				logger($line);
			}
		} else {
			logger("mcelog not installed");
		}
	}
}

#########################
# Check for call traces #
#########################

function callTrace() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$files = dirContents("/var/log");
	foreach ($files as $file) {
		if ( startsWith($file,"syslog") ) {
			$output = exec("cat /var/log/$file | grep -i '\[ cut here \]'");
		  if ( is_file($fixPaths['Traceacknowledge']) ) {
			  return;
		  }
			if ($output) {
				addError("<b>Call Traces</b> found on your server",addButton("Acknowledge Error","acknowledgeTrace(this.id);")."Your server has issued one or more call traces.  This could be caused by a Kernel Issue, Bad Memory, etc.  You should post your diagnostics and ask for assistance on the unRaid forums");
				break;
			}
		}
	}
}

##########################
# Check for hack attacks #
##########################

function checkForHack() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$varLog = array_diff(scandir($fixPaths['syslogPath']),array(".",".."));

	foreach ($varLog as $syslog) {
		if ( startsWith($syslog,"syslog") ) {
			exec('cat '.$fixPaths['syslogPath'].'/'.$syslog.' | grep "Failed password for "',$output);
			exec('cat '.$fixPaths['syslogPath'].'/'.$syslog.' | grep "invalid password for "',$output);
		}
	}
	foreach ($output as $line) {
		$split = explode(" ",preg_replace('/\s+/',' ',$line));
		$month = $split[0];
		$day = $split[1];
		$errors[$month]['Month'] = $month;
		$errors[$month][$day]['Day'] = $day;
		$errors[$month][$day][] = $line;
	}
	if ( ! is_array($errors) ) { return; }
	foreach ($errors as $errorMonth) {
		$currentMonth = $errorMonth['Month'];
		foreach ($errorMonth as $errorDay) {
			if ( is_array($errorDay) ) {
				$currentDay = $errorDay['Day'];
				if ( count($errorDay) > $fixSettings['hacksPerDay'] ) {
					addError("<font size='3'>Possible <b>Hack Attempt</b> on $currentMonth $currentDay","On $currentMonth $currentDay there were <b>".count($errorDay)."</b> invalid login attempts.  This could either be yourself attempting to login to your server (SSH / Telnet) with the wrong user or password, or <b>you could be actively be the victim of hack attacks</b>.  A common cause of this would be placing your server within your router's DMZ, or improperly forwarding ports.  <font color='red'><b><h2>This is a major issue and needs to be addressed IMMEDIATELY</h2></b>NOTE: Because this check is done against the logged entries in the syslog, the only way to clear it is to either increase the number of allowed invalid logins per day (if determined that it is not a hack attempt) or to reset your server.  It is not recommended under any circumstance to ignore this error");
				}
			}
		}
	}
}

#####################################################
# Checks for moderation / blacklists on docker apps #
#####################################################

function checkForModeration() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( ! $dockerRunning ) { return; }

	$moderation = readJsonFile($fixPaths['moderation']);
	if ( ! is_array($moderation) ) { return; }

	$dockerClient = new DockerClient();
	$info = $dockerClient->getDockerContainers();

	foreach ( $info as $dockerApp ) {
		$image = $dockerApp['Image'];
		$Repository = str_replace(":latest","",$image);

		unset($comments);
		if ( $moderation[$image]['ModeratorComment'] ) {
			$comments = $moderation[$image];
		}
		if ( $moderation[$Repository]['ModeratorComment'] ) {
			$comments = $moderation[$Repository];
		}
		if ( ! $comments ) {
			continue;
		}
		if ( $comments['Blacklist'] ) {
			addWarning("Docker application <b>".$dockerApp['Name']."</b> has moderator comments listed","<b>".$dockerApp['Name']."</b> (".$dockerApp['Image'].") has the following comments: ".$comments['ModeratorComment']."  Additionally, this application has been blacklisted from Community Applications for that reason.");
			continue;
		}
		if ( $comments['Deprecated'] ) {
			addWarning("Docker application <b>".$dockerApp['Name']."</b> has moderator comments listed","<b>".$dockerApp['Name']."</b> (".$dockerApp['Image'].") has the following comments: ".$comments['ModeratorComment']."  This application has been deprecated from Community Applications for that reason.  While still functional, it is no longer recommended to utilize it.");
		} else {
			addOther("Docker application <b>".$dockerApp['Name']."</b> has moderator comments listed","<b>".$dockerApp['Name']."</b> (".$dockerApp['Image'].") has the following comments: ".$comments['ModeratorComment']."");
		}
	}
}

###########################################################
# Checks for plugins listed in CA as being not compatible #
###########################################################

function pluginNotCompatible() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$installedPlugins = array_diff(scandir("/var/log/plugins"),array(".",".."));
	$templates = readJsonFile($fixPaths['templates']);
	$moderation = readJsonFile($fixPaths['moderation']);
	
	$allApps = $templates['applist'];
	if ( ! $allApps ) { return; }

	foreach ($installedPlugins as $plugin) {
		unset($minVer);
		unset($maxVer);
		$pluginURL = getRedirectedURL(exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin pluginURL /var/log/plugins/$plugin"));

		foreach ( $allApps as $app ) {
			if ( $app['Plugin'] ) {
				if ( $app['PluginURL'] == $pluginURL ) {
					if ( $moderation[$pluginURL] ) {
						$app = array_merge($app,$moderation[$pluginURL]);
					}
					if ( ! versionCheck($app) ) {
						if ( $app['MinVer'] ) {
						  $minVer = "Minimum OS Version: {$app['MinVer']}";
						}
						if ( $app['MaxVer'] ) {
							$maxVer = "Maximum OS Version: {$app['MaxVer']}";
						}
						addWarning("<b>$plugin</b> Not Compatible with unRaid version $unRaidVersion","The author (or moderators of Community Applications) of the plugin template (<b>$pluginURL</b>) has specified that this plugin is incompatible with your version of unRaid ($unRaidVersion).  You should uninstall the plugin here:".addLinkButton("Plugins","/Plugins")." $minVer  $maxVer");
					}
					break;
				}
			}
		}
	}
}

##########################################
# Check for differences in webUI entries #
##########################################

function checkWebUI() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$templates = readJsonFile($fixPaths['templates']);
	if ( ! $templates ) { return; }
	if ( ! $dockerRunning ) { return; }
	$dockerClient = new DockerClient();
	$dockerTemplates = new DockerTemplates();
	$info = $dockerClient->getDockerContainers();
	$myTemplates = $dockerTemplates->getAllInfo();

	foreach ($info as $dockerApp) {
		foreach ($templates['applist'] as $template) {
			$image = $dockerApp['Image'];
			$Repository = explode(":",$image);

			if ( ( $image == $template['Repository'] ) || ( $Repository[0] == $template['Repository'] ) ) {
				$userTemplate = readXmlFile($myTemplates[$dockerApp['Name']]['template']);
				$templateWebUI = trim($userTemplate['WebUI']);
				if ( is_array($template['WebUI']) ) {
					$defaultUI = $templateWebUI;   # IE: no UI was specified
				} else {
					$defaultUI = $template['WebUI'];
				}
				$defaultUI = trim($defaultUI);
				$defaultUI     = str_replace("&amp;","&",$defaultUI);
				$templateWebUI = str_replace("&amp;","&",$templateWebUI);
				if ( htmlspecialchars($templateWebUI) != htmlspecialchars($defaultUI) ) {
					addWarning("Docker application <b>".$dockerApp['Name']."</b> does not have the same webUI interface as what the template author specified","The webUI the author specified is $defaultUI and the webUI you are using is $templateWebUI.  If you are specifying an absolute port (IE: <b>PORT:xxxx</b> is missing from your specified webUI address, then you will have issues should you ever have to change the host port on the docker applications's settings.  In the same vein, specifying an absolute IP address in the webUI will cause issues should your server's IP address ever change.  Note that the PORT:xxxx refers to the <b>Container's</b> port, not the host port.  There may however be perfectly valid reasons to change the default webUI entry on the application.  You can fix this problem here:".addLinkButton("Docker","/Docker"));
				}
				break;
			}
		}
	}
}

###################################################
# Check for cache only shares, but no cache drive #
###################################################

function cacheOnlyNoCache() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( is_dir("/mnt/cache") ) { return; }
	foreach ($shareList as $share) {
		if ( ! is_file("/boot/config/shares/$share.cfg") ) {
			continue;
		}
		$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
		if ( $shareCfg['shareUseCache'] == "only" ){
			addError("Share <b>$share</b> set to use cache only, but the cache drive is not present","Setting a share to be cache-only, but without a cache drive present can have unpredictable results at best, and in some cases can prevent proper operation of any application attempting to use that share.  Fix it here:".addLinkButton("$share Settings","/Shares/Share?name=$share")."  Alternatively, depending upon your version of unRaid, you may have to manually delete this file: <b>/config/shares/$share.cfg</b> from the flash drive to fix this issue");
		}
	}
}

################################################
# Check for sharenames the same as a disk name #
################################################

function shareNameSameAsDiskName() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$disks = my_parse_ini_file($fixPaths['disks.ini'],true);

	foreach ($disks as $disk) {
		if ( $disk['name'] == "parity" ) { continue; }
		if ( is_dir("/mnt/user/".$disk['name']) ) {
			addError("Share <b>".$disk['name']."</b> is identically named to a <b>disk share</b>","While there *may be* (doubtful) valid use cases for having a share named identically to a disk share, this is only going to cause some confusion as if disk shares are enabled, then you will have duplicated share names, and possibly if disk shares are not enabled, then you might not be able to gain access to the share.  This is usually caused by moving the contents of one disk to another (XFS Conversion?) and an improperly placed <b>slash</b>.  The solution is to move the contents of the user share named ".$disk['name']." to be placed within a validly named share.  Ask for assistance on the forums for guidance on doing this.");
		}
	}
}

#############################################
# Check for no CPU scaling driver installed #
#############################################

function noCPUscaling() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$output = exec("cpufreq-info -d");
	$output = trim($output);

	if ( ! $output ) {
		addOther("CPU possibly will not throttle down frequency at idle","Your CPU is running constantly at 100% and will not throttle down when it's idle (to save heat / power).  This is because there is currently no CPU Scaling Driver Installed.  Seek assistance on the unRaid forums with this issue");
	}
}

#################################################################################
# Check for extra parameters within Repository (deprecated way of doing things) #
#################################################################################

function extraParamInRepository() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( ! $dockerRunning ) { return; }

	$dockerTemplates = new DockerTemplates();
	$info = $dockerTemplates->getAllInfo();
	foreach ($info as $container) {
		if ( is_file($container['template']) ) {
			$xmlTemplate = readXmlFile($container['template']);

			$repository = trim($xmlTemplate['Repository']);
			if ( strpos($repository," ") ) {
				addWarning("Docker application <b>".$xmlTemplate['Name']."</b> appears to have extra parameters contained within its <b>Repository</b> entry","Adding extra parameters by including them in the repository (eg: --cpuset-cpus) is deprecated, and may impact the ability of dockerMan to correctly manage your application (eg: unable to update, etc).  The proper way to add extra parameters is through the <b>Extra Parameters</b> section when you edit the container (you will have to hit Advanced Settings)  Fix this here: ".addLinkButton("Edit ".$xmlTemplate['Name'],"/Docker/UpdateContainer?xmlTemplate=edit:".$container['template']));
			}
		}
	}
}

#####################################################
# Checks for multiple .key files on the flash drive #
#####################################################

function multipleKey() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	exec("ls --file-type -1 /boot/config/*.key",$keyfiles);
	if ( ( count($keyfiles) == 2 ) && ( ( $keyfiles[0] == "/boot/config/Trial.key" ) || ( $keyfiles[1] == "/boot/config/Trial.key" ) ) ) {
		return;
	}
	if (count($keyfiles) > 1) {
		addWarning("Multiple registration keys found","While unRaid will operate with multiple .key (registration files) within the config folder on the flash drive (ie: For simplicity sake you have multiple valid registrations and are storing them all within the config folder, you will run into problems if you ever need to transfer one of the registrations to another USB stick, as unRaid will not know which registration file to transfer, and the incorrect registration may get blacklisted.  You should investigate and determine which key belongs to which USB stick and only have that particular key on that stick");
	}
}

#################################################################
# Checks for NerdPack installing inotifytools on unRaid 6.3RC6+ #
#################################################################
function inotifyToolsNerdPack() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.3.0-rc6", "<") ) {
		return;
	}
	$nerdPackCFG = @parse_ini_file("/boot/config/plugins/NerdPack/NerdPack.cfg");
	if ( ! $nerdPackCFG ) {
		return;
	}
	$cfgEntries = array_keys($nerdPackCFG);

	foreach ($cfgEntries as $cfg) {
		if ( ! startsWith($cfg,"inotify-tools") ) {
			continue;
		}
		if ( $nerdPackCFG[$cfg] == "yes" ) {
			addWarning("inotify-tools set to install","inotify-tools is set to be installed by the NerdPack plugin.  This package is now included in unRaid 6.3RC6+, and NerdPack should be set to not install it.  Fix this here: ".addLinkButton("NerdPack Settings","/Settings/NerdPack"));
		}
	}
}

#######################################
# Check for exhausted inotify watches #
#######################################
function inotifyExhausted() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( ! is_file("/usr/bin/inotifywatch") ) {
		return;
	}

	$filename = randomFile("/tmp");
	file_put_contents($filename,"doesn't matter");

	$inotifyResult = passthru("inotifywatch $filename -t 1 > /dev/null 2>&1",$returnValue);
	if ( $returnValue ) {
		addWarning("Possibly out of inotify watches","Many plugins (dynamix File Integrity, File Activity, Ransomware Protection and others utilize inotify watches to run.  Your system is returning an error when attempting to watch a file.  You may need to increase the maximum number of watches available (usually can be set within the plugin's settings");
	}
	@unlink($filename);
}

################################
# Check for IRQ x nobody cared #
################################
function nobodyCared() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	exec("cat /var/log/syslog | grep -i -o 'irq .* nobody cared'",$out);
	if ( $out ) {
		foreach ($out as $output) {
			addWarning("$output found on your server","You should post your diagnostics and seek assistance from the forums before deciding to ignore this error.  The interrupts in use have been logged to assist in diagnosis");
		}
		exec("cat /proc/interrupts",$interrupts);
		logger("Interrupts listed as being in use:");
		foreach($interrupts as $line) {
			logger($line);
		}
	}
}
############################################
# Check for reiserfs formatted cache drive #
############################################
function reiserCache() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$disks = parse_ini_file("/var/local/emhttp/disks.ini",true);
	if ( ($disks['cache']['fsType'] == "reiserfs") && ( ! $disks['cache']['rotational'] ) ) {
		addWarning("SSD Cache Drive formatted as reiserFS","You have an SSD cache drive which has been formatted as reiserFS.  ReiserFS does not support trim, so you will encounter performance issues.  You should convert the cache drive's format to XFS (or to BTRFS if you are planning a future cache-pool)");
	}
}
################################################
# Check for SSD Cache, but not SSD trim plugin #
################################################
function SSDcacheNoTrim() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$disks = parse_ini_file("/var/local/emhttp/disks.ini",true);
	if ( ! is_array($disks['cache']) ) { return; }
	if ( (! $disks['cache']['rotational']) && (! is_file("/var/log/plugins/dynamix.ssd.trim.plg")) && ( $disks['cache']['status'] != "DISK_NP") ) {
		addWarning("Dynamix SSD Trim Plugin Not installed","Your cache drive is an SSD Drive, but you do not have the Dynamix SSD Trim plugin installed.  Your performance will suffer.  Install the plugin via the Apps Tab (Community Applications)");
	}
}

##################################
# Check for missing template URL #
##################################
function templateURLMissing() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	function my_xml_decode($string) {
		return strval(html_entity_decode($string, ENT_XML1, 'UTF-8'));
	}

	if ( $dockerRunning ) {
		$templates = readJsonFile($fixPaths['templates']);
		$dockerClient = new DockerClient();
		$dockerTemplates = new DockerTemplates();
		$info = $dockerClient->getDockerContainers();
		$myTemplates = $dockerTemplates->getAllInfo();

		if ( is_array($templates['applist']) ) {
			$allApps = $templates['applist'];

			foreach ($info as $dockerInstalled) {
				$dockerImage = $dockerInstalled['Image'];
				foreach ($allApps as $app) {
					if (empty($app['TemplateURL'])) { continue; }
					if ( ($app['Repository'] === str_replace(":latest","",$dockerImage) ) || ($app['Repository'] === $dockerImage) ) {
						$name = $dockerInstalled['Name'];
						$xmlfile = $myTemplates[$name]['template'];
						$template = simplexml_load_file($xmlfile);
						$template_url = my_xml_decode($template->TemplateURL);
						$warning = null;
						if (empty($template_url)) {
							$warning = "Template URL for docker application <b>".$dockerInstalled['Name']."</b> is missing.";
						}
						else if ($template_url !== $app['TemplateURL']) {
							$warning = "Template URL for docker application <b>".$dockerInstalled['Name']."</b> is not the as what the template author specified.";
						}
						if (!empty($warning)) {
							addWarning($warning,  "The template URL the author specified is ".$app['TemplateURL'].". The template can be updated automatically with the correct URL. <input type='button' id='apply_fix' value='Apply Fix' onclick='openBox(\"/plugins/fix.common.problems/scripts/applyFix.php?cmd=templateURL&template=".$xmlfile."&url=".$app['TemplateURL']."\",\"Docker Application Template URL Fix\",490,430);'>");
						}
					}
				}
			}
		} else {
			addOther("Could not perform <b>docker application port</b> tests","The download of the application feed failed.");
		}
	}
}

function marvelControllerTest() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	exec("lspci -k",$testResults);
	foreach ($testResults as $line) {
		if (strpos($line,"Kernel driver in use: mvsas") ) {
			addWarning("Marvel Hard Drive Controller Installed","It appears that your server has a Marvel based hard drive controller installed within it.  <b>Some</b> users with Marvel based controllers exhibit random drives dropping offline, recurring parity errors during checks etc.  This tends to be exacberated if VT-D / IOMMU is enabled in the BIOS.  Generally, LSI based controllers would be preferred over Marvel based controllers because of these issues.  <strong>Note that these issues are out of Limetech's hands</strong>.  Depending upon the exact combination of hardware present in your server, you may not have any problems whatsoever.  <strong><em>If you have no problems, then this warning can be safely ignored</em></strong>, but future versions of unRaid (and later Kernel versions) may (or may not) present you with the previously mentioned issues."); 
			break;
		}
	}
}

function breadTest() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	$syslogs = dirContents("/var/log");
	foreach ($syslogs as $syslog) {
		if ( startsWith($syslog,"syslog") ) {
			foreach (logline($syslog) as $logline) {
				if ( strpos($logline,"Directory bread") ) {
					if ( strpos($logline,"sda1") ) {
						addError("Directory Bread Errors found","Directory Bread errors have been found.  This usually means (assuming that your flash drive didn't physically come disconnected) that your flash drive has dropped offline.  The most common solution to this is to try a different USB controller for your flash drive (USB2<-->USB3)");
						$foundFlag = true;
						break;
					}
				}
			}
		}
		if ( $foundFlag ) {
			break;
		}
	}
}	

function logline($filename) {
	$file = fopen("/var/log/$filename","r");
	if ( ! $file ) {
		die("$filename does not exist or cannot be opened");
	}
	while (($line = fgets($file)) !== false) {
		yield $line;
	}
	fclose($file);
}

function Ryzen63() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;

	if ( version_compare($unRaidVersion,"6.4.0-rc1","<") ) {
		$output = exec("lscpu | grep Ryzen");
		if ( $output ) {
			addWarning("Ryzen CPU on unRaid 6.3","It is <b>highly</b> recommended to only use a Ryzen CPU if you are running unRaid version 6.4+");
		}
	}
}
			
function lessThan2G() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList, $unRaidVersion;
	
	$file = trim(str_replace("MemTotal:","",exec("cat /proc/meminfo | grep MemTotal:")));
	$raw = explode(" ",$file);
	
	if ($raw[0] < 1500000 ) {
		addWarning("Less than 2GB Memory Installed","Your system currently has {$raw[0]} {$raw[1]} memory installed.  For a trouble-free experience with unRaid, the functional minimum for a basic NAS server is 2GB of memory.  You should add more memory");
	}
}

function checkDockerCompatible() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( ! $dockerRunning ) { return; }

	$moderation = readJsonFile($fixPaths['moderation']);
	if ( ! is_array($moderation) ) { return; }
	$templates = readJsonFile($fixPaths['templates']);
	$allApps = $templates['applist'];
	if ( ! is_array($allApps) ) { return; }

	$dockerClient = new DockerClient();
	$info = $dockerClient->getDockerContainers();

	foreach ( $info as $dockerApp ) {
		$image = $dockerApp['Image'];
		$Repository = explode(":",$image);
		$index = searchArray($allApps,"Repository",$Repository[0]);
		if ( $index === false ) { continue;}
		$template = $allApps[$index];
		if ( $moderation[$Repository[0]] ) {
			$template = array_merge($template,$moderation[$Repository[0]]);
		}
		if ( ! versionCheck($template) ) {
			unset($verMsg);
			if ( $template['MinVer'] ) {
				$verMsg = "Minimum OS Version: unRaid v{$template['MinVer']} ";
			}
			if ( $template['MaxVer'] ) {
				$verMsg .= "Maximum OS Version: unRaid v{$template['MaxVer']}";
			}
			addWarning("{$template['Name']} ({$template['Repository']}) Incompatible","{$template['Name']} has been flagged as being incompatible with your version of unRaid.  $verMsg");
		}
	}
}

function CPUoverheat() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	$syslogs = glob("/var/log/syslog*");
	foreach ( $syslogs as $syslog ) {
		if (exec("cat $syslog | grep 'Package temperature above threshold'") ) {
			addWarning("CPU Overheating","Your CPU is overheating and has been throttled down (This may however be a transient occurance).  You may need to clean your filters and/or increase your cooling capacity.  Investigate here: ".addLinkButton("SYSLOG","Tools/Syslog"));
		}
	}
}

function statsButNoPreclear() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( is_file("/var/log/plugins/statistics.sender.plg") && ( ! is_file("/var/log/plugins/preclear.disk.plg") ) ) {
		addWarning("Statistics installed","The statistics plugin is installed, but the preclear disk plugin is not installed.  There is no reason to have statistics installed but not Preclear.  It is recommended to uninstall statistics here: ".addLinkButton("Plugins","/Plugins"));
	}
}

function moverLogging() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

	if ( ! is_dir("/mnt/cache") ) { return; }
	
	$iniFile = @parse_ini_file("/boot/config/share.cfg",true);
  if ( strtolower($iniFile['shareMoverLogging']) == "yes" ) {
		addOther("Mover logging is enabled","It is generally recommended to disable mover logging as unless there are problems with the moving, it performs no useful function and merely fills up the syslog and makes other issues harder to diagnose.   Disable it here: ".addLinkButton("Scheduler","/Settings/Scheduler")."  (Go To Mover Settings)");
  }
}	

function zenStates() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( version_compare($unRaidVersion,"6.4.0-rc1","<") ) {
		return;
	}
	$output = exec("lscpu | grep Ryzen");
	if ( $output ) {
		$output = exec("cat /boot/config/go | grep  /usr/local/sbin/zenstates");
		if ( ! $output ) {
			addWarning("You have a Ryzen CPU, but <font color='purple']>Zenstates not installed.","Adding zenstates to your 'go' file can improve the stability of your system.  See this thread for more details <a href='https://lime-technology.com/forums/topic/66327-unraid-os-version-641-stable-release-update-notes/' target='_blank'>6.4.1 Upgrade Notes</a>");
		}
	}
}

function phpWarnings() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( ! is_file("/var/log/plugins/tips.and.tweaks.plg") ) { return; }
	$tweaks = @parse_ini_file("/boot/config/plugins/tips.and.tweaks/tips.and.tweaks.cfg");
	if ( $tweaks['PHP_WARNINGS'] == "yes" ) {
		addOther("PHP Warnings are enabled","While this is not an error per se, if you are not a plugin developer, there is generally zero reason to enable this option, and under certain circumstances it can do nothing but spam your log with PHP warnings.  Fix it here: ".addLinkButton("Tips And Tweaks Settings","/Settings/TipsAndTweaks")."  (Go to Tweaks)");
	}
}

function invalidIncludedDisk() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( ! is_dir("/mnt/user") ) { return; }
	
	foreach ($shareList as $share) {
		$shareCfg = my_parse_ini_file("/boot/config/shares/$share.cfg");
		$includedDisks = explode(",",$shareCfg['shareInclude']);
		foreach ($includedDisks as $disk) {
			if ( ! is_dir("/mnt/$disk") ) {
				addError("Share <b>$share</b> has $disk set in its included disk settings","$disk is not defined / installed in the array.  This will cause errors when writing to the array.  Fix it here: ".addLinkButton("$share Settings","/Shares/Share?name=$share")."  NOTE:  Because of how the UI works, $disk will not appear in on this page.  You will need to make a change (any change), then revert the change and hit apply to fix this issue");
			}
		}
	}
}

function CPUSet() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( ! $dockerRunning ) { return; }
	if ( ! version_compare($unRaidVersion,"6.5.3",">") ) { return; }

	$dockerTemplates = new DockerTemplates();
	$info = $dockerTemplates->getAllInfo();
  
	$userTemplates = glob("/boot/config/plugins/dockerMan/templates-user/*.xml");
	foreach ($userTemplates as $template) {
		$xml = @simplexml_load_file($template);
		$Name = (string)$xml->Name;
		if ( ! $info[$Name] ) continue;
		if (strlen($xml->CPUset) && strpos($xml->ExtraParams,"cpuset-cpus")) {
			addError("Docker Application $Name has duplicated entries for CPU pinning","Under unRaid version 6.6+, CPU pinning for docker applications via the extra parameters has been deprecated.  On this template, you have CPU pinning (--cpuset-cpus) contained in the extraparameters section of the template, and have also checked off one or more CPU's to pin the container to via the UI.  You should edit the container via the ".addLinkButton("Docker Tab","/Docker")." and remove the pinning from the extra parameters field");
		}
		if (strpos($xml->ExtraParams,"cpuset-cpus")) {
			addWarning("Docker Application $Name has CPU cores pinned via the extra parameters (--cpuset-cpus)","Under unRaid version 6.6+, CPU pinning for docker applications should be handled via the CPU Pinning section.  Fix it here: ".addLinkButton("Docker Tab","/Docker")." and remove the CPU pinning from the extra parameters section.  Note that if you DO NOT fix this, then you will NOT be able to change CPU pinning for the application via the GUI");
	}	}
}

function isolatedCPUdockerCollision() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( ! $dockerRunning ) { return; }
	if ( ! version_compare($unRaidVersion,"6.5.3",">") ) { return; }
	
	$cmdLine = explode(" ",file_get_contents("/proc/cmdline"));
#	$cmdLine = explode(" ","BOOT_IMAGE=/bzimage pcie_acs_override=downstream isolcpus=1,3 initrd=/bzroot,/bzroot-gui");
	
	foreach ($cmdLine as $option) {
		if (strpos($option,"isolcpus") !== false ) {
			$isolatedCPUs = explode("=",$option)[1];
		}
	}
	$cpus = explode(",",$isolatedCPUs);
	foreach ($cpus as $test) {
		if (strpos($test,"-")) {
			$range = explode("-",$test);
			for ( $j = $range[0]; $j <= $range[1]; $j++ ) {
				$iso[$j] = true;
			}
		} else {
			$iso[$test] = true;
		}
	}

	$dockerTemplates = new DockerTemplates();
	$info = $dockerTemplates->getAllInfo();
	
	foreach ($info as $name=>$container) {
		$flag = 0;
		foreach (explode(",",$container['cpuset']) as $dockerCPU) {
			if ( $iso[$dockerCPU] ) {
				$flag++;
			}
		}
		if ( $flag > 1 ) {
			addError("Docker Application $name has multiple CPUs pinned to isolated CPUs","Unless you know exactly what you are doing, pinning a docker application to multiple isolated CPUs will only allow the application to execute on a single isolated core ".addLinkButton("CPU Set","/Settings/CPUset")." and adjust either the Isolated CPUs or the application's CPU pinning");
		}
	}
}

function testXML() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	require_once("/usr/local/emhttp/plugins/fix.common.problems/include/xmlHelpers.php");
	
	foreach ( ["/boot/config/plugins/dockerMan/templates","/boot/config/plugins/dockerMan/templates-user"] as $directory) {
		$contents = glob("$directory/*.xml");
		foreach ($contents as $xmlfile) {
			$xml = file_get_contents($xmlfile);
			$o = TypeConverter::xmlToArray($xml,TypeConverter::XML_GROUP);
			if ( ! $o ) {
				addError("$xmlfile corrupted","A corrupted xml file will wind up having unRaid display numerous php errors in various tabs on the UI.  You will need to delete or edit and fix the file manually");
			}
		}
	}
}

function writeCacheDisabled() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;
	
	$disksIni = my_parse_ini_file($fixPaths['disks.ini'],true);	
	foreach ($disksIni as $drive) {	
		if ( $fixSettings['disableSpinUp'] == "true" ) {
			if ( stripos($drive['color'],"blink") ) {
				continue;
			}
		}
		if ( ! $drive['device'] || ($drive['status'] == "DISK_NP") ) {
			continue;
		}
		if ( $drive['name'] == "flash" ) {
			continue;
		}
		
		$writeCache = exec("/usr/sbin/hdparm -W /dev/{$drive['device']} | grep 'write-caching' 2>/dev/null");
		
		if (stripos($writeCache,"off")) {
			addWarning("Write Cache is disabled on {$drive['name']}","You may experience slow read/writes to {$drive['name']}.  Write Cache should be enabled for better results.  Read this post ( <a href='https://forums.unraid.net/topic/46802-faq-for-unraid-v6/page/2/?tab=comments#comment-755621'>https://forums.unraid.net/topic/46802-faq-for-unraid-v6/page/2/?tab=comments#comment-755621</a> for more information.  NOTE: If this drive is connected to your server via USB, then this test and the fix may or may not work / be accurate as USB support for smartctl and hdparm is hit and miss");
		}
	}
}
	
function updatePluginSupport() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;
	
	$raw_templates = readJsonFile($fixPaths['templates']);
	$templates = $raw_templates['applist'];
	if ( ! is_array($templates) ) { return; }
	$plugins = glob("/boot/config/plugins/*.plg");
	foreach ($plugins as $plugin) {
		$pluginURL = plugin("pluginURL",$plugin);
		$pluginEntry = searchArray($templates,"PluginURL",$pluginURL);
		if ( $pluginEntry === false ) {
			$pluginEntry = searchArray($templates,"PluginURL",str_replace("https://raw.github.com/","https://raw.githubusercontent.com/",$pluginURL));
		}
		if ( $pluginEntry !== false && $templates[$pluginEntry]['PluginURL']) {
			$xml = simplexml_load_file($plugin);
			if ( ! $templates[$pluginEntry]['Support'] ) {
				continue;
			}
			if ( @plugin("support",$plugin) !== $templates[$pluginEntry]['Support'] ) {
				// remove existing support attribute if it exists
				if ( @plugin("support",$plugin) ) {
					$existing_support = $xml->xpath("//PLUGIN/@support");
					foreach ($existing_support as $node) {
						unset($node[0]);
					}
				}
				$xml->addAttribute("support",$templates[$pluginEntry]['Support']);
				$dom = new DOMDocument('1.0');
				$dom->preserveWhiteSpace = false;
				$dom->formatOutput = true;
				$dom->loadXML($xml->asXML());
				file_put_contents($plugin, $dom->saveXML()); 
				addOther("<b>".plugin("name",$plugin)."</b> Support Updated","The support thread has been updated to {$templates[$pluginEntry]['Support']}");
			}
		}
	}
}

function flashSyslog() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;
	
	$rsyslogCFG = @file_get_contents("/boot/config/rsyslog.conf");
	if ( strpos($rsyslogCFG,"/boot/logs/syslog") ) {
		addWarning("<b>Syslog mirrored to flash</b>","The syslog is currently mirrored to the flashdrive.  This option should only be used for troubleshooting, and extended use of this option may fill up your flash drive, increase the number of writes to it etc.  It should be disabled if you are not actively troubleshooting an issue with your server.  Change this here: ".addLinkButton("Syslog Server","/SyslogSettings"));
	}
}

function unassignedDevicesPlus() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( is_file("/var/log/plugins/unassigned.devices.plg") && ! is_file("/var/log/plugins/unassigned.devices-plus.plg") ) {
		addWarning("Unassigned Devices <b>Plus</b> not installed","If you use unassigned devices to mount devices that are formatted with exFAT (ie: flash drives) or HSF+, then you also require UD+ to be installed (available within Apps).  If you only use UD to mount disks formatted as BTRFS, XFS, or network shares via SMB/NFS, then this is not required and this message can be safely ignored");
	}
	if ( ! is_file("/var/log/plugins/unassigned.devices.plg") && is_file("/var/log/plugins/unassigned.devices-plus.plg") ) {
		addOther("Unassigned Devices <b>Plus</b> installed","Unassigned Devices Plus is installed, but Unassigned Devices is not.  There is zero point in having Unassigned Devices Plus installed without having Unassigned Devices installed.  You should uninstall Unassigned Devices Plus or install Unassigned Devices");
	}		
}	

function sysdream() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( version_compare($unRaidVersion,"6.7.2",">") && version_compare($unRaidVersion,"6.8.1","<") ) {
		addError("Vulnerable unRaid Version","You are currently running unRaid version $unRaidVersion.  This version is susceptible to a would be attacker being able to bypass the login credentials and potentially run inject code into your server's software.  You should upgrade your OS to 6.8.2+.  See <a href='https://forums.unraid.net/topic/88253-critical-security-vulnerabilies-discovered/' target='_blank'>HERE</a> for more details");
	}
	if ( version_compare($unRaidVersion,"6.6.0",">=") && version_compare($unRaidVersion,"6.7.2","<=") ) {
		if ( ! is_file("/var/log/plugins/sysdream.plg") ) {
			addError("Vulnerable unRaid Version","You are currently running unRaid version $unRaidVersion.  This version is susceptible to a would be attacker being able to bypass the login credentials and potentially run inject code into your server's software.  There is a plugin available within the Apps tab (sysdream) to mitigate this security vulnerabilty that should be installed.  See <a href='https://forums.unraid.net/topic/88253-critical-security-vulnerabilies-discovered/' target='_blank'>HERE</a> for more details");
		}
	}
}

function caNotifications() {
	global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList,$unRaidVersion;

	if ( is_file("/var/log/plugins/community.applications.plg") ) {
		$caNotify = parse_plugin_cfg("community.applications");
		if ( $caNotify['notifications'] == "no" ) {
			addOther("Background notifications not enabled","CA has background notifications to alert you if you have known malware installed on your server, or if your server is vulnerable to certain security issues, etc.  It is highly advised to enable this scanning via ".addLinkButton("CA Settings","/Apps/ca_settings"));
		}
	}
}	
?>