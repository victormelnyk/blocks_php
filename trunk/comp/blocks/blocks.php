<?
//!
//SetNameFull = AppName.SetName
//PageNameFull = AppName.SetName.PageName
//ComponentNameFull = AppName.LibraryName.ComponentName
//SetBlockNameFull = AppName.SetName.BlockName
//BlockNameFull = AppName.SetName.PageName.BlockName
/*!
$settings = array(
  'rootDir'    => '',//!*
  'rootRunDir' => '',//!!

  'isCache'   => false,
  'isProfile' => false,
  'isTest'    => true
);
*/
/*delete
  public function log($aMessage)//!!delete
  {
    $filePath = $this->rootDir.'tmp/logs/'.gmdate('YmdHis').'.log';//!! add app name
    $lData = gmdate('YmdHis').' : '.$aMessage.CRLF;
    stringToFileExt($lData, $filePath, True, 'a');
  }

*/

class Cache {
  private $cacheDir = '';

  public $data     = array();//!!linear list
  public $isActive = false;
  public $isValid  = false;

  public function __construct($cacheDir, $isActive) {
    $this->cacheDir = $cacheDir;
    $this->isActive = $isActive;

    if ($this->isActive && file_exists($this->cacheDir . '.blc')) {
      $this->data = unserialize(fileToString($this->cacheDir . '.blc'));
      $this->isValid = true;
    }
  }

  public function save() {
    stringToFile(serialize($this->data), $this->cacheDir . '.blc');
  }

  public function saveToFile($value, $relativeFilePath) {
    stringToFile($value, $this->cacheDir . $relativeFilePath);
    return $this->cacheDir . $relativeFilePath;
  }
}

abstract class Context {
  protected $page = null;

  public function init(Page $page) {
    $this->page = $page;
  }

  public function readSettings(LinearList $settings) {}

  public function validate() {
    return false;
  }
}

abstract class MetaData {
  //!FileLevels
  const FL_PAGE     = 'p';
  const FL_BLOCK    = 'b';
  const FL_EXTERNAL = 'e';
  const FL_WEB      = 'w';//!!check

  private $configs       = array();
  private $fileDatas     = array();
  private $filePaths     = array();
  private $filePathsList = array();

  protected $configSettings = null;

  protected $scripts = null;
  protected $styles  = null;

  public $filesMl = array();
  public $tagsMl = null;
  public $tags = null;

  public $initScript = '';
  public $workDirs   = array();

  public $appName = '';
  public $setName = '';
  public $name    = '';

  public $page = null;

  public function __construct($appName, $setName, $name, Page $page) {
    $this->scripts       = new NamedList();
    $this->scriptsDirect = new NamedList();
    $this->styles        = new NamedList();
    $this->stylesDirect  = new NamedList();

    $this->tagsMl = new NamedList();
    $this->tags   = new NamedList();

    $this->appName  = $appName;//!!!to page level
    $this->setName  = $setName;
    $this->name     = $name;
    $this->page     = $page;
  }

  public function getAppDir($appName = '') {
    return $this->page->rootDir . 'app/' .
      ($appName ? $appName : $this->appName) . '/';
  }

  protected function checkConfig($workDir) {
    $filePath = $workDir . '.json';
    $this->workDirs[] = $workDir;

    if (!file_exists($filePath)) {
      return null;
    }

    $config = new HierarchyList();
    $config->getCurrList()->loadFromJsonFile($filePath);
    array_unshift($this->configs, $config);
    return $config;
  }

