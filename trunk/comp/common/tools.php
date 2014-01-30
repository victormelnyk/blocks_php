<?php
//!consts
define('CRLF', "\r\n");
//!Enums
//!VAR_TYPE
define('VAR_TYPE_BOOLEAN',  'Boolean');
define('VAR_TYPE_DATE',     'Date');
define('VAR_TYPE_DATETIME', 'DateTime');
define('VAR_TYPE_FLOAT',    'Float');
define('VAR_TYPE_INTEGER',  'Integer');
define('VAR_TYPE_STRING',   'String');
define('VAR_TYPE_TIME',     'Time');
//!INPUT_TYPE
define('INPUT_TYPE_CHECKBOX', 'checkbox');
define('INPUT_TYPE_DATE',     'date');
define('INPUT_TYPE_DATETIME', 'datetime');
define('INPUT_TYPE_SELECT',   'select');
define('INPUT_TYPE_TEXT',     'text');
define('INPUT_TYPE_TEXTAREA', 'textarea');
define('INPUT_TYPE_TIME',     'time');

//!functions
//!common
function p($aValue)
{
  echo $aValue;
}
//!exception
function eAssert($aCondition, $aMessage = 'Assert')
{
  if (!$aCondition)
    throw new Exception($aMessage);
}

function notSupportedRaise($aMessage, $aValue)
{
  throw new Exception('Not supported'.
    $aMessage.($aValue ? ': "' : '').$aValue.($aValue ? '"': ''));
}
//!varType
function varTypeCheckAssert($aVarType)
{
  switch ($aVarType) {
  case VAR_TYPE_BOOLEAN:
  case VAR_TYPE_DATE:
  case VAR_TYPE_DATETIME:
  case VAR_TYPE_FLOAT:
  case VAR_TYPE_INTEGER:
  case VAR_TYPE_STRING:
  case VAR_TYPE_TIME:
    return true;
  default:
    throw new Exception('Not supported VarType: "'.$aVarType.'"');
  }
}

function valueByType($aValue, $aVarType)
{
  if (is_null($aValue))
    return null;

  switch ($aVarType) {
  case VAR_TYPE_BOOLEAN:
    switch ($aValue) {
    case 'true': case '1':
      return true;
    case 'false': case '0':
      return false;
    default:
      throw new Exception('Not supported boolean value: "'.$aValue.'"'.
        ' must be "true" or "false"');
    }
  case VAR_TYPE_DATE:
  {
    $lDateTime = new DateTime($aValue, new DateTimeZone('GMT'));
    if (!$lDateTime)
      throw new Exception('Can not convert value: "'.$aValue.'"'.' to DateTime');
    return $lDateTime->format('Y-m-d');
  }
  case VAR_TYPE_DATETIME:
  {
    $lDateTime = new DateTime($aValue, new DateTimeZone('GMT'));
    if (!$lDateTime)
      throw new Exception('Can not convert value: "'.$aValue.'"'.' to DateTime');
    return $lDateTime->format('Y-m-d H:i:s');
  }
  case VAR_TYPE_FLOAT:
    return (float)$aValue;
  case VAR_TYPE_INTEGER:
    return (int)$aValue;
  case VAR_TYPE_STRING:
    return $aValue;
  case VAR_TYPE_TIME:
  {
    /*!! not work for 2 days 10:00:00
    $lDateTime = new DateTime($aValue, new DateTimeZone('GMT'));
    if (!$lDateTime)
      throw new Exception('Can not convert value: "'.$aValue.'"'.' to Time');
    return $lDateTime->format('H:i:s');
    */
    return $aValue;
  }
  default:
    throw new Exception('Not supported VarType: "'.$aVarType.'"');
  }
  throw new Exception('Can not convert value: "'.$aValue.
    '" to VarType: "'.$aVarType.'"');
}
//!string
function mb_trim($aStr)
{
  return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $aStr);
}

function strToCapitalize($aStr)
{
  $lParts = explode('_', $aStr);

  for ($i = 0, $l = count($lParts); $i < $l; $i++)
    $lParts[$i] = ucfirst($lParts[$i]);

  return implode('', $lParts);
}
//!array
function arrayValueGet($aArray, $aKey)
{
  if (array_key_exists($aKey, $aArray))
    return $aArray[$aKey];
  else
    throw new Exception('Value by key "'.$aKey.'" is undefined');
}

