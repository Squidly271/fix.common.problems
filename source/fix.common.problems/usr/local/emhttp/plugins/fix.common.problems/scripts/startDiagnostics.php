#!/usr/bin/php
<?PHP
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################


require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/tests.php");

exec("mkdir -p ".$fixPaths['tempFiles']);

while ( true ) {
  exec("/usr/local/emhttp/webGui/scripts/diagnostics");
  exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php troubleshoot");
  sleep(600);
  exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php troubleshoot");
  sleep(600);
  exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php troubleshoot");
  sleep(600);  
}
?>
