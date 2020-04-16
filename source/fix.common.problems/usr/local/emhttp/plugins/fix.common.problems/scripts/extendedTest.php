#!/usr/bin/php
<?
###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2020, Andrew Zawadzki    #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");


function illegalCharacter($filename) {
  if ( strpos($filename, '\\') != false ) { return true; }
  if ( strpos($filename,"/") != false )   { return true; }
  if ( strpos($filename,":") != false )   { return true; }
  if ( strpos($filename,"*") != false )   { return true; }
  if ( strpos($filename,"?") != false )   { return true; }
  if ( strpos($filename,'"') != false )   { return true; }
  if ( strpos($filename,"<") != false )   { return true; }
  if ( strpos($filename,">") != false )   { return true; }
  if ( strpos($filename,"|") != false )   { return true; }
  if ( trim($filename) == "" )            { return true; }
  if ( substr($filename, -1) == "." )     { return true; }

  return false;
}

function echoResult($string) {
  global $fixPaths;
  
  echo $string;
	$string = str_replace("\n","<br>",$string);
	$string = str_replace(" ","&nbsp;",$string);
  file_put_contents($fixPaths['extendedLog'],"<tt>$string</tt>",FILE_APPEND);
}


function scanDirectory($directory) {
  global $disks, $errors, $excludedDirectory, $fixPaths, $totalDir, $totalFile;
  
  echo $directory."\n";
  if ( is_link($directory) ) { return; }
/*   $symLinkTest = exec("file ".escapeshellarg($directory));
  if ( strpos($symLinkTest,"symbolic link") ) { return; }; */
#  if ( $directory == "/mnt/user/appdata" || $directory == "/mnt/user/Backups" ) { return; }
  $folderContents = array_diff(scandir($directory),array(".",".."));
  foreach ($folderContents as $entry) {
    $testing = str_replace("/mnt/user/","","$directory/$entry");
    $share = explode("/",$testing);
    if ( $directory == "/mnt/user" )
    {
		  if ( $excludedDirectory[$share[0]] ) continue;
      echoResult("Processing $directory/$entry\n");
      file_put_contents($fixPaths['extendedStatus'],"Processing $directory/$entry");
    }


    

    if ( ! $excludedDirectory[$share[0]] ) {
      if ( illegalCharacter($entry) ) {
        $errors['illegal'][] = "$directory/$entry";
      }

      if ( is_file("$directory/$entry") || is_dir("$directory/$entry") ) {
        $fileOwner = fileowner("$directory/$entry");
        $fileGroup = filegroup("$directory/$entry");
        $filePerm = fileperms("$directory/$entry");
      } else {
        $fileOwner = false;
        $fileGroup = false;
        $filePerm = false;
      }
      if ( ($fileOwner == 99 ) && ($fileGroup == 100) ) {
        if ( (! ($filePerm & 0x0060)) || (! ($filePerm & 0x0006) ) ) {
          $userinfo = posix_getpwuid($fileOwner);
          $groupinfo = posix_getgrgid($fileGroup);
          $permissioninfo = substr(sprintf("%o", $filePerm),-4);
          $errors['permissions'][] = "$directory/$entry  ".$userinfo['name']."/".$groupinfo['name']." ($fileOwner/$fileGroup)  $permissioninfo";
        }
      } else {
        if ( ! ($filePerm & 0x0006) ) {
          $userinfo = posix_getpwuid($fileOwner);
          $groupinfo = posix_getgrgid($fileGroup);
          $permissioninfo = substr(sprintf("%o", $filePerm),-4);
          $errors['permissions'][] = "$directory/$entry   ".$userinfo['name']."/".$groupinfo['name']." ($fileOwner/$fileGroup)  $permissioninfo";
        }
      }        
    }
	  if ( $excludedDirectory[$share[0]] ) continue;
    if ( $entry != trim($entry) ) {
      $errors['whitespace'][] = escapeshellarg("$directory/$entry");
    }
    if ( is_dir("$directory/$entry") ) {
      $totalDir = $totalDir + 1;
      $origContent = strtolower($entry);
      $count = 0;
      foreach ( $folderContents as $testContent) {
        if ( is_dir("$directory/$testContent") ) {
          if ( (strtolower($testContent) === $origContent) ) {
            $count = $count + 1;
          }
        }
      }
      if ( $count > 1 ) {
        $errors['case'][] = "$directory/$entry";
      }

      scanDirectory("$directory/$entry");
    } else {
      $totalFile = $totalFile + 1;
      $count = 0;
      unset($dupeDisk);
      foreach ($disks as $disk) {
        $testPath = str_replacE("/mnt/user/","/mnt/".$disk['name']."/","$directory/$entry");
        if ( is_file($testPath) ) {
          $count = $count + 1;
          $dupeDisk .= " ".$disk['name'];
        }
      }
      if ( $count > 1 ) {
        $errors['dupe'][] = "$directory/$entry  $dupeDisk";
      }
    }
  }
}

