<?php
cPage::moduleAdd('blocks/lib/db_view/controler/.php');
cPage::moduleAdd('blocks/comp/helpers/recordset_tree_adaptation/.php');

class cBlocks_DbView_ControlerTree extends cBlocks_DbView_Controler
{
  protected function recordsetGet()
  {
    parent::recordsetGet();

    cRecordsetTreeAdaptationHelper::process($this->recordset, 'id', 'parent_id');
  }
}
?>