  public function getScriptsRecursive(NamedList $scripts,
    NamedList $scriptsDirect) {
    $this->addFileDataToListIfExist($scripts, 'web/.js');
    $this->getResources($this->scripts, $scripts, false);
    $this->getResources($this->scriptsDirect, $scriptsDirect, true);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++)
      $this->blocks[$i]->getScriptsRecursive($scripts, $scriptsDirect);
  }

  public function getStylesRecursive(NamedList $styles,
    NamedList $stylesDirect) {
    $this->addFileDataToListIfExist($styles, 'web/.css');
    $this->getResources($this->styles, $styles, false);
    $this->getResources($this->stylesDirect, $stylesDirect, true);

    for ($i = 0, $l = count($this->blocks); $i < $l; $i++) {
      $this->blocks[$i]->getStylesRecursive($styles, $stylesDirect);
    }
  }

  protected function readConfig() {
    if (!count($this->configs)) {
      return;
    }

    $config = $this->configs[0];

    for ($i = 1; $i < count($this->configs); $i++) {
      $config->getCurrList()->marge($this->configs[$i]->getCurrList()->getItems());
    }

    $configList = $config->getCurrList();
    $configList->clearPosition();

    if ($configList->getCheckNextByN('inherited', $inherited)) {
      $configList->deleteCurrByN('inherited');
    }

    if ($config->tryBeginLevelByN('ml', $ml)) {
      if ($ml->getCheckNextByN('files', $filesMl)) {
        assert(is_array($filesMl));

        for ($i = 1; $i < count($this->configs); $i++) {
          $this->filesMl[$filesMl] = true;
        }
      }

      if ($config->tryBeginLevelByN('tags')) {
        while ($config->tryBeginLevelNV($tagName, $tag)) {
          while ($tag->getCheckNextNV($language, $value)) {
            $values = null;
            if (!$this->tagsMl->getCheck($tagName, $values)) {
              $values = new NamedList();
              $this->tagsMl->add($tagName, $values);
            }

            $values->add($language, $value);
          }

          $config->endLevel($tagName);
        }

        $config->endLevel('tags');
      }

      $config->endLevel('ml');
      $configList->deleteCurrByN('ml');
    }

    if ($config->tryBeginLevelByN('tags', $tags)) {
      while ($tags->getCheckNextNV($tagName, $tagValue)) {
        $this->tags->add($tagName, $tagValue);
      }

      $config->endLevel('tags');
      $configList->deleteCurrByN('tags');
    }

    if ($configList->count()) {
      $configString = $configList->saveToString();
      $configString = $this->processStringTags($configString, array());
      $config = new HierarchyList();
      $config->getCurrList()->loadFromString($configString);

      $this->readConfigInternal($config);
    }

    $config->finalize();
  }

  private function readResourcesConfigArray(NamedList $configList, $name,
    NamedList $list) {
    if ($configList->getCheckNextByN($name, $resultArray)) {
      assert(is_array($resultArray));
      for ($i = 1; $i < count($resultArray); $i++) {
        $list->add($resultArray[$i], true);
      }
    }
  }

  protected function readConfigInternal(HierarchyList $config) {
    $configList = $config->getCurrList();
    $this->readResourcesConfigArray($configList, 'styles', $this->styles);
    $this->readResourcesConfigArray($configList, 'stylesDirect',
      $this->stylesDirect);
    $this->readResourcesConfigArray($configList, 'scripts', $this->scripts);
    $this->readResourcesConfigArray($configList, 'scriptsDirect',
      $this->scriptsDirect);

    $configList->getCheckNextByN('initScripts', $this->initScript);

    if ($config->tryBeginLevelByN('settings', $settings)) {
      if ($settings->count()) {
        $this->configSettings = new LinearList($settings);
      }

      $config->endLevel('settings');
    }
  }

  private function getFileData($filePath, $isAddToCache = true,
    array $tagsValues = array()) {
    if ($this->page->cache->isValid) {
      return $this->fileDatas[$filePath];
    }
    $result = fileToString($filePath);
    $result = $this->processStringTags($result, $tagsValues);
    if ($isAddToCache) {
      $this->fileDatas[$filePath] = $result;
    }
    return $result;
  }

  private function addFileDataToListIfExist(NamedList $list, $relativeFilePath) {
    if ($this->getCheckFirstExistFilePath($relativeFilePath, $filePath)) {
      $list->add($filePath, $this->getFileData($filePath, false));
    }
  }

  public function getExistFilePaths($relativeFilePath) {
    if ($this->cache->isValid) {
      return $this->filePathsList[$relativeFilePath];
    }

    $result = array();
    for ($i = 0; $i < count($this->workDirs); $i++) {
      $filePath = $this->workDirs[$i] . $relativeFilePath;
      if (file_exists($filePath)) {
        $result[] = $filePath;
      }
    }
    $this->filePathsList[$relativeFilePath] = $result;
    return $result;
  }

  public function getFirstExistFileData($relativeFilePath, $isAddToCache = true,
    array $tagsValues = array()) {
    return $this->getFileData($this->getFirstExistFilePath($relativeFilePath),
      $isAddToCache, $tagsValues);
  }

  public function getFirstExistFilePath($relativeFilePath) {
    if ($this->getCheckFirstExistFilePathInternal($relativeFilePath, $filePath)) {
      return $filePath;
    } else {
      throw new Exception('File not exist by relative file path: "' .
        $relativeFilePath . '". ' . 'work dirs: "' .
        implode(', ', $this->workDirs) . '"');
    }
  }

  public function getCheckFirstExistFilePath($relativeFilePath, &$filePath) {
    return $this->getCheckFirstExistFilePathInternal($relativeFilePath,
      $filePath);
  }

  private function getCheckFirstExistFilePathInternal($relativeFilePath,
    &$filePath) {
    if ($this->page->cache->isValid) {
      if (isset($this->filePaths[$relativeFilePath])) {
        $filePath = $this->filePaths[$relativeFilePath];
        return true;
      } else {
        return false;
      }
    } else { //!! optimize, check
      $relativeFilePaths = array();

      if (isset($this->filesMl[$relativeFilePath])) {
        $filePathParts = explode('.', $relativeFilePath);
        $filePathPartCount = count($filePathParts);
        eAssert($filePathPartCount > 1);
        $fileName = $filePathParts[$filePathPartCount - 1 - 1];
        $filePathParts[$filePathPartCount - 1 - 1] =
          $fileName . '_' . $this->page->language;
        $relativeFilePaths[] = implode('.', $filePathParts);
        $filePathParts[$filePathPartCount - 1 - 1] =
          $fileName . '_' .$this->page->settings->defaultLanguage;
        $relativeFilePaths[] = implode('.', $filePathParts);
      }

      $relativeFilePaths[] = $relativeFilePath;

      for ($i = 0; $i < count($this->workDirs); $i++) {
        for ($j = 0; $j < count($relativeFilePaths); $j++) {
          $lFilePath = $this->workDirs[$i] . $relativeFilePaths[$j];
          if (file_exists($lFilePath)) {
            $this->filePaths[$relativeFilePath] = $lFilePath;
            $filePath = $lFilePath;
            return true;
          }
        }
      }
      //!!end coment
      return false;
    }
  }

  abstract public function getFileNameByLevel($leveFilePath);

  protected function getInheritedParams(HierarchyList $config) {
    $result = array();
    $config->getCurrList()->getCheckNextByN('inherited', $result);

    if ($result === '') {
      $result = array();
    } else {
      eAssert(is_array($result));
    }

    return $result;
  }

  public function addInitScript($initScript) {
    $this->initScript .= ($this->initScript ? CRLF : '') . $initScript;
  }

  protected function loadFromCache(array $cacheData) {//!!not updated
    $this->fileDatas     = $cacheData['fileDatas'];
    $this->filePaths     = $cacheData['filePaths'];
    $this->filePathsList = $cacheData['filePathsList'];
    $this->initScript    = $cacheData['initScript'];
    $this->tagsMl->loadFromString($cacheData['tagsMl']);
    $this->tags->loadFromString($cacheData['tags']);

    if (isset($cacheData['configSettings'])) {
      $this->configSettings = new LinearList($cacheData['configSettings']);
    }
  }

  private function getCheckMlTagValue($tagName, $language, &$value) {
    if ($this->tagsMl->getCheck($tagName, $tagsByLanguage)) {
      return $tagsByLanguage->getCheck($language, $value);
    } else {
      return false;
    }
  }

  public function getMlTagValue($tagName) {
    if ($this->getCheckMlTagValue($tagName, $this->page->language, $value)) {
      return $value;
    } else if ($this->getCheckMlTagValue($tagName,
      $this->page->settings->defaultLanguage, $value)) {
      return $value;
    } else {
      throw new Exception('Can not get LocalizationTagValue for Tag: "' .
        $tagName . '" by language: "' . $this->page->language . '"');
    }
  }

  protected function explodeNameByLevel($levelName, &$name) {
    $level = $levelName[0];
    eAssert($levelName[1] == ':', 'Invalid LevelPrefix in LevelName: "' .
      $levelName . '"');
    if (strlen($levelName) > 2) {
      $name = substr($levelName, 2);
    } else {
      $name = '';
    }
    return $level;
  }

  protected function explodeFullName($nameFull, $count) {
    $result = explode('.', $nameFull);
    eAssert(count($result) == $count, 'Wrong NameFull: "' . $nameFull .
      '" for count: ' . $count);
    return $result;
  }

  public function getRunDir($runDir) {//!! check host
    $params = explode('.', $runDir);//!!'.' wrong delimiter
    $isAbsolute = false;

    switch (count($params)) {
      case 1:
        $runDirName = $params[0];
        break;
      case 2:
        $runDirName = $params[0];
        eAssert($params[1] === 'host', 'Not supported param ' . $params[1]);
        $isAbsolute = true;
        break;
      default:
        throw new Exception('Wrong RunDirName: "'.$runDir.'"');
    }

    $result = $this->settings->rootDir .
      ($runDirName ? $this->settings->rootRunDir .
        (isset($this->page->set->runDirs[$runDirName])
          ? $this->page->set->runDirs[$runDirName] : $runDirName . '/') : '');

    if ($isAbsolute)
      $result = $this->addHostUrl($result, true);

    return $result;
  }

  public function saveToCache(array &$cacheData) {//!! not updated
    $cacheData['fileDatas']     = $this->fileDatas;
    $cacheData['filePaths']     = $this->filePaths;
    $cacheData['filePathsList'] = $this->filePathsList;
    $cacheData['initScript']    = $this->initScript;
    $cacheData['tagsMl']        = $this->tagsMl->saveToString();
    $cacheData['tags']          = $this->tags->saveToString();

    if ($this->configSettings)
      $cacheData['configSettings'] = $this->configSettings->getItems();
  }

  private function getResources(NamedList $sources, NamedList $destinations,
    $isDirect) {
    for ($i = 0, $l = $sources->count(); $i < $l; $i++) {
      $filePath = $this->getFileNameByLevel($sources->getByI($i));
      if (!$destinations->exist($filePath)) {//!! need to log file dublication
        $destinations->add($filePath, $isDirect ? $filePath
          : $this->getFileData($filePath, false));
      }
    }
  }

  protected function readSettings(LinearList $configSettings) {}//!to override

  protected function readProcessSettings() {
    if (isset($this->configSettings)) {
      $this->readSettings($this->configSettings);
      if (!$this->cache->isValid) {
        $this->configSettings->finalize();
      }
    }
  }

  protected function processStringTags($string, array $tagsValues)
  {
    $result = $string;
    $result = htmlspecialchars_decode($string);//!!fix and delete
    $tagsAll = tagsFind($result);
    $lTagsValues = array();

    for ($i = 0; $i < count($tagsAll); $i++) {
      $tag = $tagsAll[$i];

      if (isset($tagsValues[$tag])) {
        $lTagsValues[$tag] = $tagsValues[$tag];
      } else {
        $tagParts = explode('|', $tag);

        switch (count($tagParts)) {
          case 1:
            $tagKey = $tagParts[0];

            switch ($tagKey) {
              case 'language':
                $lTagsValues[$tag] = $this->page->language;
                break;
              case 'name':
                $lTagsValues[$tag] = $this->name;
                break;
              default:
                if (!isset($tagsValues[$tagKey])) {
                  raiseNotSupported('tag', $tag);
                }
            }
            break;
          case 2:
            $tagKey   = $tagParts[0];
            $tagParam = $tagParts[1];

            switch ($tagKey) {
              case 'getFileNameByLevel':
                $lTagsValues[$tag] = $this->getFileNameByLevel($tagParam);
                break;
              case 'getWorkDirByLevel':
                $lTagsValues[$tag] = $this->getWorkDirByLevel($tagParam);
                break;
              case 'getRunDir':
                $lTagsValues[$tag] = $this->getRunDir($tagParam);
                break;
              case 'ml':
                $lTagsValues[$tag] = $this->getMlTagValue($tagParam);
                break;
              case 'tag':
                $lTagsValues[$tag] = $this->tags->getByN($tagParam);
                break;
              case 'Host':
                $lTagsValues[$tag] = $this->addHostUrl($tagParam, false);
                break;
              default:
                raiseNotSupported('tag', $tag);
            }
            break;
          default:
            raiseNotSupported('tag', $tag);
        }
      }
    }

    if (count($tagsAll)) {
      $result = tagsReplaceArray($result, $lTagsValues);
    }
    return $result;
  }

  public function addHostUrl($url, $isAddSlash) {
    return $_SERVER['HTTP_HOST'].($isAddSlash ? '/' : '').
      str_replace('../', '', $url);
  }

  abstract public function getWorkDirByLevel($levelName);

  public function buildWorkDir($appName, $setName, $pageName = '',
    $blockName = '') {
    return $this->getAppDir($appName) . 'tpl/' . $setName . '/' .
      ($pageName ? $pageName . '/' : '') .
      ($blockName ? $blockName . '/' : '');
  }
}

