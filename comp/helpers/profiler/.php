<?
//!PF = Profiler

class cPFHelper
{
  const FIREPHP_DIR = 'blocks/external/web/firephp/server/0.3.2/lib/';
  const XHPROF_DIR = 'blocks/external/web/xhprof/0.10.3_php53_vc9/';

  private static $isEnable = false;

  private $sqlParamsToProfile = array();

  public static function init($aRootDir)
  {
    self::$isEnable = true;

    $lXhProfRootDir = $aRootDir.self::XHPROF_DIR;
    require_once($aRootDir.self::FIREPHP_DIR.'fb.php');
    require_once($lXhProfRootDir.'xhprof_lib/utils/xhprof_lib.php');
    require_once($lXhProfRootDir.'xhprof_lib/utils/xhprof_runs.php');

    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY,
      array('ignored_functions' =>  array('sqlProfile')));
  }

  private static function isEnableCheckAssert()
  {
    eAssert(self::$isEnable, 'Profiler Helper is not initialized');
  }

  public static function stop()
  {
    self::isEnableCheckAssert();
    $lXhprofResult = xhprof_disable();
    $lXhprofRuns = new XHProfRuns_Default();
    $lRunId = $lXhprofRuns->save_run($lXhprofResult, "xhprof");
    FB::info('http://'.$_SERVER['HTTP_HOST'].'/'.self::XHPROF_DIR.
      'xhprof_html/index.php?run='.$lRunId. '&source=xhprof');

 //!!   if (count($this->sqlParamsToProfile))
 //     $this->sqlsProcess();
  }

  public static function log($aMessage)
  {
    self::isEnableCheckAssert();
    FB::info($aMessage);
  }

  private function sqlsProcess()
  {
    $lDb = new cDbMySql('localhost', 'root', '', 'gurtivonka'); //!!

    for($i = 0, $l = count($this->sqlParamsToProfile); $i < $l; $i++)
    {
      $lParams = $this->sqlParamsToProfile[$i];
      //$lDb = $lParams['db'];
      $lDb->executeRecordset($lParams['sql'], $lRecordset, $lParams['params']);
      self::log($lRecordset);
    }
  }

  public static function sqlProfile($aDb, $aSql, $aParams)
  {
    self::isEnableCheckAssert();
    $aSql = 'SET profiling = 1;'.CRLF.$aSql.';'.CRLF.'SHOW profiles;';
    self::log($aSql);
    self::log($aParams);
 //!! dont work
 //   $lInstance->sqlParamsToProfile[] = array('db' => $aDb, 'sql' => $aSql,
 //     'params' => $aParams);
  }
}
?>