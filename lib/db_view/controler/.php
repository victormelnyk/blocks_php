<?
Page::addModule('blocks/lib/sys/named_list/.php');

abstract class cOptionBase
{
  public $list = null;

  public $name = '';
  public $title = '';

  public function __construct($aList, $aName)
  {
    $this->list = $aList;
    $this->name = $aName;

    $aList->options->add($this->name, $this);
  }

  public function loadFromXml($aXmlNode)
  {
    $this->title = $aXmlNode->attrs->getNextByN('Title')->getS();
  }

  public function toArray(&$aArray)
  {
    $aArray['name']  = $this->name;
    $aArray['title'] = $this->title;
  }
}

abstract class cOptionsBase
{
  protected $isInitialized = false;

  public $options = null;

  public function __construct()
  {
    $this->options = new NamedIndexedList();
  }

  abstract public function loadFromXml($aXmlNode);
  abstract public function paramsRead();

  public function asArrayGet()
  {
    $lResult = array();
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lArray = array();
      $this->options->getByI($i)->toArray($lArray);
      $lResult[] = $lArray;
    }
    return $lResult;
  }

  public function optionGetByN($aName)
  {
    return $this->options->getByN($aName);
  }
}

class cFilterOptionBase extends cOptionBase
{
  protected $isRequired       = false;
  protected $posibleValuesSql = '';
  protected $type             = '';

  public $isKey        = false;
  public $isReadOnly   = false;
  public $isValueExist = false;
  public $sqlSource    = '';
  public $sqlTemplate  = '';
  public $useInOrder   = false;
  public $inputType    = '';

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    $this->type = $aXmlNode->attrs->getNextByN('Type')->getS();

    if ($aXmlNode->attrs->getCheckNextByN('InputType', $lAttr))
      $this->inputType = $lAttr->getS();
    else
      $this->inputType = inputTypeByVarType($this->type);

    if ($aXmlNode->attrs->getCheckNextByN('SqlSource', $lAttr))
      $this->sqlSource = $lAttr->getS();
    else
    if ($aXmlNode->attrs->getCheckNextByN('SqlTemplate', $lAttr))
      $this->sqlTemplate = $lAttr->getS();
    else
      throw new Exception('SqlSource or SqlTemplate must be set for filter'.
        'option');

    if ($aXmlNode->attrs->getCheckNextByN('IsKey', $lAttr))
      $this->isKey = $lAttr->getB();

    if ($aXmlNode->attrs->getCheckNextByN('IsRequired', $lAttr))
      $this->isRequired = $lAttr->getB();

    if ($aXmlNode->attrs->getCheckNextByN('IsReadOnly', $lAttr))
      $this->isReadOnly = $lAttr->getB();

    if ($aXmlNode->attrs->getCheckNextByN('UseInOrder', $lAttr))
      $this->useInOrder = $lAttr->getB();

    if ($this->inputType == INPUT_TYPE_SELECT)
      $this->posibleValuesSql =
        $aXmlNode->nodes->getNextByN('PosibleValuesSql')->getS();
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);

    $aArray['inputType']  = $this->inputType;
    $aArray['isReadOnly'] = $this->isReadOnly;
  }
}

class cFilterOptionEqual extends cFilterOptionBase
{
  public $value = null;
  public $posibleValues = array();

  public function addToSqlParams(&$aSqlList, &$aParams)
  {
    if (!$this->isValueExist)
      return;

    if ($this->sqlTemplate)
      $aSqlList[] = sprintf($this->sqlTemplate, ' :'.$this->name);
    else
      $aSqlList[] = $this->sqlSource.' = :'.$this->name;

    if ($this->type == V_BOOLEAN)
      $aParams[$this->name] = $this->value ? 1 : 0;
    else
      $aParams[$this->name] = $this->value;
  }

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    if ($aXmlNode->attrs->getCheckNextByN('SessionParamName', $lAttr))
    {
      eAssert($this->isReadOnly, 'ReadOnly must be set for FilterOption: "'.
        $this->name.'" because SessionParamName is set');

      $this->isValueExist = paramSessionGetCheck($lAttr->getS(), $this->type,
        $this->value);
    }

    if ($aXmlNode->attrs->getCheckNextByN('DefaultValue', $lAttr))
    {
      if (!$this->isValueExist)
      {
        $this->value = $lAttr->getByType($this->type);
        $this->isValueExist = true;
      }
    }

