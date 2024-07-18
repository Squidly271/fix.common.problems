#!/usr/bin/php
<?PHP
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once("$docroot/plugins/dynamix.plugin.manager/include/PluginHelpers.php");

$nextBranch = ( ($argv[1] ?? "") == "next" );

echo "Disclaimer:  This script is NOT definitive.  There may be other issues with your server that will affect compatibility.\n\n";

$currentUnRaidVersion = parse_ini_file("/etc/unraid-version");
@unlink("/tmp/plugins/unRAIDServer-.plg");
if ( version_compare($currentUnRaidVersion['version'],"6.3.5","<=") ) {
	plugin("checkall");
	$unRaid635 = "true";
} else {
	if ( $nextBranch ) {
		exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/checkNext.php next");
	} else {
		exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/checkNext.php stable");
	}
}

if ( is_file("/tmp/plugins/unRAIDServer-.plg") ) {
	$newUnRaidVersion = plugin("version","/tmp/plugins/unRAIDServer-.plg");
} else {
	$newUnRaidVersion = plugin("version","/tmp/plugins/unRAIDServer.plg");
}
$downgrade = version_compare($newUnRaidVersion,$currentUnRaidVersion['version'],"<") ? "Downgrade" : "Upgrade";
echo "<font color='blue'>Current unRaid Version: {$currentUnRaidVersion['version']}   $downgrade unRaid Version: $newUnRaidVersion</font>\n\n";

if ( version_compare($newUnRaidVersion,$currentUnRaidVersion['version'],"=") ) {
	echo "NOTE: You are currently running the latest version of unRaid.  \n\n";
}

# MAIN
$unRaid63 = version_compare($currentUnRaidVersion['version'],"6.4.0-rc1","<");
$disks = parse_ini_file("/var/local/emhttp/disks.ini",true);
# Check for correct starting sector on the partition for cache drive
if ( $unRaid63 ) {
	echo "Checking cache drive partitioning\n";

	if ( $disks['cache']['status'] == "DISK_OK" ) {
		$cacheDevice = $disks['cache']['device'];
		$output = trim(exec("fdisk -l /dev/$cacheDevice | grep /dev/{$cacheDevice}1"));
		if ( ! $output ) {
			$output = trim(exec("fdisk -l /dev/$cacheDevice | grep /dev/{$cacheDevice}p1"));
		}
		if ( ! $output ) {
			echo "<font color='orange'>Unknown: Could not determine</font>\n";
		} else {
			$line = preg_replace('!\s+!',' ',$output);
			$contents = explode(" ",$line);
			if ( $contents[1] != "64" ) {
				ISSUE("Cache drive partition doesn't start on sector 64.  You will have problems.  See here https://lime-technology.com/forums/topic/46802-faq-for-unraid-v6/?tab=comments#comment-511923 for how to fix this.");
			} else {
				OK("Cache drive partition starts on sector 64");
			}
		}
	} else {
		OK("Cache drive not present");
	}
}
#check for plugins up to date
echo "\nChecking for plugin updates\n";
exec("plugin checkall");

$installedPlugs = glob("/var/log/plugins/*.plg");
$updateFlag = false;
foreach ($installedPlugs as $installedPlg) {
	if ( basename($installedPlg) == "unRAIDServer.plg" ) { continue; }
	if ( basename($installedPlg) == "unRAIDServer-.plg") { continue; }
	if ( basename($installedPlg) == "dynamix.plg")			 { continue; }
	if ( is_file("/tmp/plugins/".basename($installedPlg)) ) {
		$updateVer = plugin("version","/tmp/plugins/".basename($installedPlg));
	} else {
		$updateVer = 0;
	}
	$installedVer = plugin("version","/boot/config/plugins/".basename($installedPlg));
	if (strcasecmp($updateVer,$installedVer) > 0) {
		$pluginName = plugin("name",$installedPlg);
		ISSUE(basename($installedPlg)." ($pluginName) is not up to date.  It is recommended to update all your plugins.");
		$updateFlag = true;
	}
}
if ( ! $updateFlag ) {
	OK("All plugins up to date");
}
 
