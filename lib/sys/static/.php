<?php
class cBlocks_Sys_Static extends cBlock
{
  public function build()
  {
    return $this->fileFirstExistDataGet('.htm');
  }
}
?>