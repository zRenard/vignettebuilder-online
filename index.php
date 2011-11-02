<?session_start();
//////////////////////////////////////////
// VignetteBuilder Online
// More Infos : http://www.zrenard.com/
//////////////////////////////////////////
// 12-juin-2003 12:53 AM : V1.1 beta : SubDirectory Managment
// 15-juin-2003 02:32 PM : V1.1 : Del Dir, Empty Dir
// 16-juin-2003 05:59 PM : V1.2 alpha : Pages Numbers
// 18-juin-2003 03:45 AM : V1.2 : Multiples options, sort, onfly thumbnail
// 10-juil.-2003 05:20 PM : V1.3 : Some design fix
// 24-juil.-2003 10:06 AM : V1.4 : Option to use one main description file by directory
// 15-août-2003 11:07 AM : V1.5 : SlideShow
// 19-nov.-2003 10:32 PM : V1.6 : Current directory size, Create directory, Zip current directory, sort by size.
// 19-nov.-2003 10:45 PM : V1.6.1 : Bug Fix in RemoteLink
// 20-nov.-2003 11:30 PM : V1.7 : Upload Zip
// 21-nov.-2003 12:02 AM : V1.8 : New Login PopUp
// 21-nov.-2003 12:12 AM : V1.8.1 : Exif Bug Fix, Bug Fix (login in dir doesn't return in dir)
// 21-nov.-2003 12:12 AM : V1.8.2 : Comments Fix for realase.
// 12-déc.-2003 11:28 PM : V1.8.3 : Bugfix for Directory creation with zip under linux/unix
// 14-déc.-2003 05:17 PM : V.1.8.4 : Security Fix denied acces to upper directory than root dir.
// 28-oct.-2004 10:12 PM : V.1.8.5 : scandir function renamed for php5 compatibility
// 26-jan.-2005 01:43 AM : V.1.8.6 : Small change on config file
// 28-jan.-2005 11:10 PM : V.1.8.7 : Adding "copy to clipboard" directory feature
// 29-jan.-2005 01:23 AM : V.1.8.8 : Displaying Exif icon only when exif data are avail.
// 29-jan.-2005 02:48 AM : V.1.8.9 : Adding "copy to clipboard" image feature
// 03-fev.-2005 01:31 AM : V.1.8.10 : New toolbar design
// 03-fev.-2005 02:45 AM : V.1.8.11 : CSS/Design clean up
// 09-fev.-2005 00:22 AM : V.1.8.12 : Bugfix when directory has space
// 22-mars-2006 07:47 PM : V.1.8.13 : Number directory by line
// 26-mars-2006 11:57 AM : V.1.8.14 : Adding video type
// 19-mai-2006 12:05 AM : V.1.8.15 : Bugfix Directory not sorted like other files
//////////////////////////////////////////
$version="1.8.15";
//////////////////////////////////////////

//$admin=1;
//session_register('admin');

include("config.php");

$error=0;
$computed_nb_files=0;
$size_computed=0;

// Security patch for www hosting 
$dir=str_replace("..","**",$dir); // don't go on up dir
$dir=str_replace(":","|",$dir); // don't read system dir

function cmp($a,$b) {
 global $order_by,$order;
	if ($order=="desc")
   return ($a[$order_by]<$b[$order_by]);
 else
  return ($a[$order_by]>$b[$order_by]);	
} 
function extr($n) {
 return $n["filename"];
}
function getmicrotime(){
 list($usec, $sec) = explode(" ",microtime()); 
 return ((float)$usec + (float)$sec);
}

$time_start = getmicrotime();

function rotate($src_img, $degrees = 90) {
 global $use_gd2;

 $degrees %= 360;
 if ($degrees == 0) {
  $dst_img = $src_img;
 } elseif ($degrees == 180) {
  $dst_img = imagerotate($src_img, $degrees, 0);
 } else {
  $width = imagesx($src_img);
  $height = imagesy($src_img);
  if ($width > $height) $size = $width; else $size = $height;
	 if ($use_gd2==1)
    $dst_img = imagecreatetruecolor($size, $size);
	 else
    $dst_img = imagecreate($size, $size);
  imagecopy($dst_img, $src_img, 0, 0, 0, 0, $width, $height);
  $dst_img = imagerotate($dst_img, $degrees, 0);
  $src_img = $dst_img;
	if ($use_gd2==1)
   $dst_img = imagecreatetruecolor($height, $width);
	else
   $dst_img = imagecreate($height, $width);

  if ((($degrees == 90) && ($width > $height)) || (($degrees == 270) && ($width < $height)))
   imagecopy($dst_img, $src_img, 0, 0, 0, 0, $size, $size);
  if ((($degrees == 270) && ($width > $height)) || (($degrees == 90) && ($width < $height)))
   imagecopy($dst_img, $src_img, 0, 0, $size - $height, $size - $width, $size, $size);
 }
 return $dst_img;
}

function extract_exif($exif) {
 global $exif_section,$exif_key,$exif_exclude_key;

 $have_exif=true;
 $exifdatah="<font class=c>";
 $exifdata="";
 foreach($exif as $key=>$section)
  foreach($section as $name=>$val) {
	 if (((in_array ($key,$exif_section))||
	     (in_array ($name,$exif_key)))&&
	     (!in_array ($name,$exif_exclude_key)))
		$exifdata=$exifdata.$key."-".$name.":".$val."<br>";
	}
	if ($exifdata=="") {
	 $exifdata=$exifdatah."No Exif Data to display";
   $have_exif=false;
	} else
	 $exifdata=$exifdatah.$exifdata;
 $exifdata=$exifdata."</font><br>";
 return array($have_exif, $exifdata);
}

function delete_all_from_dir($Dir){
       // delete everything in the directory
       if      ($handle = @opendir($Dir)) {
             while   (($file = readdir($handle)) !== false) {
                      if      ($file == "." || $file == "..") {
                              continue;
                       }
                     if      (is_dir($Dir.$file)){
                             // call self for this directory
                              delete_all_from_dir($Dir.$file."/");
//                              chmod($Dir.$file,0777);
                              @rmdir($Dir.$file); //remove this directory
                      }else   {
//                               chmod($Dir.$file,0777);
                              unlink($Dir.$file); // remove this file
                      }
               }
       }
      @closedir($handle);
			rmdir($Dir);
}
if ($action=="login") {
 if ($login=="") {
  $error=1; $error_string="Error : Bad Password";
 } else {
  if (($login==$main_login)&&($pwd==$main_pwd)) {
	 session_register('login');
	 session_register('pwd');
	 $admin=1;
	 session_register('admin');
	} else {
   $error=1; $error_string="Error : Bad Password";
	}
 }
}
if ($action=="logout") {
 @session_destroy();
 header("Location: index.php?dir=$dir&page=$page");
}

