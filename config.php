<?
////////////////////////////////////////////////////////////////
//// Config
$main_login=""; // TO MODIFY
$main_pwd=""; // TO MODIFY

// Thumbnail Config
$maxWidth=160;
$maxHeight=120;
$thumbnail_quality=30;

// Image Rotate Option
$rotate_jpeg_quality=100;

// Thumbnail display config
$nb_cols=4;
$nb_lines=3;
$nb_by_page=$nb_cols*$nb_lines; // or 16

$dir_to_process="."; // Initial Dir
$root_dir = "."; // Root link

$dirchar = '/'; // directory separator '/' for unix or windows work with both, you should use '\\' for windows.

$use_maindesc=0; // 1 : Use only one file for main description (on main dir)
								 // 0 : Use main description by directory (If none, directory is used)

$page_pos = "both"; // top, bottom or both

$order_by = "filedate"; // filedate, filename or filesize
$order="asc"; // desc (last file on first page, Z to A for filename) or asc

$img_target="myimage";
$create_thumbnail=1; // 0 : Thumbnail are generate onfly

$show_exif= 1; // Show Exif if possible (exif must be enabled and available)

// Exif Priority : Section, Key, Exclude Key
$exif_section=array("EXIF");
$exif_key=array("Make","Model"); // Make & Model are in IFD0 section
$exif_exclude_key=array("FileSource","SceneType","ComponentsConfiguration","MakerNote"); // Binaray Exif Key

$show_compute_time=0; // Show compute time (and stats) at the bottom
$compute_file_size=0; // Compute and Show filesize on stats (used only if $show_compute_time=1)
$size_value="Mo"; // FileSize in Parameters (Ko, Mo or Go) if not Go is used

//$use_gd2 = 1; // Force to Use/or not GD2 : Comment to leave script check availability and use GD2
//$use_exif = 1; // Force to Use/or not Exif : Comment to leave script check availability and use Exif

//// End Of Config
////////////////////////////////////////////////////////////////

// If GD 2 was use (do no support gif), gif file was resized in html (more bandwith use)
$image_file_type=array("image/gif","image/jpeg","image/png","image/x-png","image/pjpeg","image/jpg");
$zip_file_type = array("application/x-zip-compressed");
$file_ext=array(".jpg",".png",".gif",".rlk",".mov",".mp4",".3gp",".avi");
$file_ext_video=array(".mov",".mp4",".3gp",".avi");

if (!isset($use_exif))
 if (function_exists(exif_read_data))
	$use_exif=1; 
 else
  $use_exif=0; 

// Check GD 2.0
if (!isset($use_gd2)&&(function_exists(gd_info))) {
 $gdinfo=gd_info(); 
 if (strpos($gdinfo["GD Version"],"2.0")===false)
  $use_gd2=0;
 else
  $use_gd2=1;
} else
 $use_gd2=1;
 
if (!(function_exists(file_get_contents))) {
 function file_get_contents($f) {
   ob_start();
   $retval = @readfile($f);
   if (false !== $retval) { // no readfile error
     $retval = ob_get_contents();
   }
   ob_end_clean();
  return $retval;
 }
}

if (ini_get("file_uploads")=="1")
 $upload=1;
else
 $upload=0;

function unzip($file, $path) {
  global $dirchar;
  $zip = zip_open($file);
  if ($zip) {
    while ($zip_entry = zip_read($zip)) {
      if (zip_entry_filesize($zip_entry) > 0) {
        // str_replace must be used under windows to convert "/" into "\"
        $complete_path = $path.str_replace('/',$dirchar,dirname(zip_entry_name($zip_entry)));
        $complete_name = $path.str_replace ('/',$dirchar,zip_entry_name($zip_entry));
        if(!file_exists($complete_path)) { 
          $tmp = '';
          foreach(explode($dirchar,$complete_path) AS $k) {
            $tmp .= $k.$dirchar;
            if(!file_exists($tmp)) {
              mkdir($tmp, 0777); 
            }
          } 
        }
        if (zip_entry_open($zip, $zip_entry, "r")) {
         $fd = fopen($complete_name, 'w');
         fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
         fclose($fd);
         zip_entry_close($zip_entry);
        }
      }
    }
    zip_close($zip);
  }
}?>
