<?
//!PT = PerformanceCounter
class cPCHelper
{
  private static $items = array();

  private static function byNameGet($aName)
  {
    eAssert(isset(self::$items[$aName]), 'Counter by name "'.$aName.
      '" is undefined');
    return  self::$items[$aName];
  }

  private static function byNameSet($aName, $aRecord)
  {
    self::$items[$aName] = $aRecord;
  }

  public static function start($aName)
  {
    if (isset(self::$items[$aName]))
    {
      $lRecord = self::$items[$aName];
      $lRecord['count']++;
    }
    else
      $lRecord = array('start' => 0, 'time' => 0, 'count' => 1);

    $lRecord['start'] = microtime(true);

    self::byNameSet($aName, $lRecord);
  }

  public static function stop($aName)
  {
    $lRecord = self::byNameGet($aName);
    eAssert($lRecord['start'] > 0, 'Counter "'.$aName.'" not started');
    $lRecord['time'] += microtime(true) - $lRecord['start'];
    $lRecord['start'] = 0;
    self::byNameSet($aName, $lRecord);
  }

  public static function stopAll()
  {
    foreach (self::$items as $lKey => $lValue)
      self::stop($lKey);
  }

  public static function infoGet($aName)
  {
    $lRecord = self::byNameGet($aName);
    return '"'.$aName.'": time = '.number_format($lRecord['time'], 3, '.', '').
      ' count = '.$lRecord['count'].
      (($lRecord['start'] == 0) ? '' : ' (Not stoped)');
  }

  public static function infoGetAll($aDelimiter)
  {
    $lResult = '';

    foreach (self::$items as $lKey => $lValue)
      $lResult .= self::infoGet($lKey).$aDelimiter;

    return $lResult;
  }

  public static function saveToFile($aFlp)
  {
    stringToFile(self::infoGetAll(CRLF), $aFlp);
  }

  public static function saveToHtml()
  {
    p(self::infoGetAll('<br>'));
  }

  public static function saveToString()
  {
    return self::infoGetAll('; ');
  }
}
?>