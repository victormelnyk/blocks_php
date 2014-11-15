<?php
//!consts
define('CRLF', "\r\n");
//!enums
//!VAR_TYPE
define('V_BOOLEAN',  'Boolean');
define('V_DATE',     'Date');
define('V_DATETIME', 'DateTime');
define('V_FLOAT',    'Float');
define('V_INTEGER',  'Integer');
define('V_INTERVAL', 'Interval');
define('V_STRING',   'String');
define('V_TIME',     'Time');

//!functions
//!common
function p($value) {
  echo $value;
}
//!exception
function eAssert($condition, $message = 'Assert') {
  if (!$condition) {
    throw new Exception($message);
  }
}

function raiseNotSupported($message, $value) {
  throw new Exception('Not supported '. $message .
    ($value ? ': "' . $value . '"' : ''));
}
//!varType
function varTypeCheckAssert($varType) {
  switch ($varType) {
    case V_BOOLEAN:
    case V_DATE:
    case V_DATETIME:
    case V_FLOAT:
    case V_INTEGER:
    case V_STRING:
    case V_TIME:
      return true;
    default:
      raiseNotSupported('VarType', $varType);
  }
}

function valueByType($value, $varType) {
  if (is_null($value)) {
    return null;
  }

  switch ($varType) {
    case V_BOOLEAN:
      switch ($value) {
        case 'true':
        case '1':
          return true;
        case 'false':
        case '0':
          return false;
        default:
          raiseNotSupported('Boolean value must be "true" or "false"', $value);
      }
    case V_DATE:
    case V_DATETIME:
    case V_TIME:
      $dateTime = new DateTime($value, new DateTimeZone('UTC'));
      if (!$dateTime) {
        throw new Exception('Can not convert value: "' . $value . '" to DateTime');
      }

      switch ($varType) {
        case V_DATE:
          $format = 'Y-m-d';
          break;
        case V_DATETIME:
          $format = 'Y-m-d H:i:s';
          break;
        case V_TIME:
          $format = 'H:i:s';
          break;
        default:
          raiseNotSupported('Date VarType', $varType);
      }

      return $dateTime->format($format);
    case V_FLOAT:
      return (float) $value;
    case V_INTEGER:
      return (int) $value;
    case V_STRING:
      return $value;
    case V_INTERVAL:
      return $value;
    default:
      throw new Exception('Not supported VarType: "'.$varType.'"');
  }
  raiseNotSupported('VarType', $varType);
}
//!string
function mb_trim($str) {
  return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
}

function strToCapitalize($str) {
  $parts = explode('_', $str);
  for ($i = 0; $i < count($parts); $i++) {
    $parts[$i] = ucfirst($parts[$i]);
  }
  return implode('', $parts);
}
//!array
function getArrayValue($array, $key) {
  if (array_key_exists($key, $array)) {
    return $array[$key];
  } else {
    throw new Exception('Value by key "' . $key . '" is undefined');
  }
}

function getArrayValueTyped($array, $key, $type) {
  return valueByType(getArrayValue($array, $key), $type);
}

function getCheckArrayValue($array, $key, &$value) {
  $result = array_key_exists($key, $array);
  if ($result) {
    $valueLocal = $array[$key];
    $result = $valueLocal !== '';
    if ($result) {
      $value = $valueLocal;
    }
  }
  return $result;
}

function getCheckArrayValueTyped($array, $key, $type, &$value) {
  $result = getCheckArrayValue($array, $key, $value);
  if ($result) {
    $value = valueByType($value, $type);
  }
  return $result;
}
//!files
function fileToString($filePath) {
  if (!file_exists($filePath)) {
    throw new Exception('File not exist by file name: "' . $filePath . '"');
  }
  return file_get_contents($filePath);
}

function forceDir($filePath, $ignoreLats = false) {
  $dirs = explode('/', $filePath);
  $dir = '';

  for ($i = 0; $i < count($dirs) - ($ignoreLats ? 1 : 0); $i++) {
    $lSubDir = ($dir ? $dir . '/' : '') . $dirs[$i];
    if (file_exists($lSubDir) || mkdir($lSubDir)) {
      $dir = $lSubDir;
    } else {
      throw new Exception('Can not create dir "' . $lSubDir . '"');
    }
  }
}