    eAssert($this->isValueExist || !$this->isReadOnly,
      'DefaultValue or SessionParamName should be set for ReadOnly '.
      'FilterOption: "'.$this->name.'"');
  }

  public function paramsRead()
  {
    if (paramPostGetGetCheck($this->name, $this->type, $lValue))
    {
      eAssert(!$this->isReadOnly,
        'Can not set Value for ReadOnly FilterOption: "'.$this->name.'"');
      $this->value = $lValue;
      $this->isValueExist = true;
    }
    else
    if (!$this->isValueExist && $this->isRequired)
      throw new Exception('Not set filter required param: "'.$this->name.'"');
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);

    $aArray['optionType'] = cFilter::FILTER_OPTION_TYPE_EQUAL;
    $aArray['value']      = $this->value;

    if (count($this->posibleValues))
      $aArray['posibleValues'] = $this->posibleValues;
  }

  public function urlParamsBuild()
  {
    return ($this->isReadOnly ? '' : ($this->name.'='.$this->value));
  }

  public function valueLoadFromDb($aDb)
  {
    eAssert($this->posibleValuesSql);


    $aDb->executeRecordset($this->posibleValuesSql, $this->posibleValues);

    if (!$this->isRequired)
      array_unshift($this->posibleValues, array('id' => '', 'value' => ''));

    for ($i = 0, $l = count($this->posibleValues); $i < $l; $i++)
      $this->posibleValues[$i]['is_active'] =
        (getArrayValueTyped($this->posibleValues[$i], 'id', V_INTEGER)
          == $this->value);
  }
}

class cFilterOptionRange extends cFilterOptionBase
{
  const SUFFIX_FROM = '_from';
  const SUFFIX_TO   = '_to';

  public $valueFrom = null;
  public $valueTo   = null;

  public function addToSqlParams(&$aSqlList, &$aParams)
  {
    if (!$this->isValueExist)
      return;

    if ($this->sqlTemplate)
    {
      $aSqlList[] = sprintf($this->sqlTemplate,
        ' :'.$this->name.self::SUFFIX_FROM,
        ' :'.$this->name.self::SUFFIX_TO);

      $aParams[$this->name.self::SUFFIX_FROM] = $this->valueFrom;
      $aParams[$this->name.self::SUFFIX_TO]   = $this->valueTo;
    }
    else
    {
      $lSqlFrom = '';
      $lSqlTo   = '';

      if (!is_null($this->valueFrom))
      {
        $lSqlFrom = $this->sqlSource.' >= :'.
          $this->name.self::SUFFIX_FROM;
        $aParams[$this->name.self::SUFFIX_FROM] = $this->valueFrom;
      }

      if (!is_null($this->valueTo))
      {
        $lSqlTo = $this->sqlSource.' <= :'.
          $this->name.self::SUFFIX_TO;
        $aParams[$this->name.self::SUFFIX_TO] = $this->valueTo;
      }

      if ($lSqlFrom && $lSqlTo)
        $aSqlList[] = '('.$lSqlFrom.' AND '.$lSqlTo.')';
      elseif ($lSqlFrom)
        $aSqlList[] = $lSqlFrom;
      else
        $aSqlList[] = $lSqlTo;
    }
  }

  private function defaultValueGetCheck($aXmlNode, $aName, &$aValue)
  {
    if (!$aXmlNode->attrs->getCheckNextByN($aName, $lAttr))
      return false;

    if ($this->type == V_DATE)
    {
      $lValue = $lAttr->getByType(V_STRING);

      switch ($lValue) {
      case 'today':
        $aValue = gmdate('Y-m-d');
        return true;
      case 'first day of this month':
        $lDate = new DateTime('NOW', new DateTimeZone("UTC"));
        $lDate->modify('first day of this month');
        $aValue = $lDate->format('Y-m-d');
        return true;
      }
    }
    else//!!case
    if ($this->type == V_DATETIME)
    {
      $lValue = $lAttr->getByType(V_STRING);

      $lDate = new DateTime('NOW', new DateTimeZone("UTC"));
      $lDate->modify($lValue);
      $aValue = $lDate->format('Y-m-d H:i:s');

      return true;
    }

    $aValue = $lAttr->getByType($this->type);
    return true;
  }

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    if ($this->defaultValueGetCheck($aXmlNode, 'DefaultValueFrom',
      $this->valueFrom)
    )
      $this->isValueExist = true;

