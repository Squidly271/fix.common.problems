#!/usr/bin/php
<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2022, Andrew Zawadzki    #
#                                                             #
###############################################################

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/tests.php");

exec("mkdir -p ".$fixPaths['tempFiles']);
libxml_use_internal_errors(true);

##################################################################################################################
#                                                                                                                #
# Global variables.  All test functions are standalone, but for ease of use, the following globals are available #
#                                                                                                                #
##################################################################################################################

logger("Fix Common Problems Version ".exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin version /var/log/plugins/fix.common.problems.plg"));
if ( $argv[1] == "troubleshoot" ) {
	$troubleshooting = true;
/* 	logger("Fix Common Problems: Troubleshooting scan running");
	$uptime = exec("uptime");
	logger("Fix Common Problems: Uptime: $uptime");
	unset($output);
	exec("free",$output);
	foreach ($output as $line) {
		logger("Fix Common Problems: $line");
	}

	logger("Fix Common Problems: ps aux output (only CPU % > 0)");
	unset($output);
	exec("ps aux",$output);
	logger("Fix Common Problems: ".$output[0]);
	unset($output[0]);

	foreach ($output as $line) {
		$statusLine = preg_replace('!\s+!', ' ', $line);
		$test = explode(" ",$statusLine);

		if ( $test[2] > 0 ) {
			logger("Fix Common Problems: $line");
		}
	}
	unset($output);
	exec("sensors -A",$output);
	logger("Fix Common Problems: Sensors output:");
	foreach ($output as $line) {
		logger("Fix Common Problems: ".escapeshellarg($line));
	}


 */
} else {
	$disableNotifications = $argv[1];
}

$autoUpdateOverride              = is_file("/boot/config/plugins/fix.common.problems/autoupdate-warning");
$developerMode                   = is_file("/boot/config/plugins/fix.common.problems/developer");
$communityApplicationsInstalled  = is_file("/var/log/plugins/community.applications.plg");
$dockerRunning                   = is_dir("/var/lib/docker/tmp");
$unRaidVersion                   = unRaidVersion();


$fixSettings = readJsonFile($fixPaths['settings']);
$ignoreList = readJsonFile($fixPaths['ignoreList']);

if ( ! $fixSettings['notifications'] ) { $fixSettings['notifications'] = "all"; }
if ( ! $fixSettings['disableSpinUp'] ) { $fixSettings['disableSpinUp'] = "true"; }
if ( ! $fixSettings['hacksPerDay'] ) { $fixSettings['hacksPerDay'] = 10; }
if ( ! $fixSettings['logIgnored']) { $fixSettings['logIgnored'] = "yes"; }

# download updated appfeed if necessary

publish("fixscan",json_encode(array("test"=>"Downloading Support Files"),JSON_UNESCAPED_SLASHES));

if ( is_file($fixPaths['templates']) ) {
	$templates = readJsonFile($fixPaths['templates']);
	$updatedTime = $templates['last_updated_timestamp'];
	$tempFile = randomFile("/tmp/fix.common.problems");
	download_url($fixPaths['application-feed-last-updated'],$tempFile);
	$newList = readJsonFile($tempFile);
	@unlink($tempFile);
	if ( $newList['last_updated_timestamp'] != $templates['last_updated_timestamp'] ) {
		download_url($fixPaths['application-feed'],$fixPaths['templates']);
		$templates = readJsonFile($fixPaths['templates']);
	}
} else {
	download_url($fixPaths['application-feed'],$fixPaths['templates']);
	$templates = readJsonFile($fixPaths['templates']);
}

@unlink($fixPaths['moderation']);
download_url($fixPaths['moderationURL'],$fixPaths['moderation']);


# start main

if ( $troubleshooting ) {
	varLogFilled();
	rootfsFull();
} else {
	$tests = array("HPApresent",
	"isArrayStarted",
	"impliedArrayFilesOnCache",
	"cacheOnlyFilesOnArray",
	"arrayOnlyFilesOnCache",
	"pluginUpdateCheck",
	"dockerUpdateCheck",
	"autoUpdateCheck",
	"sameShareDifferentCase",
	"outsideCommunication",
	"dockerImageOnDiskShare",
	"dockerAppdataCacheOnly",
	"disabledDisksPresent",
	"missingDisksPresent",
	"readErrorsPresent",
	"fileSystemErrors",
/*	"SSDinArray", Not necessary per JorgeB */
	"pluginsUpToDate",
	"dockerUpToDate",
	"dockerConfigUserShare",
	"varLogFilled",
	"dockerImageFull",
	"rootfsFull",
	"scheduledParityChecks",
	"shareIncludeExcludeSet",
	"shareIncludeExcludeSameDisk",
	"UDmountedSlaveMode",
	"FTPrunning",
	"checkNotifications",
	"blacklistedPluginsInstalled",
	"illegalShareName",
	"writeToDriveTest",
	"flashDriveFull",
	/* "cacheFloorTests", */
	"checkForHack",
	"checkForModeration",
	"pluginNotCompatible",
	"cacheOnlyNoCache",
	"shareNameSameAsDiskName",
	"extraParamInRepository",
	"outOfMemory",
	"mceCheck",
	"inotifyExhausted",
	"reiserCache",
	"SSDcacheNoTrim",
//	"templateURLMissing",
	"marvelControllerTest",
	"breadTest",
	"lessThan2G",
	"checkDockerCompatible",
	"CPUoverheat",
	"statsButNoPreclear",
	"moverLogging",
	"phpWarnings",
	"invalidIncludedDisk",
	"CPUSet",
	"isolatedCPUdockerCollision",
	"testXML",
	"writeCacheDisabled",
	"updatePluginSupport",
	"flashSyslog",
	"unassignedDevicesPlus",
	"sysdream",
	"caNotifications",
	"legacyVFIO",
	"checkBonding",
	"checkSameNetwork",
	"extraPackages",
	"authorizedKeysInGo",
	"reservedUserName",
	"rootPassword",
	"xmrig",
	"shareSpace69",
	"testTLD",
	"unknownPluginInstalled",
	"testDockerOptsIp",
	"wrongCachePoolFiles",
	"corruptFlash"
	);
	foreach ($tests as $test) {
		if ( $disableNotifications ) {
			$currentTest++;
			publish("fixscan",json_encode(array("test"=> intval($currentTest / count($tests) * 100)."% $test"),JSON_UNESCAPED_SLASHES));
		}
		$test();
	}
}

if ( $ignored && ( $fixSettings['logIgnored'] != "yes") ) {
	logger("Fix Common Problems: Ignored errors / warnings / other comments found, but not logged per user settings");
}


if ( ! $errors && ! $warnings && ! $otherWarnings && ! $ignored ) {
	@unlink($fixPaths['errors']);
} else {
	$allErrors['errors'] = $errors;
	$allErrors['warnings'] = $warnings;
	$allErrors['other'] = $otherWarnings;
	$allErrors['ignored'] = $ignored;

	writeJsonFile($fixPaths['errors'],$allErrors);
	if ( $errors ) {
		foreach ($errors as $error) {
			$message .= "* **".strip_tags($error['error'])."** \n";
		}
	}
	if ( $warnings ) {
		foreach ($warnings as $warning) {
			$message .= "* ".strip_tags($warning['error'])." \n";
		}
	}
	$unRaidSettings = parse_ini_file("/usr/local/emhttp/state/var.ini");
	if ( ! $disableNotifications ) {
		if ( $errors ) {
			if ( $fixSettings['notifications'] != "disabled" ) {
				notify("Fix Common Problems - {$unRaidSettings['NAME']}","Errors have been found with your server ({$unRaidSettings['NAME']}).","Investigate at Settings / User Utilities / Fix Common Problems",$message,"alert");
			}
		} else {
			if ( $warnings ) {
				if ($fixSettings['notifications'] != "errors" ) {
					notify("Fix Common Problems - {$unRaidSettings['NAME']}","Warnings have been found with your server.({$unRaidSettings['NAME']})","Investigate at Settings / User Utilities / Fix Common Problems",$message,"warning");
				}
			}
		}
	}
}
?>