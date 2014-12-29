<?php
//!COMPARE_TYPE
define('COMPARE_TYPE_GREATER', '1');
define('COMPARE_TYPE_EQUAL',   '0');
define('COMPARE_TYPE_LESS',    '-1');

abstract class cLogonContext extends cContext
{
  const NO_LOGON_LEVEL  = '';
  const LOGON_LEVEL_ALL = 'all';

  private $logonLevels = array();

  private $defaultLogonLevelPage = self::NO_LOGON_LEVEL;
  private $defaultLogonLevelSet  = self::NO_LOGON_LEVEL;

  public $isTryToLogin   = false;
  private $loginTryCount = 0;

  protected $homePage = '';

  public $isLogged   = false;
  public $logonLevel = self::NO_LOGON_LEVEL;
  public $userId     = 0;
  public $userLogin  = '';
  public $userName   = '';

  public $pageLogonLevel = self::NO_LOGON_LEVEL;

  public function clear()
  {
    $this->isLogged   = false;
    $this->logonLevel = self::NO_LOGON_LEVEL;
    $this->userId     = 0;
    $this->userLogin  = '';
    $this->userName   = '';

    $this->writeToSession();
  }

  private function defaultLogonLevelRead(cXmlNode $aXmlNode)
  {
    $lLogonLevel = self::NO_LOGON_LEVEL;

    if ($aXmlNode->attrs->getCheckNextByN('DefaultLogonLevel',
      $lLogonLevelAttr))
    {
      $lLogonLevel = $lLogonLevelAttr->getS();

      if ($lLogonLevel == self::NO_LOGON_LEVEL)
        $lLogonLevel = self::LOGON_LEVEL_ALL;
    }

    return $lLogonLevel;
  }

  protected function init()
  {
    if ($this->loginParamsReadCheck($lUserLogin, $lUserPassword))
      $this->login($lUserLogin, $lUserPassword, $this->homePage);
    else
    if (paramPostGetGetCheck('user_logout', V_STRING, $lIsLogOut))
    {
      if ($lIsLogOut)
        $this->logOut();
    }
    else
    if (paramSessionGetCheck($this->page->appName.'_isLogged', V_BOOLEAN,
        $lIsLogged) && $lIsLogged
    )
      $this->readFromSession();
  }

