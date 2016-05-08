#!/usr/bin/php
<?
  $script = "fix.common.problems.sh";
  
  @unlink("/etc/cron.daily/$script");
  @unlink("/etc/cron.hourly/$script");
  @unlink("/etc/cron.weekly/$script");
  @unlink("/etc/cron.monthly/$script");
  
  $settings = json_decode(@file_get_contents("/boot/config/plugins/fix.common.problems/settings.json"),true);
  
  if ( ( ! $settings['frequency'] ) || ( $settings['frequency'] == "disabled" ) ) {
    exit;
  }
  $path = "/etc/cron.".$settings['frequency']."/$script";
  exec("cp /usr/local/emhttp/plugins/fix.common.problems/scripts/fix.common.problems.sh $path");
?>

