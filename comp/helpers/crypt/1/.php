<?php
class cCryptHelper
{
  public static $version = '1';

  public static function randomStringGenerate($aLength)
  {
    $lChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $lLastCharIndex = strlen($lChars) - 1;
    $lResult = '';

    for ($i = 0; $i < $aLength; $i++)
      $lResult .= $lChars[mt_rand(0, $lLastCharIndex)];
    return $lResult;
  }

  public static function dynamicRandomPartGenerate()
  {
    return self::randomStringGenerate(22);
  }

  public static function stringCrypt($aValue, $aStaticRandomPart,
    $aDynamicRandomPart)
  {
    eAssert(strlen($aDynamicRandomPart) == 22,
      'DynamicRandomPart length must be equal 22'.CRLF.
        '"'.$aDynamicRandomPart.'"');
    return crypt($aStaticRandomPart.$aValue, '$2y$10$'.$aDynamicRandomPart.'$');
  }

  public static function versionCheckAssert($aUsedVersion)
  {
    eAssert(self::$version == $aUsedVersion, 'Invalid Crypt Helper version used');
  }
}
?>