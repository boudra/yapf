<?php

namespace App\Utils;

class Image {

    private $image;
    private $file;

    public function __construct($file = null) {
        if(!empty($file)) {
            $this->load_file($file);
        }
    }

    public function load_file($file)
    {
        $ctx = stream_context_create(array('http'=>
            array(
                'timeout' => 5, 
            )
        ));
        $data = file_get_contents($file, false, $ctx);
        $this->image = \imagecreatefromstring($data);
        $this->file = $file;
    }

    public function save($file = null, $type = null, $compression = 80, $permissions = null)
    {
        if(empty($file)) $file = $this->file;

        switch($type) {
        case IMAGETYPE_JPEG:
            $this->image = imagejpeg($this->image, $file, $compression);
            break;
        case IMAGETYPE_GIF:
            $this->image = imagegif($this->image, $file);
            break;
        case IMAGETYPE_PNG:
            $this->image = imagepng($this->image, $file);
            break;
        default:
            throw new Exception("File format not supported.");
        }

        if($permissions !== null) {
            chmod($file, $permissions);
        }

    }

    public function width() {
        return imagesx($this->image);
    }

    public function height() {
        return imagesy($this->image);
    } 

    public function resize_height($height) {
        $ratio = $height / $this->height();
        $width = round($this->width() * $ratio);
        $this->resize($width,$height);    
    }

    public function resize_width($width) {
        $ratio = $width / $this->width();
        $height = round($this->height() * $ratio);
        $this->resize($width,$height); 
    }

    public function resize($width,$height) {
        $new_image = imagecreatetruecolor($width, $height);
        imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0,
            $width, $height,
            $this->width(), $this->height());
        $this->image = $new_image;
    } 

}

?>
