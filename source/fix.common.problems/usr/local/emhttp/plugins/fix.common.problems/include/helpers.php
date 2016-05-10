<?PHP
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");

###########################################################################
#                                                                         #
# Helper function to determine if a plugin has an update available or not #
#                                                                         #
###########################################################################

function checkPluginUpdate($filename) {
  $filename = basename($filename);
  $installedVersion = exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin version /var/log/plugins/$filename");
  if ( is_file("/tmp/plugins/$filename") ) {
    $upgradeVersion = exec("/usr/local/emhttp/plugins/dynamix.plugin.manager/scripts/plugin version /tmp/plugins/$filename");
  } else {
    $upgradeVersion = "0";
  }
  if ( $installedVersion < $upgradeVersion ) {
    return true;
  } else {
    return false;
  }
}

###################################################################################
#                                                                                 #
# returns a random file name                                                      #
#                                                                                 #
###################################################################################

function randomFile($basePath) {
  global $communityPaths;
  while (true) {
    $filename = $basePath."/".mt_rand().".tmp";
    if ( ! is_file($filename) ) {
      break;
    }
  }
  return $filename;
}

##################################################################
#                                                                #
# 2 Functions to avoid typing the same lines over and over again #
#                                                                #
##################################################################

function readJsonFile($filename) {
  return @json_decode(@file_get_contents($filename),true);
}

function writeJsonFile($filename,$jsonArray) {
  file_put_contents($filename,json_encode($jsonArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

##############################################################
#                                                            #
# Searches an array of docker mappings (host:container path) #
# for a container mapping of /config and returns the host    #
# path                                                       #
#                                                            #
##############################################################

function findAppdata($volumes) {
  $path = false;
  if ( is_array($volumes) ) {
    foreach ($volumes as $volume) {
      $temp = explode(":",$volume);
      $testPath = strtolower($temp[1]);
    
      if (startsWith($testPath,"/config") ) {
        $path = $temp[0];
        break;
      }
    }
  }
  return $path;
}

############################################
#                                          #
# Function to write a string to the syslog #
#                                          #
############################################

function logger($string) {
  $string = str_replace("'","",$string);
  shell_exec("logger '$string'");
}

###########################################
#                                         #
# Function to send a dynamix notification #
#                                         #
###########################################

function notify($event,$subject,$description,$message,$type="normal") {
  $command = '/usr/local/emhttp/plugins/dynamix/scripts/notify -e "'.$event.'" -s "'.$subject.'" -d "'.$description.'" -m "'.$message.'" -i "'.$type.'"';
  shell_exec($command);
}

#################################################################
#                                                               #
# Helper function to determine if $haystack begins with $needle #
#                                                               #
#################################################################

function startsWith($haystack, $needle) {
  return $needle === "" || strripos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

###############################################
#                                             #
# Helper function to download a URL to a file #
#                                             #
###############################################

function download_url($url, $path = "", $bg = false){
  exec("curl --max-time 60 --silent --insecure --location --fail ".($path ? " -o '$path' " : "")." $url ".($bg ? ">/dev/null 2>&1 &" : "2>/dev/null"), $out, $exit_code );
  return ($exit_code === 0 ) ? implode("\n", $out) : false;
}

###
#
# returns unRaid version
#
###

function unRaidVersion() {
  $unRaidVersion = parse_ini_file("/etc/unraid-version");
  return $unRaidVersion['version'];
}

###############################################
#                                             #
# Search array for a particular key and value #
# returns the index number of the array       #
# return value === false if not found         #
#                                             #
###############################################

function searchArray($array,$key,$value) {
  $result = array_search($value, array_column($array, $key));
  
  return $result;
}

?>
