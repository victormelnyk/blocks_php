<?php
class cBlocks_Auth_Static extends cBlock
{
  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
       $this->settings->context->toArray());
  }
}
?>