# Check for plugins compatible
echo "\nChecking for plugin compatibility\n";

$moderation = download_json("https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json","/tmp/upgradeAssistantModeration.json");
$appfeed = download_json("https://raw.githubusercontent.com/Squidly271/AppFeed/master/applicationFeed.json","/tmp/upgradeAssistantAppfeed.json");

if ( ! $appfeed ) {
	echo "<font color='orange'>Unable to check</font>\n";
} else {
	$redirectedURLs = [];
	foreach ($appfeed['applist'] as &$template) {
		if ( ! isset($template['Plugin']) ) { continue; }
		$redirectedurls[] = getRedirectedURL($template['PluginURL']);
	}
//	file_put_contents("/tmp/blah",print_r($appfeed,true));
	foreach ($installedPlugs as $installedPlg) {
		unset($pluginName);
		$versionsFlag = false;
		if ( basename($installedPlg) == "unRAIDServer.plg" ) { continue; }
		if ( basename($installedPlg) == "unRAIDServer-.plg") { continue; }
		if ( basename($installedPlg) == "dynamix.plg")       { continue; }
		$pluginURL = plugin("pluginURL",$installedPlg);
		if ( isset($moderation[$pluginURL]['MaxVer']) ) {
			if ( version_compare($newUnRaidVersion,$moderation[$pluginURL]['MaxVer'],">") ) {
				$pluginName = plugin("name",$installedPlg);
				
				ISSUE(basename($installedPlg)." is not compatible with $newUnRaidVersion.  It is HIGHLY recommended to uninstall this plugin. {$moderation[$pluginURL]['ModeratorComment']}");
				$versionsFlag = true;
			}
		}
		if ( isset($moderation[$pluginURL]['DeprecatedMaxVer']) ) {
			if ( version_compare($newUnRaidVersion,$moderation[$pluginURL]['DeprecatedMaxVer'],">") ) {
				$pluginName = plugin("name",$installedPlg);
				ISSUE(basename($installedPlg)." is deprecated with $newUnRaidVersion.  It is recommended to uninstall this plugin. {$moderation[$pluginURL]['ModeratorComment']}");
				$versionsFlag = true;
			}
		}
		if ( isset($moderation[$pluginURL]['Deprecated']) && filter_var($moderation[$pluginURL]['Deprecated'],FILTER_VALIDATE_BOOLEAN) ) {
			ISSUE(basename($installedPlg)." is deprecated for ALL unRaid versions.  This does not necessarily mean you will have any issues with the plugin, but there are no guarantees.  It is recommended to uninstall the plugin");
			$versionsFlag = true;
		}
		$foundAppFlag = false;
		$pluginURL = getRedirectedURL($pluginURL);
		if ( ! in_array($pluginURL,$redirectedurls) ) {
				ISSUE(basename($installedPlg)." is not known to Community Applications.  Compatibility for this plugin CANNOT be determined and it may cause you issues.");
		}		
		
	}
	if ( ! $versionsFlag ) {
		OK("All plugins are compatible");
	}
}

# Check for extra parameters on emhttp executable
echo "\nChecking for extra parameters on emhttp\n";
$emhttpExe = exec("cat /boot/config/go | grep /usr/local/sbin/emhttp");

$emhttpParams = trim(str_replace("/usr/local/sbin/emhttp","",$emhttpExe));
if ( $emhttpParams == "&" || ! $emhttpParams) {
	OK("emhttp command in /boot/config/go contains no extra parameters");
} else {
	ISSUE("emhttp command in /boot/config/go has extra parameters passed to it.  Currently emhttp does not accept any extra paramters.  These should be removed");
}

# check for zenstates in go file
/* echo "\nChecking for zenstates on Ryzen CPU\n";
$output = exec("lscpu | grep Ryzen");
if ( $output ) {
	$output = exec("cat /boot/config/go | grep  /usr/local/sbin/zenstates");
	if ( ! $output ) {
		ISSUE("zenstates is not loading within /boot/config/go  See here: https://lime-technology.com/forums/topic/66327-unraid-os-version-641-stable-release-update-notes/");
	}
} else {
	OK("Ryzen CPU not detected");
} */