class Page extends MetaData {
  private static $modules = array();
  private static $initSettings = null;
  private static $rootDirStatic = '';

  private $appNames    = array();
  private $setNames    = array();
  private $blocksAll   = null;
  private $blocksInfos = array();//!!array of record

  public $isCache   = true;
  public $isProfile = false;
  public $isTest    = false;

  public $rootDir = '';
  public $rootRunDir = '';

  public $blocks = array();
  public $params = null;
  public $session = null;

  public $title = '';
  public $meta = '';

  public $defaultLanguage = 'en';
  public $language = '';

  public $db      = null;
  public $context = null;

  public $onErrorFunction = '';//!!delete
  public $onTemplateErrorFunction = '';

  public function __construct($setNameFull) {
    session_start();

    $this->params = new NamedList(
      $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET);
    $this->session = new NamedList($_SESSION);

    $this->rootDir = self::$rootDirStatic;

    $value = null;
    if (self::$initSettings->getCheckNextOByN('isCache', $value)) {
      $this->isCache = $value->getB();
    }

    if (self::$initSettings->getCheckNextOByN('isProfile', $value)) {
      $this->isProfile = $value->getB();
    }
    if (self::$initSettings->getCheckNextOByN('isTest', $value)) {
      $this->isTest = $value->getB();
    }
    //!!defaultLanguage
/*!!
    if ($this->onErrorFunction)//!!delete
      eAssert(function_exists($this->onErrorFunction),
        'onErrorFunction with name: "'.$this->onErrorFunction.'" do not exsist');

    if ($this->onTemplateErrorFunction)
      eAssert(function_exists($this->onTemplateErrorFunction),
        'onTemplateErrorFunction with name: "'.$this->onTemplateErrorFunction.
        '" do not exsist');
*/
    self::$initSettings->finalize();

    if ($this->isTest) {
      if ($this->params->getCheck('is_cache', $value)) {
        $this->isCache = $value->getB();
        $_SESSION['is_cache'] = $this->isCache;
      } else if ($this->session->getCheck('is_cache', $value)) {
        $this->isCache = $value->getB();
      }

      if ($this->params->getCheck('is_profile', $value)) {
        $this->isProfile = $value->getB();
        $_SESSION['is_profile'] = $this->isCache;
      } else if ($this->session->getCheck('is_profile', $value)) {
        $this->isProfile = $value->getB();
      }

      if ($this->isProfile) {
        require_once($this->rootDir.'blocks/comp/helpers/profiler/.php');//!!fix
        cPFHelper::init($this->rootDir);
      }
    }

    //
    $names = $this->explodeFullName($setNameFull, 2);
    $appName = $names[0];
    $setName = $names[1];
    $pageName = basename($_SERVER['SCRIPT_NAME'], '.php');

    if ($this->params->getCheck('l', $this->language)) {
      $_SESSION['language'] = $this->language;
    } else if (!$this->session->getCheck('language', $this->language)) {
      $this->language = $this->defaultLanguage;
    }

    $this->cache = new Cache($this->rootDir . 'tmp/cache/' .
      $setName . '/' . $pageName . '/' .
      ($this->language ? $this->language . '/' : ''),
      $this->isCache);

    parent::__construct($appName, $setName, $pageName, $this);

    $this->blocksAll = new NamedList();

    if ($this->context) {
      $this->context->init($this);
    }

    $this->appNames = array_merge(array($appName), $this->appNames,
      array('blocks'));
    $this->setNames[] = $setName;

    $this->initMt();
  }

