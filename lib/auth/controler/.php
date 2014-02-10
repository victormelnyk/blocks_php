<?php
cPage::moduleAdd('blocks/lib/db_view/controler/.php');

class cBlocks_Auth_Controler extends cBlocks_DbView_Controler
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
      return parent::init();
  }
}
?>