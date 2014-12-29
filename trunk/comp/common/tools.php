<?
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
    $lValue = $array[$key];
    $result = $lValue !== '';
    if ($result) {
      $value = $lValue;
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

function insertBeforeArrayValue($array, $key, $value, $keyBefore) {
  $lArray = array();
  $inserted = false;
  foreach($array as $lKey => $lValue) {
    if ($lKey === $keyBefore) {
      $lArray[$key] = $value;
      $inserted = true;
    }
    $lArray[$lKey] = $lValue;
  }
  eAssert($inserted);
  return $lArray;
}

function margeArray(array $array1, array $array2) {
  $result = $array1;
  foreach ($array2 as $key => $value) {
    $action = 'update';

    if ((mb_strlen($key) > 3) && ($key[0] == '_') && ($key[2] == '-')) {
      $keyParts = explode('-', $key);
      $keyPartCount = count($keyParts);
      eAssert($keyPartCount > 1);

      switch ($key[1]) {
        case 'b':
          eAssert($keyPartCount == 3);
          $action = 'before';
          $keyBefore = $keyParts[1];
          $key = $keyParts[2];
          break;
        case 'd':
          eAssert($keyPartCount === 2);
          $action = 'delete';
          $key = $keyParts[1];
          break;
        case 'c':
          eAssert($keyPartCount === 2);
          $action = 'concat';
          $key = $keyParts[1];
          break;
        default:
          raiseNotSupported('ActionPrefix', $key[1]);
          break;
      }
    }

    switch ($action) {
      case 'before':
        $result = insertBeforeArrayValue($result, $key, $value, $keyBefore);
        break;
      case 'concat':
        if (is_array($value)) {
          eAssert(isset($value[0]), 'Array is not sequential');
          $result[$key] = array_merge($result[$key], $value);
        } else {
          $result[$key] .= $value;
        }
        break;
      case 'delete':
        unset($result[$key]);
        break;
      case 'update':
        if (gettype($value) === 'array'
          and !isset($value[0])
          and isset($result[$key])) {
          $result[$key] = margeArray($result[$key], $value);
        } else {
          $result[$key] = $value;
        }
        break;
      default:
        raiseNotSupported('Action', $action);
    }
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
class NamedList {
  private $items = null;
  private $notSupportedDuplication = true;

  public function __construct(array $items = array(), $notSupportedDuplication = true) {
    $this->items = $items;
    $this->notSupportedDuplication = $notSupportedDuplication;
  }

  public function add($name, $value) {
    $this->duplicationCheck($name);
    $this->items[$name] = $value;
    return $value;
  }

  public function count() {
    return count($this->items);
  }

  public function delete($name) {
    unset($this->items[$name]);
  }

  private function duplicationCheck($name) {
    if ($this->notSupportedDuplication && $this->exist($name)) {
      throw new Exception('Duplicated value by name: "' . $name . '"');
    }
  }

  public function exist($name) {
    return array_key_exists($name, $this->items);
  }

  public function getByI($index) {
    $name = '';
    return $this->getNVByI($index, $name);
  }

  public function getItems() {
    return $this->items;
  }

  public function getNVByI($index, &$name) {
    $arrayKeys = array_keys($this->items);
    $name = $arrayKeys[$index];
    return $this->getByN($name);
  }

  public function getByN($name) {
    if (!$this->exist($name)) {
      throw new Exception('Item not exist by name: "' . $name . '"');
    }
    return $this->items[$name];
  }

  public function getCheck($name, &$value) {
    $result = $this->exist($name);
    if ($result) {
      $value = $this->items[$name];
    }
    return $result;
  }

  public function insert($name, $value, $nameBefore) {
    $this->duplicationCheck($name);
    $this->items = insertBeforeArrayValue($this->items, $name, $value,
      $nameBefore);
  }

  public function loadFromJsonFile($filePath) {
    $items = json_decode(fileToString($filePath), true);//!!!not check hierarchy
    if (!$items) {
      throw new Exception('Can not load JsonFile: "' . $filePath .
        '" content: :' . fileToString($filePath));
    }
    foreach ($items as $name => $value) {
      $this->add($name, $value);
    }
  }

  public function loadFromString($value) {
    $items = unserialize($value);
    foreach ($items as $name => $value) {
      $this->add($name, $value);
    }
  }

  public function marge(array $items) {
    $this->items = margeArray($this->items, $items);
  }

  public function saveToString() {
    return serialize($this->items);
  }

  public function valuesToSectionString($delimiter) {
    return implode($delimiter, array_values($this->items));
  }
}

class LinearList extends NamedList {
  private $position = -1;

  public function clearPosition() {
    $this->position = -1;
  }

  public function deleteCurrByN($name) {
    eAssert($this->position > -1);
    $value = $this->getNVByI($this->position, $lName);
    eAssert($name === $lName);
    $this->delete($name);
    $this->position--;
  }

  public function existNext() {
    return $this->position + 1 < $this->count();
  }

  public function existNextByN($name) {
    $lName = '';
    $result = $this->existNextN($lName);

    if ($result) {
      $result = $name === $lName;
    }

    return $result;
  }

  public function existNextN(&$name) {
    $result = $this->existNext();

    if ($result) {
      $this->getNVByI($this->position + 1, $name);
    }

    return $result;
  }

  public function finalize() {
    if ($this->position !== $this->count() - 1) {
      throw new Exception('Not all items processed. Position: ' .
        $this->position . ' Count: ' . $this->count() . ' ' .
        $this->saveToString());
    }
  }

  public function getCheckNext(&$value) {
    if (!$this->existNext()) {
      return false;
    }
    $value = $this->getNext();
    return true;
  }

  public function getCheckNextByN($name, &$value) {
    if (!$this->existNextByN($name)) {
      return false;
    }
    $value = $this->getNextByN($name);
    return true;
  }

  public function getCheckNextNV(&$name, &$value) {
    if (!$this->existNextN($name)) {
      return false;
    }
    $value = $this->getNextByN($name);
    return true;
  }

  public function getCheckNextOByN($name, &$object) {
    if (!$this->existNextByN($name)) {
      return false;
    }
    $object = $this->getNextOByN($name);
    return true;
  }

  public function getNext() {
    $result = $this->getByI($this->position + 1);
    $this->position++;
    return $result;
  }

  public function getNextN(&$name) {
    $result = $this->getNVByI($this->position + 1, $name);
    $this->position++;
    return $result;
  }

  public function getNextByN($name) {
    $lName = '';
    $result = $this->getNVByI($this->position + 1, $lName);
    if ($name !== $lName) {
      throw new Exception('Invalid next param name: "' . $name .
        '" must be: "' . $lName . '"');
    }
    $this->position++;
    return $result;
  }

  public function getNextOByN($name) {
    $value = $this->getNextByN($name);
    return new NameValueObject($name, $value);
  }
}

class NameValueObject {
  protected $value = '';

  public $name = '';
  public $index = -1;

  public function __construct($name, $value) {
    $this->name = $name;
    $this->set($value);
  }

  public function get() {
    return $this->value;
  }

  public function getB() {
    return $this->getByType(V_BOOLEAN);
  }

  public function getD() {
    return $this->getByType(V_DATE);
  }

  public function getDT() {
    return $this->getByType(V_DATETIME);
  }

  public function getByType($varType) {
    return valueByType($this->value, $varType);
  }

  public function getF() {
    return $this->getByType(V_FLOAT);
  }

  public function getI() {
    return $this->getByType(V_INTEGER);
  }

  public function getS() {
    return $this->getByType(V_STRING);
  }

  public function getT() {
    return $this->getByType(V_TIME);
  }

  public function set($value) {
    $this->value = $value;
  }
}

class HierarchyList {
  private $currList = null;
  private $level = 0;
  private $levels = array();
  private $notSupportedDuplication = true;

  public function __construct(array $items = array(),
    $notSupportedDuplication = true) {
    $this->notSupportedDuplication = $notSupportedDuplication;
    $this->addList($items);
  }

  private function addList(array $items) {
    $this->currList = new LinearList($items,
      $this->notSupportedDuplication);
    $this->levels[] = $this->currList;
    return $this->currList;
  }

  public function beginLevel($name) {
    $items = $this->currList->getNextByN($name);
    return $this->addList($items);
  }

  public function beginLevelN(&$name) {
    $items = $this->currList->getNextN(&$name);
    return $this->addList($items);
  }

  public function endLevel() {
    eAssert(count($this->levels) > 1, 'Cannot close root level');
    $this->currList->finalize();
    array_pop($this->levels);
    $this->currList = $this->levels[count($this->levels) - 1];
  }

  public function finalize() {
    eAssert(count($this->levels) === 1, 'Exist not ended levels ' .
      count($this->levels));

    $this->currList->finalize();
  }

  public function getCurrList() {
    return $this->currList;
  }

  public function tryBeginLevelByN($name, &$currList = null) {
    $result = $this->currList->existNextByN($name);
    if ($result) {
      $currList = $this->beginLevel($name);
    }
    return $result;
  }

  public function tryBeginLevelNV(&$name, &$currList) {
    $result = $this->currList->existNext();
    if ($result) {
      $currList = $this->beginLevelN($name);
    }
    return $result;
  }
}
?>