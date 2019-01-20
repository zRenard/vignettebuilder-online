# Vignette Builder Online

# List of Features
*   Dynamic Jpeg Thumbnail Generation (t\_Filename)
*   Login/password For Administrator
*   No Database Require
*   Upload Picture, Doen't Need To Upload Picture Via Ftp (if Available)
*   Picture Rotation (if Available)
*   Main Album Description
*   File Rename
*   Multiples Pages
*   Online Pictures Notes
*   Upload/Download Zip
*   Exif Data From Digital Picture (if Available)
*   Remote Link (Link To A Http:// Picture), Generate Thumbail
*   Sub-Directory
*   Rebuild Thumbnail
*   Erase All Pictures
*   Option To Generate Thumbnail Only On-fly
*   Sorting Options

# List of Requirements
*   PHP 4.x.x could be find on www.php.net
*   Use GD 2.0 or GD 1.x (choise was automatic in config file). Gif are resized in html when using gd2.
*   Use Exif (Only available in PHP 4 compiled using --enable-exif)
*   Modify $main\_login=""; and $main\_pwd=""; in config.php
*   Add $admin=1;session\_register('admin'); in index.php to bypass login/pwd
*   Configure some options like thumbnail size or exif infos

# List of todo
*   Fix known
 *   [Session issues](https://github.com/zRenard/vignettebuilder-online/issues/1)
 *   [Exif data lost after a rotation](https://github.com/zRenard/vignettebuilder-online/issues/2)
*   Autoconfig Script
*   Sort Data in XML
*   Guest user
*   Search in name or description
