<?

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2017, Andrew Zawadzki    #
#                                                             #
###############################################################

$communityPaths['autoUpdateSettings']   = "/boot/config/plugins/ca.update.applications/AutoUpdateSettings.json";
$communityPaths['dockerUpdateSettings'] = "/boot/config/plugins/ca.update.applications/DockerUpdateSettings.json";

$fixPaths['dockerUpdateStatus']         = "/var/lib/docker/unraid-update-status.json";
$fixPaths['tempFiles']                  = "/tmp/fix.common.problems";
$fixPaths['flashPath']                  = "/boot/config/plugins/fix.common.problems";
$fixPaths['errors']                     = $fixPaths['tempFiles']."/errors.json";
$fixPaths['disks.ini']                  = "/var/local/emhttp/disks.ini";
#$fixPaths['disks.ini']                 = "/tmp/GitHub/disks.ini";                   # ONLY REMOVE COMMENT FOR TESTING
$fixPaths['syslogPath']                 = "/var/log";
#$fixPaths['syslogPath']                 = "/tmp/GitHub";                  # ONLY REMOVE COMMENT FOR TESTING
$fixPaths['settings']                   = $fixPaths['flashPath']."/settings.json";
$fixPaths['moderation']                 = $fixPaths['tempFiles']."/moderation.json";          /* json file that has all of the moderation */
$fixPaths['moderationURL']              = "https://raw.githubusercontent.com/Squidly271/Community-Applications-Moderators/master/Moderation.json";
$fixPaths['application-feed']           = "https://tools.linuxserver.io/unraid-docker-templates.json";
$fixPaths['templates']                  = $fixPaths['tempFiles']."/templates.json";
$fixPaths['var.ini']                    = "/var/local/emhttp/var.ini";
$fixPaths['ignoreList']                 = $fixPaths['flashPath']."/ignoreList.json";
$fixPaths['uncleanReboot']              = $fixPaths['tempFiles']."/resetCheckFlag";
$fixPaths['uncleanRebootFlag']          = $fixPaths['flashPath']."/resetCheckFlag";
$fixPaths['application-feed-last-updated'] = "http://tools.linuxserver.io/unraid-docker-templates.json?last_updated=1";
$fixPaths['troubleshoot']               = $fixPaths['tempFiles']."/troubleshoot";
$fixPaths['extendedStatus']             = $fixPaths['tempFiles']."/extendedStatus";
$fixPaths['extendedPID']                = $fixPaths['tempFiles']."/extendedPID";
$fixPaths['extendedLog']                = $fixPaths['tempFiles']."/extendedLog";
$fixPaths['OOMacknowledge']             = $fixPaths['tempFiles']."/OOMFlag";
$fixPaths['Traceacknowledge']           = $fixPaths['tempFiles']."/CallTraceFlag";
$fixPaths['MCEacknowledge']           = $fixPaths['tempFiles']."/mceFlag";

?>