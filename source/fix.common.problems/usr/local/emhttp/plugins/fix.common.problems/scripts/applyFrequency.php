#!/usr/bin/php
<?
  $script = "fix.common.problems.sh";
  
  @unlink("/etc/cron.daily/$script");
  @unlink("/etc/cron.hourly/$script");
  @unlink("/etc/cron.weekly/$script");
  @unlink("/etc/cron.monthly/$script");
  
  $settings = json_decode(@file_get_contents("/boot/config/plugins/fix.common.problems/settings.json"),true);
  
  $frequency = $settings['frequency'] ?? "disabled";

  # The settings page offers exactly these, but it is written from a POST and
  # nothing else checks it, so anything not on the list is treated as disabled
  # rather than being pasted into a path and a shell command.
  if ( ! in_array($frequency,["hourly","daily","weekly","monthly"],true) ) {
    exit;
  }
  $path = "/etc/cron.$frequency/$script";
  exec("cp /usr/local/emhttp/plugins/fix.common.problems/scripts/fix.common.problems.sh ".escapeshellarg($path));
?>

