<?php
class cBlocks_PagePreview_Build extends cBlock
{
  private $recordset = array();

  public function build()
  {
    return templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'recordset' => $this->recordset
    ));
  }

  protected function settingsRead(cXmlNode $aXmlNode)
  {
    parent::settingsRead($aXmlNode);

    $lPagesNode = $aXmlNode->nodes->nextGetByN('Pages');
    while ($lPagesNode->nodes->nextGetCheck($lPageNode))
    {
      $lNames = $this->nameFullExplode($lPageNode->name, 2);

      $lRunDirName = $lNames[0];
      $lPageName   = $lNames[1];

      $lAttr = null;

      if ($lPageNode->attrs->nextGetCheckByN('Name', $lAttr))
        $lName = $lAttr->getS();
      else
        $lName = '';

      if ($lPageNode->attrs->nextGetCheckByN('Description', $lAttr))
        $lDescription = $lAttr->getS();
      else
        $lDescription = '';

      if ($lPageNode->attrs->nextGetCheckByN('Params', $lAttr))
        $lParams = $lAttr->getS();
      else
        $lParams = '';

      if ($lPageNode->attrs->nextGetCheckByN('Width', $lAttr))
        $lWidth = $lAttr->getS();
      else
        $lWidth = '100%';

     if ($lPageNode->attrs->nextGetCheckByN('Height', $lAttr))
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