function removeDir($dir, $isRemoveRoot = true) {
  if (!file_exists($dir) || !is_dir($dir)) {
    throw new Exception('Dir not exist: "' . $dir . '"');
  }

  $dirHandle = opendir($dir);

  while (false !== ($pathPart = readdir($dirHandle))) {//!!not clear
    if ($pathPart === '.' || $pathPart === '..')
      continue;

    $path = $dir . $pathPart;

    if (is_dir($path)) {
      removeDir($path.'/');
    } else if (file_exists($path)) {
      unlink($path);
    }
  }

  closedir($dirHandle);

  if ($isRemoveRoot)
    rmdir($dir);
}

function stringToFile($data, $filePath) {
  stringToFileExt($data, $filePath, True, 'w');
}

function stringToFileExt($data, $filePath, $forceDir, $mode) {
  if ($forceDir) {
    $pathInfo = pathinfo($filePath);
    forceDir($pathInfo['dirname']);
  }

  $file = fopen($filePath, $mode);

  if (!$file) {
    throw new Exception('Can not open file "' . $filePath . '"');
  }

  if (!fwrite($file, $data)) {
    throw new Exception('Can not write to file "' . $$filePath . '"');
  }

  if ($file) {
    fclose($file);
  }
}
//!tag
function tagsFind($str) {
  $result = array();
  $currIndex = 0;
  $tagStartIndex = false;
  $tagFinishIndex = false;

  while (true) {
    $tagStartIndex = mb_strpos($str, '<~', $currIndex);

    if ($tagStartIndex === false) {
      break;
    }

    $tagStartIndex += 2;
    $currIndex = $tagStartIndex;

    $tagFinishIndex = mb_strpos($str, '~>', $currIndex);

    if ($tagFinishIndex === false) {
      break;
    }

    $result[mb_substr($str, $tagStartIndex,
      $tagFinishIndex - $tagStartIndex)] = true;

    $currIndex = $tagFinishIndex + 2;

    $tagStartIndex = false;
    $tagFinishIndex = false;
  }

  return array_keys($result);
}

function tagsReplace($str, $tags, $values) {
  if (count($tags) != count($values)) {
    throw new Exception('Count tags and values are not same: Tags: "' .
      print_r($tags, true) . '" Values: "' . print_r($values, true) . '"');
  }

  for ($i = 0; $i < count($tags); $i++) {
    $tags[$i] = '<~' . $tags[$i] . '~>';
  }

  return str_replace($tags, $values, $str);
}

