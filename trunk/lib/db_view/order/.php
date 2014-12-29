<?
class Blocks_DbView_Order extends Block
{
  public function build()
  {
    if ($this->owner->filter->isEmpty)
    {
      //!for cache
      if (!$this->cache->isValid)
        $this->getFirstExistFileData('.htm');

      return '';
    }

    $lOrder = $this->owner->order;
    return $this->templateProcess($this->getFirstExistFileData('.htm'), array(
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