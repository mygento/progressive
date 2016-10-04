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

    protected static $_callbacks = array(
        IMAGETYPE_GIF  => array('output' => 'imagegif',  'create' => 'imagecreatefromgif'),
        IMAGETYPE_JPEG => array('output' => 'imagejpeg', 'create' => 'imagecreatefromjpeg'),
        IMAGETYPE_PNG  => array('output' => 'imagepng',  'create' => 'imagecreatefrompng'),
        IMAGETYPE_XBM  => array('output' => 'imagexbm',  'create' => 'imagecreatefromxbm'),
        IMAGETYPE_WBMP => array('output' => 'imagewbmp', 'create' => 'imagecreatefromxbm'),
    );


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
        if(!Mage::getStoreConfigFlag('progressive/general/enabled')) {
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

    /**
     * Obtain function name, basing on image type and callback type
     *
     * @param string $callbackType
     * @param int $fileType
     * @return string
     * @throws Exception
     */
    protected function _getCallback($callbackType, $fileType = null, $unsupportedText = 'Unsupported image format.')
    {
        if (null === $fileType) {
            $fileType = $this->_fileType;
        }
        if (empty(self::$_callbacks[$fileType])) {
            throw new Exception($unsupportedText);
        }
        if (empty(self::$_callbacks[$fileType][$callbackType])) {
            throw new Exception('Callback not found.');
        }
        return self::$_callbacks[$fileType][$callbackType];
    }

    protected function _getTransparency($imageResource, $fileType, &$isAlpha = false, &$isTrueColor = false)
    {
        $isAlpha     = false;
        $isTrueColor = false;
        // assume that transparency is supported by gif/png only
        if ((IMAGETYPE_GIF === $fileType) || (IMAGETYPE_PNG === $fileType)) {
            // check for specific transparent color
            $transparentIndex = imagecolortransparent($imageResource);
            if ($transparentIndex >= 0) {
                return $transparentIndex;
            }
            // assume that truecolor PNG has transparency
            elseif (IMAGETYPE_PNG === $fileType) {
                $isAlpha     = $this->checkAlpha($this->_fileName);
                $isTrueColor = true;
                return $transparentIndex; // -1
            }
        }
        if (IMAGETYPE_JPEG === $fileType) {
            $isTrueColor = true;
        }
        return false;
    }
}
