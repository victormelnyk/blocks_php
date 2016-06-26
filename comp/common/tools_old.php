<?
//!INPUT_TYPE
define('INPUT_TYPE_CHECKBOX', 'checkbox');//!!!move to form helper?
define('INPUT_TYPE_CODE',     'code');
define('INPUT_TYPE_DATE',     'date');
define('INPUT_TYPE_DATETIME', 'datetime');
define('INPUT_TYPE_FILE',     'file');
define('INPUT_TYPE_SELECT',   'select');
define('INPUT_TYPE_TEXT',     'text');
define('INPUT_TYPE_TEXTAREA', 'textarea');
define('INPUT_TYPE_TIME',     'time');


//!param
function paramGetGet($aParamName, $type)
{
  return getArrayValueTyped($_GET, $aParamName, $type);
}

function paramPostGet($aParamName, $type)
{
  return getArrayValueTyped($_POST, $aParamName, $type);
}

function paramPostGetGet($aParamName, $type)
{
  return ($_SERVER['REQUEST_METHOD'] == 'POST')
    ? paramPostGet($aParamName, $type)
    : paramGetGet($aParamName, $type);
}

function paramSessionGet($aName, $type)
{
  return getArrayValueTyped($_SESSION, $aName, $type);
}

function paramSessionGetCheck($aName, $type, &$value)
{
  return getCheckArrayValueTyped($_SESSION, $aName, $type, $value);
}

function paramGetGetCheck($aName, $type, &$value)
{
  return getCheckArrayValueTyped($_GET, $aName, $type, $value);
}

function paramPostGetCheck($aName, $type, &$value)
{
  return getCheckArrayValueTyped($_POST, $aName, $type, $value);
}

function paramPostGetGetCheck($aName, $type, &$value)
{
  return (($_SERVER['REQUEST_METHOD'] == 'POST')
    ? paramPostGetCheck($aName, $type, $value)
    : paramGetGetCheck($aName, $type, $value));
}

function paramPostGetSessionGetCheck($aName, $type, &$value)
{
  $result = paramPostGetGetCheck($aName, $type, $value);

  if ($result)
    $_SESSION[$aName] = $value;
  else
    $result = paramSessionGetCheck($aName, $type, $value);

  return $result;
}

//!exception
function messageLog($message)
{
  $lFlp ='logs/'.gmdate('Y').'/'.gmdate('Ym').'/'.gmdate('Ymd').'/'.
    gmdate('YmdHis').'.log';
  $lData = gmdate('YmdHis').' : '.$message.CRLF;

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
//html
function inputTypeByVarType($varType)
{
  switch ($varType) {
    case V_STRING:
    case V_INTEGER:
    case V_FLOAT:
      return INPUT_TYPE_TEXT;
    case V_DATE:
      return INPUT_TYPE_DATE;
    case V_TIME:
      return INPUT_TYPE_TIME;
    case V_DATETIME:
      return INPUT_TYPE_DATETIME;
    case V_BOOLEAN:
      return INPUT_TYPE_CHECKBOX;
    default:
      throw new Exception('Not supported VarType: "'.$varType.'"');
  }
}

class cXmlBase extends NameValueObject
{ //!ACTION
  const ACTION_INSERT = '_i';
  const ACTION_UPDATE = '_u';
  const ACTION_DELETE = '_d';
  const ACTION_BEFORE = '_b';
  const ACTION_NAME   = '_n';

  public $action = '_u';
  public $id = '';
  public $anchor = '';

  public function __construct($name, $value)
  {
    if (!$name)//!!test it
      return;

    $name = $name;

    if ((mb_strlen($name) > 3) && ($name[0] == '_') && ($name[2] == '-'))
    {
      $names = explode('-', $name);
      $nameCount = count($names);
      eAssert($nameCount > 1);
      $this->action = $names[0];

      switch ($this->action) {
      case self::ACTION_INSERT:
        eAssert($nameCount == 2);
        $name = $names[1];
        break;
     case self::ACTION_UPDATE:
        eAssert($nameCount == 3);
        $this->id = $names[1];
        $name = $names[2];
        break;
     case self::ACTION_DELETE:
        if ($nameCount == 2)
          $name = $names[1];
        else
        {
          eAssert($nameCount == 3);
          $this->id = $names[1];
          $name = $names[2];
        }
        break;
     case self::ACTION_BEFORE:
        eAssert($nameCount == 3);
        $this->anchor = $names[1];
        $name = $names[2];
        break;
     case self::ACTION_NAME:
        eAssert($nameCount == 3, $name);
        $this->id = $names[1];
        $name = $names[2];
        break;
      default:
        throw new Exception('Not supported action: "'.$this->action.'"');
      }
    }

    if (!$this->id)
      $this->id = $name;

    parent::__construct($name, $value);
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

  public function __construct($name, $value)
  {
    parent::__construct($name, $value);
    $this->attrs = new NameValueLinearNamedIndexedList();
    $this->nodes = new NameValueLinearNamedIndexedList(false);
    $this->nodesById = new NamedList(false);
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

  private function fulnameGet()
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
    $lNode = $this->nodeAdd($aXmlNode->fulnameGet(), $aXmlNode->getS());
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
    $lNode = new cXmlNode($aXmlNode->fulnameGet(), $aXmlNode->getS());
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