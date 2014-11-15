<?php
abstract class cBlocks_Auth_Login extends cBlock
{
  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
       $this->settings->context->toArray());
  }

  protected function init()
  {
    parent::init();

    if ($this->settings->context->isLogged)
      $this->onSuccess();
    else
    if ($this->settings->context->isTryToLogin)
      $this->onError();
  }

  protected function onError()
  {
    $this->initScriptAdd('page.logger.error("'.
      $this->localizationTagValueGet('Error').'");');
  }

  protected function onSuccess()
  {
  }
}
?>