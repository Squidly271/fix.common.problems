<?
#
# Fix Common Problems
#

$docroot = $docroot ?: @$_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "$docroot/webGui/include/Markdown.php";

###########
# Helpers #
###########

function xml_encode($string) {
  return htmlspecialchars($string, ENT_XML1, 'UTF-8');
}

function error($msg) {
  echo Markdown("**ERROR:** ".$msg);
  return -1;
}

####################
# Fix template URL #
####################

function fixTemplateURL($template, $url) {
  if (empty($template)) {
    return error("Missing template.");
  }
  if (empty($url)) {
    return error("Missing template URL.");
  }
  if (!is_file($template)) {
    return error("Template not found: ".$template);
  }

  echo Markdown("### Template to fix:");
  echo Markdown("`".$template."`");
  echo Markdown("### New template URL:");
  echo Markdown("`".$url."`");
  echo Markdown("---");

  echo Markdown("Loading template...");
  $xml = simplexml_load_file($template);

  echo Markdown("Replacing template URL...");
  $xml->TemplateURL = xml_encode($url);

  echo Markdown("Saving template...");
  $dom = new DOMDocument('1.0');
  $dom->preserveWhiteSpace = false;
  $dom->formatOutput = true;
  $dom->loadXML($xml->asXML());
  file_put_contents($template, $dom->saveXML());
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-fonts.css">
<link type="text/css" rel="stylesheet" href="/webGui/styles/default-white.css">
</head>
<body style="margin:14px 10px">
<?
$rc = -1;
switch ($_GET['cmd']) {
  case 'templateURL':
    $rc = fixTemplateURL($_GET['template'], $_GET['url']);
    break;
  default:
    error("Invalid fix command: ".$_GET['cmd']);
    break;
}
if ($rc === 0 || $rc === null) {
  echo Markdown("*Fix applied successfully!*");
}
?>
<br><div style="text-align:center"><input type="button" value="Done" onclick="top.Shadowbox.close()"></div>
</body>
</html>