    if ($this->defaultValueGetCheck($aXmlNode, 'DefaultValueTo',
      $this->valueTo)
    )
      $this->isValueExist = true;
  }

  public function paramsRead()
  {
    if (paramPostGetGetCheck($this->name.self::SUFFIX_FROM, $this->type, $lValue))
    {
      eAssert(!$this->isReadOnly,
        'Can not set Value for ReadOnly FilterOption: "'.$this->name.'"');//!!cd
      $this->valueFrom = $lValue;
      $this->isValueExist = true;
    }

    if (paramPostGetGetCheck($this->name.self::SUFFIX_TO, $this->type, $lValue))
    {
      eAssert(!$this->isReadOnly,
        'Can not set Value for ReadOnly FilterOption: "'.$this->name.'"');//!!cd
      $this->valueTo = $lValue;
      $this->isValueExist = true;
    }

    if (!$this->isValueExist && $this->isRequired)
      throw new Exception('Not set filter required param: "'.$this->name.'"');//!!cd
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);

    $aArray['optionType'] = cFilter::FILTER_OPTION_TYPE_RANGE;
    $aArray['valueFrom']  = $this->valueFrom;
    $aArray['valueTo']    = $this->valueTo;
  }

  public function urlParamsBuild()
  {
    return ($this->isReadOnly ? '' : (
      $this->name.self::SUFFIX_FROM.'='.$this->valueFrom.'&'.
      $this->name.self::SUFFIX_TO.'='.$this->valueTo));
  }
}

class cFilterOptionEntry extends cFilterOptionBase
{
  public $values = array();
}

class cFilter extends cOptionsBase
{
  //!FILTER_IS_EMPTY
  const FILTER_IS_EMPTY_NONE  = 'none';
  const FILTER_IS_EMPTY_EMPTY = 'empty';
  //!FILTER_OPTION_TYPE
  const FILTER_OPTION_TYPE_EQUAL = 'Equal';
  const FILTER_OPTION_TYPE_RANGE = 'Range';
  const FILTER_OPTION_TYPE_ENTRY = 'Entry';

  private $filterIsEmpty = self::FILTER_IS_EMPTY_NONE;

  public $isEmpty = false;

  public function keyOptionsAsNameSqlFieldNameArrayGet()
  {
    $lResult = array();
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);
      if ($lOption->isKey)
      {
        eAssert($lOption->sqlSource, 'SqlSource mast by set for key option');
        $lSqlArray = explode('.', $lOption->sqlSource);
        eAssert(count($lSqlArray) == 2);
        $lResult[$lOption->name] = $lSqlArray[1];
      }
    }
    return $lResult;
  }

  public function loadFromXml($aXmlNode)
  {
    if ($aXmlNode->attrs->getCheckNextByN('FilterIsEmpty', $lAttr))
      $this->filterIsEmpty = $lAttr->getS();

    while ($aXmlNode->nodes->getCheckNext($lFilterNode))
    {
      $lOptionType = $lFilterNode->attrs->getNextByN('OptionType')->getS();

      switch ($lOptionType) {
      case self::FILTER_OPTION_TYPE_EQUAL:
        $lOption = new cFilterOptionEqual($this, $lFilterNode->name);
        break;
      case self::FILTER_OPTION_TYPE_RANGE:
        $lOption = new cFilterOptionRange($this, $lFilterNode->name);
        break;
      case self::FILTER_OPTION_TYPE_ENTRY:
        $lOption = new cFilterOptionEntry($this, $lFilterNode->name);
        break;
      default:
        throw new Exception('Not suported OptionType: "'.$lOptionType.'"');
      }

      $lOption->loadFromXml($lFilterNode);
    }

    $this->isInitialized = true;
  }

  public function loadValueFromDb($aDb)
  {
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);

      if ($lOption->inputType == INPUT_TYPE_SELECT)
        $lOption->valueLoadFromDb($aDb);
    }
  }

  public function paramsRead()
  {
    $lIsEmpty = false;
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
    {
      $lOption = $this->options->getByI($i);
      $lOption->paramsRead();
      if ($lOption->isKey && !$lOption->isValueExist)
        $lIsEmpty = true;
    }

    if ($lIsEmpty)
      switch ($this->filterIsEmpty) {
      case self::FILTER_IS_EMPTY_NONE:
        break;
      case self::FILTER_IS_EMPTY_EMPTY:
        $this->isEmpty = true;
        break;
      default:
        throw new Exception('Not suported FilterIsEmpty value: "'.
          $this->filterIsEmpty.'"');
      }
  }

  public function sqlAndParamsGet(&$aParams)
  {
    if (!$this->isInitialized)
        return '';

    $lSqlList = array();

    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
      $this->options->getByI($i)->addToSqlParams($lSqlList, $aParams);

    return count($lSqlList)
      ? 'WHERE'.CRLF.implode(CRLF.'  AND ', $lSqlList).CRLF
      : '';
  }

  public function urlParamsBuild()
  {
    $lResult = array();
    for ($i = 0, $l = $this->options->count(); $i < $l; $i++)
      $lResult[] = $this->options->getByI($i)->urlParamsBuild();
    return implode('&', $lResult);
  }
}