# Check for disabled disks
echo "\nChecking for disabled disks\n";
$diskDSBLflag = false;
foreach ($disks as $disk) {
	if ($disk['status'] == 'DISK_DSBL') {
		ISSUE("{$disk['name']} is disabled.  Highly recommended to fix this problem before upgrading your OS");
		$diskDSBLflag = true;
	}
}
if ( ! $diskDSBLflag ) {
	OK("No disks are disabled");
}

# Check for less than 2G memory_get_peak_usage
echo "\nChecking installed RAM\n";
$file = trim(str_replace("MemTotal:","",exec("cat /proc/meminfo | grep MemTotal:")));
$raw = explode(" ",$file);
	
if ($raw[0] < 3000000 ) {
	ISSUE("The functional minimum of memory for unRaid is 4G as a very basic NAS.  You will need to increase your memory");
} else {
	OK("You have 4+ Gig of memory");
}

# Check for R/W to flash drive
echo "\nChecking flash drive\n";
if ( ! is_file("/boot/config/plugins/fix.common.problems.plg") ) {
	ISSUE("Possibly the flash drive has dropped offline");
} else {
	file_put_contents("/boot/update.assistant.tmp","blah");
	$test = file_get_contents("/boot/update.assistant.tmp");
	@unlink("/boot/update.assistant.tmp");
	if ( $test != "blah" ) {
		ISSUE("Unable to write to flash drive.  Either full, read-only, or dropped offline");
	} else {
		OK("Flash drive is read/write");
	}
}
	
# Check for valid NETBIOS name
echo "\nChecking for valid NETBIOS name\n";
$netBIOSflag = false;
$identity = @parse_ini_file("/boot/config/ident.cfg");
if ( strlen($identity['NAME']) > 15 ) {
	ISSUE("Server Name is not NETBIOS compliant (greater than 15 characters)  You may have trouble accessing your server.  Change in Settings - Identity");
	$netBIOSflag = true;
}
$testName = preg_replace("/[a-zA-Z0-9.-]/","",$identity['NAME']);
if ( $testName ) {
	ISSUE("Server Name contains invalid characters for NETBIOS - Only 'A-Z', 'a-z', and '0-9'), dashes ('-'), and dots ('.') are allowed.  You may have trouble accessing your server.  Change in Settings - Identity");
	$netBIOSflag = true;
}
if ( ! $netBIOSflag ) {
	OK("NETBIOS server name is compliant.");
}
	
# Check for ancient dynamix.plg
echo "\nChecking for ancient version of dynamix.plg\n";
if ( is_file("/boot/config/plugins/dynamix.plg") ) {
	ISSUE("Ancient version of dynamix.plg found.  You may have issues.  Recommended to delete dynamix.plg from /config/plugins on the flash drive");
} else {
	OK("Dynamix plugin not found");
}

# Check for VM DomainDir / MediaDir set to be /mnt
echo "\nChecking for VM MediaDir / DomainDir set to be /mnt\n";
$domainCFG = @parse_ini_file("/boot/config/domain.cfg");
if ($domainCFG['SERVICE'] != "enable") {
	OK("VMs are not enabled");
} else {
	$domaindir = str_replace("/mnt","",$domainCFG['DOMAINDIR']);
	$mediadir = str_replace("/mnt","",$domainCFG['MEDIADIR']);
	$domaindir = str_replace("/","",$domaindir);
	$mediadir = str_replace("/","",$mediadir);
	if ( ! $domaindir || ! $mediadir) {
		ISSUE("Either domain directory or ISO directory is set to be /mnt.  Your VMs will not properly start up.  Fix in Settings - VM Settings");
	} else {
		OK("VM domain directory and ISO directory not set to be /mnt");
	}
}

# check for mover logging enabled
echo "\nChecking for mover logging enabled\n";
if ( is_dir("/mnt/cache") ) {
	$iniFile = @parse_ini_file("/boot/config/share.cfg",true);
	if ( strtolower($iniFile['shareMoverLogging']) == "yes" ) {
		echo "<font color='orange'>Mover logging is enabled.  While this isn't an issue, it is now recommended to disable this setting on all versions of unRaid.  You can do this in Settings - Schedule - Mover Schedule.</font>\n";
	} else {
		OK("Mover logging not enabled");
	}
} else {
	OK("Cache drive not installed");
}

