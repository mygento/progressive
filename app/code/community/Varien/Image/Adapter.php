<?php
class Varien_Image_Adapter
{
    const ADAPTER_GD    = 'GD';
    const ADAPTER_GD2   = 'GD2';
    const ADAPTER_IM    = 'IMAGEMAGIC';
    const ADAPTER_IME   = 'IMAGEMAGIC_EXTERNAL';
    public static function factory($adapter)
    {
        switch( $adapter ) {
            case self::ADAPTER_GD:
                return new Varien_Image_Adapter_Gd();
                break;
            case self::ADAPTER_GD2:
                return new Mygento_Progressive_Model_Progressive();
                break;
            case self::ADAPTER_IM:
                return new Varien_Image_Adapter_Imagemagic();
                break;
            case self::ADAPTER_IME:
                return new Varien_Image_Adapter_ImagemagicExternal();
                break;
            default:
                throw new Exception('Invalid adapter selected.');
                break;
        }
    }
}
