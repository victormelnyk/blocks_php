<?
class Blocks_DbView_Filter extends Block
{
  public function build()
  {
    return $this->templateProcess($this->getFirstExistFileData('.htm'), array(
      'options'   => $this->owner->filter->asArrayGet(),
      'urlParams' => $this->owner->urlParamsBuild(true, true, true)
    ));
  }
}
?>