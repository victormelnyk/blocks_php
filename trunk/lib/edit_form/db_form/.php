<?php
cPage::moduleAdd('blocks/lib/edit_form/form/.php');

//!EDIT_MODE
define('EDIT_MODE_UNDEFINED', 'undefined');
define('EDIT_MODE_SAVE',      'save');
define('EDIT_MODE_DELETE',    'delete');

class cDbFormOption extends cFormOption
{
  private $posibleValuesSql = '';

  public $sqlField = '';

  public $isKey = false;

  public function __construct($aList, $aXmlNode)
  {
    parent::__construct($aList, $aXmlNode);

    $this->sqlField = $aXmlNode->attrs->nextGetByN('SqlField')->getS();

    if ($aXmlNode->attrs->nextGetCheckByN('IsKey', $lAttr))
      $this->isKey = $lAttr->getB();

    if ($this->inputType == INPUT_TYPE_SELECT)
      $this->posibleValuesSql =
        $aXmlNode->nodes->nextGetByN('PosibleValuesSql')->getS();
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);
    $aArray['isKey'] = $this->isKey;
  }

  public function valueLoadFromDb($aDb, $aCurrentValue)
  {
    eAssert($this->posibleValuesSql);

    $lRecordset = array();
    $aDb->executeRecordset($this->posibleValuesSql, $lRecordset);

    if (!$this->isRequired)
      array_unshift($lRecordset, array('id' => '', 'value' => ''));

    for ($i = 0, $l = count($lRecordset); $i < $l; $i++)
      $lRecordset[$i]['is_active'] =
        (arrayValueGetTyped($lRecordset[$i], 'id', VAR_TYPE_INTEGER)
          == $aCurrentValue);

    $this->value = $lRecordset;
  }
}

class cDbForm extends cForm
{
  public $callbackUrl = '';
  public $editMode = EDIT_MODE_UNDEFINED;

  public $tableName = '';

  protected function optionCreate($aXmlNode)
  {
    return new cDbFormOption($this, $aXmlNode);
  }

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    $this->tableName = $aXmlNode->attrs->nextGetByN('TableName')->getS();
  }

  public function loadValueFromRecordset($aRecordset)
  {
    eAssert(count($aRecordset) == 1);
    $lRecord = $aRecordset[0];

    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if (!$lOption->isKey)
        $lOption->valueSet(arrayValueGet($lRecord, $lOption->sqlField));
    }
  }

  public function loadValueFromDb($aDb)
  {
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if ($lOption->inputType == INPUT_TYPE_SELECT)
        $lOption->valueLoadFromDb($aDb, $lOption->value);
    }
  }

  public function paramsRead()
  {
    parent::paramsRead();

    if (paramPostGetGetCheck('edit_mode', VAR_TYPE_STRING, $lValue))
      $this->editMode = $lValue;

    if (paramPostGetGetCheck('callback_url', VAR_TYPE_STRING, $lValue))
      $this->callbackUrl = $lValue;
  }

  public function sqlBuildAndParamGetForDeleteMode(&$aSqlParams)
  {
    if ($this->sqlKeysExist())
      return $this->sqlDeleteBuildAndParamGet($aSqlParams);
    else
      throw new Exception('Not set key params for DeleteMode');
  }

  public function sqlBuildAndParamGetForSaveMode(&$aSqlParams)
  {
    if ($this->sqlKeysExist())
      return $this->sqlUpdateBuildAndParamGet($aSqlParams);
    else
      return $this->sqlInsertBuildAndParamGet($aSqlParams);
  }

  private function sqlDeleteBuildAndParamGet(&$aSqlParams)
  {
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if ($lOption->isKey)
        $aSqlParams[$lOption->sqlField] = $lOption->value;
    }

    return cDbBase::sqlDeleteBuild($this->tableName, $aSqlParams);
  }

  private function sqlInsertBuildAndParamGet(&$aSqlParams)
  {
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if (!$lOption->isValueExist)
        continue;

      if ($lOption->isKey && $lOption->value === cDbBase::BAD_IDENTITY_ID)
        continue;

      if ($lOption->type == VAR_TYPE_BOOLEAN)
        $aSqlParams[$lOption->sqlField] = $lOption->value ? 'TRUE' : 'FALSE';//!!bag in PDO PG
      else
        $aSqlParams[$lOption->sqlField] = $lOption->value;
    }

    return cDbBase::sqlInsertBuild($this->tableName, $aSqlParams);
  }

  public function sqlKeysExist()
  {
    $lResult = true;

    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);
      if ($lOption->isKey)
        $lResult = $lOption->isValueExist
          && ($lOption->value !== cDbBase::BAD_IDENTITY_ID) && $lResult;
    }

    return $lResult;
  }

  private function sqlUpdateBuildAndParamGet(&$aSqlParams)
  {
    $lWhereSqlParams = array();

    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if ($lOption->isKey)
      {
        $lWhereSqlParams[$lOption->sqlField] = $lOption->value;
        continue;
      }

      if (($lOption->type == VAR_TYPE_BOOLEAN) && $lOption->isValueExist)
        $aSqlParams[$lOption->sqlField] = $lOption->value ? 'TRUE' : 'FALSE';//!!bag in PDO PG
      else
        $aSqlParams[$lOption->sqlField] =
          $lOption->isValueExist ? $lOption->value : null;
    }

    return cDbBase::sqlUpdateBuild($this->tableName, $aSqlParams,
      $lWhereSqlParams);
  }
}

class cBlocks_EditForm_DbForm extends cBlocks_EditForm_Form
{
  public function build()
  {
    $lForm = $this->form;
    return templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'editMode'    => $lForm->editMode,
      'callbackUrl' => $lForm->callbackUrl,
      'options'     => $lForm->asArrayGet()
    ));
  }

  protected function formCreate()
  {
    return new cDbForm();
  }

  protected function delete()
  {
    $lSqlParams = array();
    $lSql = $this->form->sqlBuildAndParamGetForDeleteMode($lSqlParams);
    $this->settings->db->execute($lSql, $lSqlParams);
  }

  protected function init()
  {
    parent::init();
    $this->process();
  }

  private function process()
  {
    $lForm = $this->form;

    switch ($lForm->editMode) {
    case EDIT_MODE_UNDEFINED:
      if ($lForm->sqlKeysExist())
        $lForm->loadValueFromRecordset($this->owner->recordset);
      $lForm->loadValueFromDb($this->settings->db);
      break;
    case EDIT_MODE_SAVE:
      if ($lForm->paramsValidCheck())
      {
        $this->save();

        if ($lForm->callbackUrl)
          header('Location: '.$lForm->callbackUrl);
      }
      $lForm->loadValueFromDb($this->settings->db);
      break;
    case EDIT_MODE_DELETE:
      $this->delete();

      if ($lForm->callbackUrl)
          header('Location: '.$lForm->callbackUrl);
      break;
    default:
      throw new Exception('Not suported EditMode: "'.$lForm->editMode.'"');
    }
  }

  protected function save()
  {
    $lSqlParams = array();
    $lSql = $this->form->sqlBuildAndParamGetForSaveMode($lSqlParams);
    $this->settings->db->execute($lSql, $lSqlParams);
  }
}
?>