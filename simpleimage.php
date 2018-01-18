<?php

class SimpleImage {

	public $image;
	public $ani_image;
	public $image_type;
	public $force = false;
	public $is_animated = false;
	public $supported = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG);

	public function __construct($filename = null) {

		if(!empty($filename)) {
			$this->load($filename);
		}

	}

	public function load($filename) {

		$image_info = @getimagesize($filename);

		if(is_array($image_info)) {

			$this->image_type = $image_info[2];

			if(in_array($this->image_type, $this->supported)) {

				if($this->image_type == IMAGETYPE_JPEG) {
					$this->image = imagecreatefromjpeg($filename);
				}
				elseif($this->image_type == IMAGETYPE_GIF) {

					if(extension_loaded('imagick') && $this->isAnimatedGif($filename)) {

						$this->ani_image = new Imagick($filename);
						$this->is_animated = true;

					} else {
						$this->image = imagecreatefromgif($filename);
					}

				}
				elseif($this->image_type == IMAGETYPE_PNG) {
					$this->image = imagecreatefrompng($filename);
				}

				return true;

			}

		}

		return false;

	}

	public function save_with_new_imagetype($filename, $image_type=IMAGETYPE_JPEG, $compression=90, $permissions=null) {

		if($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image, $filename, $compression);
		}
		elseif($image_type == IMAGETYPE_GIF) {

			if($this->is_animated) {
				$this->ani_image->writeImages($filename, true);
			} else {
				imagegif($this->image, $filename);
			}

		}
		elseif($image_type == IMAGETYPE_PNG) {
			imagepng($this->image, $filename);
		}

		if($permissions != null) {
			chmod($filename, $permissions);
		}

		return filesize($filename);

	}

	public function save($filename, $compression=90, $permissions=null) {

		if($this->image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image, $filename, $compression);
		}
		elseif($this->image_type == IMAGETYPE_GIF) {

			if($this->is_animated) {
				$this->ani_image->writeImages($filename, true);
			} else {
				imagegif($this->image, $filename);
			}

		}
		elseif($this->image_type == IMAGETYPE_PNG) {
			imagepng($this->image, $filename);
		}

		if($permissions != null) {
			chmod($filename, $permissions);
		}

		return filesize($filename);

	}

	public function output($image_type=IMAGETYPE_JPEG) {

		if($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image);
		}
		elseif($image_type == IMAGETYPE_GIF) {

			if($this->is_animated) {
				echo $this->ani_image->getImagesBlob();
			} else {
				imagegif($this->image);
			}

		}
		elseif($image_type == IMAGETYPE_PNG) {
			imagepng($this->image);
		}

	}

	public function getWidth() {

		if($this->is_animated) {
			$width = $this->ani_image->getImageWidth();
		} else {
			$width = imagesx($this->image);
		}

		return $width;

	}

	public function getHeight() {

		if($this->is_animated) {
			$height = $this->ani_image->getImageHeight();
		} else {
			$height = imagesy($this->image);
		}

		return $height;

	}

	public function resizeToHeight($height, $upscale = false) {

		if($height > $this->getHeight() && $upscale == false) {
			return array('w' => $this->getWidth(), 'h' => $this->getHeight());
		}

		$ratio = $height / $this->getHeight();
		$width = round($this->getWidth() * $ratio);

		if($this->is_animated) {
			$this->resizeGif($width, $height);
		} else {
			$this->resize($width, $height);
		}

		return array('w' => $width, 'h' => $height);

	}

	public function resizeToWidth($width, $upscale = false) {

		if($width > $this->getWidth() && $upscale == false) {
			return array('w' => $this->getWidth(), 'h' => $this->getHeight());
		}

		$ratio = $width / $this->getWidth();
		$height = round($this->getHeight() * $ratio);

		if($this->is_animated) {
			$this->resizeGif($width, $height);
		} else {
			$this->resize($width, $height);
		}

		return array('w' => $width, 'h' => $height);

	}

	public function resizeToFit($maxwidth, $maxheight) {

		if($this->getWidth() > $this->getHeight()) {
			$this->resizeToWidth($maxwidth);
		} else {
			$this->resizeToHeight($maxheight);
		}

	}

	public function scale($scale) {

		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;

		if($this->is_animated) {
			$this->resizeGif($width, $height);
		} else {
			$this->resize($width, $height);
		}

	}

	public function crop($x, $y, $w, $h) {

		if($this->is_animated) {
			$this->cropGif($x, $y, $w, $h);
			exit;
		}

		$new_image = imagecreatetruecolor($w, $h);
		imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $w, $h, $w, $h);

		$this->image = $new_image;

	}

    public function resize($width, $height) {

		if($this->is_animated) {
			$this->resizeGif($width, $height);
			exit;
		}

		if(!$this->force) {

			if($width > $this->getWidth() && $height > $this->getHeight()) {

				$width = $this->getWidth();
				$height = $this->getHeight();

			}

		}

		$new_image = imagecreatetruecolor($width, $height);

        if($this->image_type == IMAGETYPE_GIF || $this->image_type == IMAGETYPE_PNG) {

			$current_transparent = imagecolortransparent($this->image);

			if($current_transparent != -1) {

				$transparent_color = imagecolorsforindex($this->image, $current_transparent);
				$current_transparent = imagecolorallocate($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagefill($new_image, 0, 0, $current_transparent);
				imagecolortransparent($new_image, $current_transparent);

            }
			elseif($this->image_type == IMAGETYPE_PNG) {

				imagealphablending($new_image, false);
				$color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
				imagefill($new_image, 0, 0, $color);
				imagesavealpha($new_image, true);

			}

		}

		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

		$this->image = $new_image;

	}

	public function resizeGif($width, $height) {

		if(!$this->is_animated) {
			$this->resize($width, $height);
			exit;
		}

		if(!$this->force) {

			if($width > $this->getWidth() && $height > $this->getHeight()) {

				$width = $this->getWidth();
				$height = $this->getHeight();

			}

		}

		$image = $this->ani_image;

		foreach($image AS $frame) {
			$frame->setImageBackgroundColor('none');
		}

		$image = $image->coalesceImages();

		foreach($image AS $frame) {

			$frame->thumbnailImage($width, $height);
			$frame->setImagePage($width, $height, 0, 0);

		}

		$image = $image->deconstructImages();

		$this->ani_image = $image;

	}

	public function cropGif($x, $y, $w, $h) {

		if(!$this->is_animated) {
			$this->crop($x, $y, $w, $h);
			exit;
		}

		$image = $this->ani_image;

		foreach($image AS $frame) {
			$frame->setImageBackgroundColor('none');
		}

		$image = $image->coalesceImages();

		foreach($image AS $frame) {

			$frame->cropImage($w, $h, $x, $y);
			$frame->thumbnailImage($w, $h);
			$frame->setImagePage($w, $h, 0, 0);

		}

		$image = $image->deconstructImages();

		$this->ani_image = $image;

	}

	public function isAnimatedGif($image) {
		return (bool)preg_match('#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', @file_get_contents($image));
	}

}

?>