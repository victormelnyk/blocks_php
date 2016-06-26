<?
class Blocks_PagesView_Build extends Block {
  private $recordset = array();

  public function build() {
    return $this->processTemplate($this->getFirstExistFileData('.htm'), array(
      'recordset' => $this->recordset
    ));
  }

  protected function readSettings(HierarchyList $settings)
  {
    parent::readSettings($settings);

    $pages = $settings->getCurrList()->getNextByN('pages');

    for ($i = 0; $i < count($pages); $i++) {
      $page = new LinearList($pages[$i]);

      $pathParts = $this->explodeFullName($page->getNextByN('path'), 2);

      $runDir = $pathParts[0];
      $pageName = $pathParts[1];

      $value = null;

      if ($page->getCheckNextOByN('name', $value)) {
        $name = $value->getS();
      } else {
        $name = '';
      }

      if ($page->getCheckNextOByN('description', $value)) {
        $description = $value->getS();
      } else {
        $description = '';
      }

      if ($page->getCheckNextOByN('params', $value)) {
        $lParams = $value->getS();
      } else {
        $lParams = '';
      }

      if ($page->getCheckNextOByN('width', $value)) {
        $width = $value->getS();
      } else {
        $width = '100%';
      }

     if ($page->getCheckNextOByN('height', $value)) {
        $height = $value->getS();
      } else {
        $height = '200px';
      }

      $url = $this->page->getRunDir($runDir) . $pageName . '.php' .
        ($lParams ? '?' . $lParams : '');

      if (!$name) {
        $name = $url;
      }

      $this->recordset[] = array(
        'url'         => $url,
        'name'        => $name,
        'description' => $description,
        'width'       => $width,
        'height'      => $height
      );
    }
  }
}
?>