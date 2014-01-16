<?php
//!uses
//SimpleXML

//!
//SetNameFull = AppName.SetName
//PageNameFull = AppName.SetName.PageName
//ComponentNameFull = AppName.LibraryName.ComponentName
//SetBlockNameFull = AppName.SetName.BlockName
//BlockNameFull = AppName.SetName.PageName.BlockName

//!
//PageParams = array(
//'cacheDir' -> 'cache/',
//'rootDir'  -> '../',
//'db'       -> null
//)

class cPageSettings
{
  public $rootDir = '';

  public $db      = null;
  public $context = null;

  public $isCache   = true;
  public $isProfile = false;
  public $isTest    = false;

  public $onErrorFunction = '';
  public $onTemplateErrorFunction = '';

  public function __construct()
  {
    session_start();
  }
  /*
    $this->rootDir = arrayValueGetTyped($aPageParams, 'rootDir',
      VAR_TYPE_STRING);

    $this->runDir = $this->rootDir.substr(dirname($_SERVER['SCRIPT_NAME']), 1).
      '/';

    if (!arrayValueGetCheckTyped($aPageParams, 'cacheDir', VAR_TYPE_STRING,
      $this->cacheDir))
      $this->cacheDir = 'cache/';

    $this->db = isset($aPageParams['db']) ? $aPageParams['db'] : null;

    arrayValueGetCheckTyped($aPageParams, 'isTest', VAR_TYPE_BOOLEAN,
      $this->isTest);

    if ($this->isTest)
    {
      if (!paramPostGetSessionGetCheck('is_cache', VAR_TYPE_BOOLEAN,
        $this->isCache))
        $this->isCache = false;

      paramPostGetSessionGetCheck('is_profile', VAR_TYPE_BOOLEAN,
        $this->isProfile);

      if ($this->isProfile)
      {
        require_once($this->rootDir.'blocks/components/helpers/profiler/.php');
        cPFHelper::init($this->rootDir);
      }
    }

    if (arrayValueGetCheckTyped($aPageParams, 'onErrorFunction',
      VAR_TYPE_STRING, $this->onErrorFunction))
      eAssert(function_exists($this->onErrorFunction),
        'onErrorFunction with name: "'.$this->onErrorFunction.'" do not exsist');

    if (arrayValueGetCheckTyped($aPageParams, 'onTemplateErrorFunction',
      VAR_TYPE_STRING, $this->onTemplateErrorFunction))
      eAssert(function_exists($this->onTemplateErrorFunction),
        'onTemplateErrorFunction with name: "'.$this->onTemplateErrorFunction.
        '" do not exsist');
  }
  */
}

class cCache
{
  public $cacheDir = '';

  public $data     = array();
  public $isActive = false;
  public $isValid  = false;

  public function __construct($aCacheDir, $aIsActive)
  {
    $this->cacheDir = $aCacheDir;
    $this->isActive = $aIsActive;

    if ($this->isActive)
      $this->load();
  }

  public function save()
  {
    stringToFile(serialize($this->data), $this->cacheDir.'.blc');
  }

  public function saveToFile($aValue, $aRelativeFlp)
  {
    stringToFile($aValue, $this->cacheDir.$aRelativeFlp);
    return $this->cacheDir.$aRelativeFlp;
  }

  private function load()
  {
    if (file_exists($this->cacheDir.'.blc'))
    {
      $this->isValid = true;
      $this->data = unserialize($this->loadFromFile('.blc'));
    }
  }

  public function loadFromFile($aRelativeFlp)
  {
    return fileToString($this->cacheDir.$aRelativeFlp);
  }
}

class cParams
{
  public $blocks = null;
  public $sys = null;

  public function __construct()
  {
    $this->sys = new cNameValueLinearNamedIndexedList(
      cNamedList::DUPLICATION_TYPE_ERROR);
    $this->blocks = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->load();
  }

  private function load()
  {
    $lParams = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;

    $lBlockParams = null;
    $lBlockParamsList = array();

    foreach ($lParams as $lName => $lValue)
    {
      if ($lName == 'b')
      {
        $lBlockNames = explode(',', $lValue);
        $lBlockParamsList = array();

        for ($i = 0, $l = count($lBlockNames); $i < $l; $i++)
        {
          $lBlockName = $lBlockNames[$i];

          if (!$this->blocks->getCheck($lBlockName, $lBlockParams))
            $lBlockParams = $this->blocks->add($lBlockName,
              new cNameValueLinearNamedIndexedList(
                cNamedList::DUPLICATION_TYPE_ERROR));

          $lBlockParamsList[$i] = $lBlockParams;
        }
        eAssert(count($lBlockParamsList));
      }
      else
      {
        $lParam = new cNameValueObject($lName, $lValue);
        if (count($lBlockParamsList))
          for ($i = 0, $l = count($lBlockParamsList); $i < $l; $i++)
            $lBlockParamsList[$i]->addNameValueObject($lParam);
        else
          $this->sys->addNameValueObject($lParam);
      }
    }
  }
}

class cWebFile
{
  public $isNotCollect = true;
  public $relativeFlp  = '';

  public function __construct($aRelativeFlp, $aIsNotCollect)
  {
    $this->relativeFlp  = $aRelativeFlp;
    $this->isNotCollect = $aIsNotCollect;
  }
}

abstract class cContext
{
  protected $page = null;

  public function pageSet(cPage $aPage)
  {
    $this->page = $aPage;
  }

  public function settingsReadPage(cXmlNode $aXmlNode)
  {
  }

  public function settingsReadSet(cXmlNode $aXmlNode)
  {
  }

  public function validate()
  {
    return false;
  }
}

abstract class cMetaData
{ //!FileLevels
  const LEVEL_SET      = 's';
  const LEVEL_PAGE     = 'p';
  const LEVEL_BLOCK    = 'b';
  const LEVEL_EXTERNAL = 'e';
  const LEVEL_WEB      = 'w';

  private $configXmls   = array();
  private $fileDatas    = array();
  private $fileFlps     = array();//!! -> fileNames, FilePath
  private $fileFlpsList = array();

  protected $settingsXmlNode = null;

  protected $scripts = null;
  protected $styles  = null;

  public $filesByMl = array(); //!! ->LocalizationFiles
  public $tagsMl = Null;
  public $tags = Null;

  public $initScript = '';
  public $workDirs   = array();

  public $appName = '';
  public $name    = '';