ini_set("memory_limit","-1");
if ( ! is_dir("/mnt/user") ) {
  echoResult("User shares must be enabled to perform the extended file tests\n\n");
  return;
}

file_put_contents($fixPaths['extendedPID'],getmypid());
@unlink($fixPaths['extendedLog']);

notify("Fix Common Problems","Extended Tests Beginning","","","normal");
$excludedDirectory = getAppData();
$settings = readJsonFile($fixPaths['settings']);
if ( $settings['excludedPerms'] ) {
  $exclude = explode(",",$settings['excludedPerms']);
  foreach ($exclude as $excluded) {
    $excludedDirectory[$excluded] = $excluded;
  }
}

if ( ! empty($excludedDirectory) ) {
  echoResult("<b>The following user shares will be excluded from the permissions tests:</b>\n\n");
  foreach ($excludedDirectory as $share) {
    echoResult("/mnt/user/$share\n");
  }
  echoResult("\n");
}

$disks = my_parse_ini_file("/var/local/emhttp/disks.ini",true);
scanDirectory("/mnt/user");

if ( ! is_array($errors) ) {
  echoResult("<b>No problems found</b>\n");
  notify("Fix Common Problems","Extended Tests Completed","No faults found","","normal");
  @unlink($fixPaths['extendedPID']);
} else {
  echoResult("\n\n");
  if ( is_array($errors['case']) ) {
    echoResult("\n<b>The following directories exist with similar names, only differing by the 'case' which will play havoc with Windows / SMB access.  Windows does NOT support folder names only differing by their case and strange results will happen should you attempt to manipulate the folders or files</b>\n\n");
    foreach ( $errors['case'] as $error ) {
      echoResult($error."\n");
    }
    echoResult("\n");
  }
  if ( is_array($errors['dupe']) ) {
    echoResult("<b>The following files exist within the same folder on more than one disk.  This duplicated file means that only the version on the lowest numbered disk will be readable, and the others are only going to confuse unRaid and take up excess space:</b>\n\n");
    foreach ( $errors['dupe'] as $error ) {
      echoResult($error."\n");
    }    
    echoResult("\n");
  }
  if ( is_array($errors['permissions']) ) {
    echoResult("<b>The following files / folders may not be accessible to the users allowed via each Share's SMB settings.  This is often caused by wrong permissions being used on new downloads / copies by CouchPotato, Sonarr, and the like:</b>\n\n");
    foreach ( $errors['permissions'] as $error ) {
      echoResult($error."\n");
    }    
    echoResult("\n");
  }
  if ( is_array($errors['illegal']) ) {
    echoResult("<b>The following files / folders contain illegal characters in its name, which is going to confuse / cause issues with Windows / MAC systems:</b>\n\n");
    foreach ( $errors['illegal'] as $error ) {
      echoResult($error."\n");
    }    
    echoResult("\n");
  }
  if ( is_array($errors['whitespace']) ) {
    echoResult("<b>The following files / folders contain whitespace at either the beginning or the end of the file names.  This will cause issues with Windows / SMB because whitespace is disallowed at either the beginning or end of a file/folder</b>\n\n");
    foreach ( $errors['whitespace'] as $error ) {
      echoResult($error."\n");
    }    
    echoResult("\n"); 
  }
  echoResult("\n");
  echoResult("Directories Scanned: $totalDir  Files Scanned: $totalFile\n");
  notify("Fix Common Problems","Extended Tests Completed","Errors Found","See logs for details","warning");
}
@unlink($fixPaths['extendedPID']);

?>
