<?
class Blocks_Sys_NamedList extends Block
{
  public function build()
  {
    $lValues = array();

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
    {
      $lBlock = $this->blocks[$i];
      $lValues[$lBlock->name] = $lBlock->getContent();
    }

    return $this->templateProcess($this->getFirstExistFileData('.htm'),
      $lValues);
  }
}
?>