  public $cache    = null;
  public $settings = null;

  public $page = null;

  public function __construct($aAppName, $aName, cPage $aPage, cCache $aCache,
    cPageSettings $aSettings)
  {
    $this->scripts = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);
    $this->styles  = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->tagsMl = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);
    $this->tags   = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->appName  = $aAppName;
    $this->name     = $aName;
    $this->page     = $aPage;
    $this->cache    = $aCache;
    $this->settings = $aSettings;
  }

  public function appDirGet($aAppName = '')
  {
    return $this->settings->rootDir.($aAppName ? $aAppName : $this->appName).'/';
  }

  protected function configCheck($aWorkDir, $aRootNodeName)
  {
    $lFlp = $aWorkDir.'.xml';
    $this->workDirs[] = $aWorkDir;

    if (!file_exists($lFlp))
      return null;

    $lXmlDocument = new cXmlDocument();
    $lXmlDocument->loadFromFile($lFlp);
    $lXmlDocument->rootNodeNameCheckAssert($aRootNodeName);
    array_unshift($this->configXmls, $lXmlDocument);
    return $lXmlDocument;
  }

  protected function configRead()
  {
    if (!count($this->configXmls))
      return;

    $lXmlDocument = $this->configXmls[0];

    for ($i = 1, $l = count($this->configXmls); $i < $l; $i++)
      $lXmlDocument->update($this->configXmls[$i]);

    $lXmlDocument->nodes->positionClear();

    if ($lXmlDocument->nodes->nextGetCheckByN('Inherited', $lInheritedNode))
      $lXmlDocument->nodes->currDeleteByN('Inherited');

    if ($lXmlDocument->nodes->nextGetCheckByN('Localization',
      $lLocalizationNode))
    {
      if ($lLocalizationNode->nodes->nextGetCheckByN('FilesByMl',
        $lFilesByMlNode))
        while ($lFilesByMlNode->nodes->nextGetCheckByN('F', $lFileByMlNode))
          $this->filesByMl[$lFileByMlNode->getS()] = true;

      if ($lLocalizationNode->nodes->nextGetCheckByN('Tags', $lTags))
        while ($lTags->nodes->nextGetCheck($lTag))
        {
          while ($lTag->nodes->nextGetCheck($lValueMl))
          {
            if (!$this->tagsMl->getCheck($lValueMl->name, $lValuesMl))
            {
              $lValuesMl = new cNamedIndexedList(//!! do not create
                cNamedList::DUPLICATION_TYPE_ERROR);
              $this->tagsMl->add($lValueMl->name, $lValuesMl);
            }
            $lValuesMl->add($lTag->name, $lValueMl->getS());
          }
        }

      $lXmlDocument->nodes->currDeleteByN('Localization');
    }

    if ($lXmlDocument->nodes->nextGetCheckByN('Tags', $lTags))
    {
      while ($lTags->nodes->nextGetCheck($lTag))
        $this->tags->add($lTag->name, $lTag->getS());
      $lXmlDocument->nodes->currDeleteByN('Tags');
    }

    if ($lXmlDocument->nodes->count() || $lXmlDocument->attrs->count())
    {
      $lXmlString = $lXmlDocument->saveToString();
      $lXmlString = $this->stringTagsProcess($lXmlString, array());
      $lXmlDocument = new cXmlDocument();
      $lXmlDocument->loadFromString($lXmlString);

      $this->configReadInternal($lXmlDocument);
    }

    $lXmlDocument->allReadAsser();
  }

  protected function configReadInternal(cXmlDocument $aXmlDocument)
  {
    if ($aXmlDocument->nodes->nextGetCheckByN('Styles', $lStylesNode))
      while ($lStylesNode->nodes->nextGetCheckByN('Style', $lStyleNode))
      {
        $lRelativeFlp = $lStyleNode->getS();
        if ($lStyleNode->attrs->nextGetCheckByN('IsNotCollect',
          $lAttrIsNotCollect))
          $lIsNotCollect = $lAttrIsNotCollect->getB();
        else
          $lIsNotCollect = false;
        $this->styles->add($lRelativeFlp, new cWebFile($lRelativeFlp,
          $lIsNotCollect));
      }

    if ($aXmlDocument->nodes->nextGetCheckByN('Scripts', $lScriptsNode))
      while ($lScriptsNode->nodes->nextGetCheckByN('Script', $lScriptNode))
      {
        $lRelativeFlp = $lScriptNode->getS();
        if ($lScriptNode->attrs->nextGetCheckByN('IsNotCollect',
          $lAttrIsNotCollect))
          $lIsNotCollect = $lAttrIsNotCollect->getB();
        else
          $lIsNotCollect = false;
        $this->scripts->add($lRelativeFlp, new cWebFile($lRelativeFlp,
          $lIsNotCollect));
      }

    if ($aXmlDocument->nodes->nextGetCheckByN('InitScripts', $lInitScriptsNode))
      while ($lInitScriptsNode->nodes->nextGetCheckByN('InitScript',
        $lInitScriptNode))
        $this->initScript .= mb_trim($lInitScriptNode->getS());

    if ($aXmlDocument->nodes->nextGetCheckByN('Settings', $lSettingsNode))
    {
      if (!$lSettingsNode->nodes->count() && !$lSettingsNode->attrs->count())
        return;

      $this->settingsXmlNode = $lSettingsNode;
      $aXmlDocument->nodes->currDeleteByN('Settings');
    }
  }

  private function fileDataGet($aFlp, $aIsAddToCache = true,
    array $aTagsValues = array())
  {
    if ($this->cache->isValid)
      return $this->fileDatas[$aFlp];

    $lResult = fileToString($aFlp);
    $lResult = $this->stringTagsProcess($lResult, $aTagsValues);

    if ($aIsAddToCache)
      $this->fileDatas[$aFlp] = $lResult;

    return $lResult;
  }

  private function fileDataAddToListIfExist(cNamedIndexedList $aList,
    $aRelativeFlp)
  {
    if ($this->fileFirstExistFlpGetCheck($aRelativeFlp, $lFlp))
      $aList->add($lFlp, $this->fileDataGet($lFlp, false));
  }

  public function fileExistFlpsGet($aRelativeFlp)
  {
    if ($this->cache->isValid)
      return $this->fileFlpsList[$aRelativeFlp];
    else
    {
      $lResult = array();

      for ($i = 0, $l = count($this->workDirs); $i < $l; $i++)
      {
        $lFlp = $this->workDirs[$i].$aRelativeFlp;

        if (file_exists($lFlp))
          $lResult[] = $lFlp;
      }

      $this->fileFlpsList[$aRelativeFlp] = $lResult;
      return $lResult;
    }
  }

  public function fileFirstExistDataGet($aRelativeFlp, $aIsAddToCache = true,
    array $aTagsValues = array())
  {
    return $this->fileDataGet($this->fileFirstExistFlpGet($aRelativeFlp),
      $aIsAddToCache, $aTagsValues);
  }

  public function fileFirstExistFlpGet($aRelativeFlp)
  {
    if ($this->fileFirstExistFlpGetCheckInternal($aRelativeFlp, $lFlp))
      return $lFlp;
    else
      throw new Exception('File not exist by RelativeFlp: "'.$aRelativeFlp.
        '". '.'Work DIRs: "'.implode(', ', $this->workDirs).'"');
  }

  public function fileFirstExistFlpGetCheck($aRelativeFlp, &$aFlp)
  {
    return $this->fileFirstExistFlpGetCheckInternal($aRelativeFlp, $aFlp);
  }

  private function fileFirstExistFlpGetCheckInternal($aRelativeFlp, &$aFlp)
  {
    if ($this->cache->isValid)
    {
      if (isset($this->fileFlps[$aRelativeFlp]))
      {
        $aFlp = $this->fileFlps[$aRelativeFlp];
        return true;
      }
      else
        return false;
    }
    else
    { //!!! optimize
      $lRelativeFilePaths = array();

      if (isset($this->filesByMl[$aRelativeFlp]))
      {
        $lFilePathParts = explode('.', $aRelativeFlp);
        $lFilePathPartCount = count($lFilePathParts);
        eAssert($lFilePathPartCount > 1);
        $lFileName = $lFilePathParts[$lFilePathPartCount - 1 - 1];
        $lFilePathParts[$lFilePathPartCount - 1 - 1] =
          $lFileName.'_'.$this->page->language;
        $lRelativeFilePaths[] = implode('.', $lFilePathParts);
        $lFilePathParts[$lFilePathPartCount - 1 - 1] =
          $lFileName.'_'.$this->page->defaultLanguage;
        $lRelativeFilePaths[] = implode('.', $lFilePathParts);
      }

      $lRelativeFilePaths[] = $aRelativeFlp;

      for ($i = 0, $l = count($this->workDirs); $i < $l; $i++)
      {
        for ($lRfpIndex = 0, $lRfpCount = count($lRelativeFilePaths);
          $lRfpIndex < $lRfpCount; $lRfpIndex++)
        {
          $lFlp = $this->workDirs[$i].$lRelativeFilePaths[$lRfpIndex];

          if (file_exists($lFlp))
          {
            $this->fileFlps[$aRelativeFlp] = $lFlp;
            $aFlp = $lFlp;
            return true;
          }
        }
      }
      //!!!
      return false;
    }
  }

  abstract public function fileFlpByLevelGet($aLevelFlp);

  protected function inheritedParamsGet(cXmlDocument $aXmlDocument)
  {
    $lResult = array();

    if ($aXmlDocument->nodes->nextGetCheckByN('Inherited', $lInheritedsNode))
      while ($lInheritedsNode->nodes->nextGetCheck($lInheritedNode))
        $lResult[] = $lInheritedNode->name;

    return $lResult;
  }

  protected function initScriptAdd($aInitScript)
  {
    $this->initScript .= ($this->initScript ? CRLF : '').$aInitScript;
  }

  protected function loadFromCache(array $aCacheData)
  {
    $this->fileDatas    = $aCacheData['fileDatas'];
    $this->fileFlps     = $aCacheData['fileFlps'];
    $this->fileFlpsList = $aCacheData['fileFlpsList'];
    $this->initScript   = $aCacheData['initScript'];

    if (isset($aCacheData['settingsXml']))
    {
      $this->settingsXmlNode = new cXmlDocument();
      $this->settingsXmlNode->loadFromString($aCacheData['settingsXml']);
    }
  }

  private function localizationTagValueGetCheck($aTagName, $aLanguage, &$aValue)
  {
    if ($this->tagsMl->getCheck($aLanguage, $lTagsByLanguage))
      return $lTagsByLanguage->getCheck($aTagName, $aValue);
    else
      return false;
  }

  protected function localizationTagValueGet($aTagName)
  {
    if ($this->localizationTagValueGetCheck($aTagName, $this->page->language,
      $lValue))
      return $lValue;
    else
    if ($this->localizationTagValueGetCheck($aTagName,
      $this->page->defaultLanguage, $lValue))
      return $lValue;
    else
      throw new Exception('Can not get LocalizationTagValue for Tag: "'.
        $aTagName.'" by language: "'.$this->page->language.'"');
  }

  protected function nameByLevelExplode($aLevelName, &$aName)
  {
    $lLevel = $aLevelName[0];
    eAssert($aLevelName[1] == ':', 'Invalid LevelPrefix in LevelName: "'.
      $aLevelName.'"');

    if (strlen($aLevelName) > 2)
      $aName = substr($aLevelName, 2);
    else
      $aName = '';
    return $lLevel;
  }

  protected function nameFullExplode($aNameFull, $aCount)
  {
    $lResult = explode('.', $aNameFull);
    eAssert(count($lResult) == $aCount, 'Wrong NameFull: "'.$aNameFull.
      '" for count: '.$aCount);
    return $lResult;
  }

  public function runDirGet($aRunDirName)
  {
    $lParams = explode('.', $aRunDirName);
    $lIsAbsolute = false;

    switch (count($lParams)) {
    case 1:
      $aRunDirName = $lParams[0];
      break;
    case 2:
      $aRunDirName = $lParams[0];
      eAssert($lParams[1] == 'host', 'Not supported param ' + $lParams[1]);//!!check
      $lIsAbsolute = true;
      break;
    default:
      throw new Exception('Wrong RunDirName: "'.$aRunDirName.'"');
    }

    return ($lIsAbsolute ? $_SERVER['HTTP_HOST'].'/'.
      str_replace('../', '', $this->settings->rootDir) : $this->settings->rootDir).
      ($aRunDirName ? $this->page->set->runDirs[$aRunDirName] : '');
  }

  public function saveToCache(array &$aCacheData)
  {
    $aCacheData['fileDatas']    = $this->fileDatas;
    $aCacheData['fileFlps']     = $this->fileFlps;
    $aCacheData['fileFlpsList'] = $this->fileFlpsList;
    $aCacheData['initScript']   = $this->initScript;

    if ($this->settingsXmlNode)
      $aCacheData['settingsXml'] = $this->settingsXmlNode->saveToString();
  }

  public function scriptsGet(cNamedIndexedList $aScripts,
    cNamedIndexedList $aNotCollectedScripts)
  {
    for ($i = 0, $l = $this->scripts->count(); $i < $l; $i++)
    {
      $lWebFile = $this->scripts->getByI($i);
      $lFlp = $this->fileFlpByLevelGet($lWebFile->relativeFlp);

      if ($lWebFile->isNotCollect)
        $aNotCollectedScripts->add($lFlp, $lFlp);
      else
      if (!$aScripts->exist($lFlp))//!! need to log file dublication
        $aScripts->add($lFlp, $this->fileDataGet($lFlp, false));
    }
  }

  public function scriptsDefaultGet(cNamedIndexedList $aScripts)
  {
    $this->fileDataAddToListIfExist($aScripts, 'web/.js');
  }

  protected function settingsRead(cXmlNode $aXmlNode) //!to override
  {
  }

  protected function settingsReadProcess()
  {
    if (isset($this->settingsXmlNode))
    {
      $this->settingsRead($this->settingsXmlNode);
      if (!$this->cache->isValid)
        $this->settingsXmlNode->allReadAsser();
    }
  }

  private function stringTagsProcess($aString, array $aTagsValues)
  {
    $lResult = $aString;
    $lResult = htmlspecialchars_decode($aString);//!!fix and delete

    $lTags = tagsFind($lResult);
    $lValues = array();

    for ($i = 0, $l = count($lTags); $i < $l; $i++)
    {
      $lTag = $lTags[$i];

      if (isset($aTagsValues[$lTag]))
        $lValues[] = $aTagsValues[$lTag];
      else
      {
        $lTagArray = explode('|', $lTag);

        switch (count($lTagArray)) {
        case 1:
          $lTagKey = $lTagArray[0];

          switch ($lTagKey) {
          case 'Language':
            $lValues[] = $this->page->language;
            break;
          case 'Name':
            $lValues[] = $this->name;
            break;
          default:
            throw new Exception('Not suported tag: "'.$lTag.'"');
            break;
          }

          break;
        case 2:
          $lTagKey   = $lTagArray[0];
          $lTagParam = $lTagArray[1];

          switch ($lTagKey) {
          case 'fileFlpByLevelGet'://!!Capitalized
            $lValues[] = $this->fileFlpByLevelGet($lTagParam);
            break;
          case 'workDirByLevelGet':
            $lValues[] = $this->workDirByLevelGet($lTagParam);
            break;
          case 'runDirGet':
            $lValues[] = $this->runDirGet($lTagParam);
            break;
          case 'ml':
            $lValues[] = $this->localizationTagValueGet($lTagParam);
            break;
          case 'Tag':
            $lValues[] = $this->tags->getByN($lTagParam);
            break;
          default:
            throw new Exception('Not suported tag: "'.$lTag.'"');
          }

          break;
        default:
          throw new Exception('Not suported tag: "'.$lTag.'"');
        }
      }
    }

    if (count($lTags))
      $lResult = tagsReplace($lResult, $lTags, $lValues);

    return $lResult;
  }

  public function stylesDefaultGet(cNamedIndexedList $aStyles)
  {
    $this->fileDataAddToListIfExist($aStyles, 'web/.css');
  }

  public function stylesGet(cNamedIndexedList $aStyles,
    cNamedIndexedList $aNotCollectedStyles)
  {
    for ($i = 0, $l = $this->styles->count(); $i < $l; $i++)
    {
      $lWebFile = $this->styles->getByI($i);
      $lFlp = $this->fileFlpByLevelGet($lWebFile->relativeFlp);

      if ($lWebFile->isNotCollect)
        $aNotCollectedStyles->add($lFlp, $lFlp);
      else
        $aStyles->add($lFlp, $this->fileDataGet($lFlp, false));
    }
  }

  abstract public function workDirByLevelGet($aLevelName);

  public function workDirBuild($aAppName, $aSetName, $aPageName = '',
    $aBlockName = '')
  {
    return $this->appDirGet($aAppName).'tpl/'.$aSetName.'/'.
      ($aPageName ? $aPageName.'/': '').
      ($aBlockName ? $aBlockName.'/': '');
  }
}

