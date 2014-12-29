<?
class Blocks_DbView_View extends Block
{
  public function build()
  {
    return $this->templateProcess($this->getFirstExistFileData('.htm'), array(
      'params'    => $this->owner->params,
      'recordset' => $this->owner->recordset,
      'urlParams' => $this->owner->urlParamsBuild(true, true, true)
    ));
  }
}
?>