function tagsReplaceArray($str, $tagsValues) {
  $tags = array();
  $values = array();

  foreach ($tagsValues as $tag => $value) {
    $tags[] = $tag;
    $values[] = $value;
  }

  return tagsReplace($str, $tags, $values);
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

  public function add($aName, $value)
  {
    $this->duplicationCheck($aName);
    $this->items[$aName] = $value;
    return $value;
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

  public function getCheck($aName, &$value)
  {
    $result = $this->exist($aName);
    if ($result)
      $value = $this->items[$aName];
    return $result;
  }

  public function insert($aName, $value, $aNameBefore)
  {
    $this->duplicationCheck($aName);
    $this->getByN($aNameBefore);

    $lItems = array();

    foreach($this->items as $lName => $value)
    {
      if ($lName == $aNameBefore)
        $lItems[$aName] = $value;
      $lItems[$lName] = $value;
    }

    $this->items = $lItems;
  }

  public function loadFromString($value)
  {
    $lItems = unserialize($value);
    foreach ($lItems as $lName => $value)
      $this->add($lName, $value);
  }

  public function toArray()
  {
    $result = array();

    foreach ($this->items as $lName => $value)
      $result[$lName] = $value;

    return $result;
  }

  public function saveToString()
  {
    return serialize($this->items);
  }

  public function valuesToSectionString($aDelimiter)
  {
    return implode($aDelimiter, array_values($this->items));
  }
}

class cNamedIndexedList extends cNamedList
{
  private $itemsByI = array();

  public function add($aName, $value)
  {
    parent::add($aName, $value);
    $this->itemsByI[] = $value;
    return $value;
  }

  public function count()
  {
    return count($this->itemsByI);
  }

  public function delete($aName)
  {
    $value = $this->getByN($aName);
    $lIndex = array_search($value, $this->itemsByI);
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

  public function insert($aName, $value, $aNameBefore)
  {
    parent::insert($aName, $value, $aNameBefore);

    $lItemsByI = array();

    foreach($this->itemsByI as $lIndex => $value)
    {
      if ($value->name == $aNameBefore)
        $lItemsByI[] = $value;
      $lItemsByI[] = $value;
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
    $result = $this->getByI($this->position + 1);
    $this->position++;
    return $result;
  }

  public function nextGetCheck(&$value)
  {
    if (!$this->nextExist())
      return false;

    $value = $this->nextGet();
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

  public function __construct($aName, $value)
  {
    $this->name = $aName;
    $this->valueSet($value);
  }

  public function getB()
  {
    return $this->getByType(V_BOOLEAN);
  }

  public function getD()
  {
    return $this->getByType(V_DATE);
  }

  public function getDT()
  {
    return $this->getByType(V_DATETIME);
  }

  public function getByType($varType)
  {
    return valueByType($this->value, $varType);
  }

  public function getF()
  {
    return $this->getByType(V_FLOAT);
  }

  public function getI()
  {
    return $this->getByType(V_INTEGER);
  }

  public function getS()
  {
    return $this->getByType(V_STRING);
  }

  public function getT()
  {
    return $this->getByType(V_TIME);
  }

  public function valueSet($value)
  {
    $this->value = $value;
  }
}

class cNameValueLinearNamedIndexedList extends cLinearNamedIndexedList
{
  public function add($aName, $value)
  {
    parent::add($aName, $value);
    $value->index = $this->count() - 1;//!!test on delete
    return $value;
  }

  public function addNameValueObject(cNameValueObject $aObject)
  {
    $this->add($aObject->name, $aObject);
  }

  public function currDeleteByN($aName)
  {
    eAssert($this->position > -1);
    $value = $this->getByI($this->position);
    eAssert($value->name == $aName);
    $this->delete($aName);
    $this->position--;
  }

  public function nextGetCheckByN($aName, &$value)
  {
    if (!$this->nextExist())
      return false;

    $value = $this->getByI($this->position + 1);

    if ($value->name != $aName)
      return false;

    $value = $value;
    $this->position++;
    return true;
  }

  public function nextGetByN($aName)
  {
    $result = $this->nextGet();
    if ($result->name != $aName)
      throw new Exception('Invalid next param name: "'.$aName.'" must be: "'.
        $result->name.'"');
    return $result;
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

  public function __construct($aName, $value)
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

    parent::__construct($lName, $value);
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

  public function __construct($aName, $value)
  {
    parent::__construct($aName, $value);
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

  private function nodeValueValidCheck($value)
  {
    for ($i = 0, $l = mb_strlen($value); $i < $l; $i++)
    {
      $lChar = $value[$i];
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

  public function saveToFile($filePath)
  {
    $lDom = dom_import_simplexml($this->saveToSimpleXMLElement())->ownerDocument;
    $lDom->formatOutput = true;
    $lDom->save($filePath);
  }

  private function saveToSimpleXMLElement()
  {
    $result = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>'.
      '<'.$this->name.'>'.$this->value.'</'.$this->name.'>');

    $this->save($result);

    return $result;
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

    $value = $aXmlNode->getS();
    if ($value != '')
    {
      if ($this->getS() == $value)
        throw new Exception('Duplicated value for node: "'.$this->name.
          '" value: "'.$value.'"');

      $this->valueSet($value);
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

  public function loadFromFile($filePath)
  {
    $lDoc = simplexml_load_file($filePath);
    eAssert($lDoc, 'Can not load xmlFlp: "'.$filePath.'"');
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