class cSet extends cMetaData
{
  private $appNames = array();

  public $title = '';
  public $meta = '';

  public $runDirs = array();

  public function __construct($aAppName, $aSetName, cPage $aPage)
  {
    parent::__construct($aAppName, $aSetName, $aPage, $aPage->cache,
      $aPage->settings);

    $this->initMt();
  }

  protected function configReadInternal(cXmlDocument $aXmlDocument)
  {
    parent::configReadInternal($aXmlDocument);

    if ($aXmlDocument->nodes->nextGetCheckByN('Title', $lTitleNode))
      $this->title = $lTitleNode->getS();

    if ($aXmlDocument->nodes->nextGetCheckByN('Meta', $lMetaNode))
      $this->meta = $lMetaNode->getS();

    if ($aXmlDocument->nodes->nextGetCheckByN('RunDirs', $lRunDirsNode))
      while ($lRunDirsNode->nodes->nextGetCheck($lRunDirNode))
        $this->runDirs[$lRunDirNode->name] = $lRunDirNode->getS();
  }

  public function fileExternalFirstExistFlpGet($aRelativeFlp)
  {
    foreach ($this->appNames as $lAppName)
    {
      $lFlp = $this->appDirGet($lAppName).'ext/'.$aRelativeFlp;

      if (file_exists($lFlp))
        return $lFlp;
    }

    throw new Exception('File External not exist by RelativeFlp: "'.
      $aRelativeFlp.'"'.'AppNames: "'.implode(', ', $this->appNames).'"');
  }

