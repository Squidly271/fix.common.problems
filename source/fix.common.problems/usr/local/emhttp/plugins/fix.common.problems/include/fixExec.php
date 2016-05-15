<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

$communityPaths['autoUpdateSettings'] = "/boot/config/plugins/community.applications/AutoUpdate.json";
$fixPaths['tempFiles'] = "/tmp/fix.common.problems";
$fixPaths['errors'] = $fixPaths['tempFiles']."/errors.json";
$fixPaths['settings'] = "/boot/config/plugins/fix.common.problems/settings.json";
$fixPaths['ignoreList'] = "/boot/config/plugins/fix.common.problems/ignoreList.json";


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

function displayErrors() {

}

switch ($_POST['action']) {
  case 'scan':
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php 1",$output);
    if ($output) {
      foreach ($output as $line) {
        echo $line."<br>";
      }
    }

      $allErrors = readJsonFile($fixPaths['errors']);
    
    $errors = $allErrors['errors'];
    echo "<table class='tablesorter'>";
    echo "<thead><th>Errors Found</th><th>Suggested Fix</th><th></th></thead>";    
    if ( ! $errors ) {
      echo "<tr><td><b>No errors found";
    } else {

      foreach ($errors as $error) {
        echo "<tr><td width='40%'>".$error['error']."</td><td>".$error['suggestion']."</td>";
        echo "<td><input type='button' value='Ignore Error' onclick='ignoreError(&quot;".strip_tags($error['error'])."&quot;);';></td></tr>";
      }
    }
    echo "</table>";
    echo "<table class='tablesorter'>";
    echo "<thead><th>Warnings Found</th><th>Suggested Fix</th><th></th><th></th></thead>";
    $warnings = $allErrors['warnings'];
    if ( ! $warnings ) {
      echo "<tr><td><b>No Warnings found";
    } else {
      foreach ($warnings as $warning) {
        echo "<tr><td width='40%'>".$warning['error']."</td><td>".$warning['suggestion']."</td>";
        echo "<td><input type='button' value='Ignore Warning' onclick='ignoreError(&quot;".strip_tags($warning['error'])."&quot;);';></td></tr>";
      }
    }
    echo "</table>";

    $others = $allErrors['other'];
    if ( $others ) {
      echo "<table class='tablesorter'>";
      echo "<thead><th>Other Comments</th><th>Comments</th></thead>";
      foreach ($others as $other) {
        echo "<tr><td width='40%'>".$other['error']."</td><td>".$other['suggestion']."</td></tr>";      
      }
      echo "</table>";
    }
    $ignored = $allErrors['ignored'];
    if ( $ignored ) {
      echo "<table class='tablesorter'>";
      echo "<thead><th>Ignored Errors</th><th>Suggested Fix</th><th></th></thead>";
      foreach ($ignored as $ignore) {
        echo "<tr><td width='40%'>".$ignore['error']."</td><td>".$ignore['suggestion']."</td>";
        echo "<td><input type='button' value='ReAdd Error' onclick='readdError(&quot;".strip_tags($ignore['error'])."&quot;);';></td></tr>";
      }
      echo "</table>";
      echo "<center>";
      echo "<input type='button' value='ReAdd ALL Errors' onclick='readdAll();'>";
    }
    
    break;
  case 'apply':
    $settings['frequency'] =isset($_POST['frequency']) ? urldecode(($_POST['frequency'])) : "";
    $settings['notifications'] = isset($_POST['notifications']) ? urldecode(($_POST['notifications'])) : "";
    $settings['disableSpinUp'] = isset($_POST['disableSpinUp']) ? urldecode(($_POST['disableSpinUp'])) : "";
    
    writeJsonFile($fixPaths['settings'],$settings);
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/applyFrequency.php");
    break;
  case 'ignoreError':
    $ignore = isset($_POST['error']) ? urldecode(($_POST['error'])) : "";
    $ignoreList = readJsonFile($fixPaths['ignoreList']);
    $ignoreList[$ignore] = "true";
    writeJsonFile($fixPaths['ignoreList'],$ignoreList);
    break;
  case 'readdError':
    $ignore = isset($_POST['error']) ? urldecode(($_POST['error'])) : "";
    $ignoreList = readJsonFile($fixPaths['ignoreList']);
    unset($ignoreList[$ignore]);
    writeJsonFile($fixPaths['ignoreList'],$ignoreList);
    break;
  case 'readdAll':
    @unlink($fixPaths['ignoreList']);
    break;
    
}
?>