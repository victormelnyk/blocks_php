<?
Page::addModule('blocks/comp/helpers/recordset_tree_adaptation/.php');

class Blocks_Effects_MenuStatic extends Block
{
  private $activeItemExists = false;

  protected $recordset = array();

  public function build()
  {
    return $this->templateProcess($this->getFirstExistFileData('.htm'),
      array('recordset' => $this->recordset));
  }

  private function nodesProcess($aNodes, $aParentId, $aLevel)
  {
    $lId           = $aParentId + 1;
    $lNode         = null;
    $lParamNode    = null;
    $lChildrenNode = null;
    $lIsActiveAttr = null;

    while ($aNodes->getCheckNext($lNode))
    {
      $lRecord = array(
        'id'        => $lId,
        'parent_id' => $aParentId,
        'name'      => $lNode->name,
        'isActive'  => false
      );

      if ($lNode->nodes->count())
      {
        while ($lNode->nodes->getCheckNext($lParamNode))
        {
          if ($lParamNode->name == 'Children')
            $lChildrenNode = $lParamNode;
          else
            $lRecord[$lParamNode->name] = $lParamNode->getS();
        }

        if (isset($lChildrenNode))
          $lId = $this->nodesProcess($lChildrenNode->nodes, $lId, $aLevel + 1);
        else
          $lId++;
      }
      else
      {
        $lRecord['value'] = $lNode->getS;
        $lId++;
      }

      if ($aLevel == 0
        && $lNode->attrs->getCheckNextByN('IsActive', $lIsActiveAttr)
        && $lIsActiveAttr->getB())
      {
        if ($this->activeItemExists)
          throw new Exception('IsActive is already set for another item');
        $lRecord['isActive']    = true;
        $this->activeItemExists = true;
      }

      $this->recordset[] = $lRecord;
    }

    return $lId;
  }

  protected function readSettings(cXmlNode $aXmlNode)
  {
    parent::readSettings($aXmlNode);

    $this->nodesProcess(
      $aXmlNode->nodes->getNextByN('MenuStaticItems')->nodes, 0, 0);

    cRecordsetTreeAdaptationHelper::process($this->recordset, 'id', 'parent_id');  //!!to cache ?
  }
}
?>