  private function addBlock($blockName, $componentNameFull,
    $parentBlockName) {
    $names = $this->explodeFullName($componentNameFull, 3);
    $appName       = $names[0];
    $libraryName   = $names[1];
    $componentName = $names[2];

    $moduleFilePath = 'app/' . $appName . '/lib/' . $libraryName . '/' .
      $componentName . '/.php';

    $this->addModule($moduleFilePath);

    $class = strToCapitalize($appName) . '_' .
      strToCapitalize($libraryName) . '_' . strToCapitalize($componentName);

    $block = new $class($blockName, $componentNameFull, $this);

    $this->blocksAll->add($blockName, $block);

    if ($parentBlockName) {
      $parent = $this->blocksAll->getByN($parentBlockName);
      $parent->blocks[] = $block;
      $block->owner = $parent;
    } else {
      $this->blocks[] = $block;
    }
  }

  private function addBlocks() {
    for ($i = 0; $i < count($this->blocksInfos); $i++) {
      $blocksInfo = $this->blocksInfos[$i];
      $this->addBlock(
        $blocksInfo['blockName'],
        $blocksInfo['componentName'],
        $blocksInfo['parentBlockName']
      );
    }
  }

  protected function build() {
    for ($i = 0; $i < count($this->blocks); $i++) {
      $this->blocks[$i]->initRecursive();
    }

    $body = '';
    for ($i = 0; $i < count($this->blocks); $i++) {
      $body .= $this->blocks[$i]->getContent();
    }

    if ($this->cache->isValid) {
      $result = $this->getFirstExistFileData('.htm');
    } else {
      $result = $this->getFirstExistFileData('.htm', true, array(
        'meta' => $this->meta,
        'title'=> $this->title,
        'css'  => $this->buildStyles(),
        'js'   => $this->buildScripts()
      ));
      $this->saveAllToCache();
    }

    return $this->templateProcess($result, array(
      'body'   => $body,
      'initJs' => $this->buildInitScripts()
    ));
  }

