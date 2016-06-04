<?PHP

require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");



switch ($_POST['action']) {
  case "showExcluded":
    $excludedShares = getAppData();
  
    if ( empty($excludedShares) ) {
      echo "No shares will be excluded";
    }
    foreach ($excludedShares as $share) {
      echo "<b>$share</b><br>";
    }
}
 ?>