<?

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
$fixPaths['var.ini']                  = "/var/local/emhttp/var.ini";
$fixPaths['ignoreList']               = "/boot/config/plugins/fix.common.problems/ignoreList.json";

?>