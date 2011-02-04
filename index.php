<?php
//header("Location: /timekeeper/");
//exit;
########################################################
# Script Info
# ===========
# File: DirectoryListing.php
# Author: Ash Young (ash@evoluted.net
# Created: 20/12/03
# Modified: 27/09/04
# Website: http://evoluted.net/directorylisting.php
# Requirements: PHP
#
# Description
# ===========
# Displays all files contained within a directory in
# a well formed table, with category-image tags
#
# If you have any functions that you like to see
# implemented in this script then please just send
# an email to ash@evoluted.net
#
# Useage
# ======
#
# To change the colours display when using the script
# scroll down to set up section
#
# To use the script just upload to your server with
# the images and point your browser to the scripts
# filename
#
# SETUP
# =====
#
# Change the following variables to display what colours
# the script outputs
########################################################

DEFINE("IMAGEROOT", "/images/");  #CHANGE /images/ TO THE PATH OF THE ASSOCIATED IMAGES

$textcolor = "#FFFFFF";           #TEXT COLOUR
$bgcolor = "#535353";             #PAGE BACKGROUND COLOUR

$normalcolor = "#0066FF";         #TABLE ROW BACKGROUND COLOUR
$highlightcolor = "#006699";      #TABLE ROW BACKGROUND COLOUR WHEN HIGHLIGHTED
$headercolor = "#003366";         #TABLE HEADER BACKGROUND COLOUR
$bordercolor = "#202750";         #TABLE BORDER COLOUR

?>
<html>
<head>
<title>Directory Listings of <? echo $_SERVER["REQUEST_URI"]; ?> </title>
<style type='text/css'>
<!--
body {     color: <? echo $textcolor; ?>; font: tahoma, small verdana,arial,helvetica,sans-serif; background-color: <? echo $bgcolor; ?>; }
table { font-family: tahoma, Verdana, Geneva, sans-serif; border: 1px; border-style: solid; border-color: <? echo $bordercolor; ?>; }
.row { background-color: <? echo $normalcolor; ?>; border: 0px;}
a:link { color: <? echo $textcolor; ?>;  text-decoration: none; }
a:visited { color: <? echo $textcolor; ?>;  text-decoration: none; }
a:hover, a:active { color: <? echo $textcolor; ?>;  text-decoration: none; }
img {border: 0;}
#bottomborder {border: <? echo $bordercolor;?>;border-style: solid;border-top-width: 0px;border-right-width: 0px;border-bottom-width: 1px;border-left-width: 0px}
.copy { text-align: center; color: <? echo $textcolor; ?>; font-family: tahoma, Verdana, Geneva, sans-serif;   text-decoration: underline; }
//-->
</style>
</head>
<body>
<?php
clearstatcache();
if ($handle = opendir('.')) {
  while (false !== ($file = readdir($handle))) {
    if ($file != "." && $file != ".." && $file != substr($PHP_SELF, -(strlen($PHP_SELF) - strrpos($PHP_SELF, "/") - 1))) {

	  if (filetype($file) == "dir") {
		  //SET THE KEY ENABLING US TO SORT
		  $n++;
		  if($_REQUEST['sort']=="date") {
			$key = filemtime($file) . ".$n";
		  }
		  else {
			$key = $n;
		  }
          $dirs[$key] = $file . "/";
      }
      else {
		  //SET THE KEY ENABLING US TO SORT
		  $n++;
		  if($_REQUEST['sort']=="date") {
			$key = filemtime($file) . ".$n";
		  }
		  elseif($_REQUEST['sort']=="size") {
			$key = filesize($file) . ".$n";
		  }
		  else {
			$key = $n;
		  }
          $files[$key] = $file;
      }
    }
  }
closedir($handle);
}

#USE THE CORRECT ALGORITHM AND SORT OUR ARRAY
if($_REQUEST['sort']=="date") {
	@ksort($dirs, SORT_NUMERIC);
	@ksort($files, SORT_NUMERIC);
}
elseif($_REQUEST['sort']=="size") {
	@natcasesort($dirs);
	@ksort($files, SORT_NUMERIC);
}
else {
	@natcasesort($dirs);
	@natcasesort($files);
}

