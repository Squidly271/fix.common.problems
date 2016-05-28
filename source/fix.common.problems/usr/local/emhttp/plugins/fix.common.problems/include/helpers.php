<?PHP
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");

#############################
#                           #
# Adds an error to the list #
#                           #
#############################

function addError($description,$action) {
  global $errors, $ignoreList, $ignored;

  $originalDescription = $description;
  $description = str_replace("'","&#39;",$description);
  $newError['error'] = "<font color='red'>$description</font>";
  $newError['suggestion'] = $action;
  logger("Fix Common Problems: Error: ".strip_tags($description));
#  logger("Fix Common Problems: Suggestion:".strip_tags($action));
  if ( $ignoreList[strip_tags($description)] ) {
    $ignored[] = $newError;
  } else {
    $errors[] = $newError;
  }
}

#############################
#                           #
# Add a warning to the list #
#                           #
#############################

function addWarning($description,$action) {
  global $warnings, $ignoreList, $ignored;
  
  $originalDescription = $description;
  $description = str_replace("'","&#39;",$description);
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Warning: ".strip_tags($description));
#  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  
  if ( $ignoreList[strip_tags($originalDescription)] ) {
    $ignored[] = $newWarning;
  } else {
    $warnings[] = $newWarning;
  }
}

#####################################
#                                   #
# Adds an other comment to the list #
#                                   #
#####################################

function addOther($description,$action) {
  global $otherWarnings;

  $description = str_replace("'","&#39;",$description);
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Other Warning: ".strip_tags($description));
#  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  $otherWarnings[] = $newWarning;
}

############################################
#                                          #
# Adds a button with a link attached to it #
#                                          #
############################################

function addLinkButton($buttonName,$link) {
  $link = str_replace("'","&quot;",$link);
  return "<input type='button' value='$buttonName' onclick='window.location.href=&quot;$link&quot;'>";
}

########################################
#                                      #
# Adds a button with a javascript link #
#                                      #
########################################

function addButton($buttonName,$action) {
  $action = str_replace("'","&quot;",$action);
  $id = mt_rand();
  return "<input type='button' id='$id' value='$buttonName' onclick='$action'>";
}

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

##########################
#                        #
# returns unRaid version #
#                        #
##########################

function unRaidVersion() {
  $unRaidVersion = parse_ini_file("/etc/unraid-version");
  return $unRaidVersion['version'];
}

#################################################################
#                                                               #
# checks the Min/Max version of an app against unRaid's version #
# Returns: TRUE if it's valid to run, FALSE if not              #
#                                                               #
#################################################################

function versionCheck($template) {
  global $unRaidVersion;

  if ( $template['MinVer'] ) {
    if ( version_compare($template['MinVer'],$unRaidVersion) > 0 ) { return false; }
  }

  if ( $template['MaxVer'] ) {
    if ( version_compare($template['MaxVer'],$unRaidVersion) < 0 ) { return false; }
  }

  return true;
}

###############################################
#                                             #
# Function to read a template XML to an array #
#                                             #
###############################################

function readXmlFile($xmlfile) {
  $doc = new DOMDocument();
  $doc->load($xmlfile);
  if ( ! $doc ) { return false; }
    $o['WebUI']       = $doc->getElementsByTagName( "WebUI" )->item(0)->nodeValue;

  $o['Path']        = $xmlfile;
  $o['Repository']  = stripslashes($doc->getElementsByTagName( "Repository" )->item(0)->nodeValue);
  $o['Author']      = preg_replace("#/.*#", "", $o['Repository']);
  $o['Name']        = stripslashes($doc->getElementsByTagName( "Name" )->item(0)->nodeValue);
  $o['DockerHubName'] = strtolower($o['Name']);
  $o['Beta']        = strtolower(stripslashes($doc->getElementsByTagName( "Beta" )->item(0)->nodeValue));
  $o['Changes']     = $doc->getElementsByTagName( "Changes" )->item(0)->nodeValue;
  $o['Date']        = $doc->getElementsByTagName( "Date" ) ->item(0)->nodeValue;
  $o['Project']     = $doc->getElementsByTagName( "Project" ) ->item(0)->nodeValue;
  $o['SortAuthor']  = $o['Author'];
  $o['SortName']    = $o['Name'];
  $o['MinVer']      = $doc->getElementsByTagName( "MinVer" ) ->item(0)->nodeValue;
  $o['MaxVer']      = $doc->getElementsByTagName( "MaxVer" ) ->item(0)->nodeValue;
  $o['Overview']    = $doc->getElementsByTagName("Overview")->item(0)->nodeValue;
  if ( strlen($o['Overview']) > 0 ) {
    $o['Description'] = stripslashes($doc->getElementsByTagName( "Overview" )->item(0)->nodeValue);
    $o['Description'] = preg_replace('#\[([^\]]*)\]#', '<$1>', $o['Description']);
  } else {
    $o['Description'] = $doc->getElementsByTagName( "Description" )->item(0)->nodeValue;
  }
  $o['Plugin']      = $doc->getElementsByTagName( "Plugin" ) ->item(0)->nodeValue;
  $o['PluginURL']   = $doc->getElementsByTagName( "PluginURL" ) ->item(0)->nodeValue;
  $o['PluginAuthor']= $doc->getElementsByTagName( "PluginAuthor" ) ->item(0)->nodeValue;

# support both spellings
  $o['Licence']     = $doc->getElementsByTagName( "License" ) ->item(0)->nodeValue;
  $o['Licence']     = $doc->getElementsByTagName( "Licence" ) ->item(0)->nodeValue;
  $o['Category']    = $doc->getElementsByTagName ("Category" )->item(0)->nodeValue;

  if ( $o['Plugin'] ) {
    $o['Author']     = $o['PluginAuthor'];
    $o['Repository'] = $o['PluginURL'];
    $o['Category']   .= " Plugins: ";
    $o['SortAuthor'] = $o['Author'];
    $o['SortName']   = $o['Name'];
  }
  $o['Description'] = preg_replace('#\[([^\]]*)\]#', '<$1>', $o['Description']);
  $o['Overview']    = $doc->getElementsByTagName("Overview")->item(0)->nodeValue;

  $o['Announcement'] = $Repo['forum'];
  $o['Support']     = ($doc->getElementsByTagName( "Support" )->length ) ? $doc->getElementsByTagName( "Support" )->item(0)->nodeValue : $Repo['forum'];
  $o['Support']     = $o['Support'];
  $o['IconWeb']     = stripslashes($doc->getElementsByTagName( "Icon" )->item(0)->nodeValue);
  
  return $o;
}


?>
