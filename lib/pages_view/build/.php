<?php
class cBlocks_PagesView_Build extends cBlock
{
  private $recordset = array();

  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'recordset' => $this->recordset
    ));
  }

  protected function settingsRead(cXmlNode $aXmlNode)
  {
    parent::settingsRead($aXmlNode);

    $lPagesNode = $aXmlNode->nodes->getNextByN('Pages');
    while ($lPagesNode->nodes->getCheckNext($lPageNode))
    {
      $lNames = $this->nameFullExplode($lPageNode->name, 2);

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

      $lUrl = $this->page->runDirGet($lRunDirName).$lPageName.'.php'.
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