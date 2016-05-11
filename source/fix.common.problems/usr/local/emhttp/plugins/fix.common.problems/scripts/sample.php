#!/usr/bin/php
<?PHP

##################################################
#                                                #
# Some helper functions to make your life easier #
#                                                #
##################################################

function readJsonFile($filename) {
  return @json_decode(@file_get_contents($filename),true);
}

function writeJsonFile($filename,$jsonArray) {
  file_put_contents($filename,json_encode($jsonArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

function addError($description,$action) {
  global $errors;

  $newError['error'] = "<font color='red'>$description</font>";
  $newError['suggestion'] = $action;
  logger("Fix Common Problems: Error: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion:".strip_tags($action));
  $errors[] = $newError;
}

function addWarning($description,$action) {
  global $warnings;
  
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Warning: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  $warnings[] = $newWarning;
}

function addOther($description,$action) {
  global $otherWarnings;
  
  $newWarning['error'] = "$description";
  $newWarning['suggestion'] = $action;
  logger("Fix Common Problems: Other Warning: ".strip_tags($description));
  logger("Fix Common Problems: Suggestion: ".strip_tags($action));
  $otherWarnings[] = $newWarning;
}

function addLinkButton($buttonName,$link) {
  $link = str_replace("'","&quot;",$link);
  return "<input type='button' value='$buttonName' onclick='window.location.href=&quot;$link&quot;'>";
}

function logger($string) {
  $string = str_replace("'","",$string);
  shell_exec("logger '$string'");
}

#######################
#                     #
# BEGIN MAIN ROUTINES #
#                     #
#######################


# Read in the existing error log

$existingErrors = readJsonFile("/tmp/fix.common.problems/errors.json");

$errors        = $existingErrors['errors'];
$warnings      = $existingErrors['warnings'];
$otherWarnings = $existingErrors['other'];

####
##
## Begin your tests, calling addErrors, addWarning, addOther accordingly.
## call addLinkButton() to easily include a link to somewhere in unRaid
## 
####

file_put_contents("/tmp/blah","here");

################################
#                              #
# Write the resulting file out #
#                              #
################################

if ( ! $errors && ! $warnings && ! $otherWarnings ) {
  @unlink($fixPaths['errors']);
} else {
  $allErrors['errors'] = $errors;
  $allErrors['warnings'] = $warnings;
  $allErrors['other'] = $otherWarnings;
  writeJsonFile("/tmp/fix.common.problems/errors.json",$allErrors);
}

?>
