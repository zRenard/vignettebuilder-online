<?
Header("Cache-Control: no-cache");

include("config.php");

$fullfilename=base64_decode($filename);

// $filename should be fichier.ext or something/fichier.ext

$dir=dirname($fullfilename);	 
$filename=basename($fullfilename);
$filename_noext=substr($filename,0,(strlen($filename)-4)); 

if (!file_exists($dir."/t_".$filename_noext.".jpg")||$clean==1||(filemtime($fullfilename)>filemtime($dir."/t_".$filename_noext.".jpg"))) {
 $ext=substr(strtolower($filename),(strlen($filename)-4),4);
 switch($ext){ 
  case ".jpg":   $img = imagecreatefromjpeg ($fullfilename); break; 
  case ".png":   $img = imagecreatefrompng ($fullfilename); break; 
  case ".gif":
	 if ($use_gd2==0) 
	  $img = imagecreatefromgif ($fullfilename);
	 else {
	  if ($back==1) { header("Location: index.php?dir=$dir&page=$page"); }
    $file = fopen($fullfilename,"rb");
    $contents = fread ($file, filesize ($fullfilename));
    Header("Content-type: image/gif"); echo $contents;
    fclose ($file);
		exit();
	 }
	break; 
  case ".mov":
  case ".mp4":
  case ".3gp":
  case ".avi":
   if ($back==1) { header("Location: index.php?dir=$dir&page=$page"); }
   $file = fopen("ico/mov.png","rb");
   $contents = fread ($file, filesize ("ico/mov.png"));
   Header("Content-type: image/png"); echo $contents;
   fclose ($file);
	 exit();
	break; 
  case ".rlk":
   $filename=file_get_contents($fullfilename);
   $ext2=substr(strtolower($filename),(strlen($filename)-4),4);
   switch($ext2){ 
    case ".jpg":   $img = imagecreatefromjpeg ($filename); break; 
    case ".png":   $img = imagecreatefrompng ($filename); break; 
    default : 
	   if ($back==1) { header("Location: index.php?dir=$dir&page=$page"); }
     $file = fopen($filename,"rb");
		 Header("Content-type: image/gif");
	   while (!feof($file)) $contents .= fread ($file, 512);
     echo $contents;
     fclose ($file);
	   exit();
	   break;
	 } 
 }
 $originalWidth = imagesx($img);
 $originalHeight = imagesy($img);
 
 if (($maxHeight >= $originalHeight) &&
	   ($maxWidth >= $originalWidth)) {
	$img2 = $img;
 } else {
	if ($originalWidth > $originalHeight) {
 	 if ((($originalHeight*$maxWidth)/$originalWidth)>$maxHeight) {
		$tmp=floor(($maxHeight*$originalWidth)/$originalHeight);
		if ($use_gd2==1)
     $img2 = imagecreatetruecolor ($tmp, $maxHeight);
		else
     $img2 = imagecreate ($tmp, $maxHeight);		 
    imagecopyresized ($img2,$img, 0, 0, 0, 0, $tmp, $maxHeight,$originalWidth, $originalHeight);
	 } else { 
		$tmp=floor(($maxWidth*$originalHeight)/$originalWidth);
		if ($use_gd2==1)
     $img2 = imagecreatetruecolor ($maxWidth, $tmp);		
		else
     $img2 = imagecreate ($maxWidth, $tmp);
 	  imagecopyresized ($img2,$img, 0, 0, 0, 0,$maxWidth, $tmp,$originalWidth, $originalHeight);
	 }
	} else {
	 if ((($originalWidth*$maxHeight)/$originalHeight)>$maxWidth) {
		$tmp=floor(($maxWidth*$originalHeight)/$originalWidth);
		if ($use_gd2==1)
     $img2 = imagecreatetruecolor ($maxWidth, $tmp);		
		else
     $img2 = imagecreate ($maxWidth, $tmp);				
    imagecopyresized ($img2,$img, 0, 0, 0, 0,$maxWidth, $tmp,$originalWidth, $originalHeight);
	 } else { 
		$tmp=floor(($maxHeight*$originalWidth)/$originalHeight);
		if ($use_gd2==1)
     $img2 = imagecreatetruecolor ($tmp, $maxHeight);
		else
     $img2 = imagecreate ($tmp, $maxHeight);		 
    imagecopyresized ($img2,$img, 0, 0, 0, 0, $tmp, $maxHeight,$originalWidth, $originalHeight);
	 }
	}
 }
 if ($create_thumbnail==1)
  @imagejpeg($img2,$dir."/t_".$filename_noext.".jpg",$thumbnail_quality);
 if ($back==1) { header("Location: index.php?dir=$dir&page=$page"); } else { Header("Content-type: image/jpeg"); imagejpeg($img2); }
} else {
 $file = fopen($dir."/t_".$filename_noext.".jpg","rb");
 $contents = fread ($file, filesize ($dir."/t_".$filename_noext.".jpg"));
 echo $contents;
 fclose ($file);
} ?>