  public function fileFlpByLevelGet($aLevelFlp)
  {
    $lLevel = $this->nameByLevelExplode($aLevelFlp, $lFlp);
    switch ($lLevel) {
    case self::LEVEL_SET:
      return $this->fileFirstExistFlpGet($lFlp);
    case self::LEVEL_PAGE:
      return $this->page->fileFirstExistFlpGet($lFlp);
    case self::LEVEL_EXTERNAL:
      return $this->fileExternalFirstExistFlpGet($lFlp);
    case self::LEVEL_WEB:
      return $lFlp;
    default:
      throw new Exception('Not supported Level: "'.$lLevel.'" in LevelFlp: "'.
        $aLevelFlp.'"');
    }
  }

  private function initMt()
  {
    if ($this->cache->isValid)
      $this->loadFromCache($this->cache->data['set']);
    else
    {
      $this->initMtInternal($this->appName.'.'.$this->name);
      $this->initMtInternal('blocks.sys');
      $this->configRead();
    }

    $this->settingsReadProcess();
  }

  private function initMtInternal($aSetNameFull)
  {
    $lNames = $this->nameFullExplode($aSetNameFull, 2);
    $lAppName = $lNames[0];
    $this->appNames[$lAppName] = $lAppName;
    $lWorkDir = $this->workDirBuild($lAppName, $lNames[1]);

    $lXmlDocument = $this->configCheck($lWorkDir, 'Set');

    if (!$lXmlDocument)
      return;

    $lInheritedParams = $this->inheritedParamsGet($lXmlDocument);

    for ($i = 0, $l = count($lInheritedParams); $i < $l; $i++)
      $this->initMtInternal($lInheritedParams[$i]);
  }

