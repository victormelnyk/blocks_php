<?
class cRecordsetGridAdaptationHelper
{
  public static function process(&$aRecordset, $aColCount)
  {
    $lResult = array();
    $lColIndex = 0;
    $lRowIndex = 0;

    for ($i = 0, $l = count($aRecordset); $i < $l; $i++)
    {
      $lRecord = $aRecordset[$i];
      $lIsFirstCol = false;
      $lIsLastCol = false;

      if ($lColIndex == $aColCount)
      {
        $lColIndex = 0;
        $lRowIndex++;

        $lIsFirstCol = true;
      }
      else
      if ($lColIndex == 0)
        $lIsFirstCol = true;
      else
      if (($aColCount - $lColIndex) == 1)
        $lIsLastCol = true;

      $lRecord['index']        = $i;
      $lRecord['col']          = $lColIndex;
      $lRecord['row']          = $lRowIndex;
      $lRecord['is_first']     = ($i == 0);
      $lRecord['is_last']      = ($i == ($l - 1));
      $lRecord['is_first_col'] = $lIsFirstCol;
      $lRecord['is_last_col']  = $lIsLastCol;

      $aRecordset[$i] = $lRecord;
      $lColIndex++;
    }
  }
}
?>