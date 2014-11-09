<?php
class  cRecordsetTreeAdaptationItem
{
  public $children = array();
  public $parentId = 0;
  public $record = array();

  public function __construct($aParentId, $aRecord)
  {
    $this->parentId = $aParentId;
    $this->record = $aRecord;
  }

  public function addToRecordset(&$aRecordset, $aLevel, $aIndex, $aFullIndex,
    $aIsLast) //!!$aIsLast -> count - one item close two level
  {
    $lChildrenCount = count($this->children);

    $this->record['level']        = $aLevel;
    $this->record['index']        = $aIndex;
    $this->record['full_index']   = $aFullIndex;
    $this->record['is_last']      = $aIsLast;
    $this->record['has_children'] = ($lChildrenCount > 0);

    $aRecordset[] = $this->record;

    for ($i = 0; $i < $lChildrenCount; $i++)
      $this->children[$i]->addToRecordset($aRecordset, $aLevel + 1, $i,
        $aFullIndex.'_'.$i, ($i == ($lChildrenCount - 1)));
  }
}


class cRecordsetTreeAdaptationHelper
{
  public static function process(&$aRecordset, $aIdFildName,
    $aParentIdFieldName)
  {
    $lList = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    for ($i = 0, $l = count($aRecordset); $i < $l; $i++)
    {
      $lRecord = $aRecordset[$i];

      $lId = arrayValueGet($lRecord, $aIdFildName);

      if (!arrayValueGetCheck($lRecord, $aParentIdFieldName, $lParentId))
        $lParentId = 0;

      $lList->add($lId, new cRecordsetTreeAdaptationItem($lParentId, $lRecord));
    }

    for ($i = 0, $l = $lList->count(); $i < $l; $i++)
    {
      $lItem = $lList->getByI($i);
      if ($lItem->parentId)
      {
        $lParentItem = $lList->getByN($lItem->parentId);
        $lParentItem->children[] = $lItem;
      }
    }

    $aRecordset = array();
    $lIndex = 0;

    for ($i = 0, $l = $lList->count(); $i < $l; $i++)
    {
      $lItem = $lList->getByI($i);
      if (!$lItem->parentId)
      {
        $lItem->addToRecordset($aRecordset, 1, $lIndex, $lIndex, ($i == ($l - 1)));
        $lIndex++;
      }
    }
  }
}
?>