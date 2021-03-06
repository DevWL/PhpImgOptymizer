<?php

// https://stackoverflow.com/questions/1201798/use-php-to-convert-png-to-jpg-with-compression

ini_set('memory_limit', '-1');

class File{

    public $dir = NULL;

    public function __construct(public $name, public $size, public $src, public $temp)
    {
        
    }

    public function setDir()
    {
        
    }
}

class FileCollection{
    public $fileCollection = [];
    public function addFile(File $file)
    {
        if(!$file->dir){
            $fileCollection[$file->dir][] = $file;
        }else{
            $fileCollection[] = $file;
        }
        
    }
}

Class ImageOptymizer {

    public $counterAllFiles = 0;
    public $countFilesOptymized = 0;

    public function __construct(private $path, private $extRegEx, private $recursive, private $options) // PHP8.0 Syntax
    {
        $this->searchForFiles($this->path, $this->extRegEx, $this->recursive, $this->options);
    }

    /**
     * In development  - not used
     * 
     * Function remove CCI Profile information from image (make image size smaller)
     * @param string $src
     * @param string $dest
     * @src https://stackoverflow.com/questions/3614925/remove-exif-data-from-jpg-using-php
     * status - currently not used by this script
     */
    public function removeIcc()
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
    public function blureImage($src, $img_filter_gaussian_blur = [45, 999], $img_filter_smooth = [1, 99], $img_filter_brightness = [1, 10]){
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
     * @param integer $resizeProcent - width in %
     * @param string $targetFile - path to a file
     * @param string $originalFile - path to a file
     * @param mix array $options - $options['compresionLvlForPng'] image compression strength value from 0 - 9 for PNG | $options['qualityForJpgAndWebp'] and qualityForJpgAndWebp from 0 to 100 for JPG
     * @throws Exception - if file type not supported
     */
    public function resizeImg($resizeProcent, $targetFile, $originalFile, $options) {
        $image_create_func = NULL;
        $info = getimagesize($originalFile);
        //print_r($info);
        // var_dump($targetFile);
        $mime = $info['mime'];

        switch ($mime) {
                case 'image/jpeg':
                        $image_create_func = 'imagecreatefromjpeg';
                        $image_save_func = 'imagejpeg'; // not used - file are saved with oryginal extension
                        $new_image_ext = 'jpg';
                        $args = [$targetFile, $options['qualityForJpgAndWebp']]; // first argument will be added to the fornt of the array below - $temp
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

                case 'image/webp':
                    $image_create_func = 'imagecreatefromwebp';
                    $image_save_func = 'imagewebp'; // not used - file are saved with oryginal extension
                    $new_image_ext = 'webp';
                    $args = [$targetFile, $options['qualityForJpgAndWebp']]; // first argument will be added to the fornt of the array below - $temp
                    break;

                default: 
                        throw new Exception('Unknown image type!');
        }

        // If sawe as webp then change extension to webp
        // var_dump('args', $args);
        // var_dump('options', $this->options);
        if($this->options['outputFormat'] != NULL){
            if($this->options['outputFormat'] == 'webp'){
                $image_save_func = 'imagewebp';
                $new_image_ext = 'webp';
                $withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $targetFile);
                $targetFile = $withoutExt.".".$new_image_ext;
                $args[0] = $targetFile;
                // var_dump('targetfile - new', $targetfile);
            }
        }

        // If keepOryginal === true then add char to name so the file will not get overriden - if prepend string is was set in options
        if($this->options['keepOriginal']){
            $exploded = explode('/', $targetFile);
            var_dump('$exploded', $exploded);
            $arrLength = count($exploded);
            $exploded[$arrLength-1] = $this->options['keepOriginal'].$exploded[$arrLength-1];
            $imploded = implode('/', $exploded);
            $targetFile = $imploded;
            $args[0] = $targetFile;
            var_dump('$imploded', $imploded);
            var_dump('$args', $args);
        }


        $img = $image_create_func($originalFile);
        list($width, $height) = getimagesize($originalFile);

        $imgOrientaton = null;
        if($width == $height){
            $imgOrientaton = 'square';
        }else if($width > $height){
            $imgOrientaton = 'landscape';
        }else if($width < $height){
            $imgOrientaton = 'portrait';
        }else{
            throw new Exception("not assigned to aby of those: [landscape, portrait, square] ");
        }

        // return if this image orientatin is set to false
        if($this->options['imgOrientaton'][$imgOrientaton] != true) return;
        
        if($imgOrientaton == 'landscape' || $imgOrientaton == 'square'){
            // return if this image not in size range
            if($width < $options['widthRange'][0] || $width > $options['widthRange'][1]) return;

            // if landscape or square set width and calculate height
            $newWidth = $width / 100 * $resizeProcent;
            if($newWidth > $options['maxWidthForLandscape']) $newWidth = $options['maxWidthForLandscape'];
            $newHeight = ($height / $width) * $newWidth;

            echo "IMG orientation: --- $imgOrientaton".PHP_EOL;
            echo "1 width ". $width . " => newWidth " . $newWidth.PHP_EOL;
            echo "2 height ". $height . " => newHeight " . $newHeight.PHP_EOL;

        }else if($imgOrientaton == 'portrait'){
            // return if this image not in size range
            if($height < $options['heightRange'][0] || $height > $options['heightRange'][1]) return;

            // if portait set height and calculate width
            $newHeight = $height / 100 * $resizeProcent;
            if($newHeight > $this->options['maxHeightForPortreit']) $newHeight = $this->options['maxHeightForPortreit'];
            $newWidth = ($width / $height) * $newHeight;

            echo "IMG orientation: | $imgOrientaton".PHP_EOL;
            echo "1 height ". $height . " => newHeight " . $newHeight.PHP_EOL;
            echo "2 width ". $width . " => newWidth " . $newWidth.PHP_EOL;

        }else{
            throw new Exception("Not able to set newWidth or newHeight...");
        }


        


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

        if (file_exists($targetFile) && !$this->options['keepOriginal']) {
                unlink($targetFile);
        }
        array_unshift($args, $tmp);
        $this->countFilesOptymized;
        $this->countFilesOptymized++; // for information reference only
        call_user_func_array($image_save_func, $args); // new
        //$image_save_func($tmp, $targetFile, $value); //old
    }

    public function saveAsWEBP(){
        // What are you creating your .webp images from? Assuming you're "converting" jpegs or pngs to WebP you can just create an image resource with imagecreatefromjpegor imagecreatefrompngand then just save with imagewebp($im, 'file.webp'); You could also batch convert with convert (imagick) or call a service like Cloudinary: cloudinary.com/documentation/php_image_manipulation (awesome service btw!) – Eduardo Romero Aug 18 '14 at 23:20
    }

    /**
     * Function responsible for image optimisation.
     * Function is used by listFiles function,
     */
    public function optymizeFile($file, $options){
        $this->resizeImg($options['resizeImgProcent'], $file, $file, $options);
        // removeIcc(...);
        // blureImage(...);
    }

    /**
     * Function is used by searchForFiles function 
     * Function depends on optymizeFile function to run image optimisation.
     * @param string $path - starting path
     * @param string $extRegEx - what file name/extension to search
     */
    public function listFiles($path, $extRegEx, $options){
        $this->countFilesOptymized;
        $this->counterAllFiles;
        // BLOB REGULAR EXPRESION IS VERY LIMITED - that is why we get all files from directory and make comparisoin with preg_match
        // it is possible to use filter with file iterators instead
        $files = array_filter(glob($path.'*'));
        
        clearstatcache();
        foreach($files as $file){
            // TRUE REGULAR EXPRESION
            if(preg_match($extRegEx, $file) && preg_match($options['fileNamePattern'], $file)) {
                // DEPENDENCY - image optymisation
                $this->counterAllFiles++; // for information reference only
                echo ceil((filesize($file) / 1024)).'Kb'.PHP_EOL;
                if(filesize($file) > ($options['optymizeFileLargerThen'] * 1024)){
                    $this->optymizeFile($file, $options);
                }
            }

        }
        
        // Information about end of the script
        echo "End of file optimisation...".PHP_EOL; 
        echo "File optimized: ".$this->countFilesOptymized." / ".$this->counterAllFiles.PHP_EOL;
        
    }

    /**
     * This function search for files with given extension
     * Function depends on listFiles function to list all the files.
     * @param string $path - starting path
     * @param string $extRegEx - what file name/extension to search
     * @param boolean $recursive - search in subdirectories
     */
    public function searchForFiles($path, $extRegEx, $recursive = false, $options){
        $dir = new DirectoryIterator($path);
        // DEPENDENCY - list files in curent directory
        $this->listFiles($path.'/', $extRegEx, $options);
        foreach ($dir as $fileinfo) {
            // if set as recursive 
            if ($recursive == true && $fileinfo->isDir() && !$fileinfo->isDot()) {
                echo $path."/".$fileinfo->getFilename().PHP_EOL;
                // GO RECURSIVE
                $this->searchForFiles($path.'/'.$fileinfo->getFilename(), $extRegEx, $recursive, $options);
            }
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
$extRegEx = '%.*(png|jpg|jpeg|gif|webp)$%'; //regular expresion - selects file extensions - "%" is expresion delimiter
$recursive = true;
$options = [
    // 'mode' => 'keep', // NOT USED - TODO *** keep original img or raplce 'mode' => [keep, replace] string
    // 'imageresolution' => [96, 96] // NOT USED - TODO *** sets img resolution dpi 'imageresolution' => [96, 96] @array of int
    'resizeImgProcent' => 100, // set new size of all optymized img files. 100 = no resizes
    'compresionLvlForPng' => 6, // compresion strength from 0 to 9 where 9 is the strongest and 6 is the default
    'qualityForJpgAndWebp' => 60, // image quality from 0 to 100 where 100 is the heighest quality and 75 is the default
    'imgOrientaton' => ['landscape'=> true, 'portrait' => true, 'square' => true], // NOT USED - TODO *** 'applyToImgOrintation' => ['landscape'=> false, 'portrait' => false, 'square' => false]
    'widthRange' => [0, 99999], // apply to images sized between 0px to 99999 px width
    'heightRange' => [0, 99999], // apply to images sized between 0px to 99999 px height
    'optymizeFileLargerThen' => 10, // optymise files larger then number of Kb - example: 50 kb
    'fileNamePattern' => '%.*%', // compress only files which name mach this regular expresion use "%.*%" as default | %^(?!web-).*% will exlude files starting with "web-"
    'outputFormat' => 'jpg', // Converts to other file type - NULL - do nothing | or convert to: jpg | png | gif | webp
    // 'outputSizes' => [250, 600, 900], // NOT USED - TODO *** - it shoud output additional file with size maching it name like web600-filename.ext and so on....
    'keepOriginal' => false, // false || null OR prepend new file with custom string exemaple" web- //   TODO *** or use array for multiple output

    'maxWidthForLandscape' => 1200, // images larger then this value will be resize to maxWidthForLandscape value // TODO make an array of sizes so it output files in few formats
    'maxHeightForPortreit' => 1200, // images larger then this value will be resize to maxHeightForPortreit value // TODO make an array of sizes so it output files in few formats
];

new ImageOptymizer($path, $extRegEx, $recursive, $options);