class cOrderOption extends cOptionBase
{
  public $sqlSource = '';

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    $this->sqlSource = $aXmlNode->attrs->getNextByN('SqlSource')->getS();
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);
    $aArray['isActive'] = ($this->name == $this->list->currentOptionName);
  }
}

class cOrder extends cOptionsBase
{
  private $currentOption     = null;
  private $defaultOptionName = '';
  private $isReadOnly        = false;

  private $isReadOnlyDirection = false;

  public $currentOptionName  = '';
  public $currentOptionTitle = '';
  public $paramName          = '';

  public $directionParamName = '';
  public $isDesc             = false;

  public function loadFromFilter($aFilter)
  {
    for ($i = 0, $l = $aFilter->options->count(); $i < $l; $i++)
    {
      $lFilterOption = $aFilter->options->getByI($i);

      if ($lFilterOption->useInOrder)
      {
        $lOption = new cOrderOption($this, $lFilterOption->name);
        $lOption->title     = $lFilterOption->title;
        $lOption->sqlSource = $lFilterOption->sqlSource;
      }
    }
  }

  public function loadFromXml($aXmlNode)
  {
    while ($aXmlNode->nodes->getCheckNext($lOptionNode))
    {
      $lOption = new cOrderOption($this, $lOptionNode->name);
      $lOption->loadFromXml($lOptionNode);
    }

    $lOptionCount = $this->options->count();
    if ($lOptionCount == 0)
      return;

    if ($lOptionCount == 1)
    {
      $this->isReadOnly = true;
      $this->defaultOptionName = $this->options->getByI(0)->name;
    }
    else
    {
      $this->paramName         = $aXmlNode->attrs->getNextByN('ParamName')->getS();
      $this->defaultOptionName = $aXmlNode->attrs->getNextByN('DefaultOptionName')->getS();
    }

    $lAttr = null;

    if ($aXmlNode->attrs->getCheckNextByN('DirectionParamName', $lAttr))
      $this->directionParamName = $lAttr->getS();
    else
      $this->isReadOnlyDirection = true;

    if ($aXmlNode->attrs->getCheckNextByN('DirectionIsDesc', $lAttr))
      $this->isDesc = $lAttr->getB();

    $this->isInitialized = true;
  }

  public function paramsRead()
  {
    if (!$this->isInitialized)
      return;

    if ($this->isReadOnly
      || !paramPostGetGetCheck($this->paramName, V_STRING, $lOptionName))
      $lOptionName = $this->defaultOptionName;

    $this->currentOptionName  = $lOptionName;
    $this->currentOption      = $this->options->getByN($lOptionName);
    $this->currentOptionTitle = $this->currentOption->title;

    if (!$this->isReadOnlyDirection
      && paramPostGetGetCheck($this->directionParamName, V_BOOLEAN,
      $lDirectionParamValue))
      $this->isDesc = $lDirectionParamValue;
  }

  public function sqlGet()
  {
    if (!$this->isInitialized)
      return '';

    return $this->currentOption
      ? ('ORDER BY '.$this->currentOption->sqlSource.
        ($this->isDesc ? ' DESC' : '').CRLF)
      : '';
  }

  public function urlParamsBuild()
  {
    $lResult = $this->currentOption
      ? $this->paramName.'='.$this->currentOption->name : '';
    $lResult .= $this->isDesc
      ? ($lResult ? '&' : '').$this->directionParamName.'='.'1' : '';
    return $lResult;
  }
}

class cLimitOption extends cOptionBase
{
  public $value = '';

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    $this->value = $aXmlNode->attrs->getNextByN('Value')->getI();
  }

  public function toArray(&$aArray)
  {
    parent::toArray($aArray);
    $aArray['isActive'] = ($this->name == $this->list->currentOptionName);
  }
}

class cLimit extends cOptionsBase
{
  private $currentOption     = null;
  private $defaultOptionName = '';
  private $isReadOnly        = false;