  protected function loadFromCache(array $aCacheData)
  {
    parent::loadFromCache($aCacheData);
    $this->appNames = $aCacheData['appNames'];
    $this->runDirs  = $aCacheData['runDirs'];
  }

  public function saveToCache(array &$aCacheData)
  {
    parent::saveToCache($aCacheData);
    $aCacheData['appNames'] = $this->appNames;
    $aCacheData['runDirs']  = $this->runDirs;
  }

  protected function settingsRead(cXmlNode $aXmlNode)
  {
    parent::settingsRead($aXmlNode);

    if ($this->settings->context)
      $this->settings->context->settingsReadSet($aXmlNode);
  }

  public function workDirByLevelGet($aLevelName)
  {
    $lLevel = $this->nameByLevelExplode($aLevelName, $lName);
    switch ($this->nameByLevelExplode($aLevelName, $lName)) {
    case self::LEVEL_SET:
      if ($lName == '')
      {
        $lAppName = $this->appName;
        $lSetName = $this->name;
      }
      else
      {
        $lNames = $this->nameFullExplode($lName, 2);

        $lAppName = $lNames[0];
        $lSetName = $lNames[1];
      }

      return $this->workDirBuild($lAppName, $lSetName);
    case self::LEVEL_PAGE:
      if ($lName == '')
      {
        $lAppName  = $this->appName;
        $lSetName  = $this->name;
        $lPageName = $this->page->name;
      }
      else
      {
        $lNames = $this->nameFullExplode($lName, 3);

        $lAppName  = $lNames[0];
        $lSetName  = $lNames[1];
        $lPageName = $lNames[2];
      }

      return $this->workDirBuild($lAppName, $lSetName, $lPageName);
    default:
      throw new Exception('Not supported Level: "'.$lLevel.
        '" in LevelName: "'.$aLevelName.'"');
    }
  }
}

class cPage extends cMetaData
{
  private static $modules = array();
  private static $settingsInstance = null;

  protected static $instance = null;

  private $blocksAll   = null;
  private $blocksInfos = array();

  public $title = '';
  public $meta = '';

  public $defaultLanguage = '';
  public $language = '';

  public $blocks      = array();
  public $params      = null;
  public $set         = null;

  public function __construct($aSetNameFull)
  {
    $lNames = $this->nameFullExplode($aSetNameFull, 2);
    $lAppName = $lNames[0];
    $lSetName = $lNames[1];
    $lPageName = basename($_SERVER['SCRIPT_NAME'], '.php');
    $lSettings = self::settingsGet();

    if (paramPostGetGetCheck('l', VAR_TYPE_STRING, $this->language))//!!use params logic
      $_SESSION['language'] = $this->language;
    else
    if (!paramSessionGetCheck('language', VAR_TYPE_STRING, $this->language))
      $this->language = $this->defaultLanguage;

    $lCache = new cCache($lSettings->rootDir.$lAppName.'/tmp/cache/'.
      $lSetName.'/'.$lPageName.'/', $lSettings->isCache);

    parent::__construct($lAppName, $lPageName, $this, $lCache, $lSettings);

    $this->blocksAll = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->set = $this->setCreate($lAppName, $lSetName, $this);

    if ($this->settings->context)
      $this->settings->context->pageSet($this);

    $this->params = new cParams();

    $this->initMt();
  }

  private function blockAdd($aBlockName, $aComponentNameFull,
    $aParentBlockName)
  {
    $lNames = $this->nameFullExplode($aComponentNameFull, 3);

    $lAppName       = $lNames[0];
    $lLibraryName   = $lNames[1];
    $lComponentName = $lNames[2];

    $lModuleFlp = $lAppName.'/lib/'.$lLibraryName.'/'.$lComponentName.'/'.
      '.php';

    $this->moduleAdd($lModuleFlp);

    $lClass = 'c'.strToCapitalize($lAppName).'_'.
      strToCapitalize($lLibraryName).'_'.strToCapitalize($lComponentName);

    $lBlock = new $lClass($aBlockName, $aComponentNameFull, $this);

    $this->blocksAll->add($aBlockName, $lBlock);

    if ($aParentBlockName)
    {
      $lParent = $this->blocksAll->getByN($aParentBlockName);
      $lParent->blocks[] = $lBlock;
      $lBlock->owner = $lParent;
    }
    else
      $this->blocks[] = $lBlock;
  }

  private function blocksAdd()
  {
    for ($i = 0, $l = count($this->blocksInfos); $i < $l; $i++)
    {
      $lBlocksInfo = $this->blocksInfos[$i];
      $this->blockAdd(
        $lBlocksInfo['BlockName'],
        $lBlocksInfo['ComponentName'],
        $lBlocksInfo['ParentBlockName']
      );
    }
  }