function arrayValueGetTyped($aArray, $aKey, $aType)
{
  return valueByType(arrayValueGet($aArray, $aKey), $aType);
}

function arrayValueGetCheck($aArray, $aKey, &$aValue)
{
  $lResult = array_key_exists($aKey, $aArray);
  if ($lResult)
  {
    $lValue = $aArray[$aKey];
    $lResult = ($lResult && $lValue !== '');//!! ''
    if ($lResult)
      $aValue = $lValue;
  }
  return $lResult;
}

function arrayValueGetCheckTyped($aArray, $aKey, $aType, &$aValue)
{
  $lResult = arrayValueGetCheck($aArray, $aKey, $lValue);
  if ($lResult)
    $aValue = valueByType($lValue, $aType);
  return $lResult;
}
//!files
function fileToString($aFlp)
{
  if (!file_exists($aFlp))
    throw new Exception('File not exist by file name: "'.$aFlp.'"');
  return file_get_contents($aFlp);
}

function forceDir($aDir)
{
  $lDirs = explode('/', $aDir);
  $lDir = '';

  for ($i = 0, $l = count($lDirs); $i < $l; $i++)
  {
    $lSubDir = ($lDir ? $lDir.'/' : '').$lDirs[$i];
    if (file_exists($lSubDir) || mkdir($lSubDir))
      $lDir = $lSubDir;
    else
      throw new Exception('Can not create dir "'.$lSubDir.'"');
  }
}

function removeDir($aDir, $isRemoveRoot = true)
{
  if(!file_exists($aDir) || !is_dir($aDir))
    throw new Exception('Dir not exist: "'.$aDir.'"');

  $aDirHandle = opendir($aDir);

  while (false !== ($lFln = readdir($aDirHandle)))
  {
    if ($lFln == '.' || $lFln == '..')
      continue;

    $lFlp = $aDir.$lFln;
    //!!chmod($lFlp, 0777);

    if (is_dir($lFlp))
      removeDir($lFlp.'/');
    else
    if(file_exists($lFlp))
      unlink($lFlp);
  }

  closedir($aDirHandle);

  if ($isRemoveRoot)
    rmdir($aDir);
}

function stringToFile($aData, $aFlp)
{
  stringToFileExt($aData, $aFlp, True, 'w');
}

function stringToFileExt($aData, $aFlp, $aIsForceDir, $aMode)
{
  if ($aIsForceDir)
  {
    $lPathinfo = pathinfo($aFlp);
    forceDir($lPathinfo['dirname']);
  }

  $lFile = fopen($aFlp, $aMode);

  if (!$lFile)
    throw new Exception('Can not open file "'.$aFlp.'"');

  if (!fwrite($lFile, $aData))
    throw new Exception('Can not write to file "'.$lFlp.'"');

  if ($lFile)
    fclose($lFile);
}
//!param
function paramGetGet($aParamName, $aType)
{
  return arrayValueGetTyped($_GET, $aParamName, $aType);
}

function paramPostGet($aParamName, $aType)
{
  return arrayValueGetTyped($_POST, $aParamName, $aType);
}

function paramPostGetGet($aParamName, $aType)
{
  return ($_SERVER['REQUEST_METHOD'] == 'POST')
    ? paramPostGet($aParamName, $aType)
    : paramGetGet($aParamName, $aType);
}

function paramSessionGet($aName, $aType)
{
  return arrayValueGetTyped($_SESSION, $aName, $aType);
}

function paramSessionGetCheck($aName, $aType, &$aValue)
{
  return arrayValueGetCheckTyped($_SESSION, $aName, $aType, $aValue);
}

function paramGetGetCheck($aName, $aType, &$aValue)
{
  return arrayValueGetCheckTyped($_GET, $aName, $aType, $aValue);
}

function paramPostGetCheck($aName, $aType, &$aValue)
{
  return arrayValueGetCheckTyped($_POST, $aName, $aType, $aValue);
}

function paramPostGetGetCheck($aName, $aType, &$aValue)
{
  return (($_SERVER['REQUEST_METHOD'] == 'POST')
    ? paramPostGetCheck($aName, $aType, $aValue)
    : paramGetGetCheck($aName, $aType, $aValue));
}