echo "\nChecking for reserved name being used as a user share\n";
$reservedNames = ["parity","parity2","parity3","diskP","diskQ","diskR","disk","disks","flash","boot","user","user0","dev","disk0","disk1","disk2","disk3","disk4","disk5","disk6","disk7","disk8","disk9","disk10","disk11","disk12","disk13","disk14","disk15","disk16","disk17","disk18","disk19","disk20","disk21","disk22","disk23","disk24","disk25","disk26","disk27","disk28","disk29","disk30","disk31"];
$flag = false;
foreach ($reservedNames as $reservedName) {
	if ( is_dir("/mnt/user/$reservedName") ) {
		$flag = true;
		ISSUE("You have a share named $reservedName.  Since 6.9.0, this is now a reserved name and cannot be used as a share.  You will need to rename this share for the system to work properly");
	}
}
if ( ! $flag )
	OK("No user shares using reserved names were found");


echo "\nChecking for extra.cfg\n";
# check for extra.cfg
if ( version_compare($newUnRaidVersion,"6.7.9",">") ) {
	if ( is_file("/boot/config/extra.cfg") ) {
		ISSUE("File file /config/extra.cfg on the flash drive exists.  This file is currently not used and may cause issues with your server.  You should delete it");
	} else {
		OK("/boot/config/extra.cfg does not exist");
	}
}

if ( version_compare($newUnRaidVersion,"6.10.3","<") ) {
	echo "\nChecking for tg3 driver and IOMMU enabled\n";
	$iommu_groups = shell_exec("find /sys/kernel/iommu_groups/ -type l");
	if ( $iommu_groups ) {
		if ( shell_exec("lsmod | grep tg3") ) {
			ISSUE("It appears that you have an ethernet adapter using the tg3 driver and IOMMU enabled.  This combination has been found to potentially cause serious data corruption issues.  Disable IOMMU in the BIOS");
		} else {
			OK("tg3 driver not present");
		}
	} else {
		OK("IOMMU not enabled, tg3 driver test not applicable");
	}
}

echo "\nChecking for root kernel parameter\n";

$config = @file_get_contents("/boot/syslinux/syslinux.cfg");
  
if ( strpos($config,"root=") ) {
  ISSUE("A kernel parameter (root=...) is present in your syslinux.cfg file (/syslinux/syslinux.cfg on the flash drive).  This option was previously needed for some users in order to boot Unraid.  It has not been required for a number of releases, and will prevent the OS from booting 7.0 if it is present.  You should edit your syslinux.cfg file and remove that option.");
} else {
	OK("No root kernel parameter found");
}

if ( isset($ISSUES_FOUND)) {
	echo "\n\n<font color='red'>Issues have been found with your server that may hinder the OS upgrade.  You should rectify those problems before upgrading</font>\n";
} else {
	echo "\n\n<font color='blue'>No issues were found with your server that may hinder the OS upgrade.  You should be good to go</font>\n";
}

#Support stuff
function download_url($url, $path = "", $bg = false,$requestNoCache=false){
	if ( ! strpos($url,"?") ) {
		$url .= "?".time(); # append time to always wind up requesting a non cached version
	}
	exec("curl --compressed --max-time 60 --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
	return ($exit_code === 0 ) ? implode("\n", $out) : false;
}
function readJsonFile($filename) {
	$json = json_decode(@file_get_contents($filename),true);
	if ( ! is_array($json) ) { $json = array(); }
	return $json;
}
function download_json($url,$path) {
	download_url($url,$path);
	return readJsonFile($path);
}

function OK($msg) {
	echo "<font color='blue'>OK: $msg</font>\n";
}
function ISSUE($msg) {
	global $ISSUES_FOUND;
	echo "<font color='red'>Issue Found: $msg</font>\n";
	$ISSUES_FOUND = true;
}
function getRedirectedURL($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$a = curl_exec($ch);
	$ret = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	return $ret;
}
?>