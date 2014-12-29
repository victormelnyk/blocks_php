<?
class Blocks_Auth_Static extends Block
{
  public function build()
  {
    return $this->templateProcess($this->getFirstExistFileData('.htm'),
       $this->settings->context->toArray());
  }
}
?>