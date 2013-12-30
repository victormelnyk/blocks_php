<?php
class cBlocks_Cache_Clear extends cBlock
{
  public function build()
  {
    return $this->fileFirstExistDataGet('.htm');
  }

  protected function init()
  {
    parent::init();

    $lIsClear = false;
    paramPostGetGetCheck('cache_clear', VAR_TYPE_BOOLEAN, $lIsClear);

    if ($lIsClear)
    {
      removeDir($this->appDirGet().$this->cache->cacheDir, false);

      $this->initScriptAdd('page.logger.log("Кеш очищено")');//!!ML
    }
  }
}
?>