function paramPostGetSessionGetCheck($aName, $aType, &$aValue)
{
  $lResult = paramPostGetGetCheck($aName, $aType, $aValue);

  if ($lResult)
    $_SESSION[$aName] = $aValue;
  else
    $lResult = paramSessionGetCheck($aName, $aType, $aValue);

  return $lResult;
}
//!exception
function messageLog($aMessage)
{
  $lFlp ='logs/'.gmdate('Y').'/'.gmdate('Ym').'/'.gmdate('Ymd').'/'.
    gmdate('YmdHis').'.log';
  $lData = gmdate('YmdHis').' : '.$aMessage.CRLF;

  stringToFileExt($lData, $lFlp, True, 'a');
}

function exceptionToString($aException)
{
  messageLog($aException->__toString());
  return $aException->getMessage();
}

function exceptionShow($aException)
{
  p(exceptionToString($aException));
}

function exceptionXmlShow($aException)
{
  p('<error><![CDATA['.exceptionToString($aException).']]></error>');
}
//!tag
function tagsFind($aStr)
{
  $lResult = array();
  $lCurrIndex = 0;
  $lTagStartIndex = false;
  $lTagFinishIndex = false;

  while (true)
  {
    $lTagStartIndex = mb_strpos($aStr, '<~', $lCurrIndex);

    if ($lTagStartIndex === false)
      break;

    $lTagStartIndex += 2;
    $lCurrIndex = $lTagStartIndex;

    $lTagFinishIndex = mb_strpos($aStr, '~>', $lCurrIndex);

    if ($lTagFinishIndex === false)
      break;

    $lResult[mb_substr($aStr, $lTagStartIndex,
      $lTagFinishIndex - $lTagStartIndex)] = true;

    $lCurrIndex = $lTagFinishIndex + 2;

    $lTagStartIndex = false;
    $lTagFinishIndex = false;
  }

  return array_keys($lResult);
}

function tagsReplace($aStr, $aTags, $aValues)
{
  if (count($aTags) != count($aValues))
     throw new Exception('Count tags and values are not same: Tags: "'.
      print_r($aTags, true).'" Values: "'. print_r($aValues, true));

  for ($i = 0, $l = count($aTags); $i < $l; $i++)
    $aTags[$i] = '<~'.$aTags[$i].'~>';

  return str_replace($aTags, $aValues, $aStr);
}

function tagsReplaceArray($aStr, $aTagsValues)
{
  $lTags = array();
  $lValues = array();

  foreach ($aTagsValues as $lTag => $lValue)
  {
    $lTags[] = $lTag;
    $lValues[] = $lValue;
  }

  return tagsReplace($aStr, $lTags, $lValues);
}
//html
function inputTypeByVarType($aVarType)
{
  switch ($aVarType) {
    case VAR_TYPE_STRING:
    case VAR_TYPE_INTEGER:
    case VAR_TYPE_FLOAT:
      return INPUT_TYPE_TEXT;
    case VAR_TYPE_DATE:
      return INPUT_TYPE_DATE;
    case VAR_TYPE_TIME:
      return INPUT_TYPE_TIME;
    case VAR_TYPE_DATETIME:
      return INPUT_TYPE_DATETIME;
    case VAR_TYPE_BOOLEAN:
      return INPUT_TYPE_CHECKBOX;
    default:
      throw new Exception('Not supported VarType: "'.$aVarType.'"');
  }
}
//!classes
class cNamedList
{
  //!DUPLICATION_TYPE
  const DUPLICATION_TYPE_NONE  = 'DUPLICATION_TYPE_NONE';//!!delete
  const DUPLICATION_TYPE_ERROR = 'DUPLICATION_TYPE_ERROR';

  private $items = array();

  private $duplicationType = '';

  public function __construct($aDuplicationType)
  {
    $this->duplicationType = $aDuplicationType;
  }

  public function add($aName, $aValue)
  {
    $this->duplicationCheck($aName);
    $this->items[$aName] = $aValue;
    return $aValue;
  }

  public function count()
  {
    return count($this->items);
  }

  public function delete($aName)
  {
    unset($this->items[$aName]);
  }

  private function duplicationCheck($aName)
  {
    switch ($this->duplicationType) {
    case self::DUPLICATION_TYPE_NONE:
      break;
    case self::DUPLICATION_TYPE_ERROR:
      if ($this->exist($aName))
        throw new Exception('Duplicated value by name: "'.$aName.'"');
      break;
    default:
      throw new Exception('Not supported DuplicationType: "'.
        $this->duplicationType.'"');
    }
  }