  protected function buildByBlockNames(array $blockNames) {
    for ($i = 0; $i < count($blockNames); $i++) {
      $this->blocksAll->getByN($blockNames[$i])->initRecursive();
    }

    $result = array();
    $blocks = array();

    for ($i = 0; $i < count($blockNames); $i++) {
      //!! add raise when block names has parent and children block names
      $block = array();
      $blockName = $blockNames[$i];
      $block['data'] = $this->blocksAll->getByN($blockName)->getContent();

      $initScripts = array();
      $this->blocksAll->getByN($blockName)->getInitScriptsRecursive($initScripts);
      if (count($initScripts))
        $block['initJs'] = implode(CRLF, $initScripts);

      $blocks[$blockName] = $block;
    }

    $result['blocks'] = $blocks;
    //!! add $this->saveAllToCache();
    return json_encode($result);
  }

  private function buildInitScripts() {
    $result = array();
    if ($this->initScript) {
      $result[] = $this->initScript;
    }
    for ($i = 0, $l = count($this->blocks); $i < $l; $i++) {
      $this->blocks[$i]->getInitScriptsRecursive($result);
    }
    return implode(CRLF, $result);
  }

  private function buildResources(NamedList $resources,
    NamedList $resourcesDirect, $prefix, $suffix, $resourceFileName) {
    $result = '';

    for ($i = 0; $i < $resourcesDirect->count(); $i++) {
      $result .= $prefix . $resourcesDirect->getByI($i) . $suffix;
    }

    if ($resources->count()) {
      $filePath = $this->cache->saveToFile(
        $resources->valuesToSectionString(CRLF), $resourceFileName);

      $result .= $prefix . $filePath . $suffix;
    }

    return $result;
  }

