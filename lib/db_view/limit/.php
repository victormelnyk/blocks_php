<?
Page::addModule('blocks/comp/helpers/page_navigator/.php');

class Blocks_DbView_Limit extends Block
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

    $lLimit = $this->owner->limit;
    return $this->processTemplate($this->getFirstExistFileData('.htm'), array(
      'paramName'          => $lLimit->paramName,
      'pageNoParamName'    => $lLimit->pageNoParamName,
      'options'            => $lLimit->asArrayGet(),

      'currentOptionName'  => $lLimit->currentOptionName,
      'currentOptionTitle' => $lLimit->currentOptionTitle,
      'currentOptionValue' => $lLimit->currentOptionValue,
      'pageNo'             => $lLimit->pageNo,

      'recordCount'        => $this->owner->recordCountGet(),
      'urlParams'          => $this->owner->urlParamsBuild(true, true, false)
    ));
  }
}
?>