  public function exist($aName)
  {
    return array_key_exists($aName, $this->items);
  }

  public function getByN($aName)
  {
    if (!$this->exist($aName))
      throw new Exception('Item not exist by name: "'.$aName.'"');
    return $this->items[$aName];
  }

  public function getCheck($aName, &$aValue)
  {
    $lResult = $this->exist($aName);
    if ($lResult)
      $aValue = $this->items[$aName];
    return $lResult;
  }

  public function insert($aName, $aValue, $aNameBefore)
  {
    $this->duplicationCheck($aName);
    $this->getByN($aNameBefore);

    $lItems = array();

    foreach($this->items as $lName => $lValue)
    {
      if ($lName == $aNameBefore)
        $lItems[$aName] = $aValue;
      $lItems[$lName] = $lValue;
    }

    $this->items = $lItems;
  }

  public function toArray()
  {
    $lResult = array();

    foreach ($this->items as $lName => $lValue)
      $lResult[$lName] = $lValue;

    return $lResult;
  }

  public function valuesToSectionString($aDelimiter)
  {
    return implode($aDelimiter, array_values($this->items));
  }
}

class cNamedIndexedList extends cNamedList
{
  private $itemsByI = array();

  public function add($aName, $aValue)
  {
    parent::add($aName, $aValue);
    $this->itemsByI[] = $aValue;
    return $aValue;
  }

  public function count()
  {
    return count($this->itemsByI);
  }

  public function delete($aName)
  {
    $lValue = $this->getByN($aName);
    $lIndex = array_search($lValue, $this->itemsByI);
    unset($this->itemsByI[$lIndex]);
    $this->itemsByI = array_values($this->itemsByI);
    parent::delete($aName);
  }

  public function getByI($aIndex)
  {
    if (!array_key_exists($aIndex, $this->itemsByI))
      throw new Exception('Item not exist by index: "'.$aIndex.'"');
    return $this->itemsByI[$aIndex];
  }

  public function insert($aName, $aValue, $aNameBefore)
  {
    parent::insert($aName, $aValue, $aNameBefore);

    $lItemsByI = array();

    foreach($this->itemsByI as $lIndex => $lValue)
    {
      if ($lValue->name == $aNameBefore)
        $lItemsByI[] = $aValue;
      $lItemsByI[] = $lValue;
    }

    $this->itemsByI = $lItemsByI;
  }
}

class cLinearNamedIndexedList extends cNamedIndexedList
{
  protected $position = -1;

  public function isAllRead()
  {
    return $this->position == $this->count() - 1;
  }

  public function nextExist()
  {
    return $this->position + 1 < $this->count();
  }

  public function nextGet()
  {
    $lResult = $this->getByI($this->position + 1);
    $this->position++;
    return $lResult;
  }

  public function nextGetCheck(&$aValue)
  {
    if (!$this->nextExist())
      return false;

    $aValue = $this->nextGet();
    return true;
  }

  public function positionClear()
  {
    $this->position = -1;
  }
}

class cNameValueObject
{
  protected $value = '';

  public $name = '';
  public $index = -1;

  public function __construct($aName, $aValue)
  {
    $this->name = $aName;
    $this->valueSet($aValue);
  }

  public function getB()
  {
    return $this->getByType(VAR_TYPE_BOOLEAN);
  }

  public function getD()
  {
    return $this->getByType(VAR_TYPE_DATE);
  }

  public function getDT()
  {
    return $this->getByType(VAR_TYPE_DATETIME);
  }

  public function getByType($aVarType)
  {
    return valueByType($this->value, $aVarType);
  }

  public function getF()
  {
    return $this->getByType(VAR_TYPE_FLOAT);
  }

  public function getI()
  {
    return $this->getByType(VAR_TYPE_INTEGER);
  }

  public function getS()
  {
    return $this->getByType(VAR_TYPE_STRING);
  }

  public function getT()
  {
    return $this->getByType(VAR_TYPE_TIME);
  }

  public function valueSet($aValue)
  {
    $this->value = $aValue;
  }
}

class cNameValueLinearNamedIndexedList extends cLinearNamedIndexedList
{
  public function add($aName, $aValue)
  {
    parent::add($aName, $aValue);
    $aValue->index = $this->count() - 1;//!!test on delete
    return $aValue;
  }

  public function addNameValueObject(cNameValueObject $aObject)
  {
    $this->add($aObject->name, $aObject);
  }