  private function buildScripts() {
    $scripts = new NamedList();
    $scriptsDirect = new NamedList();
    $this->getScriptsRecursive($scripts, $scriptsDirect);
    return $this->buildResources($scripts, $scriptsDirect,
      '<script language="JavaScript" type="text/javascript" src="',
      '"></script>', 'script.js');
  }

  private function buildStyles() {
    $styles = new NamedList();
    $stylesDirect = new NamedList();
    $this->getStylesRecursive($styles, $stylesDirect);
    return $this->buildResources($styles, $stylesDirect,
      '<link rel="stylesheet" type="text/css" href="',
      '">', 'styles.css');
  }

  private function getFirstExistExternalFilePath($relativePath)
  {
    foreach ($this->appNames as $appName) {
      $path = $this->getAppDir($appName) . 'ext/' . $relativePath;

      if (file_exists($path)) {
        return $path;
      }
    }

    throw new Exception('ExternalFile not exist by RelativePath: "' .
      $relativePath . '"AppNames: "' . implode(', ', $this->appNames) . '"');
  }

  protected function readConfigInternal(HierarchyList $config) {
    parent::readConfigInternal($config);
    $configList = $config->getCurrList();
    $configList->getCheckNextByN('title', $this->title);
    $configList->getCheckNextByN('meta', $this->meta);

    if ($config->tryBeginLevelByN('blocks')) {
      while ($config->tryBeginLevelNV($blockName, $block)) {
        $componentName = $block->getNextByN('component');

        if (!$block->getCheckNextByN('parent', $parentBlockName)) {
          $parentBlockName = '';
        }

        $this->blocksInfos[] = array(
          'blockName'       => $blockName,
          'componentName'   => $componentName,
          'parentBlockName' => $parentBlockName
        );
        $config->endLevel($blockName);
      }

      $config->endLevel('blocks');
    }
  }

  public function getFileNameByLevel($leveFilePath) {
    $level = $this->explodeNameByLevel($leveFilePath, $filePath);
    switch ($level) {
      case self::FL_PAGE:
        return $this->getFirstExistFilePath($filePath);
      case self::FL_EXTERNAL:
        return $this->getFirstExistExternalFilePath($filePath);
      case self::FL_WEB:
        return $filePath;
      default:
        throw new Exception('Not supported Level: "' . $level .
          '" in LevefilePath: "' . $leveFilePath . '"');
    }
  }

  private function initMt() {
    if ($this->cache->isValid) {
      $this->loadFromCache($this->cache->data['page']);
    } else {
      foreach ($this->appNames as $appName) {
        foreach ($this->setNames as $setName) {
          $this->initMtInternal($appName, $setName, $this->name);
        }
      }

      $this->initMtInternal('blocks', 'sys', 'page');
      $this->readConfig();
    }

    $this->readProcessSettings();//!!check

    $this->addBlocks();
  }

  private function initMtInternal($appName, $setName, $pageName) {
    $workDir = $this->buildWorkDir($appName, $setName, $pageName);
    $config = $this->checkConfig($workDir);

    if (!$config) {
      return;
    }

    $inheritedParams = $this->getInheritedParams($config);

    for ($i = 0, $l = count($inheritedParams); $i < $l; $i++) {
      $names = $this->explodeFullName($inheritedParams[$i], 3);
      $this->initMtInternal($names[0], $names[1], $names[2]);
    }
  }

  public static function run($setNameFull) {
//!!    try
//    {
    $className = get_called_class();
    $page = new $className($setNameFull);
    $page->process();
/*!!    }
    catch (Exception $e)
    {
      $function = self::initSettings->onErrorFunction;
      if ($function)
        p($function($e));
      else
        exceptionShow($e);
    }*/
  }

  protected function loadFromCache(array $cacheData) {
    parent::loadFromCache($cacheData);
    $this->blocksInfos = $cacheData['blocksInfos'];
  }

