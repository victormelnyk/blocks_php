<?
class Blocks_Cache_Clear extends Block
{
  public function build()
  {
    return $this->getFirstExistFileData('.htm');
  }

  protected function init()
  {
    parent::init();

    $lIsClear = false;
    paramPostGetGetCheck('cache_clear', V_BOOLEAN, $lIsClear);

    if ($lIsClear)
    {
      removeDir($this->getAppDir().$this->cache->cacheDir, false);

      $this->addInitScript('page.logger.log("Кеш очищено")');//!!ML
    }
  }
}
?>