  public function currDeleteByN($aName)
  {
    eAssert($this->position > -1);
    $lValue = $this->getByI($this->position);
    eAssert($lValue->name == $aName);
    $this->delete($aName);
    $this->position--;
  }

  public function nextGetCheckByN($aName, &$aValue)
  {
    if (!$this->nextExist())
      return false;

    $lValue = $this->getByI($this->position + 1);

    if ($lValue->name != $aName)
      return false;

    $aValue = $lValue;
    $this->position++;
    return true;
  }

  public function nextGetByN($aName)
  {
    $lResult = $this->nextGet();
    if ($lResult->name != $aName)
      throw new Exception('Invalid next param name: "'.$aName.'" must be: "'.
        $lResult->name.'"');
    return $lResult;
  }
}

class cXmlBase extends cNameValueObject
{ //!ACTION
  const ACTION_INSERT = '_i';
  const ACTION_UPDATE = '_u';
  const ACTION_DELETE = '_d';
  const ACTION_BEFORE = '_b';
  const ACTION_NAME   = '_n';

  public $action = '_u';
  public $id = '';
  public $anchor = '';

  public function __construct($aName, $aValue)
  {
    if (!$aName)//!!test it
      return;

    $lName = $aName;

    if ((mb_strlen($lName) > 3) && ($lName[0] == '_') && ($lName[2] == '-'))
    {
      $lNames = explode('-', $lName);
      $lNameCount = count($lNames);
      eAssert($lNameCount > 1);
      $this->action = $lNames[0];

      switch ($this->action) {
      case self::ACTION_INSERT:
        eAssert($lNameCount == 2);
        $lName = $lNames[1];
        break;
     case self::ACTION_UPDATE:
        eAssert($lNameCount == 3);
        $this->id = $lNames[1];
        $lName = $lNames[2];
        break;
     case self::ACTION_DELETE:
        if ($lNameCount == 2)
          $lName = $lNames[1];
        else
        {
          eAssert($lNameCount == 3);
          $this->id = $lNames[1];
          $lName = $lNames[2];
        }
        break;
     case self::ACTION_BEFORE:
        eAssert($lNameCount == 3);
        $this->anchor = $lNames[1];
        $lName = $lNames[2];
        break;
     case self::ACTION_NAME:
        eAssert($lNameCount == 3, $lName);
        $this->id = $lNames[1];
        $lName = $lNames[2];
        break;
      default:
        throw new Exception('Not supported action: "'.$this->action.'"');
      }
    }

    if (!$this->id)
      $this->id = $lName;

    parent::__construct($lName, $aValue);
  }
}

class cXmlAttr extends cXmlBase
{
  public function save($aSimpleXmlElement)
  {
    $aSimpleXmlElement->addAttribute($this->name, $this->value);
  }
}

class cXmlNode extends cXmlBase
{
  public $attrs = null;
  public $nodes = null;
  public $nodesById = null;

  public $isUnique = false;

