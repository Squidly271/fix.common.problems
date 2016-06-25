<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");

function displayErrors() {
  global $fixPaths, $fixSettings, $autoUpdateOverride, $developerMode, $communityApplicationsInstalled, $dockerRunning, $ignoreList, $shareList;

  $allErrors = readJsonFile($fixPaths['errors']);
    
  $errors = $allErrors['errors'];

  echo "<table class='tablesorter'>";
  echo "<thead><th width='25%'>Errors Found</th><th width='60%'>Suggested Fix</th><th></th></thead>";    
  if ( ! $errors ) {
    echo "<tr><td><b><img src='https://raw.githubusercontent.com/Squidly271/fix.common.problems/master/images/happy_face.gif' width='10%'>  No errors found</b>";
  } else {
    foreach ($errors as $error) {
      echo "<tr><td>".$error['error']."</td><td>".$error['suggestion']."</td>";
      echo "<td><input type='button' id='".mt_rand()."' value='Ignore Error' onclick='ignoreError(&quot;".strip_tags($error['error'])."&quot;,&quot;error&quot;,this.id);'></td></tr>";
    }
  }
  echo "</table>";
  echo "<table class='tablesorter'>";
  echo "<thead><th width='25%'>Warnings Found</th><th width='60%'>Suggested Fix</th><th></th></thead>";
  $warnings = $allErrors['warnings'];
  if ( ! $warnings ) {
    echo "<tr><td><b><img src='https://raw.githubusercontent.com/Squidly271/fix.common.problems/master/images/happy_face.gif' width='10%'>  No Warnings found</b>";
  } else {
    foreach ($warnings as $warning) {
      echo "<tr><td>".$warning['error']."</td><td>".$warning['suggestion']."</td>";
      echo "<td><input type='button' id='".mt_rand()."' value='Ignore Warning' onclick='ignoreError(&quot;".strip_tags($warning['error'])."&quot;,&quot;warning&quot;,this.id);'></td></tr>";
    }
  }
  echo "</table>";
  $others = $allErrors['other'];
  if ( $others ) {
    echo "<table class='tablesorter'>";
    echo "<thead><th width='25%'>Other Comments</th><th width='60%'>Comments</th><th></thead>";
    foreach ($others as $other) {
      echo "<tr><td>".$other['error']."</td><td>".$other['suggestion']."</td>";      
      echo "<td><input type='button' id='".mt_rand()."' value='Ignore Comment' onclick='ignoreError(&quot;".strip_tags($other['error'])."&quot;,&quot;other comment&quot;,this.id);'></td></tr>";
    }
    echo "</table>";
  }
  $ignored = $allErrors['ignored'];
  if ( $ignored ) {
    echo "<table class='tablesorter'>";
    echo "<thead><th width='25%'>Ignored Errors & Warnings</th><th width='60%'>Suggested Fix</th><th></th></thead>";
    foreach ($ignored as $ignore) {
      echo "<tr><td>".$ignore['error']."</td><td>".$ignore['suggestion']."</td>";
      echo "<td><input type='button' id='".mt_rand()."' value='Monitor Warning / Error' onclick='readdError(&quot;".strip_tags($ignore['error'])."&quot;,this.id);';></td></tr>";
    }
    echo "</table>";
    echo "<center>";
    echo "<input type='button' value='Monitor All Ignored' onclick='readdAll();'>";
  }
}


switch ($_POST['action']) {
  case 'scan':
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/scan.php 1",$output);
    if ($output) {
      foreach ($output as $line) {
        echo $line."<br>";
      }
    }
    displayErrors();
    break;
  case 'apply':
    $settings['frequency'] =isset($_POST['frequency']) ? urldecode(($_POST['frequency'])) : "";
    $settings['notifications'] = isset($_POST['notifications']) ? urldecode(($_POST['notifications'])) : "";
    $settings['disableSpinUp'] = isset($_POST['disableSpinUp']) ? urldecode(($_POST['disableSpinUp'])) : "";
    $settings['hacksPerDay'] = isset($_POST['hacksPerDay']) ? urldecode(($_POST['hacksPerDay'])) : "";
    $settings['logIgnored'] = isset($_POST['logIgnored']) ? urldecode(($_POST['logIgnored'])) : "";
    
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
  
  case 'acknowledgeUncleanReboot':
    @unlink("/tmp/fix.common.problems/resetCheckFlag");
    break;
    
  case 'troubleshoot':
    file_put_contents("/tmp/fix.common.problems/troubleshoot","troubleshooting mode");
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/starttail.sh");
    break;
    
  case 'getTimeStamp':
    if ( is_file("/tmp/fix.common.problems/errors.json") ) {
      $errorTimeStamp = date("l F dS  g:i A",filemtime("/tmp/fix.common.problems/errors.json"));
      echo $errorTimeStamp;
    } else {
      echo "*".date("l F dS  g:i A");
    }
    break;
  case 'displayErrors':
    displayErrors();
    break;
  
  case 'checkExtendedStatus':
    $status = @file_get_contents($fixPaths['extendedStatus']);
    $running = is_file($fixPaths['extendedPID']);
    $o = "";
    if ( $running ) {
      $o .= "$status<script>$('#extendedTest').prop('disabled',true);</script>";
      $o .= "<script>$('#extendedLog').prop('disabled',true);</script>";
    } else {
      $o .= "Not Running <script>$('#extendedTest').prop('disabled',false);</script>";
      if ( $status ) {
        $o .= "<script>$('#extendedLog').prop('disabled',false);</script>";
      } else {
        $o .= "<script>$('#extendedLog').prop('disabled',true);</script>";
      }
    }
    echo $o;
    break;
  case 'runExtended':
    exec("/usr/local/emhttp/plugins/fix.common.problems/scripts/startExtended.sh");
}
?>