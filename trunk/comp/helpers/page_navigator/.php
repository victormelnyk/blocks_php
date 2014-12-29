<?
class PageNavigatorHelper
{
  //!NAVIGATOR_ITEM_TYPE
  const NIT_FIRST = 1;
  const NIT_LAST  = 2;
  const NIT_PREV  = 3;
  const NIT_NEXT  = 4;
  const NIT_PAGE  = 5;

  private static function navigatorItemAdd(&$aResultArry, $aNavigatorItemType,
    $aPageNo, $aCurrentPageNo,
    $aPageNoParamName, $aLimitParamName, $aLimitParamValue, $aUrlParams)
  {

    $aResultArry[] = array(
      'isActive'          => ($aPageNo == $aCurrentPageNo),
      'navigatorItemType' => $aNavigatorItemType,
      'pageNo'            => $aPageNo,
      'urlParams'         => $aUrlParams.($aUrlParams ? '&' : '').
         $aLimitParamName.'='.$aLimitParamValue.'&'.$aPageNoParamName.'='.
         $aPageNo
    );
  }

  public static function navigatorCalculate($aRecordCount, $aLimitValue,
    $aCurrentPageNo, $aPageNoParamName, $aLimitParamName, $aLimitParamValue,
    $aUrlParams, $aPageCount)
  {
    $lResult = array();

    $lMaxPageNo = (int)ceil($aRecordCount / $aLimitValue);

    self::navigatorItemAdd($lResult, self::NIT_FIRST, 1, $aCurrentPageNo,
      $aPageNoParamName, $aLimitParamName, $aLimitParamValue, $aUrlParams);
    self::navigatorItemAdd($lResult, self::NIT_PREV,
      ($aCurrentPageNo > 1 ? $aCurrentPageNo - 1 : 1), $aCurrentPageNo,
      $aPageNoParamName, $aLimitParamName, $aLimitParamValue, $aUrlParams);

    for ($i = -$aPageCount; $i <= $aPageCount; $i++)
    {
      $lPageNo = $aCurrentPageNo + $i;
      if (($lPageNo >= 1) && ($lPageNo <= $lMaxPageNo))
        self::navigatorItemAdd($lResult, self::NIT_PAGE, $lPageNo,
          $aCurrentPageNo, $aPageNoParamName, $aLimitParamName,
          $aLimitParamValue, $aUrlParams);
    }

    self::navigatorItemAdd($lResult, self::NIT_NEXT,
      ($aCurrentPageNo < $lMaxPageNo ? $aCurrentPageNo + 1 : $lMaxPageNo),
      $aCurrentPageNo, $aPageNoParamName, $aLimitParamName, $aLimitParamValue,
      $aUrlParams);
    self::navigatorItemAdd($lResult, self::NIT_LAST, $lMaxPageNo,
      $aCurrentPageNo, $aPageNoParamName, $aLimitParamName, $aLimitParamValue,
      $aUrlParams);

    return $lResult;
  }
}
?>