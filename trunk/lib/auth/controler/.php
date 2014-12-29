<?
Page::addModule('blocks/lib/db_view/controler/.php');

class Blocks_Auth_Controler extends Blocks_DbView_Controler
{
  public function build()
  {
    if ($this->settings->context->isLogged)
      return parent::build();
    else
      return '';
  }

  protected function init()
  {
    if ($this->settings->context->isLogged)
      parent::init();
  }
}
?>