  public static function addModule($relativeFilePath) {
    self::addModuleDirect(self::$rootDirStatic . $relativeFilePath);
  }

  public static function addModuleDirect($filePath) {
    if (array_key_exists($filePath, self::$modules)) {
      self::$modules[$filePath] = self::$modules[$filePath] + 1;
    } else {
      require_once($filePath);
      self::$modules[$filePath] = 1;
    }
  }

  private function process() {
    if ($this->context && !$this->context->validate()) {
      return;
    }

    if (false && paramPostGetGetCheck('blocks', V_STRING, $lParam)) {//!! params, delete false &&
      $result = $this->buildByBlockNames(explode(',', $lParam));
    } else {
      if ($this->isTest) {
        $this->addInitScript('if (window.page) page.isTestMode = true;');
      }

      $result = $this->build();
    }

    if ($this->isProfile) {
      cPFHelper::stop();
    }

    p($result);
  }

  private function saveAllToCache() {
    $this->cache->data['page'] = array();
    $this->saveToCache($this->cache->data['page']);

    $blocksCache = array();
    for ($i = 0, $l = $this->blocksAll->count(); $i < $l; $i++) {
      $block = $this->blocksAll->getByI($i);
      $blocksCache[$block->name] = array();
      $block->saveToCache($blocksCache[$block->name]);
    }
    $this->cache->data['blocks'] = $blocksCache;

    $this->cache->save();
  }

  public function saveToCache(array &$cacheData) {
    parent::saveToCache($cacheData);
    $cacheData['blocksInfos'] = $this->blocksInfos;
  }

  public static function init(array $settings) {
    self::$initSettings = new LinearList($settings);
    self::$rootDirStatic = self::$initSettings->getNextByN('rootDir');
  }

  protected function readSettings(LinearList $configSettings) {
    parent::readSettings($configSettings);

    if ($this->settings->context) {
      $this->settings->context->readSettings($configSettings);
    }
  }

  public function templateProcess($template, $valuesArray) {
    try {
      $valuesArray['v'] = $valuesArray;
      extract($valuesArray, EXTR_SKIP);
      ob_start();
      eval(' ?>' . $template . '<? ');
      return ob_get_clean();
    } catch (Exception $e) {
      ob_get_clean();

      $function = $this->settings->onTemplateErrorFunction;//!!check
      if ($function) {
        return $function($e);
      } else {
        throw $e;
      }
    }
  }

  public function getWorkDirByLevel($levelName) {
    $level = $this->explodeNameByLevel($levelName, $lName);
    switch ($level) {//!!add defalt prefix, delete case
      case self::FL_PAGE:
        if ($lName == '') {
          $appName  = $this->appName;
          $setName  = $this->setName;
          $pageName = $this->name;
        } else {
          $names = $this->explodeFullName($lName, 3);
          $appName  = $names[0];
          $setName  = $names[1];
          $pageName = $names[2];
        }
        return $this->buildWorkDir($appName, $setName, $pageName);
      default:
        throw new Exception('Not supported Level: "' . $level .
          '" in LevelName: "' . $levelName . '"');
    }
  }
}

abstract class Block extends MetaData {
  private $cacheHtml = '';

  public $blocks = array();
  public $owner = null;

  public $isCache          = false;
  public $isBuildOnRequest = false;

  public function __construct($blockName, $componentNameFull, Page $page) {
    parent::__construct($page->appName, $page->setName, $blockName, $page);

    $this->initMt($componentNameFull);
  }

  protected function readConfigInternal(HierarchyList $config) {
    parent::readConfigInternal($aXmlDocument);
    $configList = $config->getCurrList();

    $configObject = null;
    if ($aXmlDocument->getCheckNextOByN('isCache', $configObject)) {
      $this->isCache = $configObject->getB();
    }
    if ($aXmlDocument->getCheckNextByN('isBuildOnRequest', $configObject)) {
      $this->isBuildOnRequest = $configObject->getB();
    }
  }

  final public function getContent() {
    if ($this->isBuildOnRequest
      && !$this->page->params->blocks->exist($this->name)) {
      return '';
    }

    if ($this->isCache) {
      if (!$this->cache->isValid) {
        $this->cacheHtml = $this->build();
      }
      return $this->cacheHtml;
    } else {
      return $this->build();
    }
  }

  abstract public function build();//!to override

  public function getFileNameByLevel($leveFilePath) {
    if ($this->explodeNameByLevel($leveFilePath, $filePath) === self::FL_BLOCK) {
      return $this->getFirstExistFilePath($filePath);
    } else {
      return $this->page->getFileNameByLevel($leveFilePath);
    }
  }

  protected function init() {//!to override
    $this->readProcessSettings();
  }

