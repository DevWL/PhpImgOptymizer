<?php
ini_set('memory_limit', '-1');

$counterAllFiles = 0;
$countFilesOptymized = 0;

/**
 * In development  - not used
 * 
 * Function remove CCI Profile information from image (make image size smaller)
 * @param string $src
 * @param string $dest
 * @src https://stackoverflow.com/questions/3614925/remove-exif-data-from-jpg-using-php
 * status - currently not used by this script
 */
function removeIcc()
{

    // ...
    
}

/**
 * In development - not used
 * 
 * Bluring image also reduce image size. Usefull efect for background images
 * @param string $src
 * @param string $dest
 * @param type $amount
 * @src https://stackoverflow.com/questions/42759135/php-best-way-to-blur-images 
 */
function blureImage($src, $img_filter_gaussian_blur = [45, 999], $img_filter_smooth = [1, 99], $img_filter_brightness = [1, 10]){
  $image = imagecreatefromjpeg($src);
  for ($x=1; $x <= $img_filter_gaussian_blur[0]; $x++){
      imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR, $img_filter_gaussian_blur[1]);
  } 
  for ($x=1; $x <= $img_filter_smooth[0]; $x++){
      imagefilter($image, IMG_FILTER_SMOOTH, $img_filter_smooth[1]);
  } 
  for ($x=1; $x <= $img_filter_brightness[0]; $x++){
      imagefilter($image, IMG_FILTER_BRIGHTNESS, $img_filter_brightness[1]);
  }

  imagejpeg($image);
  // imagedestroy($image);
}

/**
 * 
 * @param integer $newWidth - width in %
 * @param string $targetFile - path to a file
 * @param string $originalFile - path to a file
 * @param mix array $options - $options['compresionLvlForPng'] image compression strength value from 0 - 9 for PNG | $options['qualityForJpg'] and qualityForJpg from 0 to 100 for JPG
 * @throws Exception - if file type not supported
 */
function resizeImg($newWidth, $targetFile, $originalFile, $options) {

    $info = getimagesize($originalFile);
    //print_r($info);
    $mime = $info['mime'];

    switch ($mime) {
            case 'image/jpeg':
                    $image_create_func = 'imagecreatefromjpeg';
                    $image_save_func = 'imagejpeg'; // not used - file are saved with oryginal extension
                    $new_image_ext = 'jpg';
                    $args = [$targetFile, $options['qualityForJpg']]; // first argument will be added to the fornt of the array below - $temp
                    break;

            case 'image/png':
                    $image_create_func = 'imagecreatefrompng';
                    $image_save_func = 'imagepng';
                    $new_image_ext = 'png'; // not used - file are saved with oryginal extension
                    $args = [$targetFile, $options['compresionLvlForPng'], PNG_ALL_FILTERS]; // first argument will be added to the fornt of the array below - $temp
                    break;

            case 'image/gif':
                    $image_create_func = 'imagecreatefromgif';
                    $image_save_func = 'imagegif'; // not used - file are saved with oryginal extension
                    $new_image_ext = 'gif';
                    $args = [$targetFile]; // first argument will be added to the fornt of the array below - $temp
                    break;

            default: 
                    throw new Exception('Unknown image type!');
    }

    $img = $image_create_func($originalFile);
    list($width, $height) = getimagesize($originalFile);
    if($width < $options['widthRange'][0]|| $width > $options['widthRange'][1]) return;
    $newWidth = $width / 100 * $newWidth;
    if($newWidth > $options['maxWidth']) $newWidth = $options['maxWidth'];
    // echo $width . " => " . $newWidth.PHP_EOL;
    $newHeight = ($height / $width) * $newWidth;
    $tmp = imagecreatetruecolor($newWidth, $newHeight);
    
    // prevent PNG and GIFF from adding black backbround
    switch ($mime) {
        case 'image/gif':
        case 'image/png':
            // integer representation of the color black (rgb: 0,0,0)
            $background = imagecolorallocate($tmp , 0, 0, 0);
            // removing the black from the placeholder
            imagecolortransparent($tmp, $background);

        case 'image/png':
            // turning off alpha blending (to ensure alpha channel information
            // is preserved, rather than removed (blending with the rest of the
            // image in the form of black))
            imagealphablending($tmp, false);

            // turning on alpha channel information saving (to ensure the full range
            // of transparency is preserved)
            imagesavealpha($tmp, true);
            break;

        default:
            break;
    }
    // end
    
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    if (file_exists($targetFile)) {
            unlink($targetFile);
    }
    array_unshift($args, $tmp);
    global $countFilesOptymized;
    $countFilesOptymized++; // for information reference only
    call_user_func_array($image_save_func, $args); // new
    //$image_save_func($tmp, $targetFile, $value); //old
}

