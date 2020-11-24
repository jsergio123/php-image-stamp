<?php
/////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////
///                                                                                       ///
///  URL EXAMPLE: ../image.php?file=picture.jpg&width=500&crop=true                       ///
///  Set folder permissions for $photoPath to 0777                                        ///
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
$resizedFilename = $newFile;
if (isset($_GET['crop']) && filter_var($_GET['crop'], FILTER_VALIDATE_BOOLEAN)) {
        $cropImg = true;
        $resizedFilename = 'C_'.$resizedFilename;
}
if (isset($_GET['width']) && is_numeric($_GET['width']) && $_GET['width'] > 0) {
        $widthSet = true;
        $newWidth = intval($_GET['width']);
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
                list($width, $height) = getimagesize($file);

                // Set a new width, and calculate new height
                if ($widthSet === false) { $newWidth = $width; }
                $newHeight = ($cropImg === true) ? $newWidth * 0.75 : $height * ($newWidth/$width);
                $newHeight - ceil($newHeight);

                // Resample
                $image_resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($cropImg === true) {		
			if ($width >= $height) {
				$intraSourceHeight = $height * ($newWidth/$width);
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
                                        $intraSourceWidth = $width * ($newHeight/$height);
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

                imagedestroy($image);
                if ($newWidth >= $minSize && $stampImg === true) {
                        // Load the stamp and the photo to apply the watermark to
                        $stampWidth = ceil($newWidth*$stampSize);
                        if (is_file($cachePath.'stamp_w'.$stampWidth.'.png') && is_readable($cachePath.'stamp_w'.$stampWidth.'.png')) {
                                $resizedStamp = imagecreatefrompng($cachePath.'stamp_w'.$stampWidth.'.png');
                                $stampHeight = imagesy($resizedStamp);
                        }
                                else {
                                        list($sx, $sy) = getimagesize($stampFile);
                                        $stampHeight = ceil($sy * ($stampWidth/$sx));
                                        $stamp = imagecreatefrompng($stampFile);
                                        $resizedStamp = imagecreatetruecolor($stampWidth, $stampHeight);
                                        imagealphablending($resizedStamp, false);
                                        imagesavealpha($resizedStamp, true);
                                        imagecopyresampled($resizedStamp, $stamp, 0, 0, 0, 0, $stampWidth, $stampHeight, $sx, $sy);
                                        imagedestroy($stamp);
                                        imagepng($resizedStamp, $cachePath.'stamp_w'.$stampWidth.'.png');
                                }

                        // Set the margins for the stamp and get the height/width of the stamp image
                        $marge_right = 10;
                        $marge_bottom = 10;

                        // Copy the stamp image onto our photo using the margin offsets and the photo 
                        // width to calculate positioning of the stamp.
                        imagecopy($image_resized, $resizedStamp, $newWidth - $stampWidth - $marge_right, $newHeight - $stampHeight - $marge_bottom, 0, 0, $stampWidth, $stampHeight);
                        imagedestroy($resizedStamp);

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
