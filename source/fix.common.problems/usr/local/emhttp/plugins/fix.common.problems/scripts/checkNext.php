#!/usr/bin/php
<?PHP
$_GET['system'] = true;
$_GET['branch'] = $argv[1];

include("/usr/local/emhttp/plugins/dynamix.plugin.manager/include/ShowPlugins.php");
?>
