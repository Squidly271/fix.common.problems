#!/usr/bin/php
<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################


$communityPaths['autoUpdateSettings'] = "/boot/config/plugins/community.applications/AutoUpdate.json";
$fixPaths['dockerUpdateStatus']       = "/var/lib/docker/unraid-update-status.json";
$fixPaths['tempFiles']                = "/tmp/fix.common.problems";
$fixPaths['errors']                   = $fixPaths['tempFiles']."/errors.json";
$fixPaths['disks.ini']                = "/var/local/emhttp/disks.ini";
#$fixPaths['disks.ini']               = "/tmp/GitHub/disks.ini";                   # ONLY REMOVE COMMENT FOR TESTING
$fixPaths['settings']                 = "/boot/config/plugins/fix.common.problems/settings.json";
$fixPaths['moderation']               = $fixPaths['tempFiles']."/moderation.json";          /* json file that has all of the moderation */
$fixPaths['moderationURL']            = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$fixPaths['application-feed']         = "http://tools.linuxserver.io/unraid-docker-templates.json";
$fixPaths['templates']                = $fixPaths['tempFiles']."/templates.json";

$autoUpdateOverride              = is_file("/boot/config/plugins/fix.common.problems/autoupdate-warning");
$developerMode                   = is_file("/boot/config/plugins/fix.common.problems/developer");
$communityApplicationsInstalled  = is_file("/var/log/plugins/community.applications.plg");
$dockerRunning                   = is_dir("/var/lib/docker/tmp");

exec("mkdir -p ".$fixPaths['tempFiles']);

$disableNotifications = $argv[1];

require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");

$fixSettings = readJsonFile($fixPaths['settings']);
if ( ! $fixSettings['notifications'] ) { $fixSettings['notifications'] = "all"; }
if ( ! $fixSettings['disableSpinUp'] ) { $fixSettings['disableSpinUp'] = "false"; }

function addError($description,$action) {
  global $errors;

  $newError['error'] = "<font color='red'>$description</font>";
  $newError['suggestion'] = $action;
  logger("Fix Common Problems: Error: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion:".strip_tags($action));
  $errors[] = $newError;
}

function addWarning($description,$action) {
  global $warnings;
  
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Warning: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  $warnings[] = $newWarning;
}

function addOther($description,$action) {
  global $otherWarnings;
  
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Other Warning: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  $otherWarnings[] = $newWarning;
}

function addLinkButton($buttonName,$link) {
  $link = str_replace("'","&quot;",$link);
  return "<input type='button' value='$buttonName' onclick='window.location.href=&quot;$link&quot;'>";
}

function addButton($buttonName,$action) {
  $action = str_replace("'","&quot;",$action);
  return "<input type='button' value='$buttonName' onclick='$action'>";
}


# start main




if ( is_dir("/mnt/user") ) {
  $shareList = array_diff(scandir("/mnt/user"),array(".",".."));
} else {
  $shareList = array();
}

# Check for implied array only but files / folders on cache
    
foreach ($shareList as $share) {
  if ( startsWith($share,".") ) { continue; }
  if ( ! is_file("/boot/config/shares/$share.cfg") ) {
    if ( is_dir("/mnt/user0/$share") ) {
      $shareURL = str_replace(" ","+",$share);
      addWarning("Share <b><font color='purple'>$share</font></b> is an implied <em>array-only</em> share, but files / folders exist on the cache","Set <b><em>Use Cache</em></b> appropriately, then rerun this analysis. ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
    }
  }
}

# Check for cache only share, but files / folders on array
    
foreach ($shareList as $share) {
  if ( startsWith($share,".") ) { continue; }
  if ( is_file("/boot/config/shares/$share.cfg") ) {
    $shareCfg = parse_ini_file("/boot/config/shares/$share.cfg");
    if ( $shareCfg['shareUseCache'] == "only" ) {
      if (is_dir("/mnt/user0/$share") ) {
        $shareURL = str_replace(" ","+",$share);
        addWarning("Share <b><font color='purple'>$share</font></b> set to <em>cache-only</em>, but files / folders exist on the array","You should change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL")." or use the dolphin / krusader docker applications to move the offending files accordingly.  Note that there are some valid use cases for a set up like this.  In particular: <a href='https://lime-technology.com/forum/index.php?topic=40777.msg385753' target='_blank'>THIS</a>");
      }
    }
  }
}

# Check for don't use cache, but files on cache drive
    
foreach ($shareList as $share) {
  if ( startsWith($share,".") ) { continue; }
  if ( is_file("/boot/config/shares/$share.cfg") ) {
    $shareCfg = parse_ini_file("/boot/config/shares/$share.cfg");
    if ( $shareCfg['shareUseCache'] == "no" ) {
      if ( is_dir("/mnt/cache/$share") ) {
        $shareURL = str_replace(" ","+",$share);
        addWarning("Share <b><font color='purple'>$share</font></b> set to <em>not use the cache</em>, but files / folders exist on the cache drive","You should change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL")."or use the dolphin / krusader docker applications to move the offending files accordingly.  Note that there are some valid use cases for a set up like this.  In particular: <a href='https://lime-technology.com/forum/index.php?topic=40777.msg385753' target='_blank'>THIS</a>");
      }
    }
  }
}

