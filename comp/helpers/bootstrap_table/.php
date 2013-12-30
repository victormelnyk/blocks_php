<?php
class cBootstrapTableHelper
{
  const TABLE_HEADER = '<table class="table table-bordered table-striped table-hover table-condensed">';
  const TABLE_FOTER = '</table>';

  public static function stateCheckBoxBuild($aIsChecked, $aTitle)
  {
    return '<input type="checkbox" title="'.$aTitle.'" disabled="disabled" '.
      ($aIsChecked ? 'checked="checked" ' : '').'>';
  }

  public static function gridBuild($aRecordset, $aParams)//!! grid -> table
  {
    if (count($aRecordset) == 0)
      return '';

    return self::gridBuildByRows(self::recordsetToRows($aRecordset),
      self::recordsetFieldNamesGet($aRecordset, $aParams));
  }

  public static function gridBuildByRows($aRows, $aFieldNames)
  {
    return self::TABLE_HEADER.
      self::gridHeaderBuild($aFieldNames).
      self::gridRowsBuild($aRows).
      self::TABLE_FOTER;
  }

  public static function gridHeaderBuild($aFieldNames)
  {
    $lResult = '';

    for ($i = 0, $l = count($aFieldNames); $i < $l; $i++)
      $lResult .= '<td><strong>'.$aFieldNames[$i].'</strong></td>';

    return '<thead><tr>'.$lResult.'</tr></thead>';
  }

  public static function gridRowsBuild($aRows)
  {
    return '<tbody>'.implode('', $aRows).'</tbody>';
  }

  public static function recordsetFieldNamesGet($aRecordset, $aParams)
  {
    $lResult = array();

    if (count($aRecordset) > 0)
      foreach ($aRecordset[0] as $lName => $lValue)
        if ($lName != 'key_params')
          $lResult[] = isset($aParams[$lName]) ? $aParams[$lName] : $lName;
    return $lResult;
  }

  public static function recordsetToRows($aRecordset)
  {
    $lResult = array();

    for ($i = 0, $l = count($aRecordset); $i < $l; $i++)
    {
      $lRow = '<tr>';

      $lRecord = $aRecordset[$i];
      foreach ($lRecord as $lName => $lCell)
        if ($lName != 'key_params')
          $lRow .= '<td>'.$lCell.'</td>';

      $lRow .= '</tr>';

      $lResult[] = $lRow;
    }

    return $lResult;
  }
}
?>