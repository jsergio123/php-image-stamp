<?php
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////
///                                                                                       ///
///  URL EXAMPLE: ../image.php?file=picture.jpg&width=500&crop=true                       ///
///  Set folder permissions for $photoPath to 0775                                        ///
///  PECL :: Package :: imagick - PHP Required                                            ///
///  Supported File Types: JPEG, PNG, GIF, GD, GD2, WBMP, XBM                             ///
///                                                                                       ///
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////

$stampImg  = true;  //  Enable or disable image stamp
$stampSize = '0.25';  // Percentage size of stamp to be applied to photo
$minSize   = 200;  // Only photos with widths greater than or equal to will have the stamp applied
$stampFile = 'img/logo-3.png';  // Path to PNG image used for stamping photos
$photoPath = 'img/gallery/pics/';  // Location of the photos folder
$cachePath = $photoPath.'cache/';  // Cache resides in photos folder and can be deleted to clear cached images


// Do not edit anything below this line
$cropImg = false;
$widthSet = false;
if (!is_dir($cachePath)) {
        if (!mkdir($cachePath, 0775)) { die('Failed to create folder...'); }
}
$newFile = filter_var($_GET['file'], FILTER_SANITIZE_STRING);
$newWidth = intval($_GET['width']);
$resizedFilename = $newFile;
if (filter_var($_GET['crop'], FILTER_VALIDATE_BOOLEAN)) {
        $cropImg = true;
        $resizedFilename = 'C_'.$resizedFilename;
}
if (isset($newWidth) && is_numeric($newWidth) && $newWidth > 0) {
        $widthSet = true;
        $resizedFilename = 'W'.$newWidth.'_'.$resizedFilename;
}
$resizedFile = $cachePath.$resizedFilename;
$file = $photoPath.$newFile;
$stampSize = floatval($stampSize);

if (is_file($resizedFile) && is_readable($resizedFile)) {
	// Display image
	header('Location: '.$resizedFile);
	exit();
}
	else {                     
                function open_image ($file) {
                        global $imgType;

                        # JPEG:
                        $im = @imagecreatefromjpeg($file);
                        if ($im !== false) { $imgType = 'jpeg'; return $im; }

                        # GIF:
                        $im = @imagecreatefromgif($file);
                        if ($im !== false) { $imgType = 'gif'; return $im; }

                        # PNG:
                        $im = @imagecreatefrompng($file);
                        if ($im !== false) { $imgType = 'png'; return $im; }

                        # GD File:
                        $im = @imagecreatefromgd($file);
                        if ($im !== false) { $imgType = 'gd'; return $im; }

                        # GD2 File:
                        $im = @imagecreatefromgd2($file);
                        if ($im !== false) { $imgType = 'gd2'; return $im; }

                        # WBMP:
                        $im = @imagecreatefromwbmp($file);
                        if ($im !== false) { $imgType = 'wbmp'; return $im; }

                        # XBM:
                        $im = @imagecreatefromxbm($file);
                        if ($im !== false) { $imgType = 'xbm'; return $im; }

                        return false;
                }

                // Load image
                $image = open_image($file);
                if ($image === false) { die ('Unable to open image'); }

                // Get original width and height
                $width = imagesx($image);
                $height = imagesy($image);

                // Set a new width, and calculate new height
                if ($widthSet === false) { $newWidth = $width; }
                $newHeight = ($cropImg === true) ? round($newWidth * 0.75) : $height * ($newWidth/$width);

                // Resample
                $image_resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($cropImg === true) {		
			if ($width >= $height) {
				$ratio = $newWidth / $width;
				$intraSourceWidth = $newWidth;
				$intraSourceHeight = $height * $ratio;
				imagecopyresampled(
					$image_resized,
					$image,
					0,
					($newHeight / 2) - ($intraSourceHeight / 2),
					0,
					0,
					$newWidth,
					ceil($intraSourceHeight),
					$width,
					$height
				);
			}
                                else {
                                        $ratio = $newHeight / $height;
                                        $intraSourceHeight = $newHeight;
                                        $intraSourceWidth = $width * $ratio;
                                        imagecopyresampled(
                                                $image_resized,
                                                $image,
                                                ($newWidth / 2) - ($intraSourceWidth / 2),
                                                0,
                                                0,
                                                0,
                                                ceil($intraSourceWidth),
                                                $newHeight,
                                                $width,
                                                $height
                                        );
                                }
                }
                        else imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                if ($newWidth >= $minSize && $stampImg === true) {
                        // Load the stamp and the photo to apply the watermark to
                        $stampWidth = ceil($newWidth*$stampSize);
                        if (is_file($cachePath.'stamp_w'.$stampWidth.'.png') && is_readable($cachePath.'stamp_w'.$stampWidth.'.png')) {
                                $stamp = imagecreatefrompng($cachePath.'stamp_w'.$stampWidth.'.png');
                        }
                                else {
                                        $resizeStamp = new Imagick($stampFile);
                                        $resizeStamp->resizeImage($stampWidth, 0, imagick::FILTER_LANCZOS, 1);
                                        $resizeStamp->writeImage($cachePath.'stamp_w'.$stampWidth.'.png');
                                        $resizeStamp->clear();
                                        $resizeStamp->destroy();
                                        $stamp = imagecreatefrompng($cachePath.'stamp_w'.$stampWidth.'.png');
                                } 

                        // Set the margins for the stamp and get the height/width of the stamp image
                        $marge_right = 10;
                        $marge_bottom = 10;
                        $sx = imagesx($stamp);
                        $sy = imagesy($stamp);

                        // Copy the stamp image onto our photo using the margin offsets and the photo 
                        // width to calculate positioning of the stamp.
                        imagecopy($image_resized, $stamp, imagesx($image_resized) - $sx - $marge_right, imagesy($image_resized) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

                }

                // Save to cache and display resized image
                switch($imgType) {
                        case 'jpeg':
                                imagejpeg($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'gif':
                                imagegif($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'png':
                                imagepng($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'gd':
                                imagegd($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'gd2':
                                imagegd2($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'wbmp':
                                imagewbmp($image_resized, $cachePath.$resizedFilename);
                                break;
                        case 'xbm':
                                imagexbm($image_resized, $cachePath.$resizedFilename);
                                break;
                }

                imagedestroy($image_resized);
                header('Location: '.$resizedFile);
                exit();
}
?>
