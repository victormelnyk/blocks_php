<?php
cPage::moduleAdd('blocks/components/helpers/page_navigator/.php');

class cBlocks_DbView_Limit extends cBlock
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

    $lLimit = $this->owner->limit;
    return templateProcess($this->fileFirstExistDataGet('.htm'), array(
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