<?php
class cBlocks_DbView_Order extends cBlock
{
  public function build()
  {
    if ($this->owner->filter->isEmpty)
    {
      //!for cache
      if (!$this->cache->isValid)
        $this->fileFirstExistDataGet('.htm');

      return '';
    }

    $lOrder = $this->owner->order;
    return templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'paramName'          => $lOrder->paramName,
      'directionParamName' => $lOrder->directionParamName,
      'options'            => $lOrder->asArrayGet(),

      'currentOptionName'  => $lOrder->currentOptionName,
      'currentOptionTitle' => $lOrder->currentOptionTitle,
      'isDesc'             => $lOrder->isDesc,

      'urlParams'          => $this->owner->urlParamsBuild(true, false, true)
    ));
  }
}
?>