<?php
//!uses
//PDO

abstract class DbBase {
  const BAD_IDENTITY_ID = 0;
  //!SQL_ACTION
  const SQL_ACTION_DELETE = 'delete';
  const SQL_ACTION_INSERT = 'insert';
  const SQL_ACTION_UPDATE = 'update';

  protected $pdo = null;

  public function __construct($serverPrefix, $hostName, $userName, $password,
    $dbName) {
    $this->pdo = new PDO(
      $serverPrefix.':host='.$hostName.';dbname='.$dbName, $userName, $password,
      array(
        1002/*PDO::MYSQL_ATTR_INIT_COMMAND*/ => 'SET NAMES utf8',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      )
    );
  }

  public function beginTran() {
    eAssert($this->pdo->beginTransaction());
  }

  public static function buildDeleteSql($tableName, $whereParams) {
    return 'DELETE FROM ' . $tableName .
      self::buildWhereSql($whereParams);
  }

  public static function buildInsertSql($tableName, $params) {
    eAssert(count($params) > 0);
    $fieldNames = array_keys($params);

    return 'INSERT INTO ' . $tableName . CRLF .
      '(' . CRLF .
      '  ' . implode(',' . CRLF . '  ', $fieldNames) . CRLF .
      ')' . CRLF .
      'VALUES' . CRLF .
      '(' . CRLF .
      '  :' . implode(',' . CRLF .'  :', $fieldNames) . CRLF .
      ')';
  }

  public static function buildSelectSql($tableName, $fieldNames, $whereParams) {
    return 'SELECT' . CRLF .
      '  ' . implode(',' . CRLF . '  ', $fieldNames) . CRLF .
      'FROM ' . $tableName .
      self::buildWhereSql($whereParams);
  }

  public static function buildUpdateSql($tableName, &$params, $whereParams) {
    $count = count($params);
    eAssert($count > 0);
    $fieldNames = array_keys($params);
    $values = array();

    for ($i = 0; $i < $count; $i++) {
      $fieldName = $fieldNames[$i];
      $values[] = $fieldName . ' = :' . $fieldName;
    }

    $sql = 'UPDATE ' . $tableName . ' SET' . CRLF .
      '  ' . implode(',' . CRLF . '  ', $values) .
      self::buildWhereSql($whereParams);

    $params = array_merge($params, $whereParams);

    return $sql;
  }

  public static function buildWhereSql($params) {
    $count = count($params);

    if (!$count) {
      return '';
    }

    $fieldNames = array_keys($params);
    $conditions = array();

    for($i = 0; $i < $count; $i++) {
      $fieldName = $fieldNames[$i];
      $conditions[] = $fieldName . ' = :' . $fieldName;
    }

    return CRLF.
      'WHERE'.CRLF.
      '  '.implode(CRLF.'  AND ', $conditions);
  }

  public function commitTran() {
    eAssert($this->pdo->commit());
  }

  public function execute($sql, array $params = array()) {
    $this->executeInternal($sql, $params);
  }

  public function executeField($sql, $fieldName, array &$field = array(),
    array $params = array(), $fieldType = V_STRING) {
    throw new Exception('Not implemented executeField ' . $sql);
  }

  private function executeInternal($sql, array $params) {
    //!!!if (cPage::settingsGet()->isProfile)//!!delete using cPage
    //  cPFHelper::sqlProfile($this, $sql, $params);

    $statement = $this->pdo->prepare($sql);
    eAssert($statement->execute($params));
    return $statement;
  }

  public function executeRecord($sql, array &$record, array $params = array(),
    array $fieldTypes = array()) {
    eAssert(count($record) == 0, 'Record is not empty');

    $recordset = array();
    $this->executeRecordset($sql, $recordset, $params, $fieldTypes);
    $recordCount = count($recordset);

    if ($recordCount === 1) {
      $record = $recordset[0];
      return true;
    } else if ($recordCount === 0) {
      return false;
    } else {
      throw new Exception('Query return more then one record ' . $sql);
    }
  }

  public function executeRecordset($sql, array &$recordset,
    array $params = array(), array $fieldTypes = array()) {
    eAssert(count($recordset) === 0, 'Recordset is not empty');

    $statement = $this->executeInternal($sql, $params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);

    while ($record = $statement->fetch()) {
      $recordset[] = $record;
    }

    $recordCount = count($recordset);

    foreach ($fieldTypes as $fieldName => $fieldType) {
      for ($i = 0; $i < $recordCount; $i++) {
        $recordset[$i][$fieldName] = valueByType($recordset[$i][$fieldName],
          $fieldType);
      }
    }

    return ($recordCount > 0);
  }

  public function executeValue($sql, $fieldName, &$value,
    array $params = array(), $fieldType = V_STRING) {
    $record = array();
    $result = $this->executeRecord($sql, $record, $params,
      array($fieldName => $fieldType));

    if ($result) {
      if (!array_key_exists($fieldName, $record)) {
        throw new Exception('Can not get field "'. $fieldName .'" '.$sql);
      }

      $value = $record[$fieldName];
    }

    return $result;
  }

  public function rollbackTran() {
    eAssert($this->pdo->rollBack());
  }
}

class DbMySql extends DbBase {
  public function __construct($hostName, $userName, $password, $dbName) {
    parent::__construct('mysql', $hostName, $userName, $password, $dbName);
  }
}

class DbPGSql extends DbBase {
  public function __construct($hostName, $userName, $password, $dbName) {
    parent::__construct('pgsql', $hostName, $userName, $password, $dbName);
  }
}
?>