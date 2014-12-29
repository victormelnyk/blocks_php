<?
class cGdImageHelper
{
  public static function resize($aSrcFlp, $ADestFlp, $aDestWidth, $aDestHeight)
  {
    $lTypes = array('', 'gif', 'jpeg', 'png');
    list($lSrcWidth, $lSrcHeight, $lType) = getimagesize($aSrcFlp);
    $lTypeS = $lTypes[$lType];
    if (!$lTypeS)
      throw new Exception('Not valid image type: "'.$lTypeS.'" for image: "'.
        $aSrcFlp.'"');

    $lXRatio = $aDestWidth / $lSrcWidth;
    $lYRatio = $aDestHeight / $lSrcHeight;
    $lRatio = min($lXRatio, $lYRatio);
    $lIsUseXRatio = ($lXRatio == $lRatio);

    $aDestWidth  = $lIsUseXRatio  ? $aDestWidth  : floor($lSrcWidth * $lRatio);
    $aDestHeight = !$lIsUseXRatio ? $aDestHeight : floor($lSrcHeight * $lRatio);

    $lCreateFunc = 'imagecreatefrom'.$lTypeS;
    $lSrcImage = $lCreateFunc($aSrcFlp);
    $lDestImage = imagecreatetruecolor($aDestWidth, $aDestHeight);
    imagecopyresampled($lDestImage, $lSrcImage, 0, 0, 0, 0, $aDestWidth,
      $aDestHeight, $lSrcWidth, $lSrcHeight);
    $lSaveFunc = 'image'.$lTypeS;
    return $lSaveFunc($lDestImage, $ADestFlp);
  }
}
?>