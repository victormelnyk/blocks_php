<?
Page::addModule('blocks/lib/db_view/controler/.php');
Page::addModule('blocks/comp/helpers/recordset_tree_adaptation/.php');

class Blocks_DbView_ControlerTree extends Blocks_DbView_Controler
{
  protected function recordsetGet()
  {
    parent::recordsetGet();

    cRecordsetTreeAdaptationHelper::process($this->recordset, 'id', 'parent_id');
  }
}
?>