  private function initMt($componentNameFull) {
    if ($this->page->cache->isValid) {
      $this->loadFromCache($this->page->cache->data['blocks'][$this->name]);
    } else {
      $this->initMtInternal(
        $componentNameFull,
        $this->page->workDirs,
        $this->appName . '.' . $this->page->setName . '.' .
          $this->page->name . '.'.
        $this->name);
      $this->readConfig();
    }
  }

  private function initMtInternal($componentNameFull, array $pageWorkDirs,
    $blockNameFull) {
    if ($blockNameFull) {
      $names = $this->explodeFullName($blockNameFull, 4);

      $appName   = $names[0];
      $setName   = $names[1];
      $pageName  = $names[2];
      $blockName = $names[3];

      $workDir = $this->buildWorkDir($appName, $setName, $pageName, $blockName);

      $config = $this->checkConfig($workDir);

      if ($config) {
        $inheritedParams = $this->getInheritedParams($config);

        if (count($inheritedParams)) {
          for ($i = 0, $l = count($inheritedParams); $i < $l; $i++) {
            $this->initMtInternal($componentNameFull, $pageWorkDirs,
              $inheritedParams[$i]);
          }
        } else {
          $this->initMtInternal($componentNameFull, $pageWorkDirs, '');
        }
      } else {
        $this->initMtInternal($componentNameFull, $pageWorkDirs, '');
      }
    } else if (count($pageWorkDirs)) {
      for ($i = 1, $l = count($pageWorkDirs); $i < $l; $i++)//! 1 - Last WorkDir not used
      {
        $workDir = $pageWorkDirs[$i].$this->name . '/';

        $config = $this->checkConfig($workDir);

        if ($config) {
          $inheritedParams = $this->getInheritedParams($config);

          if (count($inheritedParams)) {
            throw new Exception('Not supported Inherited for block by pages');
          }
        }

      }
      $this->initMtInternal($componentNameFull, array(), '');
    } else if ($componentNameFull) {
      $names = $this->explodeFullName($componentNameFull, 3);

      $appName       = $names[0];
      $libraryName   = $names[1];
      $componentName = $names[2];

      $workDir = $this->getAppDir($appName) . 'tpl/lib/' .
        $libraryName . '/' . $componentName . '/';

      $config = $this->checkConfig($workDir, 'Block');

      if ($config) {
        $inheritedParams = $this->getInheritedParams($config);//!!Add InheritedType Component

        for ($i = 0, $l = count($inheritedParams); $i < $l; $i++) {
          $this->initMtInternal($inheritedParams[$i], array(), '');
        }
      }
    } else {
      throw new Exception('Can not init BlockMt');
    }
  }

  final public function initRecursive() {
    if ($this->isBuildOnRequest &&
      !$this->page->params->blocks->exist($this->name)) {
      return;
    }
    if ($this->isCache && $this->cache->isValid) {
      return;
    }
    $this->init();
    for ($i = 0, $l = count($this->blocks); $i < $l; $i++) {
      $this->blocks[$i]->initRecursive();
    }
  }

  public function getInitScriptsRecursive(array &$initScripts) {
    if ($this->isBuildOnRequest &&
      !$this->page->params->blocks->exist($this->name)) {//!!not work for included bloks
      return;
    }
    if ($this->initScript) {
      $initScripts[] = $this->initScript;
    }
    for ($i = 0, $l = count($this->blocks); $i < $l; $i++) {
      $this->blocks[$i]->getInitScriptsRecursive($initScripts);
    }
  }

  protected function loadFromCache(array $cacheData) {
    parent::loadFromCache($cacheData);
    $this->isCache = $cacheData['isCache'];

    if ($this->isCache) {
      $this->cacheHtml = $cacheData['cacheHtml'];
    }
  }

  public function saveToCache(array &$cacheData) {
    parent::saveToCache($cacheData);
    $cacheData['isCache'] = $this->isCache;

    if ($this->isCache) {
      $cacheData['cacheHtml'] = $this->cacheHtml;
    }
  }

  protected function templateProcess($template, $valuesArray) {
    return $this->page->templateProcess($template, $valuesArray);
  }

  public function getWorkDirByLevel($levelName) {
    if ($this->explodeNameByLevel($levelName, $lName) === self::FL_BLOCK) {
      if ($lName == '') {
        $appName   = $this->appName;
        $setName   = $this->page->setName;
        $pageName  = $this->page->name;
        $blockName = $this->name;
      } else {
        $names = $this->explodeFullName($lName, 4);
        $appName   = $names[0];
        $setName   = $names[1];
        $pageName  = $names[2];
        $blockName = $names[3];
      }
      return $this->buildWorkDir($appName, $setName, $pageName, $blockName);
    } else {
      return $this->page->getWorkDirByLevel($levelName);
    }
  }
}
?>