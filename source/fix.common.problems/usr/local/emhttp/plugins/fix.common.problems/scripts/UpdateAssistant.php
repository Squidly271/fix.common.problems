#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/PluginHelpers.php");

echo "Disclaimer:  This script is NOT definitive.  There may be other issues with your server that will affect compatibility.\n\n";

$currentUnRaidVersion = parse_ini_file("/etc/unraid-version");
if ( version_compare($currentUnRaidVersion['version'],"6.3.5","<=") ) {
	plugin("checkall");
	$unRaid635 = "true";
	
} else {
	plugin("checkos");
}
$newUnRaidVersion = plugin("version","/tmp/plugins/unRAIDServer.plg");
echo "<font color='blue'>Current unRaid Version: {$currentUnRaidVersion['version']}   Upgrade unRaid Version: $newUnRaidVersion</font>\n\n";

if ( version_compare($newUnRaidVersion,$currentUnRaidVersion['version'],"=") ) {
	echo "NOTE: You are currently running the latest version of unRaid.  To check compatibility against the 'next' branch of unRaid, go to Upgrade OS and select 'Next' branch and then re-run these tests\n\n";
}

# MAIN

# Check for correct starting sector on the partition for cache drive
echo "Checking cache drive partitioning\n";
$disks = parse_ini_file("/var/local/emhttp/disks.ini",true);
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
			ISSUE("Cache drive partition doesn't start on sector 64.  You will have problems.  See here https://lime-technology.com/forums/topic/46802-faq-for-unraid-v6/?tab=comments#comment-511923 for how to fix this.   NOTE: Currently there may be false positives associated with this test");
		} else {
			OK("Cache drive partition starts on sector 64");
		}
	}
} else {
	OK("Cache drive not present");
}
#check for plugins up to date
echo "\nChecking for plugin updates\n";
if ( ! $unRaid635 ) {
	exec("plugin checkall");
}
$installedPlugs = glob("/tmp/plugins/*.plg");
foreach ($installedPlugs as $installedPlg) {
	if ( basename($installedPlg) == "unRAIDServer.plg" ) { continue; }
	$updateVer = plugin("version",$installedPlg);
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
$appfeed = download_json("https://tools.linuxserver.io/unraid-docker-templates.json","/tmp/upgradeAssistantAppfeed.json");

foreach ($installedPlugs as $installedPlg) {
	$pluginURL = exec("plugin pluginURL ".escapeshellarg($installedPlg));
	if ( $moderation[$pluginURL]['MaxVer'] ) {
		if ( version_compare($newUnRaidVersion,$moderation[$pluginURL]['MaxVer'],">") ) {
			$pluginName = exec("plugin name ".escapeshellarg($installedPlg));
			ISSUE(basename($installedPlg)." ($pluginName) is not compatible with $newUnRaidVersion.  It is HIGHLY recommended to uninstall this plugin. {$moderation[$pluginURL]['ModeratorComment']}");
			$versionsFlag = true;
		}
	}
	if ( $moderation[$pluginURL]['DeprecatedMaxVer'] ) {
		if ( version_compare($newUnRaidVersion,$moderation[$pluginURL]['DeprecatedMaxVer'],">") ) {
			$pluginName = exec("plugin name ".escapeshellarg($installedPlg));
			ISSUE(basename($installedPlg)." ($pluginName) is deprecated with $newUnRaidVersion.  It is recommended to uninstall this plugin. {$moderation[$pluginURL]['ModeratorComment']}");
			$versionsFlag = true;
		}
	}
	if ( filter_var($moderation[$pluginURL]['Deprecated'],FILTER_VALIDATE_BOOLEAN) ) {
		ISSUE(basename($installedPlg)." ($pluginName) is deprecated for ALL unRaid versions.  This does not necessarily mean you will have any issues with the plugin, but there are no guarantees.  It is recommended to uninstall the plugin");
		$versionsFlag = true;
	}
	$foundAppFlag = false;
	if ( basename($installedPlg) != "unRAIDServer.plg" ) {
		foreach ( $appfeed['applist'] as $template ) {
			if ( ! $template['Plugin'] ) {
				continue;
			}
			$template['PluginURL'] = str_replace("raw.github.com","raw.githubusercontent.com",$template['PluginURL']);
			if ( $pluginURL == $template['PluginURL'] ) {
				$foundAppFlag = true;
				break;
			}
		}
		if ( ! $foundAppFlag ) {
			ISSUE(basename($installedPlg)." ($pluginName) is not known to Community Applications.  Compatibility for this plugin CANNOT be determined and it may cause you issues.");
		}		
	}
}
if ( ! $versionsFlag ) {
	OK("All plugins are compatible");
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
echo "\nChecking for zenstates on Ryzen CPU\n";
$output = exec("lscpu | grep Ryzen");
if ( $output ) {
	$output = exec("cat /boot/config/go | grep  /usr/local/sbin/zenstates");
	if ( ! $output ) {
		ISSUE("zenstates is not loading within /boot/config/go  See here: https://lime-technology.com/forums/topic/66327-unraid-os-version-641-stable-release-update-notes/");
	}
} else {
	OK("Ryzen CPU not detected");
}

# Check for disabled disks
echo "\nChecking for disabled disks\n";
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
	if ( $test != "blah" ) {
		ISSUE("Unable to write to flash drive.  Either full, read-only, or dropped offline");
	} else {
		OK("Flash drive is read/write");
	}
}
	
# Check for valid NETBIOS name
echo "\nChecking for valid NETBIOS name\n";
$identity = parse_ini_file("/boot/config/ident.cfg");
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
	if ( filemtime("/boot/config/plugins/dynamix.plg") < 1451606400 ) {  # Jan 1, 2016 
		ISSUE("Ancient version of dynamix.plg found.  You may have issues.  Recommended to delete dynamix.plg from /config/plugins on the flash drive");
	} else {
		OK("Dynamix plugin timestamp");
	}
} else {
	OK("Dynamix plugin not found");
}

# Check for VM DomainDir / MediaDir set to be /mnt
echo "\nChecking for VM MediaDir / DomainDir set to be /mnt\n";
$domainCFG = parse_ini_file("/boot/config/domain.cfg");
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


if ($ISSUES_FOUND) {
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
?>