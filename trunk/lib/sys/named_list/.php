<?php
class cBlocks_Sys_NamedList extends cBlock
{
  public function build()
  {
    $lValues = array();

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
    {
      $lBlock = $this->blocks[$i];
      $lValues[$lBlock->name] = $lBlock->contentGet();
    }

    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
      $lValues);
  }
}
?>