  protected function build()
  {
    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->initRecursive();

    $lBody = '';
    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $lBody .= $this->blocks[$i]->contentGet();

    if ($this->cache->isValid)
      $lResult = $this->fileFirstExistDataGet('.htm');
    else
    {
      $lResult = $this->fileFirstExistDataGet('.htm', true, array(
        'meta' => $this->set->meta.$this->meta,
        'title'=> $this->set->title.$this->title,
        'css'  => $this->buildStyles(),
        'js'   => $this->buildScripts()
      ));
      $this->saveAllToCache();
    }

    return $this->templateProcess($lResult, array(
      'body'   => $lBody,
      'initJs' => $this->buildInitScripts()
    ));
  }

  protected function buildByBlockNames(array $aBlockNames)
  {
    for ($i = 0, $l = count($aBlockNames); $i < $l; $i++)
      $this->blocksAll->getByN($aBlockNames[$i])->initRecursive();

    $lResult = array();
    $lBlocks = array();

    for ($i = 0, $l = count($aBlockNames); $i < $l; $i++)
    {
      //!! add raise when block names has parent and children block names
      $lBlock = array();
      $lBlockName = $aBlockNames[$i];
      $lBlock['data'] = $this->blocksAll->getByN($lBlockName)->contentGet();

      $lInitScripts = array();
      $this->blocksAll->getByN($lBlockName)->
        initScriptsGetRecursive($lInitScripts);
      if (count($lInitScripts))
        $lBlock['initJs'] = implode(CRLF, $lInitScripts);

      $lBlocks[$lBlockName] = $lBlock;
    }

    $lResult['blocks'] = $lBlocks;
    //!! add $this->saveAllToCache();
    return json_encode($lResult);
  }

  private function buildInitScripts()
  {
    $lResult = array();

    if ($this->set->initScript)
      $lResult[] = $this->set->initScript;
    if ($this->initScript)
      $lResult[] = $this->initScript;

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->initScriptsGetRecursive($lResult);

    return implode(CRLF, $lResult);
  }

  private function buildScripts()
  {
    $lScripts = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);
    $lNotCollectedScripts = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->set->scriptsDefaultGet($lScripts);
    $this->set->scriptsGet($lScripts, $lNotCollectedScripts);
    $this->scriptsDefaultGet($lScripts);
    $this->scriptsGet($lScripts, $lNotCollectedScripts);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->scriptsGetRecursive($lScripts, $lNotCollectedScripts);

    $lResult = '';
    $lPrefix = '<script language="JavaScript" type="text/javascript" src="';
    $lSuffix = '"></script>';

    for ($i = 0, $l = $lNotCollectedScripts->count(); $i < $l; $i++)
      $lResult .= $lPrefix.$lNotCollectedScripts->getByI($i).$lSuffix;

    if ($lScripts->count())
    {
      $lFlp = $this->cache->saveToFile($lScripts->valuesToSectionString(CRLF),
        'script.js');

      $lResult .= $lPrefix.$lFlp.$lSuffix;
    }

