# PhpImgOptymizer
PHP script that optymize img size and quality. Works with jpg as well as .gif and .png file formats. Basicaly save space on your server and makes images load quicker. Works on most of share hostings. Posibly can be turned in to WP plugin for free img optymization.

IMPORTANT! - A "GP" php extension need to be installed / enabled to run this script

```php
<?php
// DEMO - USE EXAMPLE
/**
 * Configur below setting before uploading to server and runing through comand line.
 * Use it at your own risk! 
 * Software comes without any waranty.
 * Make file backup before runing the script
 */
$path = '.'; // relative path from PhpImgOptymizer.php file to img folder (. means that images are in the same starting directory or nested below)
$extRegEx = '%.*(png|jpg|jpeg|gif)$%'; //regular expresion - selects file extensions - "%" is expresion delimiter
$recursive = true;
$options = [
    'resizeImgProcent' => 100, // set new size of all optymized img files. 100 = no resizes
    'compresionLvlForPng' => 9, // compresion strength from 0 to 9 where 9 is the strongest and 6 is the default
    'qualityForJpg' => 50, // image quality from 0 to 100 where 100 is the heighest quality and 75 is the default
    'maxWidth' => 1200, // images larger then 1200 will be resize to maxWidth value
    'widthRange' => [300, 99999], // apply to images sized between 0px to 9000px width
    'optymizeFileLargerThen' => 50, // optymise files larger then number of Kb - example: 50 
    'fileNamePattern' => '%.*%', // compress only files which name mach this regular expresion 
];

searchForFiles($path, $extRegEx, $recursive, $options);
?>
```
