<?php
class cBlocks_Sys_Languages extends cBlock
{
  private $languages = array('uk', 'ru', 'en'); //!! we need languages list on app or set level
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
    $lParams = array();/*!! do not work on real server
    explode('&', count($_SERVER['argv']) ? $_SERVER['argv'][0] : '');
    */
    for($i = 0, $l = count($lParams); $i < $l; $i++)
    {
      $lParam = $lParams[$i];

      if (strpos($lParam, 'l=') === false)
        $lUrl .= $lParam.'&';
    }

    $lUrl .= 'l=';
    for($i = 0, $l = count($this->languages); $i < $l; $i++)
    {
      $lLanguageCode = $this->languages[$i];

      $this->languagesEx[] = array(
        'code'     => $lLanguageCode,
        'isActive' => ($lLanguageCode == $this->page->language),
        'url'      => $lUrl.$lLanguageCode,
        'title'    => $this->tags->getByN('Title_' . $lLanguageCode)
      );
    }
  }
}
?>