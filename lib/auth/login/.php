<?
abstract class Blocks_Auth_Login extends Block
{
  public function build()
  {
    return $this->processTemplate($this->getFirstExistFileData('.htm'),
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
    $this->addInitScript('page.logger.error("'.
      $this->getMlTagValue('Error').'");');
  }

  protected function onSuccess()
  {
  }
}
?>