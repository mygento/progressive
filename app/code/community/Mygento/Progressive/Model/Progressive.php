<?php

/**
 *
 * @category   Mygento
 * @package    Mygento_Progressive
 */
class Mygento_Progressive_Model_Progressive extends Varien_Image_Adapter_Gd2
{
    /**
    * Whether image was resized or not
    *
    * @var bool
    */
    protected $_resized = false;


    /**
     * Change the image size
     *
     * @param int $frameWidth
     * @param int $frameHeight
     */
    public function resize($frameWidth = null, $frameHeight = null)
    {
      parent::resize($frameWidth, $frameHeight);
      $this->_resized = true;
    }

    public function save($destination=null, $newName=null)
    {
        if(!Mage::getStoreFlag('progressive/general/enabled')) {
          return parent::save($destination, $newName);
        }
        $fileName = ( !isset($destination) ) ? $this->_fileName : $destination;
        if( isset($destination) && isset($newName) ) {
            $fileName = $destination . "/" . $newName;
        } elseif( isset($destination) && !isset($newName) ) {
            $info = pathinfo($destination);
            $fileName = $destination;
            $destination = $info['dirname'];
        } elseif( !isset($destination) && isset($newName) ) {
            $fileName = $this->_fileSrcPath . "/" . $newName;
        } else {
            $fileName = $this->_fileSrcPath . $this->_fileSrcName;
        }
        $destinationDir = ( isset($destination) ) ? $destination : $this->_fileSrcPath;
        if( !is_writable($destinationDir) ) {
            try {
                $io = new Varien_Io_File();
                $io->mkdir($destination);
            } catch (Exception $e) {
                throw new Exception("Unable to write file into directory '{$destinationDir}'. Access forbidden.");
            }
        }
        if (!$this->_resized) {
            // keep alpha transparency
            $isAlpha     = false;
            $isTrueColor = false;
            $this->_getTransparency($this->_imageHandler, $this->_fileType, $isAlpha, $isTrueColor);
            if ($isAlpha) {
                if ($isTrueColor) {
                    $newImage = imagecreatetruecolor($this->_imageSrcWidth, $this->_imageSrcHeight);
                } else {
                    $newImage = imagecreate($this->_imageSrcWidth, $this->_imageSrcHeight);
                }
                $this->_fillBackgroundColor($newImage);
                imagecopy(
                    $newImage,
                    $this->_imageHandler,
                    0, 0,
                    0, 0,
                    $this->_imageSrcWidth, $this->_imageSrcHeight
                );
                $this->_imageHandler = $newImage;
            }
        }
        $functionParameters = array();
        $functionParameters[] = $this->_imageHandler;
        $functionParameters[] = $fileName;

        // Changed
        if ($this->_fileType == IMAGETYPE_JPEG)
        {
            imageinterlace($this->_imageHandler, 1);
        }

        // set quality param for JPG file type
        if (!is_null($this->quality()) && $this->_fileType == IMAGETYPE_JPEG)
        {
            $functionParameters[] = $this->quality();
        }
        // set quality param for PNG file type
        if (!is_null($this->quality()) && $this->_fileType == IMAGETYPE_PNG)
        {
            $quality = round(($this->quality() / 100) * 10);
            if ($quality < 1) {
                $quality = 1;
            } elseif ($quality > 10) {
                $quality = 10;
            }
            $quality = 10 - $quality;
            $functionParameters[] = $quality;
        }
        call_user_func_array($this->_getCallback('output'), $functionParameters);
    }
}
