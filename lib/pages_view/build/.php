<?
class Blocks_PagesView_Build extends Block
{
  private $recordset = array();

  public function build()
  {
    return $this->templateProcess($this->getFirstExistFileData('.htm'), array(
      'recordset' => $this->recordset
    ));
  }

  protected function readSettings(cXmlNode $aXmlNode)
  {
    parent::readSettings($aXmlNode);

    $lPagesNode = $aXmlNode->nodes->getNextByN('Pages');
    while ($lPagesNode->nodes->getCheckNext($lPageNode))
    {
      $lNames = $this->explodeFullName($lPageNode->name, 2);

      $lRunDirName = $lNames[0];
      $lPageName   = $lNames[1];

      $lAttr = null;

      if ($lPageNode->attrs->getCheckNextByN('Name', $lAttr))
        $lName = $lAttr->getS();
      else
        $lName = '';

      if ($lPageNode->attrs->getCheckNextByN('Description', $lAttr))
        $lDescription = $lAttr->getS();
      else
        $lDescription = '';

      if ($lPageNode->attrs->getCheckNextByN('Params', $lAttr))
        $lParams = $lAttr->getS();
      else
        $lParams = '';

      if ($lPageNode->attrs->getCheckNextByN('Width', $lAttr))
        $lWidth = $lAttr->getS();
      else
        $lWidth = '100%';

     if ($lPageNode->attrs->getCheckNextByN('Height', $lAttr))
        $lHeight = $lAttr->getS();
      else
        $lHeight = '200px';

      $lUrl = $this->page->getRunDir($lRunDirName).$lPageName.'.php'.
        ($lParams ? '?'.$lParams : '');

      if (!$lName)
        $lName = $lUrl;

      $this->recordset[] = array(
        'url'         => $lUrl,
        'name'        => $lName,
        'description' => $lDescription,
        'width'       => $lWidth,
        'height'      => $lHeight
      );
    }
  }
}
?>