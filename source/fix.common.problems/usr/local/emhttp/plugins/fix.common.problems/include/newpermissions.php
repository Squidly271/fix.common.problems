<?PHP

require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");



switch ($_POST['action']) {
  case "showExcluded":
    $excludedShares = getAppData();
    $settings = readJsonFile($fixPaths['settings']);
    
    if ( $settings['excludedPerms'] ) {
      $exclude = explode(",",$settings['excludedPerms']);
      foreach ($exclude as $excluded) {
        $excludedShares[$excluded] = $excluded."&nbsp;&nbsp&nbsp;(<i>Excluded via Fix Common Problems' Settings</i>)";
      }
    }
  
    if ( empty($excludedShares) ) {
      echo "<font color='red'>No shares will be excluded</font>";
    } 
    foreach ($excludedShares as $share) {
      echo "<font color='red'><b>$share</b></font><br>";
    }
    echo "<br>";
    break;
}
 ?>