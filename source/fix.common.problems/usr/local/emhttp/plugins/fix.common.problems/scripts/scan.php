#!/usr/bin/php
<?PHP
$communityPaths['autoUpdateSettings'] = "/boot/config/plugins/community.applications/AutoUpdate.json";
$fixPaths['dockerUpdateStatus'] = "/var/lib/docker/unraid-update-status.json";
$fixPaths['tempFiles'] = "/tmp/fix.common.problems";
$fixPaths['errors'] = $fixPaths['tempFiles']."/errors.json";
$fixPaths['disks.ini'] = "/var/local/emhttp/disks.ini";

exec("mkdir -p ".$fixPaths['tempFiles']);

require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");


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

function addLinkButton($buttonName,$link) {
  $link = str_replace("'","&quot;",$link);
  return "<input type='button' value='$buttonName' onclick='window.location.href=&quot;$link&quot;'>";
}

function addButton($buttonName,$action) {
  $action = str_replace("'","&quot;",$action);
  return "<input type='button' value='$buttonName' onclick='$action'>";
}


# start main



$communityApplicationsInstalled = is_file("/var/log/plugins/community.applications.plg");

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
      addError("Share <b><font color='purple'>$share</font></b> is an implied <em>array-only</em> share, but files / folders exist on the cache","Set <b><em>Use Cache</em></b> appropriately, then rerun this analysis. ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
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
        addError("Share <b><font color='purple'>$share</font></b> set to <em>cache-only</em>, but files / folders exist on the array",addButton("Move Files To Cache","moveToCache('$share');")." or change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
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
        addError("Share <b><font color='purple'>$share</font></b> set to <em>not use the cache</em>, but files / folders exist on the cache drive",addButton("Move Files To Array","moveToArray('$share');")." or change the share's settings appropriately ".addLinkButton("$share Settings","/Shares/Share?name=$shareURL"));
      }
    }
  }
}

# Check for Dynamix to perform plugin checks
    
if ( ! is_file("/boot/config/plugins/dynamix/plugin-check.cron") ) {
  addError("<font color='purple'><b>Plugin Update Check</b></font> not enabled",addLinkButton("Notification Settings","/Settings/Notifications"));
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
      addError("This plugin <font color='purple'><b>(Fix Common Problems)</b></font> not set to auto update</font>",addLinkButton("Auto Update Settings","/Settings/AutoUpdate")."Recommended to enable auto updates for this plugin to enable further problem solving / fixes");
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
    
foreach ($availableDrives as $drive) {
  $filename = randomFile("/mnt/$drive");
    
  @file_put_contents($filename,"test");
  $result = @file_get_contents($filename);
      
  if ( $result != "test" ) {
    addError("Unable to write to <font color='purple'>$drive","Drive mounted read-only or completely full.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
  }
  @unlink($filename);
}
$filename = randomFile("/boot");
@file_put_contents($filename,"test");
$result = @file_get_contents($filename);

if ( $result != "test" ) {
  addError("Unable to write to <font color='purple'><b>flash drive</b>","Drive mounted read-only or completely full.  Begin Investigation Here: ".addLinkButton("unRaid Main","/Main"));
}
@unlink($filename);

if ( is_dir("/var/lib/docker/tmp") ) {
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

if ( is_file("/var/lib/docker/tmp") ) {
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

if ( is_file("/var/lib/docker/tmp") ) {
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
if ( is_dir("/var/lib/docker/tmp") ) {
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

$filename = randomFile("/tmp");
download_url("http://currentmillis.com/time/minutes-since-unix-epoch.php",$filename);
$actualTime = @file_get_contents($filename);
if (intval($actualTime) > 24377381 ) { # current time as of this code being written as a check for complete download_url
  $serverTime = intval(time() / 60);
  $timeDifference = abs($serverTime - intval($actualTime));
  
  if ( $timeDifference > 5 ) {
    addWarning("Your server's <font color='purple'><b>current time</b></font> differs from the actual time by more than 5 minutes.  Currently out by approximately <font color='purple'>$timeDifference minutes</font>","Either set your date / time manually, or set up the server to use an NTP server to automatically update the date and time".addLinkButton("Date and Time Settings","/Settings/DateTime"));  
  }
}

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

@unlink($filename);

if ( ! $errors && ! $warnings ) {
  @unlink($fixPaths['errors']);
} else {
  $allErrors['errors'] = $errors;
  $allErrors['warnings'] = $warnings;
  writeJsonFile($fixPaths['errors'],$allErrors);
}
      
?>