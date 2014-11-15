<?php
cPage::moduleAdd('blocks/lib/db_view/controler/.php');

class cFormOption extends cOptionBase
{
  public $isRequired = false;
  public $type = '';
  public $inputType = '';

  public $value = '';
  public $isValueExist = false;
  public $isValueValid = false;

  public function __construct($aList, $aXmlNode)
  {
    parent::__construct($aList, $aXmlNode->name);

    $this->loadFromXml($aXmlNode);

    $this->type = $aXmlNode->attrs->nextGetByN('Type')->getS();

    if ($aXmlNode->attrs->nextGetCheckByN('InputType', $lAttr))
      $this->inputType = $lAttr->getS();
    else
      $this->inputType = inputTypeByVarType($this->type);

    if ($aXmlNode->attrs->nextGetCheckByN('IsRequired', $lAttr))
      $this->isRequired = $lAttr->getB();

    if ($aXmlNode->attrs->nextGetCheckByN('DefaultValue', $lAttr))
      $this->valueSetDirect($lAttr->getByType($this->type));
  }

  public function paramsRead()
  {
    $lIsValueExist = paramPostGetGetCheck($this->name, $this->type,
      $this->value);

    if (!$this->isValueExist)
      $this->isValueExist = $lIsValueExist;

    if (!$this->isValueExist && ($this->inputType == INPUT_TYPE_CHECKBOX))
      $this->valueSetDirect(false);
  }

  public function paramsValidCheck()
  {
    $this->isValueValid = ($this->isRequired ? $this->isValueExist : true);
    return $this->isValueValid;
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);
    $aArray['inputType']    = $this->inputType;
    $aArray['isRequired']   = $this->isRequired;
    $aArray['isValueExist'] = $this->isValueExist;
    $aArray['isValueValid'] = $this->isValueValid;
    $aArray['value']        = $this->value;
  }

  public function valueSet($aValue)
  {
    $this->valueSetDirect(valueByType($aValue, $this->type));
  }

  public function valueSetDirect($aValue)
  {
    $this->value = $aValue;
    $this->isValueExist = true;
  }
}

class cForm extends cOptionsBase
{
  protected function optionCreate($aXmlNode)
  {
    return new cFormOption($this, $aXmlNode);
  }

  public function loadFromXml($aXmlNode)
  {
    while ($aXmlNode->nodes->nextGetCheck($lOptionNode))
      $this->optionCreate($lOptionNode);

    $this->isInitialized = true;
  }

  public function paramsRead()
  {
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
      $this->options->getByI($i)->paramsRead();
  }

  public function paramsValidCheck()
  {
    $lResult = true;

    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
      $lResult = $this->options->getByI($i)->paramsValidCheck() && $lResult;

    return $lResult;
  }
}

class cBlocks_EditForm_Form extends cBlock
{
  public $form = null;

  public function build()
  {
    throw new Exception('Not implemented');
  }

  protected function formCreate()
  {
    return new cForm();
  }

  protected function init()
  {
    $this->form = $this->formCreate();

    parent::init();

    $this->form->paramsRead();
  }

  protected function settingsRead(cXmlNode $aXmlNode)
  {
    parent::settingsRead($aXmlNode);

    $this->form->loadFromXml($aXmlNode->nodes->nextGetByN('Form'));
  }
}
?>