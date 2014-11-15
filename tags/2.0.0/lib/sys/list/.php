<?php
class cBlocks_Sys_List extends cBlock
{
  public function build()
  {
    $lData = '';

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $lData .= $this->blocks[$i]->contentGet();

    return $this->templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'data' => $lData
    ));
  }
}
?>