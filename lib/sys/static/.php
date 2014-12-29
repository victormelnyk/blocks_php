<?
class Blocks_Sys_Static extends Block {
  public function build() {
    return $this->getFirstExistFileData('.htm');
  }
}
?>