  public function ipGet()
  {
    if (isset($_SERVER['HTTP_CLIENT_IP']))
      return $_SERVER['HTTP_CLIENT_IP'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return  $_SERVER['REMOTE_ADDR'];
  }

  public function isLoggedGet($aLogonLevel)//!!->LogonLevelCheck
  {
    if (($aLogonLevel == self::NO_LOGON_LEVEL)
      || ($aLogonLevel == self::LOGON_LEVEL_ALL)
    )
      return true;

    if (!$this->isLogged)
      return false;

    $lCompareResult = $this->logonLevelCompare($aLogonLevel, $this->logonLevel);
    return ($lCompareResult >= COMPARE_TYPE_EQUAL);
  }

  public function login($aUserLogin, $aUserPassword, $aRedirectTo)//!! need use onCompleteFunc
  {
    $this->isTryToLogin = true;
    $this->loginTryCountInc();

    $this->clear();
    $this->readFromDb($aUserLogin, $aUserPassword);

    if ($this->isLogged)
    {
      if (!(getCheckArrayValue($_SERVER, 'HTTP_X_REQUESTED_WITH',
          $lRequestCreator)
        && ($lRequestCreator == 'XMLHttpRequest')))//!! to AK need test
        header('Location: '.($aRedirectTo ? $aRedirectTo
          : getArrayValue($_SERVER, 'REQUEST_URI')));
     /*!! to AK need test
      for AJAX
      [HTTP_X_REQUESTED_WITH] => XMLHttpRequest
      [HTTP_REFERER] => http://web/life_master/main.php
      for all
      [PHP_SELF] => /life_master/index.php
     */
      $this->writeToSession();
      $this->loginTryCountClear();
    }
    else
      $this->clear();
  }

  protected function loginParamsReadCheck(&$aUserLogin, &$aUserPassword)
  {
    return paramPostGetGetCheck('user_login', V_STRING, $aUserLogin)
      && paramPostGetGetCheck('user_password', V_STRING, $aUserPassword);//!! use object params
  }

  protected function loginPageShow()
  {
    p(
      '<div style="position: relative">'.
        '<div style="margin: 0 auto; position: relative; width: 255px; height: 70px; '.
          'padding: 10px; top: 200px; border: 1px solid black">'.
          'You must loggin'.
          '<form method="POST">'.
            '<table style="width: 100%">'.
              '<tr>'.
                '<td align="left">'.
                  '<input type="text" name="user_login" value="" size="15" '.
                    'placeholder="login"/>'.
                '</td>'.
                '<td align="right">'.
                  '<input type="password" name="user_password" value="" size="15" '.
                    'placeholder="password"/>'.
                '</td>'.
              '</tr>'.
              '<tr>'.
                '<td colspan="2" align="center">'.
                  '<input type="submit" value="Login"/>'.
                '</td>'.
              '</tr>'.
            '</table>'.
          '</form>'.
        '</div>'.
      '</div>');
  }

  protected function loginTryCountClear()
  {
    $this->loginTryCountSet(0);
  }

  private function loginTryCountInc()
  {
    if (!paramSessionGetCheck('loginTryCount', V_INTEGER,
      $lLoginTryCount))
      $lLoginTryCount = 0;

    $this->loginTryCountSet($lLoginTryCount + 1);
  }

  private function loginTryCountSet($aValue)
  {
    $this->loginTryCount = $aValue;
    $_SESSION['loginTryCount'] = $aValue;
  }

  private function logonLevelCompare($aFirstValue, $aSecondValue)
  {
    if (!getCheckArrayValue($this->logonLevels, $aFirstValue, $lFirstValueIndex)
      || !getCheckArrayValue($this->logonLevels, $aSecondValue, $lSecondValueIndex)
    )
      return COMPARE_TYPE_LESS;

    if ($lFirstValueIndex == $lSecondValueIndex)
      return COMPARE_TYPE_EQUAL;
    else
    if ($lFirstValueIndex > $lSecondValueIndex)
      return COMPARE_TYPE_GREATER;
    else
      return COMPARE_TYPE_LESS;
  }

  private function logonLevelsRead(cXmlNode $aXmlNode)
  {
    $lLogonLevelsNode = $aXmlNode->nodes->getNextByN('LogonLevels');
    $lIndex = 0;

    while ($lLogonLevelsNode->nodes->getCheckNext($lLogonLevelNode))
    {
      $this->logonLevels[$lLogonLevelNode->name] = $lIndex;
      $lIndex++;
    }
  }

  public function logOut()
  {
    $this->clear();
    header('Location: '.($this->homePage ? $this->homePage
      : getArrayValue($_SERVER, 'PHP_SELF'))
    );
  }

  public function toArray()
  {
    return array(
      'isLogged'   => $this->isLogged,
      'logonLevel' => $this->logonLevel,
      'userLogin'  => $this->userLogin,
      'userName'   => $this->userName
    );
  }

  public function settingsReadPage(cXmlNode $aXmlNode)
  {
    parent::settingsReadPage($aXmlNode);

    $this->defaultLogonLevelPage = $this->defaultLogonLevelRead($aXmlNode);
  }

  public function settingsReadSet(cXmlNode $aXmlNode)
  {
    parent::settingsReadSet($aXmlNode);
    $this->logonLevelsRead($aXmlNode);
    $this->defaultLogonLevelSet = $this->defaultLogonLevelRead($aXmlNode);
  }

  abstract protected function readFromDb($aUserLogin, $aUserPassword);//! to override

  protected function readFromSession()
  {
    $this->logonLevel = paramSessionGet('logonLevel', V_STRING);
    $this->userId     = paramSessionGet('userId',     V_INTEGER);
    $this->userLogin  = paramSessionGet('userLogin',  V_STRING);
    $this->userName   = paramSessionGet('userName',   V_STRING);
    $this->isLogged   = true;
  }

  public function validate()
  {
    if ($this->defaultLogonLevelPage == self::NO_LOGON_LEVEL)//!! move to isLoggedGet
      $this->pageLogonLevel = $this->defaultLogonLevelSet;
    else
      $this->pageLogonLevel = $this->defaultLogonLevelPage;

    $this->init();

    $lResult = $this->isLoggedGet($this->pageLogonLevel);

    if (!$lResult)
      $this->loginPageShow();

    return $lResult;
  }

  protected function writeToSession()
  {
    $_SESSION[$this->page->appName.'_isLogged'] = $this->isLogged;

    $_SESSION['logonLevel'] = $this->logonLevel;
    $_SESSION['userId']     = $this->userId;
    $_SESSION['userLogin']  = $this->userLogin;
    $_SESSION['userName']   = $this->userName;
  }
}
?>