# Check for Dynamix to perform plugin checks
    
if ( ! is_file("/boot/config/plugins/dynamix/plugin-check.cron") ) {
  if ( $autoUpdateOverride ) {
    $func = "addWarning";
  } else {
    $func = "addError";
  }
  $func("<font color='purple'><b>Plugin Update Check</b></font> not enabled","Highly recommended to have dynamix check for plugin updates (including for the webUI".addLinkButton("Notification Settings","/Settings/Notifications"));
}

# Check for Dynamix to perform docker update checks

if ( $dockerRunning ) {
  if ( ! is_file("/boot/config/plugins/dynamix/docker-update.cron") ) {
    addWarning("<font color='purple'><b>Docker Update Check</b></font> not enabled","Recommended to enable update checks for docker applications".addLinkButton("Notification Settings","/Settings/Notifications"));
  }
}

# Check for CA to auto update certain plugins
    
if ( $communityApplicationsInstalled ) {
  $autoUpdateSettings = readJsonFile($communityPaths['autoUpdateSettings']);
  if ( $autoUpdateSettings['Global'] != "true" ) {
    if ( $autoUpdateSettings['community.applications.plg'] != "true" ) {
      addWarning("<font color='purple'><b>Community Applications</b></font> not set to auto update</font>",addLinkButton("Auto Update Settings","/Settings/AutoUpdate")."Recommended to enable auto updates for this plugin to minimize issues with applications");
    }
    if ( $autoUpdateSettings['dynamix.plg'] != "true" ) {
      addWarning("<font color='purple'><b>Dynamix WebUI</b></font> not set to auto update</font>",addLinkButton("Auto Update Settings","/Settings/AutoUpdate")."Recommended to enable auto updates for this plugin to minimize GUI problems");
    }
    if ( $autoUpdateSettings['fix.common.problems.plg'] != "true" ) {
      if ( $autoUpdateOverride ) {
        $func = "addWarning";
      } else {
        $func = "addError";
      }
      $func("This plugin <font color='purple'><b>(Fix Common Problems)</b></font> not set to auto update</font>",addLinkButton("Auto Update Settings","/Settings/AutoUpdate")."Recommended to enable auto updates for this plugin to enable further problem solving / fixes");
    }
  }
} else {
  addWarning("<font color='purple'><b>Community Applications</b></font> not installed","Recommended to install Community Applications so that plugins can auto-update.  Follow the directions <a href='http://lime-technology.com/forum/index.php?topic=40262.0' target='_blank'>HERE</a> to install");
}
   
# Check for shares spelled the same but with different case
    
foreach ( $shareList as $share ) {
  $dupShareList = array_diff(scandir("/mnt/user/"),array(".","..",$share));
  foreach ($dupShareList as $dup) {
    if ( strtolower($share) == strtolower($dup) ) {
      addError("Same share <font color='purple'>($share)</font> exists in a different case","This will confuse SMB shares.  Manual intervention required.  Use the dolphin / krusader docker app to combine the shares into one unified spelling");
      break;
    }
  }
}
    
# Check for powerdown plugin installed
    
if ( ! is_file("/var/log/plugins/powerdown-x86_64.plg") ) {
  $suggestion = $communityApplicationsInstalled ? "Install either through ".addLinkButton("Community Applications","/Apps")." or via" : "";
  addError("<font color='purple'><b>Powerdown</b></font> plugin not installed","Highly recommended to install this plugin.  Install via $suggestion the instructions <a href='http://lime-technology.com/forum/index.php?topic=31735.0' target='_blank'>HERE</a>");
}
      
# Check for communication to the outside world
 
exec("ping -c 2 github.com",$dontCare,$pingReturn);
if ( $pingReturn ) {
  addError("Unable to communicate with GitHub.com","Reset your modem / router or try again later, or set your ".addLinkButton("DNS Settings","/Settings/NetworkSettings")." to 8.8.8.8 and 8.8.4.4");
}

# Check for inability to write to drives
    
$availableDrives = array_diff(scandir("/mnt/"),array(".","..","user","user0","disks"));
$disksIni = parse_ini_file($fixPaths['disks.ini'],true);
    