    return $lResult;
  }

  private function buildStyles()
  {
    $lStyles = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);
    $lNotCollectedStyles = new cNamedIndexedList(cNamedList::DUPLICATION_TYPE_ERROR);

    $this->set->stylesDefaultGet($lStyles);
    $this->set->stylesGet($lStyles, $lNotCollectedStyles);
    $this->stylesDefaultGet($lStyles);
    $this->stylesGet($lStyles, $lNotCollectedStyles);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->stylesGetRecursive($lStyles, $lNotCollectedStyles);

    $lResult = '';
    $lPrefix = '<link rel="stylesheet" type="text/css" href="';
    $lSuffix = '">';

    for ($i = 0, $l = $lNotCollectedStyles->count(); $i < $l; $i++)
      $lResult .= $lPrefix.$lNotCollectedStyles->getByI($i).$lSuffix;

    if ($lStyles->count())
    {
      $lFlp = $this->cache->saveToFile($lStyles->valuesToSectionString(CRLF),
        'styles.css');

      $lResult .= $lPrefix.$lFlp. $lSuffix;
    }

    return $lResult;
  }

  protected function configReadInternal(cXmlDocument $aXmlDocument)
  {
    parent::configReadInternal($aXmlDocument);

    if ($aXmlDocument->nodes->nextGetCheckByN('Title', $lTitleNode))
      $this->title = $lTitleNode->getS();

    if ($aXmlDocument->nodes->nextGetCheckByN('Meta', $lMetaNode))
      $this->meta = $lMetaNode->getS();

    if ($aXmlDocument->nodes->nextGetCheckByN('DefaultLanguage',
      $lDefaultLanguageNode))
      $this->defaultLanguage = $lDefaultLanguageNode->getS();

    if ($aXmlDocument->nodes->nextGetCheckByN('Blocks', $lBlocksNode))
      while ($lBlocksNode->nodes->nextGetCheck($lBlockNode))
      {
        $lBlockName     = $lBlockNode->name;
        $lComponentName = $lBlockNode->attrs->nextGetByN('Component')->getS();

        if ($lBlockNode->attrs->nextGetCheckByN('Parent',
          $lParentBlockNameAttr))
          $lParentBlockName = $lParentBlockNameAttr->getS();
        else
          $lParentBlockName = '';

        $this->blocksInfos[] = array(
          'BlockName'       => $lBlockName,
          'ComponentName'   => $lComponentName,
          'ParentBlockName' => $lParentBlockName
        );
      }
  }

  public function fileFlpByLevelGet($aLevelFlp)
  {
    $lLevel = $this->nameByLevelExplode($aLevelFlp, $lFlp);
    switch ($lLevel)
    {
    case self::LEVEL_SET:
      return $this->set->fileFirstExistFlpGet($lFlp);
    case self::LEVEL_PAGE:
      return $this->fileFirstExistFlpGet($lFlp);
    case self::LEVEL_EXTERNAL:
      return $this->set->fileExternalFirstExistFlpGet($lFlp);
    case self::LEVEL_WEB:
      return $lFlp;
    default:
      throw new Exception('Not supported Level: "'.$lLevel.'" in LevelFlp: "'.
        $aLevelFlp.'"');
    }
  }

  private function initMt()
  {
    if ($this->cache->isValid)
      $this->loadFromCache($this->cache->data['page']);
    else
    {
      $this->initMtInternal($this->appName.'.'.$this->set->name.'.'.$this->name);
      $this->initMtInternal('blocks.sys.page');
      $this->configRead();
    }

    $this->settingsReadProcess();

    $this->blocksAdd();
  }

  private function initMtInternal($aPageNameFull)
  {
    $lNames = $this->nameFullExplode($aPageNameFull, 3);

    $lWorkDir = $this->workDirBuild($lNames[0], $lNames[1], $lNames[2]);

    $lXmlDocument = $this->configCheck($lWorkDir, 'Page');

    if (!$lXmlDocument)
      return;

    $lInheritedParams = $this->inheritedParamsGet($lXmlDocument);

    for ($i = 0, $l = count($lInheritedParams); $i < $l; $i++)
      $this->initMtInternal($lInheritedParams[$i]);
  }

  public static function instanceCreate($aSetNameFull)
  {
    eAssert(!isset(self::$instance), 'Page instance created');
    $lClassName = get_called_class();
    self::$instance = new $lClassName($aSetNameFull);
  }

  public static function instanceGet()
  {
    eAssert(isset(self::$instance), 'Page instance not created');
    return self::$instance;
  }

  public static function instanceProcess($aSetNameFull)
  {
//!!    try
//    {
      self::instanceCreate($aSetNameFull);
      $lInstance = self::instanceGet();
      $lInstance->process();
/*!!    }
    catch (Exception $e)
    {
      $lFunction = self::settingsGet()->onErrorFunction;
      if ($lFunction)
        p($lFunction($e));
      else
        exceptionShow($e);
    }*/
  }

  protected function loadFromCache(array $aCacheData)
  {
    parent::loadFromCache($aCacheData);
    $this->blocksInfos = $aCacheData['blocksInfos'];
  }

  public static function moduleAdd($aRelativeFlp)
  {
    self::moduleAddDirect(self::settingsGet()->rootDir.$aRelativeFlp);
  }

  public static function moduleAddDirect($aFlp)
  {
    if (array_key_exists($aFlp, self::$modules))
      self::$modules[$aFlp] = self::$modules[$aFlp] + 1;
    else
    {
      require_once($aFlp);
      self::$modules[$aFlp] = 1;
    }
  }

  public function process()
  {
    if ($this->settings->context && !$this->settings->context->validate())
      return;

    if (paramPostGetGetCheck('blocks', VAR_TYPE_STRING, $lParam))//!!
      $lResult = $this->buildByBlockNames(explode(',', $lParam));
    else
    {
      if ($this->settings->isTest)
        $this->initScriptAdd('if (!window.page) page = {}; page.isTestMode = true;');//!!

      $lResult = $this->build();
    }

    if ($this->settings->isProfile)
      cPFHelper::stop();

    p($lResult);
  }

  private function saveAllToCache()
  {
    $this->cache->data['set'] = array();
    $this->set->saveToCache($this->cache->data['set']);
    $this->cache->data['page'] = array();
    $this->saveToCache($this->cache->data['page']);

    $lBlocksCache = array();
    for ($i = 0, $l = $this->blocksAll->count(); $i < $l; $i++)
    {
      $lBlock = $this->blocksAll->getByI($i);
      $lBlocksCache[$lBlock->name] = array();
      $lBlock->saveToCache($lBlocksCache[$lBlock->name]);
    }
    $this->cache->data['blocks'] = $lBlocksCache;

    $this->cache->save();
  }

  public function saveToCache(array &$aCacheData)
  {
    parent::saveToCache($aCacheData);
    $aCacheData['blocksInfos'] = $this->blocksInfos;
  }

  public function setCreate($aAppName, $aSetName, cPage $aPage)
  {
    return new cSet($aAppName, $aSetName, $aPage);
  }

  public static function settingsCreate()
  {
    self::$settingsInstance = new cPageSettings();
  }

  public static function settingsGet()
  {
    eAssert(isset(self::$settingsInstance), 'Page settings not created');
    return self::$settingsInstance;
  }

  protected function settingsRead(cXmlNode $aXmlNode)
  {
    parent::settingsRead($aXmlNode);

    if ($this->settings->context)
      $this->settings->context->settingsReadPage($aXmlNode);
  }

  public function templateProcess($aTemplate, $aValuesArray)
  {
    try
    {
      $aValuesArray['v'] = $aValuesArray;
      extract($aValuesArray, EXTR_SKIP);
      ob_start();
      eval(' ?>'.$aTemplate.'<?php ');
      return ob_get_clean();
    }
    catch (Exception $e)
    {
      ob_get_clean();

      $lFunction = $this->settings->onTemplateErrorFunction;
      if ($lFunction)
        return $lFunction($e);
      else
        throw $e;
    }
  }

  public function workDirByLevelGet($aLevelName)
  {
    switch ($this->nameByLevelExplode($aLevelName, $lName))
    {
    case self::LEVEL_SET:
      if ($lName == '')
      {
        $lAppName = $this->appName;
        $lSetName = $this->set->name;
      }
      else
      {
        $lNames = $this->nameFullExplode($lName, 2);

        $lAppName = $lNames[0];
        $lSetName = $lNames[1];
      }

      return $this->workDirBuild($lAppName, $lSetName);
    case self::LEVEL_PAGE:
      if ($lName == '')
      {
        $lAppName  = $this->appName;
        $lSetName  = $this->set->name;
        $lPageName = $this->name;
      }
      else
      {
        $lNames = $this->nameFullExplode($lName, 3);

        $lAppName  = $lNames[0];
        $lSetName  = $lNames[1];
        $lPageName = $lNames[2];
      }

      return $this->workDirBuild($lAppName, $lSetName, $lPageName);
    default:
      throw new Exception('Not supported Level: "'.$lLevel.
        '" in LevelName: "'.$aLevelName.'"');
    }
  }
}

abstract class cBlock extends cMetaData
{
  private $cacheHtml = '';

  public $blocks = array();

  public $owner = null;

  public $isCache          = false;
  public $isBuildOnRequest = false;

  public function __construct($aBlockName, $aComponentNameFull, cPage $aPage)
  {
    parent::__construct($aPage->appName, $aBlockName, $aPage, $aPage->cache,
      $aPage->settings);

    $this->initMt($aComponentNameFull);
  }

  protected function configReadInternal(cXmlDocument $aXmlDocument)
  {
    parent::configReadInternal($aXmlDocument);

    $lAttr = null;
    if ($aXmlDocument->attrs->nextGetCheckByN('IsCache', $lAttr))
      $this->isCache = $lAttr->getB();
    if ($aXmlDocument->attrs->nextGetCheckByN('IsBuildOnRequest', $lAttr))
      $this->isBuildOnRequest = $lAttr->getB();
  }