/**
 * Function responsible for image optimisation.
 * Function is used by listFiles function,
 */
function optymizeFile($file, $options){
    resizeImg($options['resizeImgProcent'], $file, $file, $options);
    // removeIcc(...);
    // blureImage(...);
}

/**
 * Function is used by searchForFiles function 
 * Function depends on optymizeFile function to run image optimisation.
 * @param string $path - starting path
 * @param string $extRegEx - what file name/extension to search
 */
function listFiles($path, $extRegEx, $options){
    global $countFilesOptymized;
    global $counterAllFiles;
    // BLOB REGULAR EXPRESION IS VERY LIMITED - that is why we get all files from directory and make comparisoin with preg_match
    // it is possible to use filter with file iterators instead
    $files = array_filter(glob($path.'*'));
    
    clearstatcache();
    foreach($files as $file){
        // TRUE REGULAR EXPRESION
        if(preg_match($extRegEx, $file) && preg_match($options['fileNamePattern'], $file)) {
            // DEPENDENCY - image optymisation
            $counterAllFiles++; // for information reference only
            echo ceil((filesize($file) / 1024)).'Kb'.PHP_EOL;
            if(filesize($file) > ($options['optymizeFileLargerThen'] * 1024)){
                optymizeFile($file, $options);
            }
        }

    }
    
    // Information about end of the script
    echo "End of file optimisation...".PHP_EOL; 
    echo "File optimized: ".$countFilesOptymized." / ".$counterAllFiles.PHP_EOL;
    
}

/**
 * This function search for files with given extension
 * Function depends on listFiles function to list all the files.
 * @param string $path - starting path
 * @param string $extRegEx - what file name/extension to search
 * @param boolean $recursive - search in subdirectories
 */
function searchForFiles($path, $extRegEx, $recursive = false, $options){
    $dir = new DirectoryIterator($path);
    // DEPENDENCY - list files in curent directory
    listFiles($path.'/', $extRegEx, $options);
    foreach ($dir as $fileinfo) {
        // if set as recursive 
        if ($recursive == true && $fileinfo->isDir() && !$fileinfo->isDot()) {
            echo $path."/".$fileinfo->getFilename().PHP_EOL;
            // GO RECURSIVE
            searchForFiles($path.'/'.$fileinfo->getFilename(), $extRegEx, $recursive, $options);
        }
    }
}

// DEMO - USE EXAMPLE
/**
 * Configur below setting before uploading to server and runing through comand line.
 * Use it at your own risk! 
 * Software comes without any waranty.
 * Make file backup before runing the script
 */
$path = '.'; // relative path from PhpImgOptymizer.php file to img folder (. means that images are in the same starting directory or nested in folders below)
$extRegEx = '%.*(png|jpg|jpeg|gif)$%'; //regular expresion - selects file extensions - "%" is expresion delimiter
$recursive = true;
$options = [
    'resizeImgProcent' => 100, // set new size of all optymized img files. 100 = no resizes
    'compresionLvlForPng' => 9, // compresion strength from 0 to 9 where 9 is the strongest and 6 is the default
    'qualityForJpg' => 50, // image quality from 0 to 100 where 100 is the heighest quality and 75 is the default
    'maxWidth' => 1200, // images larger then 1200 will be resize to maxWidth value
    'widthRange' => [850, 99999], // apply to images sized between 0px to 9000px width
    'optymizeFileLargerThen' => 50, // optymise files larger then number of Kb - example: 50 
    'fileNamePattern' => '%.*%', // compress only files which name mach this regular expresion 
];

searchForFiles($path, $extRegEx, $recursive, $options);