foreach ($availableDrives as $drive) {
  if ( $fixSettings['disableSpinUp'] == "true" ) {
    if ( stripos($disksIni[$drive]['color'],"blink") ) {
      $spunDown .= " $drive ";
      continue;
    }
  }
  $filename = randomFile("/mnt/$drive");
    
  @file_put_contents($filename,"test");
  $result = @file_get_contents($filename);
      
  if ( $result != "test" ) {
    addError("Unable to write to <font color='purple'>$drive","Drive mounted read-only or completely full.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
  }
  @unlink($filename);
}
if ( $spunDown ) {
        addOther("Disk(s) <font color='purple'><b>$spunDown</b></font> are spun down.  Skipping write check","Disk spin up avoidance is enabled within this plugin's settings.");
}

$filename = randomFile("/boot");
@file_put_contents($filename,"test");
$result = @file_get_contents($filename);

if ( $result != "test" ) {
  addError("Unable to write to <font color='purple'><b>flash drive</b>","Drive mounted read-only or completely full.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
}
@unlink($filename);

if ( $dockerRunning ) {
  $filename = randomFile("/var/lib/docker/tmp");
  @file_put_contents($filename,"test");
  $result = @file_get_contents($filename);
  
  if ( $result != "test" ) {
    addError("Unable to write to <font color='purple'><b>Docker Image</b></font>","Docker Image either full or corrupted.  Investigate Here: ".addLinkButton("Docker Settings","/Settings/DockerSettings"));
  }
  @unlink($filename);
}
# check for default docker appdata location to be cache or directly on a disk_free_space

if ( is_dir("/mnt/cache") ) {
  $dockerOptions = @parse_ini_file("/boot/config/docker.cfg");
  if ( startsWith($dockerOptions['DOCKER_APP_CONFIG_PATH'],"/mnt/user/") ) {
    addWarning("<font color='purple'><b>docker appdata location</b></font> is stored within /mnt/user","Many (if not most) docker applications will have issues (weird results, not starting, etc) if their appdata is stored within a user share.  You should constrain the appdata share to a <b>single</b>disk or to the cache drive.  This is true even if the appdata share is a <em>Cache-Only</em> share.  Change the default here: ".addLinkButton("Docker Settings","/Settings/DockerSettings"));
  }
}

# check for default docker appdata location to be cache only share

if ( is_dir("/mnt/cache") ) {
  $dockerOptions = @parse_ini_file("/boot/config/docker.cfg");
  $sharename = basename($dockerOptions['DOCKER_APP_CONFIG_PATH']);
  if ( is_file("/boot/config/shares/$sharename.cfg") ) {
    $shareSettings = parse_ini_file("/boot/config/shares/$sharename.cfg");
    if ( $shareSettings['shareUseCache'] != "only" ) {
      addError("<font color='purple'><b>Default docker appdata</b></font> location is not a cache-only share","If the appdata share is not set to be cache-only, then the mover program will cause your docker applications to become inoperable when it runs.  Alternatively, if the appdata share is not constrained to a single disk, then docker applications may not run correctly.  Fix it via ".addLinkButton("$sharename Settings","/Shares/Share?name=$sharename"));
    }
  }
}

# look for disabled disks

$disks = parse_ini_file($fixPaths['disks.ini'],true);

foreach ($disks as $disk) {
  if ( startsWith($disk['status'],'DISK_DSBL') ) {
    addError("<font color='purple'><b>".$disk['name']." (".$disk['id'].")</b></font> is disabled","Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
  }
}

# look for missing disks

foreach ($disks as $disk) {
  if ( ( $disk['status'] == "DISK_NP") || ( $disk['status'] == "DISK_NP_DSBL" ) ) {
    if ( $disk['id'] ) {
      addError("<font color='purple'><b>".$disk['name']." (".$disk['id'].")</b></font> is missing","unRaid believes that your hard drive is not connected to any SATA port.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main")."  And also look at the ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
    }
  }
}

# look for read errors

foreach ($disks as $disk) {
  if ( $disk['numErrors'] ) {
    addError("<font color='purple'><b>".$disk['name']." (".$disk['id'].")</b></font> has read errors","If the disk has not been disabled, then unRaid has successfully rewritten the contents of the offending sectors back to the hard drive.  It would be a good idea to look at the S.M.A.R.T. Attributes for the drive in questionBegin Investigation Here: ".addLinkButton($disk['name']." Settings","/Main/Device?name=".$disk['name']));
  }
}

# look for file system errors

foreach ( $disks as $disk ) {
  if ( $disk['fsError'] ) {
    addError("<font color='purple'><b>".$disk['name']." (".$disk['id'].")</b></font> has file system errors (".$disk['fsError'].")","If the disk if XFS / REISERFS, stop the array, restart the Array in Maintenance mode, and run the file system checks.  If the disk is BTRFS, then just run the file system checks".addLinkButton("unRaid Main","/Main")."<b>If the disk is listed as being unmountable, and it has data on it, whatever you do do not hit the format button.  Seek assistance <a href='http://lime-technology.com/forum/index.php?board=71.0' target='_blank'>HERE</a>");
  }
}

# look for SSD's within the Array

foreach ( $disks as $disk ) {
  if ( $disk['rotational'] == "0" ) {
    if ( startsWith($disk['name'],"disk") ) {
      addWarning("<font color='purple'><b>".$disk['name']." (".$disk['id'].")</b></font> is an SSD.","SSD's are not currently supported within the array, and their background garbage collection *may* impact your ability to rebuild a disk");
    }
  }
}  

# look for plugins not up to date

$autoUpdateSettings = readJsonFile($communityPaths['autoUpdateSettings']);
if ( $autoUpdateSettings['Global'] != "true" ) {
  $installedPlugins = array_diff(scandir("/var/log/plugins"),array(".",".."));
  foreach ($installedPlugins as $Plugin) {
    if ( $autoUpdateSettings[$Plugin] != "true" ) {
      if ( is_file("/var/log/plugins/$Plugin") ) {
        if ( strtolower(pathinfo($Plugin, PATHINFO_EXTENSION)) == "plg" ) {
          if ( checkPluginUpdate($Plugin) ) {
            if ( $Plugin == "fix.common.problems.plg" ) {
              addError("Plugin <font color='purple'><b>$Plugin</b></font> is not up to date","Upgrade the plugin here: ".addLinkButton("Plugins","/Plugins"));
            } else {
              addWarning("Plugin <font color='purple'><b>$Plugin</b></font> is not up to date","Upgrade the plugin here: ".addLinkButton("Plugins","/Plugins"));
            }
          }
        }
      }
    }
  }
}

# check for 32 bit packages in /boot/extra and /boot/packages
if ( is_dir("/boot/extra") ) {
  $files = array_diff(scandir("/boot/extra"),array(".",".."));
  foreach ($files as $file) {
    if ( strpos($file,"i386") || strpos($file,"i486") ) {
      addError("Probable 32 Big package <font color='purple'><b>$file</b></font> found on the flash drive in the <b>extra</b> folder","32 Bit packages are incompatible with unRaid 6.x and need to be removed - Using your desktop, navigate to the <em>flash</em> share (extra folder) and delete the offending file");
    }
  }
}
if ( is_dir("/boot/packages") ) {
  $files = array_diff(scandir("/boot/packages"),array(".",".."));
  foreach ($files as $file) {
    if ( strpos($file,"i386") || strpos($file,"i486") ) {
      addError("Probable 32 Big package <font color='purple'><b>$file</b></font> found on the flash drive in the <b>packages</b> folder","32 Bit packages are incompatible with unRaid 6.x and need to be removed - Using your desktop, navigate to the <em>flash</em> share (packages folder) and delete the offending file");
    }
  }
}

# Check if docker containers not updated

if ( $dockerRunning ) {
  $DockerClient = new DockerClient();
  $info = $DockerClient->getDockerContainers();
  $updateStatus = readJsonFile($fixPaths['dockerUpdateStatus']);

  foreach ($info as $docker) {
    if ( $updateStatus[$docker['Image']]['status'] == 'false' ) {
      addWarning("Docker Application <font color='purple'><b>".$docker['Name']."</b></font> has an update available for it","Install the updates here: ".addLinkButton("Docker","/Docker"));
    }
  }
}

# Check for docker application's config folders pointed at /mnt/user

if ( dockerRunning ) {
  $DockerClient = new DockerClient();
  $info = $DockerClient->getDockerContainers();

  foreach ($info as $docker) {
    $appData = findAppData($docker['Volumes']);
    if ( startsWith($appData,"/mnt/user") ) {
      addWarning("<font color='purple'><b>".$docker['Name']."</b></font> docker application has its /config folder set to <font color='purple'><b>$appData</b></font>","Many (if not most docker applications) will not function correctly if their appData folder is set to a user share.  Ideally, they should be set to a disk share.  Either /mnt/cache/... or /mnt/diskX/...  Fix it here: ".addLinkButton("Docker Settings","/Docker"));    
    }
  }
}

# Check for /var/log filling up

unset($output);
exec("df /var/log",$output);
$statusLine = preg_replace('!\s+!', ' ', $output[1]);
$status = explode(" ",$statusLine);
$used = str_replace("%","",$status[4]);

if ( $used > 80 ) {
  addError("<font color='purple'><b>/var/log</b></font> is getting full (currently <font color='purple'>$used % </font>used)","Either your server has an extremely long uptime, or your syslog could be potentially being spammed with error messages.  A reboot of your server will at least temporarily solve this problem, but ideally you should seek assistance in the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
} else {
  if ( $used > 50 ) {
    addWarning("<font color='purple'><b>/var/log</b></font> is getting full (currently <font color='purple'>$used % </font>used)","Either your server has an extremely long uptime, or your syslog could be potentially being spammed with error messages.  A reboot of your server will at least temporarily solve this problem, but ideally you should seek assistance in the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
  }
}

# Check for docker image getting full

unset($output);
if ( $dockerRunning ) {
  exec("df /var/lib/docker",$output);
  $statusLine = preg_replace('!\s+!', ' ', $output[1]);
  $status = explode(" ",$statusLine);
  $used = str_replace("%","",$status[4]);

  if ( $used > 90 ) {
    addError("<font color='purple'><b>Docker image</b></font> file is getting full (currently <font color='purple'>$used % </font>used)","You should either increase the available image size to the docker image here ".addLinkButton("Docker Settings","/Settings/DockerSettings")."or investigate the possibility of docker applications storing completed downloads / incomplete downloads / etc within the actual docker image here: ".addLinkButton("Docker","/Docker"));
  } else {
    if ( $used > 80 ) {
      addWarning("<font color='purple'><b>Docker image</b></font> file is getting full (currently <font color='purple'>$used % </font>used)","You should either increase the available image size to the docker image here ".addLinkButton("Docker Settings","/Settings/DockerSettings")."or investigate the possibility of docker applications storing completed downloads / incomplete downloads / etc within the actual docker image here: ".addLinkButton("Docker","/Docker"));
    }
  }
}

# Check for rootfs getting full

unset($output);
if ( is_dir("/") ) {
  exec("df /",$output);
  $statusLine = preg_replace('!\s+!', ' ', $output[1]);
  $status = explode(" ",$statusLine);
  $used = str_replace("%","",$status[4]);

  if ( $used > 90 ) {
    addError("<font color='purple'><b>Rootfs</b></font> file is getting full (currently <font color='purple'>$used % </font>used)","Possibly an application is storing excessive amount of data in /tmp.  Seek assistance on the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
  } else {
    if ( $used > 75 ) {
      addWarning("<font color='purple'><b>Rootfs</b></font> file is getting full (currently <font color='purple'>$used % </font>used)","Possibly an application is storing excessive amount of data in /tmp.  Seek assistance on the forums and post your ".addLinkButton("Diagnostics","/Tools/Diagnostics"));
    }
  }
}

# Check if the server's time is out to lunch

$filename = randomFile("/tmp/fix.common.problems");
download_url("http://currentmillis.com/time/minutes-since-unix-epoch.php",$filename);
$actualTime = @file_get_contents($filename);
if (intval($actualTime) > 24377381 ) { # current time as of this code being written as a check for complete download_url
  $serverTime = intval(time() / 60);
  $timeDifference = abs($serverTime - intval($actualTime));
  
  if ( $timeDifference > 5 ) {
    addWarning("Your server's <font color='purple'><b>current time</b></font> differs from the actual time by more than 5 minutes.  Currently out by approximately <font color='purple'>$timeDifference minutes</font>","Either set your date / time manually, or set up the server to use an NTP server to automatically update the date and time".addLinkButton("Date and Time Settings","/Settings/DateTime"));  
  }
}
@unlink($filename);


# Check for scheduled parity checks

if ( is_file("/boot/config/plugins/dynamix/dynamix.cfg") ) {
  $dynamixSettings = parse_ini_file("/boot/config/plugins/dynamix/dynamix.cfg",true);
  
  if ( $dynamixSettings['parity']['mode'] == "0" ) {
    addWarning("Scheduled <font color='purple'><b>Parity Checks</b></font> are not enabled","It is highliy recommended to schedule parity checks for your system (most users choose monthly).  This is so that you know if unRaid has the ability to rebuild a failed drive if it needs to.  Set the schedule here: ".addLinkButton("Scheduler","/Settings/Scheduler"));
  }
}

# Check for shares having both included and excluded disks set

foreach ($shareList as $share) {
  if ( is_file("/boot/config/shares/$share.cfg") ) {
    $shareCfg = parse_ini_file("/boot/config/shares/$share.cfg");
    if ( $shareCfg['shareInclude'] && $shareCfg['shareExclude'] ) {
      $shareURL = str_replace(" ","+",$share);
      addWarning("Share <font color='purple'><b>$share</b></font> is set for both included (".$shareCfg['shareInclude'].") and excluded (".$shareCfg['shareExclude'].") disks","While if you're careful this isn't a problem, there is absolutely no reason ever to specify BOTH included and excluded disks.  It is far easier and safer to only set either the included list or the excluded list.  Fix it here: ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
    }
  }
}
# Check for global share settings having both included and exluded disks set

if ( is_file("/boot/config/share.cfg") ) {
  $shareCfg = parse_ini_file("/boot/config/share.cfg");
  if ( $shareCfg['shareUserInclude'] && $shareCfg['shareUserExclude'] ) {
    addWarning("<font color='purple'><b>Global Share Settings</b></font> is set for both included (".$shareCfg['shareUserInclude'].") and excluded (".$shareCfg['shareUserExclude'].") disks","While if you're careful this isn't a problem, there is absolutely no reason ever to specify BOTH included and excluded disks.  It is far easier and safer to only set either the included list or the excluded list.  Fix it here: ".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
  }
}

# Check for shares having duplicated disks within included and excluded

foreach ($shareList as $share) {
  if ( is_file("/boot/config/shares/$share.cfg") ) {
    $shareCfg = parse_ini_file("/boot/config/shares/$share.cfg"); 
    if ( ! $shareCfg['shareInclude'] ) { continue; }
    if ( ! $shareCfg['shareExclude'] ) { continue; }
    $shareInclude = explode(",",$shareCfg['shareInclude']);
    $shareExclude = explode(",",$shareCfg['shareExclude']);
    foreach ($shareInclude as $included) {
      foreach ($shareExclude as $excluded) {
        if ( $included == $excluded ) {
          $shareURL = str_replace(" ","+",$share);
          addError("Share <font color='purple'><b>$share</b></font> has the same disk ($included) set to be both included and excluded","The same disk cannot be both included and excluded.  There is also no reason to ever set both the included and excluded disks for a share.  Use one or the other.  Fix it here:".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
        }  
      }
    }
  }
}

# Check for having duplicated disks within global share included / excluded

if ( is_file("/boot/config/share.cfg") ) {
  $shareCfg = parse_ini_file("/boot/config/share.cfg");
  if ( ( $shareCfg['shareUserExclude'] ) && ( $shareCfg['shareUserInclude'] ) ) {
    $shareInclude = explode(",",$shareCfg['shareUserInclude']);
    $shareExclude = explode(",",$shareCfg['shareUserExclude']);
    foreach ($shareInclude as $included) {
      foreach ($shareExclude as $excluded) {
        if ( $included == $excluded ) {
          $shareURL = str_replace(" ","+",$share);
          addError("Share <font color='purple'><b>Global Share Settings</b></font> has the same disk ($included) set to be both included and excluded","The same disk cannot be both included and excluded.  There is also no reason to ever set both the included and excluded disks for a share.  Use one or the other.  Fix it here:".addLinkButton("Global Share Settings","/Settings/ShareSettings"));
        }  
      }
    }
  }
}

# Check for UD assigned disks not being passed as slave to docker containers

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
              addError("Docker application <font color='purple'><b>".$docker['Name']."</b></font> has volumes being passed that are mounted by <em>Unassigned Devices</em>, but they are not mounted with the <font color='purple'>slave</font> option","To help with a trouble free experience with this application, you need to pass any volumes mounted with Unassigned Devices using the slave option.  Fix it here: ".addLinkButton("Docker","/Docker"));
            }
          }
        }
      }
    }
  }
}

# Check for only supported file system types

$disks = parse_ini_file($fixPaths['disks.ini'],true);
foreach ($disks as $disk) {
  if ( ($disk['fsType'] != "reiserfs") && ($disk['fsType'] != "xfs") && ($disk['fsType'] != "btrfs") && ($disk['size'] != "0") && ($disk['fsType'] != "auto") ) {
    if ( ( startsWith($disk['name'],"cache") ) && ( $disk['name'] != "cache" ) ) {
      continue;
    }
    if ( $disk['name'] == "flash" ) {
      if ( $disk['fsType'] != "vfat" ) {
        addError("unRaid <font color='purple'><b>USB Flash Drive</b></font> is not formatted as FAT32","Strange results can happen if the flash drive is not formatted as FAT32.  Note that if your flash drive is > 32Gig, you must jump through some hoops to format it as FAT32.  Seek assistance in the formums if this is the case");
      } 
    } else {
      if ( ($disk['name'] != "parity") && ($disk['name'] != "parity2") ) {
        addError("Disk <font color='purple'><b>".$disk['name']."</b></font> is formatted as ".$disk['fsType'],"The only supported file systems are ReiserFS, btrFS, XFS.  This error should only happen if you are setting up a new array and the disk already has data on it.  <font color='red'><b>Prior to with a fix, you should seek assistance in the forums as the disk may simply be unmountable.  Whatever you do, do not hit the format button on the unRaid main screen as you will then lose data");
      }        
    }
  }
}

# Check for unRaid's ftp server running

unset($output);
exec("cat /etc/inetd.conf | grep vsftpd",$output);
foreach ($output as $line) {
  if ($line[0] != "#") {
    if ( is_file("/boot/config/vsftpd.user_list") ) {
      addWarning("unRaid's built in <font color='purple'><b>FTP server</b></font> is running","Opening up your unRaid server directly to the internet is an extremely bad idea. - You <b>will</b> get hacked.  If you require an FTP server running on your server, use one of the FTP docker applications instead.  They will be more secure than the built in one".addLinkButton("FTP Server Settings","/Settings/FTP")." If you are only using the built in FTP server locally on your network you can ignore this warning, but ensure that you have not forwarded any ports from your router to your server.  Note that there is a bug in unRaid 6.1.9 and 6.2b21 where if you disable the service, it will come back alive after a reboot.  This check is looking at whether you have users authenticated to use the ftp server");
    }
    break;
  } else {
    if ( is_file("/boot/config/vsftpd.user_list") ) {
      addWarning("unRaid's built in <font color='purple'><b>FTP server</b></font> is currently disabled, but users are defined","There is a bug within 6.1.9 and 6.2 beta 21 where after the server is reset, the FTP server will be automatically re-enabled regardless if you want it to be or not.  Remove the users here".addLinkButton("FTP Settings","/Settings/FTP"));
    }
  }
}

# Check for destination for Alert levels notifications

$dynamixSettings = parse_ini_file("/boot/config/plugins/dynamix/dynamix.cfg",true);

if ( $dynamixSettings['notify']['alert'] == "0" ) {
  addWarning("No destination (browser / email / agents set for <font color='purple'><b>Alert level notifications</b></font>","Without a destination set for alerts, you will not know if any issue requiring your immediate attention happens on your server.  Fix it here:".addLinkButton("Notification Settings","/Settings/Notifications"));
}
# Check for destination for Warning level notifications

if ( $dynamixSettings['notify']['warning'] == "0" ) {
 addWarning("No destination (browser / email / agents set for <font color='purple'><b>Warning level notifications</b></font>","Without a destination set for alerts, you will not know if any issue requiring your attention happens on your server.  Fix it here:".addLinkButton("Notification Settings","/Settings/Notifications"));
}

# Check for destination email address

$notificationsSet = $dynamixSettings['notify']['normal'] | $dynamixSettings['notify']['warning'] | $dynamixSettings['notify']['alert'];
$emailSelected = ($notificationsSet & 2) == 2;

if ( $emailSelected ) {
  if ( ( ! $dynamixSettings['ssmtp']['RcptTo'] ) || ( ! $dynamixSettings['ssmtp']['server'] ) ) {
    addWarning("<font color='purple'><b>Email</b></font> selected as a notification destination, but not properly configured","Either deselect email as a destination for notifications or properly configure it here: ".addLinkButton("Notification Settings","/Settings/Notifications")."  Note That this test does NOT test to see if you can actually send mail or not");
  }
}

# Check for blacklisted plugins installed

download_url($fixPaths['moderationURL'],$fixPaths['moderation']);

$caModeration = readJsonFile($fixPaths['moderation']);
if ( $caModeration ) {
  $pluginList = array_diff(scandir("/var/log/plugins"),array(".",".."));
  foreach ($pluginList as $plugin) {
    if ( ! is_file("/var/log/plugins/$plugin") ) {
      continue;
    }
    $pluginURL = exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin pluginURL /var/log/plugins/$plugin");
    if ( $caModeration[$pluginURL]['Blacklist'] ) {
      addError("Blacklisted plugin <font color='purple'><b>$plugin</b></font>","This plugin has been blacklisted and should no longer be used due to the following reason(s): <font color='purple'><em><b>".$caModeration[$pluginURL]['ModeratorComment']."</b></em></font>  You should remove this plugin as its continued installation may cause adverse effects on your server.".addLinkButton("Plugins","/Plugins"));
    }
  }
} else {
  addOther("Could not check for <font color='purple'><b>blacklisted</b></font> plugins","The download of the blacklist failed");
}
@unlink($fixPaths['moderation']);

# Check for non CA known plugins

download_url($fixPaths['application-feed'],$fixPaths['templates']);
$templates = readJsonFile($fixPaths['templates']);

if ( ! $developerMode ) {
  $pluginList = array_diff(scandir("/var/log/plugins"),array(".",".."));

  if ( is_array($templates['applist']) ) {
    foreach ($templates['applist'] as $template) {
      if ($template['Plugin']) {
        $allPlugins[] = $template;
      }
    }
 
    foreach ($pluginList as $plugin) {
      if ( is_file("/boot/config/plugins/$plugin") ) {
        if ( ( $plugin == "fix.common.problems.plg") || ( $plugin == "dynamix.plg" ) || ($plugin == "unRAIDServer.plg") || ($plugin == "community.applications.plg") ) {
          continue;
        }
        $pluginURL = exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin pluginURL /var/log/plugins/$plugin");
        $flag = false;
        foreach ($allPlugins as $checkPlugin) {
          if ( $plugin == basename($checkPlugin['PluginURL']) ) {
            $flag = true;
            break;
          }
        }
        if ( ! $flag ) {
          addWarning("The plugin <font color='purple'><b>$plugin</b></font> is not known to Community Applications and is possibly incompatible with your server","As a <em>general</em> rule, if the plugin is not known to Community Applications, then it is not compatible with your server.  It is recommended to uninstall this plugin ".addLinkButton("Plugins","/Plugins"));
        }
      }
    }
  } else {
    addOther("Could not perform <font color='purple'><b>unknown plugins</b></font> installed checks","The download of the application feed failed.");
  }
  @unlink($fixPaths['templates']);
}

# check for docker applications installed but with changed container ports from what the author specified

if ( $dockerRunning ) {
  $dockerClient = new DockerClient();
  $info = $dockerClient->getDockerContainers();

  if ( is_array($templates['applist']) ) {
    $allApps = $templates['applist'];

    foreach ($info as $dockerInstalled) {
      $dockerImage = $dockerInstalled['Image'];
      foreach ($allApps as $app) {
        if ( ($app['Repository'] === str_replace(":latest","",$dockerImage) ) || ($app['Repository'] === $dockerImage) ) {
          $mode = strtolower($app['Networking']['Mode']);
          if ( $mode == "host" ) { continue;}
          if ( ! is_array($app['Networking']['Publish'][0]['Port']) ) { continue; }
 
          $allPorts = $app['Networking']['Publish'][0]['Port'];

          foreach ($allPorts as $port) {
            $flag = false;
            foreach ($dockerInstalled['Ports'] as $containerPort) {
              if ( $containerPort['PrivatePort'] == $port['ContainerPort']) {
                $flag = true;
                break;
              }
            }
            if ( ! $flag ) {
              addError("Docker Application <font color='purple'><b>".$dockerInstalled['Name'].", Container Port ".$port['ContainerPort']."</b></font> not found or changed on installed application","When changing ports on a docker container, you should only ever modify the <font color='purple'>HOST</font> port, as the application in question will expect the container port to remain the same as what the template author dictated.  Fix this here: ".addLinkButton("Docker","/Docker"));
            }
          }
        }
      }
    }
  } else {
    addOther("Could not perform <font color='purple'><b>docker application port</b></font> tests","The download of the application feed failed.");
  }
}

# test for illegal characters in share names

$shares = array_diff(scandir("/mnt/user"),array(".",".."));

foreach ($shares as $share) {
  if ( strpos($share, '\\') != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the \\ character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,"/") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the / character</font> which is an illegal character on Windows / Linux systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums.  You probably also have some disk corruption, as this folder should be impossible to create");
  }
  if ( strpos($share,":") ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the : character</font> which is an illegal character on Windows / MAC systems.","You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,"*") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the * character</font> which is an illegal character on Windows systems.","You may also run into issues with non-Windows systems when using this character.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,"?") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the ? character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,'"') != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the \" character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,"<") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the < character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share  You may also run into issues with non-Windows systems when using this character.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,">") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the > character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( strpos($share,"|") != false ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>the | character</font> which is an illegal character on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( trim($share) == "" ) {
    addError("Share <font color='purple'><b>\"$share\"</b></font> contains <font color='purple'>only spaces</font> which is illegal Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share/  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( substr($share, -1) == "." ) {
    addError("Share <font color='purple'><b>$share</b></font> ends with <font color='purple'>the . character</font> which is an illegal character to end a file / folder name  on Windows systems.","You may run into issues with Windows and/or other programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
  if ( ! ctype_print($share) ) {
    addError("Share <font color='purple'><b>$share</b></font> contains <font color='purple'>control character</font> which should be illegal characters on any OS.","You may run into issues with programs attempting to access this share.  You will most likely have to use the command prompt in order to rectify this error.  Ask for assistance on the unRaid forums");
  }
}


###################################################################
#                                                                 #
# Execute any custom scripts at /boot/fix.common.problems/scripts #
#                                                                 #
###################################################################

$allScripts = array_diff(scandir("/boot/config/plugins/fix.common.problems/scripts"),array(".",".."));

foreach ($allScripts as $script) {
  if ( $script == "sample.php" ) { 
    continue;
  }
  if ( is_executable("/boot/config/plugins/fix.common.problems/scripts/$script") ) {
    exec("/boot/config/plugins/fix.common.problems/scripts/$script");
  }
}


if ( ! $errors && ! $warnings && ! $otherWarnings ) {
  @unlink($fixPaths['errors']);
} else {
  $allErrors['errors'] = $errors;
  $allErrors['warnings'] = $warnings;
  $allErrors['other'] = $otherWarnings;
  writeJsonFile($fixPaths['errors'],$allErrors);
  if ( $errors ) {
    foreach ($errors as $error) {
      $message .= "**** ".strip_tags($error['error'])." ****   ";
    }
  }
  if ( $warnings ) {
    foreach ($warnings as $warning) {
      $message .= "**** ".strip_tags($warning['error'])." ****   ";
    }
  }
  if ( ! $disableNotifications ) {
    if ( $errors ) {
      if ( $fixSettings['notifications'] != "disabled" ) {
        notify("Fix Common Problems","Errors have been found with your server.","Investigate at Settings / User Utilities / Fix Common Problems",$message,"alert");
      }
    } else {
      if ( $warnings ) {
        if ($fixSettings['notifications'] != "errors" ) {
          notify("Fix Common Problems","Warnings have been found with your server.","Investigate at Settings / User Utilities / Fix Common Problems",$message,"warning");
        } 
      }
    }
  }
}    
?>