<?php
class cBlocks_DbView_Filter extends cBlock
{
  public function build()
  {
    return templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'options'   => $this->owner->filter->asArrayGet(),
      'urlParams' => $this->owner->urlParamsBuild(true, true, true)
    ));
  }
}
?>