  private $isReadOnlyPageNo = false;

  public $currentOptionName  = '';
  public $currentOptionTitle = '';
  public $currentOptionValue = 100;
  public $paramName          = '';

  public $pageNoParamName = '';
  public $pageNo          = 1;

  public function loadFromXml($aXmlNode)
  {
    $lOptionCount = $aXmlNode->nodes->count();
    eAssert($lOptionCount > 0, 'LimitOptionCount must be greater than 0');

    while ($aXmlNode->nodes->getCheckNext($lOptionNode))
    {
      $lOption = new cLimitOption($this, $lOptionNode->name);
      $lOption->loadFromXml($lOptionNode);
    }

    if ($lOptionCount == 1)
    {
      $this->isReadOnly = true;
      $this->defaultOptionName = $this->options->getByI(0)->name;
    }
    else
    {
      $this->paramName         = $aXmlNode->attrs->getNextByN('ParamName')->getS();
      $this->defaultOptionName = $aXmlNode->attrs->getNextByN('DefaultOptionName')->getS();
    }

    $lAttr = null;

    if ($aXmlNode->attrs->getCheckNextByN('PageNoParamName', $lAttr))
      $this->pageNoParamName = $lAttr->getS();
    else
      $this->isReadOnlyPageNo = true;

    $this->isInitialized = true;
  }

  public function paramsRead()
  {
    if (!$this->isInitialized)
      return;

    if ($this->isReadOnly
      || !paramPostGetGetCheck($this->paramName, V_STRING, $lLimitParam))
      $lLimitParam = $this->defaultOptionName;

    $this->currentOptionName  = $lLimitParam;
    $this->currentOption      = $this->options->getByN($lLimitParam);
    $this->currentOptionTitle = $this->currentOption->title;
    $this->currentOptionValue = $this->currentOption->value;

    if (!$this->isReadOnlyPageNo
      && paramPostGetGetCheck($this->pageNoParamName, V_INTEGER,
        $lPageNoParam))
      $this->pageNo = ($lPageNoParam > 0 ? $lPageNoParam : 1);
  }

  public function sqlGet()
  {
    return 'LIMIT '.$this->currentOptionValue.($this->pageNo > 1 ?
      ' OFFSET '.(($this->pageNo - 1) * $this->currentOptionValue) : '').CRLF;
  }

  public function urlParamsBuild()
  {
    $lResult = $this->currentOption
      ? $this->paramName.'='.$this->currentOption->name : '';
    $lResult .= $this->pageNo > 1
      ? ($lResult ? '&' : '').$this->pageNoParamName.'='.$this->pageNo : '';
    return $lResult;
  }
}

class Blocks_DbView_Controler extends Blocks_Sys_NamedList
{
  protected $sql       = '';
  protected $sqlParams = array();

  //! PP - PostProcess
  public $recordPPFuncs    = array();
  public $recordsetPPFuncs = array();

  public $params     = array();//!!->templateParams
  public $fieldTypes = array();

  public $filter = null;
  public $limit  = null;
  public $order  = null;

  protected $isRecordCountRead = false;
  protected $recordCount       = 0;

  public $recordset = array();

  public function build()
  {
    $lValues = array();

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
    {
      $lBlock = $this->blocks[$i];
      $lValues[$lBlock->name] = $lBlock->getContent();
    }

    $lValues['params'] = $this->params;

    return $this->templateProcess($this->getFirstExistFileData('.htm'), $lValues);
  }

  protected function init()
  {
    $this->filter = new cFilter();
    $this->order  = new cOrder();
    $this->limit  = new cLimit();

    parent::init();

    $this->paramsRead();

    $this->sql = $this->getFirstExistFileData('.sql');

    $this->sql = $this->templateProcess($this->sql,
      array(
        'where' => $this->filter->sqlAndParamsGet($this->sqlParams),
        'order' => $this->order->sqlGet(),
        'limit' => $this->limit->sqlGet())
    );
    //!!!var_dump($this->sql);
    $this->filter->loadValueFromDb($this->settings->db);

    if ($this->filter->isEmpty)
      return;

    $this->recordsetGet();

    if ((count($this->recordPPFuncs))
      || (count($this->recordsetPPFuncs)))
      $this->recordsetPostProcess();
  }

  protected function paramsRead()
  {
    $this->filter->paramsRead();
    $this->order->paramsRead();
    $this->limit->paramsRead();
  }

