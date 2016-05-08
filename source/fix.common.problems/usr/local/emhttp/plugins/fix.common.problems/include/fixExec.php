<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

$communityPaths['autoUpdateSettings'] = "/boot/config/plugins/community.applications/AutoUpdate.json";
$fixPaths['tempFiles'] = "/tmp/fix.common.problems";
$fixPaths['errors'] = $fixPaths['tempFiles']."/errors.json";


require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");

function addError($description,$action) {
  global $errors;
  $errors .= "<tr><td><font color='red'>$description</font></td><td>$action</td></tr>";
}

function addLinkButton($buttonName,$link) {
  $link = str_replace("'","&quot;",$link);
  return "<input type='button' value='$buttonName' onclick='window.location.href=&quot;$link&quot;'>";
}
function addButton($buttonName,$action) {
  $action = str_replace("'","&quot;",$action);
  return "<input type='button' value='$buttonName' onclick='$action'>";
}

$communityApplicationsInstalled = is_file("/var/log/plugins/community.applications.plg");

switch ($_POST['action']) {
  case 'scan':
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php",$output);
    if ($output) {
      foreach ($output as $line) {
        echo $line."<br>";
      }
    }
    $allErrors = readJsonFile($fixPaths['errors']);
    
    $errors = $allErrors['errors'];
    echo "<table class='tablesorter'>";
    echo "<thead><th>Error Found</th><th>Suggested Fix</th></thead>";    
    if ( ! $errors ) {
      echo "<tr><td>No errors found";
    } else {
      foreach ($errors as $error) {
        echo "<tr><td width='40%'>".$error['error']."</td><td>".$error['suggestion']."</td></tr>";
      }
    }
    echo "</table>";
    echo "<table class='tablesorter'>";
    echo "<thead><th>Warnings Found</th><th>Suggested Fix</th></thead>";
    $warnings = $allErrors['warnings'];
    if ( ! $warnings ) {
      echo "<tr><td>No Warnings found";
    } else {

      foreach ($warnings as $warning) {
        echo "<tr><td width='40%'>".$warning['error']."</td><td>".$warning['suggestion']."</td></tr>";
      }
    }
    echo "</table>";
    break;
}
?>