<?php
class cBlocks_Sys_Languages extends cBlock
{
  private $languages = array('uk', 'ru'); //!! we need languages list on app or set level
  private $languagesEx = array();

  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
      array('languages' => $this->languagesEx));
  }

  public function init()
  {
    parent::init();

    $lUrl = $_SERVER['PHP_SELF'].'?';
    $lParams = explode('&', count($_SERVER['argv']) ? $_SERVER['argv'][0] : '');

    for($i = 0, $l = count($lParams); $i < $l; $i++)
    {
      $lParam = $lParams[$i];

      if (strpos($lParam, 'l=') === false)
        $lUrl .= $lParam.'&';
    }

    $lUrl .= 'l=';
    for($i = 0, $l = count($this->languages); $i < $l; $i++)
    {
      $lLanguage = $this->languages[$i];

      $this->languagesEx[] = array(
        'code'     => $lLanguage,
        'isActive' => ($lLanguage == $this->page->language),
        'url'      => $lUrl.$lLanguage
      );
    }
  }
}
?>