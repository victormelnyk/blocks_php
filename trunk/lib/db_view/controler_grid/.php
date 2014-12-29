<?
Page::addModule('blocks/lib/db_view/controler/.php');
Page::addModule('blocks/comp/helpers/recordset_grid_adaptation/.php');

class cColCountOption extends cOptionBase
{
  public $value = 0;

  public function loadFromXml($aXmlNode)
  {
    parent::loadFromXml($aXmlNode);

    $this->value = $aXmlNode->attrs->getNextByN('Value')->getI();
  }
}

class cColCount extends cOptionsBase
{
  private $defaultOptionName = '';
  private $isReadOnly        = false;
  private $paramName         = '';

  public $currentOptionName = '';
  public $currentOptionValue = 0;

  public function loadFromXml($aXmlNode)
  {
    while ($aXmlNode->nodes->getCheckNext($lColCountNode))
    {
      $lOption = new cColCountOption($this, $lColCountNode->name);
      $lOption->loadFromXml($lColCountNode);
    }

    if ($aXmlNode->nodes->count() == 1)
    {
      $this->isReadOnly = true;
      $this->defaultOptionName = $lColCountNode->name;
    }
    else
    {
      $this->paramName         = $aXmlNode->attrs->getNextByN('ParamName')->getS();
      $this->defaultOptionName = $aXmlNode->attrs->getNextByN('DefaultOptionName')->getS();
    }

    $this->isInitialized = true;
  }

  public function paramsRead()
  {
    if (!$this->isInitialized)
      return;

    if ($this->isReadOnly
      || !paramGetGetCheck($this->paramName, V_STRING, $lColCountParam))
      $lColCountParam = $this->defaultOptionName;

    $this->currentOptionName  = $lColCountParam;
    $this->currentOptionValue = $this->options->getByN($lColCountParam)->value;
  }
}

class Blocks_DbView_ControlerGrid extends Blocks_DbView_Controler
{
  public $colCount = null;

  protected function init()
  {
    $this->colCount = new cColCount();

    parent::init();
  }

  protected function paramsRead()
  {
    parent::paramsRead();
    $this->colCount->paramsRead();
  }

  protected function recordsetGet()
  {
    parent::recordsetGet();

    cRecordsetGridAdaptationHelper::process($this->recordset,
      $this->colCount->currentOptionValue);
  }

  protected function readSettings(cXmlNode $aXmlNode)
  {
    parent::readSettings($aXmlNode);

    if ($aXmlNode->nodes->getCheckNextByN('ColCount', $lNode))
      $this->colCount->loadFromXml($lNode);
  }
}
?>