  final public function contentGet()
  {
    if ($this->isBuildOnRequest &&
      !$this->page->params->blocks->exist($this->name))
      return '';

    if ($this->isCache)
    {
      if (!$this->cache->isValid)
        $this->cacheHtml = $this->build();
      return $this->cacheHtml;
    }
    else
      return $this->build();
  }

  abstract public function build();//!to override

  public function fileFlpByLevelGet($aLevelFlp)
  {
    if ($this->nameByLevelExplode($aLevelFlp, $lFlp) == self::LEVEL_BLOCK)
      return $this->fileFirstExistFlpGet($lFlp);
    else
      return $this->page->fileFlpByLevelGet($aLevelFlp);
  }

  protected function init()//!to override
  {
    $this->settingsReadProcess();
  }

  private function initMt($aComponentNameFull)
  {
    if ($this->cache->isValid)
      $this->loadFromCache($this->cache->data['blocks'][$this->name]);
    else
    {
      $this->initMtInternal(
        $aComponentNameFull,
        $this->page->workDirs,
        $this->appName.'.'.$this->page->set->name.'.'.$this->page->name.'.'.
          $this->name);
      $this->configRead();
    }
  }

  private function initMtInternal($aComponentNameFull, array $aPageWorkDirs,
    $aBlockNameFull)
  {
    if ($aBlockNameFull)
    {
      $lNames = $this->nameFullExplode($aBlockNameFull, 4);

      $lAppName   = $lNames[0];
      $lSetName   = $lNames[1];
      $lPageName  = $lNames[2];
      $lBlockName = $lNames[3];

      $lWorkDir = $this->workDirBuild($lAppName, $lSetName, $lPageName,
        $lBlockName);

      $lXmlDocument = $this->configCheck($lWorkDir, 'Block');

      if ($lXmlDocument)
      {
        $lInheritedParams = $this->inheritedParamsGet($lXmlDocument);

        if (count($lInheritedParams))
          for ($i = 0, $l = count($lInheritedParams); $i < $l; $i++)
            $this->initMtInternal($aComponentNameFull, $aPageWorkDirs,
              $lInheritedParams[$i]);
        else
          $this->initMtInternal($aComponentNameFull, $aPageWorkDirs, '');
      }
      else
        $this->initMtInternal($aComponentNameFull, $aPageWorkDirs, '');
    }
    else
    if (count($aPageWorkDirs))
    {
      for ($i = 1, $l = count($aPageWorkDirs); $i < $l; $i++)//! 1 - Last WorkDir not used
      {
        $lWorkDir = $aPageWorkDirs[$i].$this->name.'/';

        $lXmlDocument = $this->configCheck($lWorkDir, 'Block');

        if ($lXmlDocument)
        {
          $lInheritedParams = $this->inheritedParamsGet($lXmlDocument);

          if (count($lInheritedParams))
            throw new Exception('Not supported Inherited for block by pages');
        }

      }
      $this->initMtInternal($aComponentNameFull, array(), '');
    }
    else
    if ($aComponentNameFull)
    {
      $lNames = $this->nameFullExplode($aComponentNameFull, 3);

      $lAppName       = $lNames[0];
      $lLibraryName   = $lNames[1];
      $lComponentName = $lNames[2];

      $lWorkDir = $this->appDirGet($lAppName).'tpl/lib/'.
        $lLibraryName.'/'.$lComponentName.'/';

      $lXmlDocument = $this->configCheck($lWorkDir, 'Block');

      if ($lXmlDocument)
      {
        $lInheritedParams = $this->inheritedParamsGet($lXmlDocument);//!!Add InheritedType Component

        for ($i = 0, $l = count($lInheritedParams); $i < $l; $i++)
          $this->initMtInternal($lInheritedParams[$i], array(), '');
      }
    }
    else
      throw new Exception('Can not init cBlockMt');
  }

  final public function initRecursive()
  {
    if ($this->isBuildOnRequest &&
      !$this->page->params->blocks->exist($this->name))
      return;

    if ($this->isCache && $this->cache->isValid)
      return;

    $this->init();

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->initRecursive();
  }

  public function initScriptsGetRecursive(array &$aInitScripts)
  {
    if ($this->initScript)
      $aInitScripts[] = $this->initScript;

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->initScriptsGetRecursive($aInitScripts);
  }

  protected function loadFromCache(array $aCacheData)
  {
    parent::loadFromCache($aCacheData);
    $this->isCache = $aCacheData['isCache'];

    if ($this->isCache)
      $this->cacheHtml = $aCacheData['cacheHtml'];
  }

  public function saveToCache(array &$aCacheData)
  {
    parent::saveToCache($aCacheData);
    $aCacheData['isCache'] = $this->isCache;

    if ($this->isCache)
      $aCacheData['cacheHtml'] = $this->cacheHtml;
  }

  public function scriptsGetRecursive(cNamedIndexedList $aScripts,
    cNamedIndexedList $aNotCollectedScripts)
  {
    $this->scriptsDefaultGet($aScripts);
    $this->scriptsGet($aScripts, $aNotCollectedScripts);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->scriptsGetRecursive($aScripts, $aNotCollectedScripts);
  }

  public function stylesGetRecursive(cNamedIndexedList $aStyles,
    cNamedIndexedList $aNotCollectedStyles)
  {
    $this->stylesDefaultGet($aStyles);
    $this->stylesGet($aStyles, $aNotCollectedStyles);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->stylesGetRecursive($aStyles, $aNotCollectedStyles);
  }

  protected function templateProcess($aTemplate, $aValuesArray)
  {
    return $this->page->templateProcess($aTemplate, $aValuesArray);
  }

  public function workDirByLevelGet($aLevelName)
  {
    if ($this->nameByLevelExplode($aLevelName, $lName) == self::LEVEL_BLOCK)
    {
      if ($lName == '')
      {
        $lAppName   = $this->appName;
        $lSetName   = $this->page->set->name;
        $lPageName  = $this->page->name;
        $lBlockName = $this->name;
      }
      else
      {
        $lNames = $this->nameFullExplode($lName, 4);

        $lAppName   = $lNames[0];
        $lSetName   = $lNames[1];
        $lPageName  = $lNames[2];
        $lBlockName = $lNames[3];
      }

      return $this->workDirBuild($lAppName, $lSetName, $lPageName, $lBlockName);
    }
    else
      return $this->page->workDirByLevelGet($aLevelName);
  }
}

//!Init
cPage::settingsCreate();
?>