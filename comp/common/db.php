<?php
//!uses
//PDO
//blocks.php, profiler/.php

abstract class cDbBase
{
  const BAD_IDENTITY_ID = 0;
  //!SQL_ACTION
  const SQL_ACTION_DELETE = 'delete';
  const SQL_ACTION_INSERT = 'insert';
  const SQL_ACTION_UPDATE = 'update';

  protected $pdo = null;

  public function __construct($aServerPrefix, $aHostName, $aUserName, $aPassword, $aDbName)
  {
    $this->pdo = new PDO($aServerPrefix.':host='.$aHostName.';dbname='.$aDbName,
      $aUserName, $aPassword, array(
        1002/*PDO::MYSQL_ATTR_INIT_COMMAND*/ => 'SET NAMES utf8',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
  }

  public function beginTran()
  {
    eAssert($this->pdo->beginTransaction());
  }

  public function commitTran()
  {
    eAssert($this->pdo->commit());
  }

  public function execute($aSql, $aParams = array())
  {
    $this->executeInternal($aSql, $aParams);
  }

  public function executeField($aSql, $aFieldName, &$aField, $aParams = array(),
    $aFieldType = VAR_TYPE_STRING)
  {
    throw new Exception('Not implemented executeField '.$aSql);
  }

  private function executeInternal($aSql, array $aParams)
  {
    if (cPage::settingsGet()->isProfile)//!!delete using cPage
      cPFHelper::sqlProfile($this, $aSql, $aParams);

    $lStatement = $this->pdo->prepare($aSql);
    eAssert($lStatement->execute($aParams));
    return $lStatement;
  }

  public function executeLastInsertIdGet($aSql, $aParams = array(),
    $aFieldType = VAR_TYPE_INTEGER)
  {
    $this->execute($aSql, $aParams);
    return valueByType($this->pdo->lastInsertId(), $aFieldType);
  }

  public function executeRecord($aSql, &$aRecord, $aParams = array(),
    $aFieldTypes = array())
  {
    eAssert(count($aRecord) == 0, 'Record is not empty');

    $lRecordset = array();
    $this->executeRecordset($aSql, $lRecordset, $aParams, $aFieldTypes);
    $lRecordCount = count($lRecordset);

    if ($lRecordCount == 1)
    {
      $aRecord = $lRecordset[0];
      return true;
    }
    else
    if ($lRecordCount == 0)
      return false;
    else
      throw new Exception('Query return more then one record'.' '.$aSql);
  }

  public function executeRecordset($aSql, &$aRecordset, $aParams = array(),
    $aFieldTypes = array())
  {
    eAssert(count($aRecordset) == 0, 'Recordset is not empty');

    $lStatement = $this->executeInternal($aSql, $aParams);
    $lStatement->setFetchMode(PDO::FETCH_ASSOC);

    while ($lRecord = $lStatement->fetch())
    {
      foreach ($aFieldTypes as $lFieldName => $lFieldType)
        $lRecord[$lFieldName] = valueByType($lRecord[$lFieldName], $lFieldType);

      $aRecordset[] = $lRecord;
    }

    return (count($aRecordset) > 0);
  }

  public function executeValue($aSql, $aFieldName, &$aValue,
    $aParams = array(), $aFieldType = VAR_TYPE_STRING)
  {
    $lResult = $this->executeRecord($aSql, $lRecord, $aParams,
      array($aFieldName => $aFieldType));

    if ($lResult)
    {
      if (!array_key_exists($aFieldName, $lRecord))
        throw new Exception('Can not get field "'. $aFieldName .'" '.$aSql);

      $aValue = $lRecord[$aFieldName];
    }

    return $lResult;
  }

  public function rollbackTran()
  {
    eAssert($this->pdo->rollBack());
  }

  abstract public function stringToDate($aValue);

  public static function sqlDeleteBuild($aTableName, $aWhereParams)
  {
    return 'DELETE FROM '.$aTableName.
      self::sqlWhereBuild($aWhereParams);
  }

  public static function sqlInsertBuild($aTableName, $aParams)
  {
    eAssert(count($aParams) > 0);
    $lFieldNames = array_keys($aParams);

    return 'INSERT INTO '.$aTableName.CRLF.
      '('.CRLF.
      '  '.implode(','.CRLF.'  ', $lFieldNames).CRLF.
      ')'.CRLF.
      'VALUES'.CRLF.
      '('.CRLF.
      '  :'.implode(','.CRLF.'  :', $lFieldNames).CRLF.
      ')';
  }

  public static function sqlSelectBuild($aTableName, $aFieldNames, $aWhereParams)
  {
    return 'SELECT '.
      '  '.implode(','.CRLF.'  ', $aFieldNames).CRLF.
      'FROM '.$aTableName.
      self::sqlWhereBuild($aWhereParams);
  }

  public static function sqlUpdateBuild($aTableName, &$aParams, $aWhereParams)
  {
    $lCount = count($aParams);
    eAssert($lCount > 0);
    $lFieldNames = array_keys($aParams);
    $lValues = array();

    for ($i = 0; $i < $lCount; $i++)
    {
      $lFieldName = $lFieldNames[$i];
      $lValues[] = $lFieldName.' = :'.$lFieldName;
    }

    $lSql = 'UPDATE '.$aTableName.CRLF.
      'SET'.CRLF.
      '  '.implode(','.CRLF.'  ', $lValues).CRLF.
      self::sqlWhereBuild($aWhereParams);

    $aParams = array_merge($aParams, $aWhereParams);

    return $lSql;
  }

  public static function sqlWhereBuild($aParams)
  {
    $lCount = count($aParams);

    if (!$lCount)
      return '';

    $lFieldNames = array_keys($aParams);
    $lConditions = array();

    for($i = 0; $i < $lCount; $i++)
    {
      $lFieldName = $lFieldNames[$i];
      $lConditions[] = $lFieldName.' = :'.$lFieldName;
    }

    return CRLF.
      'WHERE'.CRLF.
      '  '.implode(CRLF.'  AND ', $lConditions);
  }
}

class cDbMySql extends cDbBase
{
  public function __construct($aHostName, $aUserName, $aPassword, $aDbName)
  {
    parent::__construct('mysql', $aHostName, $aUserName, $aPassword, $aDbName);
  }

  public function stringToDate($aValue)
  {
    $elements = explode('/', $aValue);
    eAssert(count($elements) == 3, 'Can not convert "'.$aValue.
      '" to MySql date');

    return $elements[2].'-'.$elements[1].'-'.$elements[0];
  }
}

class cDbPGSql extends cDbBase
{
  public function __construct($aHostName, $aUserName, $aPassword, $aDbName)
  {
    parent::__construct('pgsql', $aHostName, $aUserName, $aPassword, $aDbName);
  }

  public function stringToDate($aValue)
  {
    throw new Exception('Not implemented');
  }
}
?>