#ORDER ACCORDING TO ASCENDING OR DESCENDING AS REQUESTED
if($_REQUEST['order']=="desc" && $_REQUEST['sort']!="size") {$dirs = array_reverse($dirs);}
if($_REQUEST['order']=="desc") {$files = array_reverse($files);}
$dirs = @array_values($dirs); $files = @array_values($files);

echo "<table width=\"650\" border=\"0\" cellspacing=\"0\" align=\"center\"><tr bgcolor=\"$headercolor\"><td colspan=\"2\" id=\"bottomborder\">";
if($_REQUEST['sort']!="name") {
  echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=name&order=asc\">";
}
else {
  if($_REQUEST['order']=="desc") {#
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=name&order=asc\">";
  }
  else {
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=name&order=desc\">";
  }
}
echo "File</td><td id=\"bottomborder\" width=\"50\"></a>";
if($_REQUEST['sort']!="size") {
  echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=size&order=asc\">";
}
else {
  if($_REQUEST['order']=="desc") {#
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=size&order=asc\">";
  }
  else {
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=size&order=desc\">";
  }
}
echo "Size</td><td id=\"bottomborder\" width=\"120\" nowrap></a>";
if($_REQUEST['sort']!="date") {
  echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=date&order=asc\">";
}
else {
  if($_REQUEST['order']=="desc") {#
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=date&order=asc\">";
  }
  else {
    echo "<a href=\"".$_SERVER['PHP_SELF']."?sort=date&order=desc\">";
  }
}
echo "Date Modified</a></td></tr>";

$arsize = sizeof($dirs);
for($i=0;$i<$arsize;$i++) {
  echo "\t<tr class=\"row\" onMouseOver=\"this.style.backgroundColor='$highlightcolor'; this.style.cursor='hand';\" onMouseOut=\"this.style.backgroundColor='$normalcolor';\" onClick=\"window.location.href='" . $dirs[$i] . "';\">";
  echo "\t\t<td width=\"16\"><img src=\"" . IMAGEROOT . "folder.gif\" width=\"16\" height=\"16\" alt=\"Directory\"></td>";
  echo "\t\t<td><a href=\"" . $dirs[$i] . "\">" . $dirs[$i] . "</a></td>";
  echo "\t\t<td width=\"50\" align=\"left\">-</td>";
  echo "\t\t<td width=\"120\" align=\"left\" nowrap>" . date ("M d Y h:i:s A", filemtime($dirs[$i])) . "</td>";
  echo "\t</tr>";
}

$arsize = sizeof($files);
for($i=0;$i<$arsize;$i++) {
  switch (substr($files[$i], -3)) {
    case "jpg":
      $img = "jpg.gif";
      break;
    case "gif":
      $img = "gif.gif";
      break;
    case "zip":
      $img = "zip.gif";
      break;
    case "png":
      $img = "png.gif";
      break;
    case "avi":
      $img = "move.gif";
      break;
    case "mpg":
      $img = "move.gif";
      break;
    default:
      $img = "what.gif";
      break;
  }

  echo "\t<tr class=\"row\" onMouseOver=\"this.style.backgroundColor='$highlightcolor'; this.style.cursor='hand';\" onMouseOut=\"this.style.backgroundColor='$normalcolor';\" onClick=\"window.location.href='" . $files[$i] . "';\">\r\n";
  echo "\t\t<td width=\"16\"><img src=\"" . IMAGEROOT . "$img\" width=\"16\" height=\"16\" alt=\"Directory\"></td>\r\n";
  echo "\t\t<td><a href=\"" . $files[$i] . "\">" . $files[$i] . "</a></td>\r\n";
  echo "\t\t<td width=\"50\" align=\"left\">" . round(filesize($files[$i])/1024) . "KB</td>\r\n";
  echo "\t\t<td width=\"120\" align=\"left\" nowrap>" . date ("M d Y h:i:s A", filemtime($files[$i])) . "</td>\r\n";
  echo "\t</tr>\r\n";
}
echo "</table><div align=\"center\"><a href=\"http://evoluted.net/directorylisting.php\" class=\"copy\">Directory Listing Script</a>. <a href=\"http://evoluted.net/\" class=\"copy\">&copy 2003-2004 Ash Young</a></div>";
?>
</body>
</html>