  public function __construct($aName, $aValue)
  {
    parent::__construct($aName, $aValue);
    $this->attrs = new cNameValueLinearNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);
    $this->nodes = new cNameValueLinearNamedIndexedList(cNamedList::DUPLICATION_TYPE_NONE);
    $this->nodesById = new cNamedList(cNamedList::DUPLICATION_TYPE_NONE);
  }

  public function allReadAsser()
  {
    if(!$this->attrs->isAllRead() || !$this->nodes->isAllRead())
      throw new Exception('Not read xml node:'.CRLF.$this->saveToString());

    for ($i = 0, $l = $this->nodes->count(); $i < $l; $i++)
      $this->nodes->getByI($i)->allReadAsser();
  }

  private function attrAdd($aAttrName, $aAttrValue)
  {
    return $this->attrs->addNameValueObject(
      new cXmlAttr($aAttrName, $aAttrValue));
  }

  private function attrAddCopy($aAttr)
  {
    return $this->attrAdd($aAttr->name, $aAttr->getS());
  }

  private function attrInsert($aAttrName, $aAttrValue, $aAttrNameBefore)
  {
    $lAttr = new cXmlAttr($aAttrName, $aAttrValue);
    return $this->attrs->insert($lAttr->name, $lAttr, $aAttrNameBefore);
  }

  private function fullNameGet()
  {
    return (($this->id != $this->name) ? '_n-'.$this->id.'-' : '').$this->name;
  }

  protected function load($aSimpleXmlElement)
  {
    foreach($aSimpleXmlElement->attributes() as $lAttrName => $lAttrValue)
      $this->attrAdd($lAttrName, (string)$lAttrValue);

    foreach ($aSimpleXmlElement->children() as $lNode)
      $this->nodeLoad($lNode);
  }

  private function nodeAdd($aNodeName, $aNodeValue)
  {
    $lNode = new cXmlNode($aNodeName, $aNodeValue);
    $this->nodeValidate($lNode);
    return $this->nodes->add($lNode->name, $lNode);
  }

  private function nodeAddCopy($aXmlNode)
  {
    $lNode = $this->nodeAdd($aXmlNode->fullNameGet(), $aXmlNode->getS());
    $lNode->nodeColectionsAddCopy($aXmlNode);
  }

  private function nodeColectionsAddCopy($aXmlNode)
  {
    for ($i = 0, $l = $aXmlNode->attrs->count(); $i < $l; $i++)
      $this->attrAddCopy($aXmlNode->attrs->getByI($i));

    for ($i = 0, $l = $aXmlNode->nodes->count(); $i < $l; $i++)
      $this->nodeAddCopy($aXmlNode->nodes->getByI($i));
  }

  private function nodeInsertCopy($aXmlNode, $aNodeNameBefore)
  {
    $lNode = new cXmlNode($aXmlNode->fullNameGet(), $aXmlNode->getS());
    $this->nodeValidate($lNode);
    $this->nodes->insert($lNode->name, $lNode, $aNodeNameBefore);
    $lNode->nodeColectionsAddCopy($aXmlNode);
  }

  private function nodeLoad($aSimpleXmlElement)
  {
    $lNode = $this->nodeAdd($aSimpleXmlElement->getName(),
      $this->nodeValueGet($aSimpleXmlElement));
    $lNode->load($aSimpleXmlElement);
  }

  private function nodeSave($aSimpleXmlElement)
  {
    $lIsAddCData = (($this->value != '')
      && !$this->nodeValueValidCheck($this->value));

    $lSimpleXmlElement = $aSimpleXmlElement->addChild($this->name,
      $lIsAddCData ? null : ($this->value ? $this->value : null));

    if ($lIsAddCData)
    {
      $lDomNode = dom_import_simplexml($lSimpleXmlElement);
      $lDomCData = $lDomNode->ownerDocument->createCDATASection($this->value);
      $lDomNode->appendChild($lDomCData);
    }

    $this->save($lSimpleXmlElement);
  }

  private function nodeValidate($aXmlNode)
  {
    $aXmlNode->isUnique = !$this->nodesById->exist($aXmlNode->id);
    $this->nodesById->add($aXmlNode->id, $aXmlNode);
  }

  protected function nodeValueGet($aSimpleXmlElement)
  {
    $lNodeValue = (string)$aSimpleXmlElement;
    $lNodeValueTrim = mb_trim($lNodeValue);

    return  $lNodeValueTrim == '' ? '' : $lNodeValue;
  }

  private function nodeValueValidCheck($aValue)
  {
    for ($i = 0, $l = mb_strlen($aValue); $i < $l; $i++)
    {
      $lChar = $aValue[$i];
      switch ($lChar) {
      case '<':
      case '>':
      case '&':
        return false;
      }
    }
    return true;
  }

  private function save($aSimpleXmlElement)
  {
    for ($i = 0, $l = $this->attrs->count(); $i < $l; $i++)
      $this->attrs->getByI($i)->save($aSimpleXmlElement);

    for ($i = 0, $l = $this->nodes->count(); $i < $l; $i++)
      $this->nodes->getByI($i)->nodeSave($aSimpleXmlElement);
  }

  public function saveToFile($aFlp)
  {
    $lDom = dom_import_simplexml($this->saveToSimpleXMLElement())->ownerDocument;
    $lDom->formatOutput = true;
    $lDom->save($aFlp);
  }

  private function saveToSimpleXMLElement()
  {
    $lResult = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>'.
      '<'.$this->name.'>'.$this->value.'</'.$this->name.'>');

    $this->save($lResult);

    return $lResult;
  }

  public function saveToString()
  {
    return $this->saveToSimpleXMLElement()->asXML();
  }

  public function update($aXmlNode)
  {
    eAssert($this->name = $aXmlNode->name);

    if (!$this->isUnique)
      throw new Exception('Can not update not unique node: "'.$this->name.
        '" Xml1: "'.$this->saveToString().'" Xml2: "'.$aXmlNode->saveToString().
        '"');

    $lValue = $aXmlNode->getS();
    if ($lValue != '')
    {
      if ($this->getS() == $lValue)
        throw new Exception('Duplicated value for node: "'.$this->name.
          '" value: "'.$lValue.'"');

      $this->valueSet($lValue);
    }

    for ($i = 0, $l = $aXmlNode->attrs->count(); $i < $l; $i++)
    {
      $lAttrFrom = $aXmlNode->attrs->getByI($i);

      switch ($lAttrFrom->action) {
      case self::ACTION_INSERT:
        $this->attrAddCopy($lAttrFrom);
        break;
      case self::ACTION_UPDATE:
        if ($this->attrs->getCheck($lAttrFrom->name, $lAttrTo))
        {
          $lAttrFromValue = $lAttrFrom->getS();

          if ($lAttrTo->getS() == $lAttrFromValue)
            throw new Exception('Duplicated value for node: "'.$lAttrTo->name.
              '" value: "'.$lAttrFromValue.'"');

          $lAttrTo->valueSet($lAttrFromValue);
        }
        else
          $this->attrAddCopy($lAttrFrom);
        break;
      case self::ACTION_DELETE:
        $this->attrs->delete($lAttrFrom->name);
        break;
      case self::ACTION_BEFORE:
        $this->attrInsert($lAttrFrom->name, $lAttrFrom->getS(),
          $lAttrFrom->anchor);
        break;
      default:
        throw new Exception('Not supported action: "'.$lAttrFrom->action.'"');
      }
    }

    $lLastUpdateIndex = -1;
    $lIsUpdateAllowed = true;

    for ($i = 0, $l = $aXmlNode->nodes->count(); $i < $l; $i++)
    {
      $lNodeFrom = $aXmlNode->nodes->getByI($i);

      switch ($lNodeFrom->action) {
      case self::ACTION_INSERT:
        $this->nodeAddCopy($lNodeFrom);
        $lIsUpdateAllowed = false;
        break;
      case self::ACTION_UPDATE:
      case self::ACTION_NAME:
        if ($this->nodesById->getCheck($lNodeFrom->id, $lNodeTo))
        {
          if (!$lIsUpdateAllowed)
            throw new Exception('Update allowed only before insert: "'.
              $lNodeFrom->name.'"');
          if ($lNodeTo->index <= $lLastUpdateIndex)
            throw new Exception('Bad node order for update: "'.$lNodeFrom->name.
              '"');

          $lNodeTo->update($lNodeFrom);
          $lLastUpdateIndex = $lNodeTo->index;
        }
        else
        {
          $this->nodeAddCopy($lNodeFrom);
          $lIsUpdateAllowed = false;
        }
        break;
      case self::ACTION_DELETE:
        $this->nodes->delete($lNodeFrom->name);
        $this->nodesById->delete($lNodeFrom->id);
        break;
      case self::ACTION_BEFORE:
        $this->nodeInsertCopy($lNodeFrom, $lNodeFrom->anchor);
        $lIsUpdateAllowed = false;
        break;
      default:
        throw new Exception('Not supported action: "'.$lNodeFrom->action.'"');
      }
    }
  }
}

class cXmlDocument extends cXmlNode
{
  public function __construct()
  {
    parent::__construct('', '');
  }

  private function loadDocument($aXmlDocument)
  {
    $this->name = $aXmlDocument->getName();
    $this->valueSet($this->nodeValueGet($aXmlDocument));
    $this->isUnique = true;

    $this->load($aXmlDocument);
  }

  public function loadFromFile($aFlp)
  {
    $lDoc = simplexml_load_file($aFlp);
    eAssert($lDoc, 'Can not load xmlFlp: "'.$aFlp.'"');
    $this->loadDocument($lDoc);
  }

  public function loadFromString($aXmlString)
  {
    $lDoc = simplexml_load_string($aXmlString);
    eAssert($lDoc, 'Can not load xmlString: "'.$aXmlString.'"');
    $this->loadDocument($lDoc);
  }

  public function rootNodeNameCheckAssert($aNodeName)
  {
    eAssert($this->name == $aNodeName,
      'Wrong root node name: "'.$this->name.'" must be: "'.$aNodeName.
        '"');
  }
}
?>