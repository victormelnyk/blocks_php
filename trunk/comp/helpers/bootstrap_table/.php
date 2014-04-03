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

  public static function gridBuild($aRecordset, $aParams, $aFieldNames = array(),
    $aFieldSizes = array())//!! grid -> table
  {
    if (!count($aRecordset) && !count($aFieldNames))
      return '';

    return self::gridBuildByRows(
      self::recordsetToRows($aRecordset),
      (count($aFieldNames) ? $aFieldNames
        : self::recordsetFieldNamesGet($aRecordset, $aParams)),
      $aFieldSizes
    );
  }

  public static function gridBuildByRows($aRows, $aFieldNames,
    $aFieldSizes = array())
  {
    return self::TABLE_HEADER.
      self::gridHeaderBuild($aFieldNames, $aFieldSizes).
      self::gridRowsBuild($aRows).
      self::TABLE_FOTER;
  }

  public static function gridHeaderBuild($aFieldNames, $aFieldSizes)
  {
    $lResult = '';
    $lIsAddSize = count($aFieldSizes) > 0;

    for ($i = 0, $l = count($aFieldNames); $i < $l; $i++)
      $lResult .= '<td'.
        ($lIsAddSize && $aFieldSizes[$i] ? ' width="'.$aFieldSizes[$i].'"' : '').
        '><strong>'.$aFieldNames[$i].'</strong></td>';

    return '<thead><tr>'.$lResult.'</tr></thead>';
  }

  public static function gridRowsBuild($aRows)
  {
    return '<tbody>'.implode('', $aRows).'</tbody>';
  }

  public static function recordsetAddEditButtons(&$aRecordset, $aParams,
    $aUrlParams, $IsAddDeleteButton)
  {
    $lCallbackUrl = urlencode($aParams['ViewPageUrl'].'?'.$aUrlParams);

    for ($i = 0, $l = count($aRecordset); $i < $l; $i++)
    {
      $aRecordset[$i]['edit'] =
        '<a class="btn btn-success btn-sm" href="'.
          $aParams['EditPageUrl'].'?'.$aRecordset[$i]['key_params'].
          '&callback_url='.$lCallbackUrl.'">Edit</a>';
      if ($IsAddDeleteButton)
        $aRecordset[$i]['delete'] =
          '<a class="btn btn-danger btn-sm" href="'.
            $aParams['EditPageUrl'].'?edit_mode=delete&'.
            $aRecordset[$i]['key_params'].'&callback_url='.$lCallbackUrl.
            '">Delete</a>';
    }
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