  public function recordCountGet()
  {
    if (!$this->isRecordCountRead)
    {
      $lSqlFromIndex = mb_strpos($this->sql, CRLF.'FROM');

      eAssert($lSqlFromIndex !== false, 'Can not find "FROM" in sql: "'.
        $this->sql.'"');

      $lSqlToIndex = mb_strpos($this->sql, CRLF.'ORDER BY');

      if (!$lSqlToIndex)
        $lSqlToIndex = mb_strpos($this->sql, CRLF.'LIMIT');

      if (!$lSqlToIndex)
        $lSqlToIndex = mb_strlen($this->sql);

      $lSql = mb_substr($this->sql, $lSqlFromIndex,
        $lSqlToIndex - $lSqlFromIndex);

      $lSql = 'SELECT COUNT(*) as count'.CRLF.$lSql;

      $this->settings->db->executeValue($lSql, 'count', $this->recordCount,
        $this->sqlParams, V_INTEGER);

      $this->isRecordCountRead = true;
    }

    return $this->recordCount;
  }

  protected function recordsetGet()
  {
    $this->settings->db->executeRecordset($this->sql, $this->recordset,
      $this->sqlParams, $this->fieldTypes);
  }

  private function recordsetPostProcess()
  {
    $lModuleFlps = $this->getExistFilePaths('.php');

    for ($i = 0, $l = count($lModuleFlps); $i < $l; $i++)
      $this->page->addModuleDirect($lModuleFlps[$i]);

    for ($lIndex = 0, $lCount = count($this->recordPPFuncs);
      $lIndex < $lCount; $lIndex++)
    {
      $lFunction = $this->recordPPFuncs[$lIndex];

      if (!function_exists($lFunction))
        throw new Exception('RecordPostProcessFunction with name: "'.$lFunction.
          '" do not exsist');

      for ($i = 0, $l = count($this->recordset); $i < $l; $i++)
        $lFunction($this->recordset[$i], $this);
    }

    for ($lIndex = 0, $lCount = count($this->recordsetPPFuncs);
      $lIndex < $lCount; $lIndex++)
    {
      $lFunction = $this->recordsetPPFuncs[$lIndex];

      if (!function_exists($lFunction))
        throw new Exception('RecordsetPostProcessFunction with name: "'.
          $lFunction.'" do not exsist');

      $lFunction($this->recordset, $this);
    }
  }

  protected function readSettings(cXmlNode $aXmlNode)
  {
    parent::readSettings($aXmlNode);

    if ($aXmlNode->nodes->getCheckNextByN('RecordPostProcessFunctions', $lNode))
      while ($lNode->nodes->getCheckNext($lFunctionNode))
        $this->recordPPFuncs[] = $lFunctionNode->name;

    if ($aXmlNode->nodes->getCheckNextByN('RecordsetPostProcessFunctions',
      $lNode))
      while ($lNode->nodes->getCheckNext($lFunctionNode))
        $this->recordsetPPFuncs[] = $lFunctionNode->name;

    if ($aXmlNode->nodes->getCheckNextByN('Filter', $lNode))
      $this->filter->loadFromXml($lNode);

    if ($aXmlNode->nodes->getCheckNextByN('Order', $lNode))
    {
      $this->order->loadFromFilter($this->filter);
      $this->order->loadFromXml($lNode);
    }

    if ($aXmlNode->nodes->getCheckNextByN('Limit', $lNode))
      $this->limit->loadFromXml($lNode);

    if ($aXmlNode->nodes->getCheckNextByN('FieldTypes', $lNode))
      while ($lNode->nodes->getCheckNext($lFilelTypeNode))
      {
        $lFieldType = $lFilelTypeNode->getS();
        varTypeCheckAssert($lFieldType);
        $this->fieldTypes[$lFilelTypeNode->name] = $lFieldType;
      }

    if ($aXmlNode->nodes->getCheckNextByN('Params', $lNode))
      while ($lNode->nodes->getCheckNext($lParamNode))
        $this->params[$lParamNode->name] = $lParamNode->getS();
  }

  public function urlParamsBuild($aIsFilterBuild, $aIsOrderBuild,
    $aIsLimitBuild)
  {
    $lResult = '';

    if ($aIsFilterBuild)
      $lResult .= $this->filter->urlParamsBuild();
    if ($aIsOrderBuild)
      $lResult .= ($lResult ? '&' : '').$this->order->urlParamsBuild();
    if ($aIsLimitBuild)
      $lResult .= ($lResult ? '&' : '').$this->limit->urlParamsBuild();

    return $lResult;
  }
}
?>