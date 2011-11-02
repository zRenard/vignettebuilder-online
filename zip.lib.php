<?
class zipfile {
    /**
		 * Array to store exclude list
		 *
		 * @var array $exclude_file
		 */
		var $exclude_file = array();

    /**
		 * Array to store include file type
		 *
		 * @var array $include_file_type
		 */
		var $include_file_type = array();

    /**
		 * Array to store saved file number
		 *
		 * @var array $total_files
		 */
		var $total_files = 0;

    /**
		 * Array to store saved fileName
		 *
		 * @var array $fileList
		 */
		var $fileList = array();

		/**
		 * Array to store saved fileSize
		 *
		 * @var array $fileSize
		 */
		var $fileSize = array();

    /**
     * Central directory
     *
     * @var  array    $ctrl_dir
     */
    var $ctrl_dir     = array();

    /**
     * End of central directory record
     *
     * @var  string   $eof_ctrl_dir
     */
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * Last offset position
     *
     * @var  integer  $old_offset
     */
    var $old_offset   = 0;

    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
        	$timearray['year']    = 1980;
        	$timearray['mon']     = 1;
        	$timearray['mday']    = 1;
        	$timearray['hours']   = 0;
        	$timearray['minutes'] = 0;
        	$timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    } // end of the 'unix2DosTime()' method


    function scandir($dir,$maindir) { 
     $retVal=array();$dirVal=array();
		 $total_files=0;
		 if (file_exists($dir)) {
      $dossier=opendir($dir); 
      while($fichier=readdir($dossier)) { 
       if (in_array($fichier,array_merge(array('.', '..'),$this ->exclude_file))) continue; 

			 // don't zip thumbnails
       $pos=strpos(basename($fichier),"t_");
       if (($pos!==FALSE)&&($pos<1)) continue;

       if (is_dir($dir."/".$fichier))
        $dirVal[count($dirVal)+1] = $dir."/".$fichier;
       else {
			  if (in_array(substr(strtolower($fichier),(strlen($fichier)-4),4),$this -> include_file_type))
         $retVal[count($retVal)+1] = $fichier;
			 } 
      }     
      closedir($dossier);
		 }
     $nb_files=count($retVal);$nb_dir=count($dirVal);$nb=0;
		 $total_files=$total_files+$nb_files; 
     while ($nb_files>0&&list($key, $fichier) = each($retVal)) {
		  // Adding file to archive
			$fullfilename= $dir."/".$fichier;
      $handle = fopen ($fullfilename, "rb");
			$filesize = filesize ($fullfilename);
			array_push($this->fileSize,$filesize);
      $data = fread($handle, $filesize);
			$fullfilename=str_replace("//","/",$fullfilename);
			$fullfilename=str_replace("./","",$fullfilename);			
			array_push($this->fileList,$fullfilename);
			flush();
// Compute best filename for zip
$in_zip_filename="";
if ($maindir==".") $in_zip_filename.="root/";
if ($dir!=".") $in_zip_filename.=basename($dir)."/";
$in_zip_filename.=$fichier;
//echo $in_zip_filename."<br>";
      $this -> addFile($data,$in_zip_filename);
     } 
     while ($nb_dir>0&&list($key, $fichier) = each($dirVal)) { 
      $total_files=$total_files+$this->scandir($fichier,$maindir); 
      $nb++; 
     }
		 return $total_files; 
    } 

    function addDir($dirname) {
		 return $this->total_files+=$this->scandir($dirname,$dirname);
	  }
				
    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    function addFile($data, $name, $time = 0)
    {
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;
        $new_offset        = strlen(implode('', $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    } // end of the 'addFile()' method


    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    function file()
    {
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    } // end of the 'file()' method
}?>
