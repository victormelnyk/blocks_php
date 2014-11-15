<?php
class cBlocks_DbView_View extends cBlock
{
  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'params'    => $this->owner->params,
      'recordset' => $this->owner->recordset,
      'urlParams' => $this->owner->urlParamsBuild(true, true, true)
    ));
  }
}
?>