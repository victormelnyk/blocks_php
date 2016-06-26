<?
class Blocks_Sys_List extends Block
{
  public function build()
  {
    $lData = '';

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $lData .= $this->blocks[$i]->getContent();

    return $this->processTemplate($this->getFirstExistFileData('.htm'), array(
      'data' => $lData
    ));
  }
}
?>