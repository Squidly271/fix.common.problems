<?
###############################################################
#                                                             #
# Community Applications copyright 2015-2016, Andrew Zawadzki #
#                                                             #
###############################################################

function getLineCount($directory) {
  global $lineCount, $charCount;

  $allFiles = array_diff(scandir($directory),array(".",".."));
  foreach ($allFiles as $file) {
    if (is_dir("$directory/$file")) {
      getLineCount("$directory/$file");
      continue;
    }
    $extension = pathinfo("$directory/$file",PATHINFO_EXTENSION);
    if ( $extension == "sh" || $extension == "php" || $extension == "page" ) {
      $lineCount = $lineCount + count(file("$directory/$file"));
      $charCount = $charCount + filesize("$directory/$file");
    }
  }
}

$caCredits = "
<style>
table {background-color:transparent;}
</style>
    <center><table align:'center'>
      <tr>
        <td><img src='https://github.com/Squidly271/plugin-repository/raw/master/Chode_300.gif' width='50px';height='48px'></td>
        <td><strong>Andrew Zawadzki</strong></td>
        <td>Main Development</td>
      </tr>
    </table></center>
    <br>
    <center><em><font size='1'>Copyright 2015-2021 Andrew Zawadzki</font></em></center>
    <center><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7M7CBCVU732XG' target='_blank' rel='noreferrer'><img src='https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif'></a></center>
    <br><center><a href='https://forums.lime-technology.com/topic/47266-plugin-ca-fix-common-problems/' target='_blank'>Plugin Support Thread</a></center>
  ";
  getLineCount("/usr/local/emhttp/plugins/fix.common.problems");
  $caCredits .= "<center>$lineCount Lines of code and counting! ($charCount characters)</center>";
  $caCredits = str_replace("\n","",$caCredits);
?>