if ($_SESSION['admin']==1) $admin=1; else $admin=0;

if (($action=="rotate90")&&$admin==1) {
 $ext=substr(strtolower($filename),(strlen($filename)-4),4);
 switch($ext){
  case ".jpg": $img = imagecreatefromjpeg ($dir."/".$filename); break;
//  case ".png": $img = imagecreatefrompng ($filename); break; // Rotate for png don't work ... bug
//  case ".gif": if ($use_gd2==0) $img = imagecreatefromgif ($filename); break; // Not tested
 }
 if ($img!=FALSE) {
  $img2 = rotate ($img,90);
  switch($ext){
   case ".jpg": imagejpeg($img2,$dir."/".$filename,$rotate_jpeg_quality); break;
   case ".png": imagepng($img2,$dir."/".$filename); break;
   case ".gif": if ($use_gd2==0) imagegif($img2,$dir."/".$filename); break;
  }
 } else {
  $error=1; $error_string="Couln't rotate file $dir/$filename";
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}
if (($action=="rotate-90")&&$admin==1) {
 $ext=substr(strtolower($filename),(strlen($filename)-4),4);
 switch($ext){
  case ".jpg": $img = imagecreatefromjpeg ($dir."/".$filename); break;
//  case ".png": $img = imagecreatefrompng ($filename); break; // Rotate for png don't work ... bug
//  case ".gif": if ($use_gd2==0) $img = imagecreatefromgif ($filename); break; // Not tested
 }
 if ($img!=FALSE) {
  $img2 = rotate ($img,270);
  switch($ext){
   case ".jpg": imagejpeg($img2,$dir."/".$filename,80); break;
   case ".png": imagepng($img2,$dir."/".$filename); break;
   case ".gif": if ($use_gd2==0) imagegif($img2,$dir."/".$filename); break;
  }
 } else {
  $error=1; $error_string="Couln't rotate file $dir/$filename";
 }
 if ($error!=1) header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="upload")&&$admin==1) {
 if ($file=="") {
  $error=1; $error_string="Error : No file to upload.";
 } else if (!in_array($file_type,$image_file_type)) {
  $error=1; $error_string="Error : Type must be a valid type (found $file_type)";
 } else if ($file_size==0) {
  $error=1; $error_string="Error : File size is zero";
 } else if (!is_uploaded_file($file)) {
  $error=1; $error_string="Error upload attack";
 } else if (!copy($file,$dir."/".$file_name)) {
  $error=1; $error_string="Error while copying $dir.$file_name";
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="uploadzip")&&$admin==1) {
 $file=$_FILES['zipfile'];
 $file_type=$_FILES['zipfile']['type'];
 $file_size=$_FILES['zipfile']['size'];
 $file_name=$_FILES['zipfile']['name'];
 $file_error=$_FILES['zipfile']['error'];
 $file_tmp=$_FILES['zipfile']['tmp_name'];

 if ($file_error==0) {
   $error_string="Error : $file_error - Aucune erreur, le téléchargement est correct."; 
 } else if ($file_error==1) {
  $error=1;  $error_string="Error : $file_error - Le fichier téléchargé excède la taille de upload_max_filesize (".ini_get("upload_max_filesize")."), configuré dans le php.ini.";
 } else if ($file_error==2) {
  $error=1; $error_string="Error : $file_error - Le fichier téléchargé excède la taille de MAX_FILE_SIZE, qui a été spécifiée dans le formulaire HTML.";
 } else if ($file_error==3) {
  $error=1; $error_string="Error : $file_error - Le fichier n'a été que partiellement téléchargé.";
 } else if ($file_error==4) {
  $error=1; $error_string="Error : $file_error - Aucun fichier n'a été téléchargé.";
 }

 if ($error!=1) {
  if (!in_array($file_type,$zip_file_type)) {
   $error=1; $error_string="Error : Type must be a valid type (found $file_type)";
  } else if ($file_size==0) {
   $error=1; $error_string="Error : File size is zero";
  } else {
   $filename_noext=substr($file_name,0,(strlen($file_name)-4));
   unzip($file_tmp,$dir.$dirchar);
  }
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}


if (($action=="rlink")&&$admin==1) {
 if ($name==""||($fp=@fopen($dir."/".$name.".rlk","w"))==false) {
  $error=1; $error_string="Remote Link file $dir/$name.rlk error";
 } else {
  fputs($fp,$url);
  fclose($fp);
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="delete")&&$admin==1) {
 if (!is_dir($filename)) {
  if (!@unlink($filename)) {
   $error=1; $error_string="Delete $filename error";
  }
  $tmp=basename($filename);
  $filename_noext=substr($tmp,0,(strlen($tmp)-4));
  if (file_exists($dir."/t_".$filename_noext.".jpg")&&!@unlink($dir."/t_".$filename_noext.".jpg")) {
   $error=1; $error_string="Delete ".$dir."/t_".$filename_noext.".jpg error";
  }
  if (file_exists($filename.".txt")&&(!@unlink($filename.".txt"))) {
   $error=1; $error_string="Delete $filename Description File error";
  }
 } else {
   delete_all_from_dir($filename."/");
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="delpic")&&$admin==1) {
 if (!is_dir($dir)) {
  $error=1; $error_string="Emptying $dir error";
 } else {
  if ($handle = @opendir($dir)) {
   while (($file = readdir($handle)) !== false) {
    if ($file == "." || $file == "..") {
     continue;
    }
    if (in_array(substr(strtolower($file),(strlen($file)-4),4),$file_ext)) {
     if (!@unlink($dir."/".$file)) {
      $error=1; $error_string="Delete $dir/$file error";
     }
     if (file_exists($dir."/".$file.".txt"))
		  @unlink($dir."/".$file.".txt");
		}
   }
  }
  @closedir($handle);
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="adddir")&&$admin==1) {
 if (file_exists($dir."/".$new_dir)) {
  $error=1;
  $error_string="Directory $new_dir already exist !";
 } else {
	if (!mkdir($dir."/".$new_dir)) {
	 $error=1;
	 $error_string="Error while creating directory $dir/$new_dir !";
	}
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="deletemain")&&$admin==1) {
 if ($use_maindesc) $dirtoopen=""; else $dirtoopen=$dir."/";
 if (!@unlink($dirtoopen."header.html")) {
  $error=1; $error_string="Delete Main Description File error";
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}
if (($action=="deletedesc")&&$admin==1) {
 if (!@unlink($dir."/".$filename.".txt")) {
  $error=1; $error_string="Delete $dir/$filename Description File error";
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}
if (($action=="rename")&&$admin==1) {
 if (!@rename($dir."/".$filename,$dir."/".$new_filename)) {
  $error=1; $error_string="Rename $dir/$filename error";
 }
 $filename_noext=substr($filename,0,(strlen($filename)-4)); 
 $new_filename_noext=substr($new_filename,0,(strlen($new_filename)-4)); 
 if (file_exists($dir."/t_".$filename_noext.".jpg")&&!@rename($dir."/t_".$filename_noext.".jpg",$dir."/t_".$new_filename_noext.".jpg")) {
  $error=1; $error_string="Rename $dir/t_$filename_noext.jpg error";
 }
 if (file_exists($dir."/".$filename.".txt")&&(!@rename($dir."/".$filename.".txt",$dir."/".$new_filename.".txt"))) {
  $error=1; $error_string="Rename $dir/$filename Description File error";
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="desc")&&$admin==1) {
 if (($fp=@fopen($dir."/".$filename.".txt","w"))==false) {
  $error=1; $error_string="Description file $dir/$filename.txt error";
 } else {
  fputs($fp,$desc);
  fclose($fp);
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}
if (($action=="maindesc")&&$admin==1) {
 if ($use_maindesc) $dirtoopen=""; else $dirtoopen=$dir."/";
 if (($fp=@fopen($dirtoopen."header.html","w"))==false) {
  $error=1; $error_string="Main description file ($dir/header.html)error";
 } else {
  fputs($fp,$desc);
  fclose($fp);
 }
 if ($error!=1)  header("Location: index.php?dir=$dir&page=$page");
}

if (($action=="zip")) {
 $dir_filename=basename($dir);
 $dir_filename=str_replace(".","main",$dir_filename);

 header("Content-type: application/zip"); // Header MUST be before include
 header("Content-disposition: attachment; filename=".$dir_filename.".zip");
 include("zip.lib.php");

 $zipfile = new zipfile();
 $zipfile -> exclude_file = array('ico');
 $zipfile -> include_file_type = array_merge($file_ext,array(".txt"));
 $zipfile -> addDir($dir);

 print $zipfile -> file();
 exit();
}

if (($action=="slideshow")) {
 $dossier=opendir($dir);
 $retVal=array();
 while($fichier=readdir($dossier)){
  if (in_array($fichier,array('.', '..','ico','logs'))) continue;
	if (is_dir($dir."/".$fichier)) continue;
  $pos=strpos(basename($fichier),"t_");
  if (($pos!==FALSE)&&($pos<1)) continue;
  if (in_array(substr(strtolower($fichier),(strlen($fichier)-4),4),$file_ext))
		 $retVal[count($retVal)+1] = array('filename'=>$fichier,'filedate'=>filemtime($dir."/".$fichier));
 }	
 closedir($dossier); 

 $nb_files=count($retVal);
 if ($nb_files>0) {
	uasort($retVal, "cmp");
	$retVal=array_map("extr", $retVal);
	$retVal=array_slice($retVal,0,$nb_files);
	?>
<HTML><HEAD><TITLE>SlideShow</TITLE><BASE target="_self">
<script language="JavaScript1.1">
<!--
var slidespeed=10000
var slideimages=new Array(<?
 for($i=0;$i<$nb_files;$i++) {
  echo "\"$dir/$retVal[$i]\"";
	if ($i==($nb_files-1)) echo ")\n"; else echo ",";
 }
?>
var slidelinks=new Array(<?
 for($i=0;$i<$nb_files;$i++) {
  echo "\"$dir/$retVal[$i]\"";
	if ($i==($nb_files-1)) echo ")\n"; else echo ",";
 }
?>
var imageholder=new Array()
var ie55=window.createPopup
for (i=0;i<slideimages.length;i++){
imageholder[i]=new Image()
imageholder[i].src=slideimages[i]
}
function gotoshow(){
window.location=slidelinks[whichlink]
}
//-->
</script>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-62941-6";
urchinTracker();
</script></HEAD><BODY>
<table border=0 cellspacing=0 cellpadding=0 width="100%" height="100%">
<tr><form name=filename><td align=center><input style="COLOR:#527099; BORDER: 0px solid;FONT-SIZE: 8pt; TEXT-ALIGN: center;" readonly type=text size=80 name=fname>
<input style="COLOR:#527099; BORDER: 0px solid;FONT-SIZE: 8pt; TEXT-ALIGN: center;" readonly type=text size=10 name=time>
<table><tr>
<td><a title="Previous" onclick="previ();"><img alt="Previous" border=0 src="ico/prev.png"></a></td>
<td><a title="Play" onclick="start();"><img alt="Play"  border=0 src="ico/play.png"></a></td>
<td><a title="Pause" onclick="pausei();"><img alt="Pause"  border=0 src="ico/pause.png"></a></td>
<td><a title="Stop" href="index.php?dir=<?=$dir?>&page=<?=$page?>"><img alt="Stop and back to vignette" border=0 src="ico/stop.png"></a></td>
<td><a title="Next" onclick="nexti();"><img alt="Next"  border=0 src="ico/next.png"></a></td>
</td></tr>
</table>
<tr>
<!-- // 23=random-->
<td width=100% height=100% align=center><img onclick="gotoshow();" height=480 src="" name="slide" style="filter:progid:DXImageTransform.Microsoft.revealTrans(Transition=11,Duration=1)"></td>
</tr>

</td></form></tr>
</table>
<script language="JavaScript1.1">
<!--
var whichlink=0
var whichimage=0
var pixeldelay=(ie55)? document.images.slide.filters[0].duration*1000 : 0

function pausei(){
clearTimeout(sld)
clearTimeout(tm)
}
function start(){
slideit()
}
function count() {
document.filename.time.value=document.filename.time.value-1;
if (document.filename.time.value!=0) tm=setTimeout("count()",1000);
}

function slideit(){
if (!document.images) return
if (ie55) document.images.slide.filters[0].apply()
document.images.slide.src=imageholder[whichimage].src
document.filename.fname.value=imageholder[whichimage].src
if (ie55) document.images.slide.filters[0].play()
whichlink=whichimage
whichimage=(whichimage<slideimages.length-1)? whichimage+1 : 0
sld=setTimeout("slideit()",slidespeed+pixeldelay)
document.filename.time.value=slidespeed/1000
tm=setTimeout("count()",1000);
}
function nexti(){
if (!document.images) return
if (ie55) document.images.slide.filters[0].apply()
document.images.slide.src=imageholder[whichimage].src
document.filename.fname.value=imageholder[whichimage].src
if (ie55) document.images.slide.filters[0].play()
whichlink=whichimage
whichimage=(whichimage<slideimages.length-1)? whichimage+1 : 0
document.filename.time.value=slidespeed/1000
}
function previ(){
whichimage=(whichimage<1)? slideimages.length-1 : whichimage-1
whichimage=(whichimage<1)? slideimages.length-1 : whichimage-1
if (!document.images) return
if (ie55) document.images.slide.filters[0].apply()
document.images.slide.src=imageholder[whichimage].src
document.filename.fname.value=imageholder[whichimage].src
if (ie55) document.images.slide.filters[0].play()
whichlink=whichimage
whichimage=(whichimage<slideimages.length-1)? whichimage+1 : 0
document.filename.time.value=slidespeed/1000
}

slideit()
//-->
</script></BODY></HTML>
<?
  exit(0);
 } else { // No file
  $error=1; $error_string="No File to slide in directory : $dir";
 }
}

?>
<HTML>
<HEAD>
<TITLE>Vignette Builder Online</TITLE>
<style type="text/css">
<!--
a:link { color: #000080; text-decoration: none;  }
a:visited { color: #000080; text-decoration: none;  }
a:hover {color: #000080; text-decoration: none}
a:active {color: #000080; text-decoration: none}
.input {BORDER: #527099 1px solid;FONT-SIZE: 8pt; TEXT-ALIGN: left; }
.input:visited {BORDER: #527099 1px solid;FONT-SIZE: 8pt; TEXT-ALIGN: left; }
.input:link {BORDER: #527099 1px solid;FONT-SIZE: 8pt; TEXT-ALIGN: left; }
.input:active {BORDER: #2F4D76 1px solid;FONT-SIZE: 8pt; TEXT-ALIGN: left; }
.input:hover {BORDER: #2F4D76 1px solid;FONT-SIZE: 8pt; TEXT-ALIGN: left; }
.img {BORDER: #FFFFFF 0px solid;}
.imgb {border-bottom: #2F4D76 1px solid;}

.page {BORDER: #00FF00 0px solid;}
.pagesel {BORDER: #90A2B9 1px solid; font-weight : bold; background-color:#D5DBE4 ;}

.dir {BORDER: #DDDDDD 1px solid;}
.header {color:#8080C0;text-align:center}
.dirtoolbox {border:0px solid;background-color:#FEFEFE;}
.maintoolbox {border:0px solid;background-color:#FEFEFE;}
.imgtoolbox {border:0px solid;background-color:#FEFEFE;}
.computetime {width:80%; text-align:right; font-size : 9; border : 1px solid;  color :#90A2B9 ; border-color : #90A2B9;}
.computetimelink:link {font-size : 9; color :#90A2B9 ;}
.computetimelink:visited {font-size : 9; color :#90A2B9 ;}
.computetimelink:active {font-size : 9; color :#90A2B9 ;}
.computetimelink:hover {font-size : 9; color :#90A2B9 ;}
.card { border : 0px solid; border-color: #90A2B9; width : 175; filter:Alpha(opacity=90, style=0);}
.cardTitle { text-align:center; font-size : 12; font-weight : bold; font-variant : small-caps; color : #000000; background-color: #D5DBE4; border : 1px solid; border-color: #164783; }
.cardContent { font-size : 12; border-top : 1px solid; border-bottom : 1px solid; border-left : 1px solid; border-right : 1px solid; border-color: #164783; color : #000000; background-color : #E7E7E7; }
.card2 { border : 0px solid; border-color: #90A2B9; width : 250; filter:Alpha(opacity=90, style=0);}
.cardTitle2 { text-align:center; font-size : 12; font-weight : bold; font-variant : small-caps; color : #000000; background-color: #D5DBE4; border : 1px solid; border-color: #164783; }
.cardContent2 { text-align:center; font-size : 12; border : #164783 1px solid; color : #000000; background-color : #E7E7E7; }
.c { text-align:left;width:240;font-size : 11; color : #000000; background-color : #F0F6FF;  border :0px;}
.genfont { font-size: 11 ;color :#8080C0 }
.genfont:visited { font-size: 11 ;color :#8080C0 }
.genfont:link { font-size: 11 ;color :#8080C0 }
.genfont:active { font-size: 11 ;color :#8080C0 }
.genfont:hover { font-size: 11 ;color :#8080C0 }
#mytooltips {position:absolute; visibility:hidden; z-index:200;}
#mytooltips2 {position:absolute; visibility:hidden;} 
-->
</style>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-62941-6";
urchinTracker();
</script></HEAD>
<BODY>
<DIV id=mytooltips2></DIV>
<DIV id=mytooltips></DIV>
<SCRIPT TYPE="text/javascript">
<!--
// PopUp JavaScript To Show Id3card
Xoffset=20;
Yoffset=-10;
var old,skn,skn2,iex=(document.all),posmx,posmy;
var ns4=document.layers
var ns6=document.getElementById&&!document.all
var ie4=document.all
if (ns4) {
 skn=document.mytooltips;
 skn2=document.mytooltips;
} else if (ns6) {
 skn=document.getElementById("mytooltips").style;
 skn2=document.getElementById("mytooltips2").style;
} else if (ie4) {
 skn=document.all.mytooltips.style;
 skn2=document.all.mytooltips2.style;
}

if(ns4)document.captureEvents(Event.MOUSEMOVE);
else{
 skn.visibility="visible"
 skn.display="none"
 skn2.visibility="visible"
 skn2.display="none"
}
document.onmousemove=get_mouse;
function tooltips(caption,msg){
 var content="<TABLE summary=\""+caption+"\" class=card><tr><TD class=cardTitle>"+caption+"</TD></tr><tr><TD class=cardContent>"+msg+"</TD></tr></TABLE>";
 if(ns4){skn.document.write(content);skn.document.close();skn.visibility="visible"}
 if(ns6){document.getElementById("mytooltips").innerHTML=content;skn.display=''}
 if(ie4){document.all("mytooltips").innerHTML=content;skn.display=''}
}
function get_mouse(e){
 var x=(ns4||ns6)?e.pageX:event.x+document.body.scrollLeft;
 skn.left=x+Xoffset;
 posmx=x+Xoffset;

 var y=(ns4||ns6)?e.pageY:event.y+document.body.scrollTop;
 skn.top=y+Yoffset;
 posmy=y+Yoffset;

 // catch possible negative values
 if (posmx < 0){posmx = 0}
 if (posmy < 0){posmy = 0}

 return true
}
function kill(){
 if(ns4){skn.visibility="hidden";}
 else if (ns6||ie4) {
  skn.display="none"
 }
}
function kill2(){
 if(ns4){skn2.visibility="hidden";}
 else if (ns6||ie4) {
  skn2.display="none"
 }
}
function tooltips2(caption,msg,dir,page){
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2><form enctype=multipart/form-data action=index.php?action=rename&dir="+dir+"&page="+page+" method=post><input class=input size=40 type=text name=new_filename value='"+msg+"'><input type=hidden name=filename value='"+msg+"'><br><input src='ico/ok.png' type=image value=Rename>&nbsp;<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx;
 skn2.top=posmy;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}
function tooltips3(caption,msg,dir,page,content){
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2><form enctype=multipart/form-data action=index.php?action=desc&dir="+dir+"&page="+page+" method=post><textarea class=input rows=6 cols=30 name=desc>"+content+"</textarea><input type=hidden name=filename value='"+msg+"'><br><input src='ico/ok.png' type=image value=Modif>&nbsp;<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx;
 skn2.top=posmy;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}
function tooltips4(caption,msg,dir,page,content){
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2><form enctype=multipart/form-data action=index.php?action=maindesc&dir="+dir+"&page="+page+" method=post><textarea class=input rows=6 cols=30 name=desc>"+content+"</textarea><input type=hidden name=filename value='"+msg+"'><br><input src='ico/ok.png' type=image value=Modif>&nbsp;<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx;
 skn2.top=posmy;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}
function tooltips6(caption,msg,dir,page){
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2><form enctype=multipart/form-data action=index.php?action=adddir&dir="+dir+"&page="+page+" method=post><input class=input size=40 type=text name=new_dir value='"+msg+"'><br><input src='ico/ok.png' type=image value=Modif>&nbsp;<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx;
 skn2.top=posmy;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}

function tooltips5(caption,content,content2){
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2>"+content+"<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx;
 skn2.top=posmy;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}

function tooltipslogin(caption,dir,page){
 var content2="<form enctype=\"multipart/form-data\" action=\"index.php?action=login&dir="+dir+"&page="+page+"\" method=post><table><tr><td class=genfont>Login</td><td><input class=input name=\"login\" type=\"text\"></td></tr><tr><td class=genfont>Password</td><td><input class=input name=\"pwd\" type=\"password\"></td></tr></table>";
 var content="<TABLE summary=\""+caption+"\" class=card2><tr><TD class=cardTitle2>"+caption+"</TD></tr><tr><TD class=cardContent2>"+content2+"<input src='ico/ok.png' type=image value=Login>&nbsp;<img src='ico/close.png' onclick=kill2()></TD></form></tr></TABLE>";
 skn2.left=posmx-250;
 skn2.top=posmy-60;
 if(ns4){skn2.document.write(content);skn2.document.close();skn2.visibility="visible"}
 if(ns6){document.getElementById("mytooltips2").innerHTML=content;skn2.display=''}
 if(ie4){document.all("mytooltips2").innerHTML=content;skn2.display=''}
}

function Supp(Content,Url)
{
 if (window.confirm("Warning !, deleting "+Content+" ?")) {
  window.location=Url;
 }
}
function copy2clipboard(maintext) {
 // alert(maintext + ' was in your clipboard');
 if (window.clipboardData) {
  window.clipboardData.setData('Text',maintext)
 } else if (window.netscape) {
  // Firefox : You must change signed.applets.codebase_principal_support to true in about:config
  netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
  var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
  if (!str) return false;
  str.data=maintext;
  var trans = Components.classes["@mozilla.org/widget/transferable;1"].createInstance(Components.interfaces.nsITransferable);
  if (!trans) return false;
  trans.addDataFlavor("text/unicode");
  trans.setTransferData("text/unicode",str,str.data.length*2);
  var clipid=Components.interfaces.nsIClipboard;
  var clip = Components.classes["@mozilla.org/widget/clipboard;1"].getService(clipid);
  if (!clip) return false;
  clip.setData(trans,null,clipid.kGlobalClipboard);
 }
}
//-->
</SCRIPT>
<? if ($error==1) echo "<center><font color=red>$error_string</font></center><br>"; ?>
<? //<a href="index.php?clean=1">Compute thumbnails</a> - ?>
<?
if (!isset($dir)||$dir=="") $dir_to_process=$dir_to_process; else $dir_to_process=$dir;
if (!isset($clean)) $clean=0;
if (!isset($page)) $page=1;

function followdir($dir,$maindir){
 //////////////////////////////////////
 // Scan a directory recursivly to find image
 //////////////////////////////////////
 global $root_dir,$clean,$maxWidth,$maxHeight,$nb_files,$computed_nb_files,$size_computed,$admin,$use_gd2,$file_ext,$nb_cols,$img_target,$show_exif,$use_exif,$upload,$nb_by_page,$page,$order_by,$order,$page_pos,$use_maindesc,$file_ext_video;

 if (!is_dir($dir)) { // if it's not a directory, what we do here ?!
  echo "<center><font color=red>$dir is not a valid directory !</font></center><br>";
	$dir=$root_dir;
 }
 $dossier=opendir($dir);

 $total_file_size=0;

 $retVal=array();$dirVal=array();
 while($fichier=readdir($dossier)){
  if (in_array($fichier,array('.', '..','ico','logs','wordpress'))) continue;
	if (is_dir($dir."/".$fichier)) {
	 $file_size = filesize($dir."/".$fichier);
	 $dirVal[count($dirVal)+1] = array('filename'=>$dir."/".$fichier,'filedate'=>filemtime($dir."/".$fichier),'filesize'=>$file_size);
	}
  $pos=strpos(basename($fichier),"t_");
  if (($pos!==FALSE)&&($pos<1)) continue;
  if (in_array(substr(strtolower($fichier),(strlen($fichier)-4),4),$file_ext)) {
	 $file_size = filesize($dir."/".$fichier);
	 $total_file_size +=$file_size;
	 $retVal[count($retVal)+1] = array('filename'=>$fichier,'filedate'=>filemtime($dir."/".$fichier),'filesize'=>$file_size);
	}
 }
 closedir($dossier);

 $nb_files=count($retVal);
 if ($nb_files>0) {
	uasort($retVal, "cmp");
	$retVal=array_map("extr", $retVal);
  $retVal=array_slice($retVal,($page-1)*$nb_by_page,$nb_by_page);
 }
 $nb_dir=count($dirVal);
 if ($nb_dir>0) {
	uasort($dirVal, "cmp");
	$dirVal=array_map("extr", $dirVal);

 }
 
 $nb=1;
 echo "<center><table width=\"100%\">";
 if ($use_maindesc)
  if (file_exists("header.html")) $desc=file_get_contents("header.html"); else $desc="";
 else
  if (file_exists($dir."/header.html")) $desc=file_get_contents($dir."/header.html"); else $desc="<span style=\"font-size:20px\">$dir</span>";

 $desctoprint=str_replace("\"","&quot;",str_replace("'","&#039;",trim(str_replace("\r","",str_replace("\n","<br>",$desc)))));

 echo "<tr class=header><td colspan=$nb_cols>";
 if ($admin==1) {
  if ($desc!="") echo str_replace("\n","<br>",str_replace("\'","&#039;",str_replace("\\\"","\"",$desc)));
  ?>
	<span class="maintoolbox">
   <a title="Edit description" href="javascript:tooltips4('Edit main description','header.html','<?=urlencode($dir)?>','<?=$page?>','<?=$desctoprint?>');"><img border=0 width=10 src="ico/desc.png" alt="Edit Description"></a>
   <a title="Delete description" href="javascript:Supp('header description file','index.php?dir=<?=urlencode($dir)?>&page=<?=$page?>&action=deletemain');"><img border=0 width=10 src="ico/delete.png" alt="Delete Description"></a>
	</span><?
 } elseif ($desc!="") {
  echo str_replace("\n","<br>",str_replace("\'","&#039;",str_replace("\\\"","\"",$desc)));
 }
 echo "</td></tr>";
 
 // Directories
 echo "<tr><td align=center colspan=$nb_cols>";
 echo "<table class=dirtoolbox><td><a title=\"Root directory\" href=\"$root_dir\"><img border=0 src=\"ico/root.png\" alt=\"Root directory\"></a></td>";

 if (($dir!=".")&&($dir!="")) {
  $tmp=dirname($dir);
  if (($tmp==".")||($tmp=="")) $desctmp="Root Directory"; else $desctmp=$tmp;
  echo "<td><a title=\"Upper directory\" href=\"index.php?dir=$tmp\"><img border=0 src=\"ico/up.png\" alt=\"$desctmp\"></a></td>";
 }
 if (($dir==".")||($dir=="")) $descdir="Root Directory"; else $descdir=$dir;

 echo "<td><a href=\"index.php?dir=$dir\" onmouseover=\"javascript:tooltips('$descdir','".date("l d F Y H:i:s", filemtime($dir));

 if ($nb_files>0) echo "<br>$nb_files file".(($nb_files>1) ? "s" : "");
 if ($nb_dir>0) echo "<br>$nb_dir ".(($nb_dir>1) ? "directories" : "directory");
 if ($total_file_size>0) echo "<br>".floor($total_file_size/1024)."Ko";
 echo "');\" onmouseout=\"javascript:kill();\"";
 echo "><img border=0 src=\"ico/info.png\"></a></td>";

 // Generating URL to CopyToClipboard
 $request_uri=getenv("REQUEST_URI");
 $filename = ereg_replace( "\?.*",  "", $request_uri);
 if((substr($request_uri,-1,1) == "/")) {
  $filename="$filename"."index.php";
 }
 $server_name = getenv("SERVER_NAME");
 $server_port = getenv("SERVER_PORT");
 $server_query = getenv("QUERY_STRING");
 if ($server_port=="80") $server_port=""; else $server_port=":".$server_port;
 $dir_url="http://$server_name$server_port$filename?dir=$dir&page=$page";

 echo "<td><img border=0 title=\"Copy to clipboard\" src=\"ico/copy.png\" onclick=\"javascript:copy2clipboard('$dir_url')\"/></td>";

 // SlideShow Directory
 echo "<td><a title=\"SlideShow Directory\" href=\"index.php?action=slideshow&dir=$dir&page=$page\"><img alt=\"SlideShow Directory\" border=0 src=\"ico/play.png\"></a></td>";
 echo "<td><a title=\"Zip This Directory\" href=\"index.php?action=zip&dir=$dir\"><img alt=\"Zip This Directory\" border=0 src=\"ico/zip.png\"></a></td>";

 if ($admin==1) { ?>
	<td><a title="Empty directory" href="javascript:Supp('all pictures in <?=$descdir?>','index.php?dir=<?=$dir?>&page=<?=$page?>&action=delpic');"><img border=0 width=10 src="ico/delete.png" alt="Empty Directory"></a></td>
	<td><a title="Create directory" href="javascript:tooltips6('Create New directory','New Directory','<?=$dir?>','<?=$page?>');"><img border=0 width=10 src="ico/adddir.png" alt="Create Directory"></a></td>
 <?	}

 echo "</table><table><tr>"; // EO Toolbox

 $nb_dir_byline=0;
 while ($nb_dir>0&&list($key, $fichier) = each($dirVal)) {
  $tmp_d=opendir($fichier);$tmp_dir=0;$tmp_file=0;
  while($tmp=readdir($tmp_d)){
   if (in_array($tmp,array('.', '..','ico'))) continue;
	 if (is_dir($fichier."/".$tmp)) $tmp_dir++;
   $pos=strpos(basename($tmp),"t_");
   if (($pos!==FALSE)&&($pos<1)) continue;
   if (in_array(substr(strtolower($tmp),(strlen($tmp)-4),4),$file_ext))
    $tmp_file++;
  }
  closedir($tmp_d);

	$nb_dir_byline++;
	if ($nb_dir_byline==6) { echo "</TR><TR>"; $nb_dir_byline=1; }
	
  echo "<td class=dir><a class=genfont href=\"index.php?dir=$fichier\" onmouseover=\"javascript:tooltips('".basename($fichier)."','".date("l d F Y H:i:s", filemtime($fichier));
	if ($tmp_file>0) echo "<br>$tmp_file file".(($tmp_file>1) ? "s" : "");
	if ($tmp_dir>0) echo "<br>$tmp_dir ".(($tmp_dir>1) ? "directories" : "directory");
	echo "');\" onmouseout=\"javascript:kill();\"";
	echo ">".basename($fichier)."</a>";
	if ($admin==1) { ?>
	<a title="Delete directory" href="javascript:Supp('directory <?=$fichier?>','index.php?dir=<?=$dir?>&page=<?=$page?>&filename=<?=$fichier?>&action=delete');"><img border=0 width=10 src="ico/deldir.png" alt="Delete Directory"></a>
 <?	}
  echo "</td>";
 }
 echo "</TR></table>";

if (($page_pos=="top")||($page_pos=="both")) {
 // Pages Numbers
 echo "<table cellspacing=1><tr>";
 $total_pages=ceil($nb_files/$nb_by_page);
 if ($total_pages>1) {
  if ($page>1) echo "<a href=\"index.php?dir=$dir&page=".($page-1)."\"><td width=10 align=center class=page><a class=genfont href=\"index.php?dir=$dir&page=".($page-1)."\">&lt;&lt;</a></td></a>";
  for($i=1;$i<=$total_pages;$i++) {
    echo "<a href=\"index.php?dir=$dir&page=$i\"><td width=10 align=center class=page";
		if ($i==$page) echo "sel";
		echo "><a class=genfont href=\"index.php?dir=$dir&page=$i\">$i</a></td></a>";
	}
	if ($page<$total_pages) echo "<a href=\"index.php?dir=$dir&page=".($page+1)."\"><td width=10 align=center class=page><a class=genfont href=\"index.php?dir=$dir&page=".($page+1)."\">&gt;&gt;</a></td></a>";
 }
 echo "</tr></table>";
}
 echo "</td></tr>";

 $computed_nb_files=0;
 while ($nb_files>0&&list($key, $fichier) = each($retVal)) {
  if ($nb==1) echo "<tr>";
	echo "<td>";
 ?>
<table class=imgb cellpadding=0 cellspacing=0>
<tr>
<td valign=bottom>
<table class=imgtoolbox>
<? if (file_exists($dir."/".$fichier.".txt")) $desc=file_get_contents($dir."/".$fichier.".txt"); else $desc="";
$desctoprint=str_replace("\"","&quot;",str_replace("'","&#039;",trim(str_replace("\r","",str_replace("\n","<br>",$desc)))));?>
<? if ($admin==1) { ?>
<td><a title="Edit description" href="javascript:tooltips3('Description for <?=$fichier?>','<?=$fichier?>','<?=urlencode($dir)?>','<?=$page?>','<?=$desctoprint?>');"><img border=0 width=10 src="ico/desc.png" alt="Edit Description"></a></td>
</tr>
<? if ($desc!="") { ?>
<tr>
<td>
<a title="Delete description" href="javascript:Supp('description file for <?=$fichier?>','index.php?dir=<?=$dir?>&page=<?=$page?>&filename=<?=$fichier?>&action=deletedesc');"><img border=0 width=10 src="ico/delete.png" alt="Delete Description"></a></td>
</tr>
<? } ?>
<td><a title="Rename" href="javascript:tooltips2('Rename','<?=str_replace("'"," ",$fichier)?>','<?=$dir?>','<?=$page?>');"><img border=0 width=10 src="ico/rename.png" alt="Rename"></a></td>
</tr>
<? if (function_exists(imagerotate)) { // On several config in 1.6 imagerotate doesn't exist ?>
<tr>
<td><a title="Rotate 90° Left" href="index.php?dir=<?=$dir?>&page=<?=$page?>&filename=<?=$fichier?>&action=rotate90"><img border=0 width=10 src="ico/rotateL.png" alt="Rotate 90° Left"></a></td>
</tr>
<tr>
<td><a title="Rotate 90° Right" href="index.php?dir=<?=$dir?>&page=<?=$page?>&filename=<?=$fichier?>&action=rotate-90"><img border=0 width=10 src="ico/rotateR.png" alt="Rotate 90° Right"></a></td>
</tr>
<? } ?>
<tr>
<td>
<a title="Refresh thumbnail" href="show.php?filename=<?=base64_encode($dir."/".$fichier)?>&clean=1&back=1&page=<?=$page?>"><img border=0 width=10 src="ico/refresh.png" alt="Refresh"></a></td>
</tr>
<tr>
<td>
<a title="Delete picture" href="javascript:Supp('<?=$fichier?>','index.php?dir=<?=$dir?>&page=<?=$page?>&filename=<?=$dir."/".$fichier?>&action=delete');"><img border=0 width=10 src="ico/delete.png" alt="Delete"></a></td>
</tr>
<? }
	if (($show_exif==1)&&($use_exif==1)) {
   $exif = @exif_read_data ($dir."/".$fichier,0,true);
	 if ($exif!=FALSE) {
	  $returnedexifdata=extract_exif($exif);
    $exifdata=$returnedexifdata[1];
	  $have_exif=$returnedexifdata[0];
	 } else {
	  $have_exif=false;
	  $exifdata="No Exif Data<br>";
  }
	 $exifdata=str_replace("\"","&quot;",str_replace("'","&#039;",trim(str_replace("\r","",str_replace("\n","<br>",$exifdata)))));
?>
<?if ($have_exif) { ?>
<tr>
<td>
<a title="Exif Data" href="javascript:tooltips5('Exif for <?=str_replace("'","\'",$fichier)?>','<?=$exifdata?>');"><img border=0 width=10 src="ico/exif.png" alt="Exif"></a></td>
</tr>
<? } ?>
<tr>
<?
 // Generating URL to CopyToClipboard
 $request_uri=getenv("REQUEST_URI");
 $server_name = getenv("SERVER_NAME");
 $server_port = getenv("SERVER_PORT");
 $server_scriptdir = dirname(getenv("SCRIPT_NAME"));
 $filedir=str_replace( ".",  "", $dir);
 if ($server_port=="80") $server_port=""; else $server_port=":".$server_port;
 echo "<td><img border=0 title=\"Copy to clipboard\" src=\"ico/copy.png\" onclick=\"javascript:copy2clipboard('http://$server_name$server_port$server_scriptdir$filedir/$fichier')\"/></td>";?>
</tr>
</table>
<? } ?>
</td>
<td width=<?=$maxWidth+5?> height=<?=$maxHeight+5?> align=center valign=center>
<?
 $tmp=filesize($dir."/".$fichier);
 if ($tmp>0) {
  $computed_nb_files+=1;
  $size_computed+=$tmp;
  $imageInfo = getimagesize($dir."/".$fichier);
  $width = $imageInfo[0];
  $height = $imageInfo[1];
	$x=0;$y=0;

	if (($width!=0)&&($height!=0)) {
	if ($width > $height) {
 	 if ((($height*$maxWidth)/$width)>$maxHeight) {
	  $x=floor(($maxHeight*$width)/$height);
    $y=$maxHeight;
   } else {
	  $x=$maxWidth;
		$y=floor(($maxWidth*$height)/$width);
   }
	} else {
	 if ((($width*$maxHeight)/$height)>$maxWidth) {
	  $x=$maxWidth;
		$y=floor(($maxWidth*$height)/$width);
   } else {
		$x=floor(($maxHeight*$width)/$height);
    $y=$maxHeight;
   }
	}
	} else {
	 $x=$maxWidth;
   $y=$maxHeight;
	$width=0;
	$height=0;
  }
 } else {
	$x=$maxWidth;
  $y=$maxHeight;
	$width=0;
	$height=0;
 }

 // Extract Extension
 $filename_ext=substr($fichier,(strlen($fichier)-4));

 // Check for .RLK
 if ($filename_ext!=".rlk") {
  if ($width==0||$height==0)
   $file_info="<br>".floor($tmp/1024)." Ko<br>";
	else
   $file_info="<br>".$width."x".$height." - ".floor($tmp/1024)." Ko<br>";

	$file_link=$dir."/".$fichier;
 } else {
	$file_link=file_get_contents($dir."/".$fichier);
	$file_info="<br>".$file_link;
 }

  echo "<a href=\"".$file_link."\" onmouseover=\"javascript:tooltips('".str_replace("'","\'",$fichier)."','".date("l d F Y H:i:s", filemtime($dir."/".$fichier)).$file_info;
	if ($desctoprint!="") { echo "<hr>".$desctoprint; }
	echo "');\" onmouseout=\"javascript:kill();\"";

  $filename_noext=substr($fichier,0,(strlen($fichier)-4));
  if (file_exists($dir."/t_".$filename_noext.".jpg"))
   echo " target=\"$img_target\"><img class=img src=\"show.php?filename=".base64_encode($dir."/".$fichier)."&clean=$clean\"></a>";
	else {
	 // Check for .mov
   if (in_array(strtolower($filename_ext),$file_ext_video)) {
    echo " target=\"$img_target\"><img class=img src=\"show.php?filename=".base64_encode($dir."/".$fichier)."&clean=$clean\"></a>";
   } else {
    echo " target=\"$img_target\"><img width=".$x." height=".$y." class=img src=\"show.php?filename=".base64_encode($dir."/".$fichier)."&clean=$clean\"></a>";
   }
  }
	echo "</td></tr></table>";
	echo "</td>";
	if ($nb>=$nb_cols) { echo "</tr>"; $nb=0; }
	$nb++;
 }
 if ($nb>=$nb_cols) echo "</tr>";

if (($page_pos=="bottom")||($page_pos=="both")) {
 echo "<tr class=imgb><td colspan=$nb_cols>";
 // Pages Numbers
 echo "<center><table cellspacing=1><tr>";
 $total_pages=ceil($nb_files/$nb_by_page);
 if ($total_pages>1) {
  if ($page>1) echo "<a href=\"index.php?dir=$dir&page=".($page-1)."\"><td width=10 align=center class=page><a class=genfont href=\"index.php?dir=$dir&page=".($page-1)."\">&lt;&lt;</a></td></a>";
  for($i=1;$i<=$total_pages;$i++) {
    echo "<a href=\"index.php?dir=$dir&page=$i\"><td width=10 align=center class=page";
		if ($i==$page) echo "sel";
		echo "><a class=genfont href=\"index.php?dir=$dir&page=$i\">$i</a></td></a>";
	}
	if ($page<$total_pages) echo "<a href=\"index.php?dir=$dir&page=".($page+1)."\"><td width=10 align=center class=page><a class=genfont href=\"index.php?dir=$dir&page=".($page+1)."\">&gt;&gt;</a></td></a>";
 }
 echo "</tr></table></center>";
 echo "</td></tr>";
}
if ($admin==1) {
echo "<tr class=imgb><td align=right class=imgb colspan=$nb_cols><table width=100% cellspacing=0 cellpadding=0>";
 ?>
<tr align=right><form enctype="multipart/form-data" action="index.php?dir=<?=$dir?>&page=<?=$page?>&action=rlink" method=post><td class=genfont>
Name : <input class=input size=40 name="name" alt=name type="text">
Url : <input class=input size=40 name="url" alt=url type="text">
<input class=input type="submit" value="Send"></td></form></tr>
<? if ($upload==1) { ?> 
<tr align=right><form enctype="multipart/form-data" action="index.php?dir=<?=$dir?>&page=<?=$page?>&action=upload" method=post><td class=genfont>
Images :
<input class=input size=40 name="file" type="file">
<input class=input type="submit" value="Send">
</td></form></tr>
<tr align=right><form enctype="multipart/form-data" action="index.php?dir=<?=$dir?>&page=<?=$page?>&action=uploadzip" method=post><td class=genfont>
ZipFiles : 
<input class=input size=40 name="zipfile" type="file">
<input class=input type="submit" value="Send">
</td></form></tr>
<? } 
echo "</table></td></tr>";
} ?>
</table></center>
<? } // EOFunction followdir()

followdir($dir_to_process,$dir_to_process);

 $time_end = getmicrotime();
 echo "<br><center><div class=computetime><a class=computetimelink href=\"http://www.zrenard.com/vignettebuilderonline/\">Vignette builder online $version</a>";
if ($show_compute_time==1) {
 echo "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;";
 echo $computed_nb_files." file(s) ";
 if ($compute_file_size==1)
  if ($size_value=="Ko")
   echo " (".number_format($size_computed/1024,2)." Ko) ";
	elseif ($size_value=="Mo")
	 echo " (".number_format(($size_computed/1024)/1024,2)." Mo) ";
 else
	echo " (".number_format((($size_computed/1024)/1024/1024),2)." Go) ";
 echo " computed in ".number_format(($time_end - $time_start),2)." secondes - ";
 if ($use_gd2==1) echo "Use GD2"; else echo "Use GD1.6";
 echo " - ";
 if ($upload==1) echo "Upload <b>On</b>"; else echo "Upload Off";
 echo " - ";
 echo "Ordered by $order_by $order";
}
 echo " - ";
if ($admin==1) { ?>
<a class="computetimelink" href="index.php?action=logout&page=<?=$page?>&dir=<?=$dir?>">Logout</a>
<? } else { ?>
 <a class="computetimelink" href="javascript:tooltipslogin('Login','<?=$dir?>','<?=$page?>');">Login</a>
<? }
 echo "</div></center>";
?>
<br>
</BODY>
</HTML>
