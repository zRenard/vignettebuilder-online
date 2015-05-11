# List of Requirements #
  * PHP 4.x.x could be find on www.php.net
  * Use GD 2.0 or GD 1.x (choise was automatic in config file). Gif are resized in html when using gd2.
  * Use Exif (Only available in PHP 4 compiled using --enable-exif)
  * Modify $main\_login=""; and $main\_pwd=""; in config.php
  * Add $admin=1;session\_register('admin'); in index.php to bypass login/pwd
  * Configure some options like thumbnail size or exif infos