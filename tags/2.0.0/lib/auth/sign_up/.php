<?php
//!!$aLogin -> $aUserLogin
abstract class cBlocks_Auth_SignUp extends cBlock
{
  private $status    = false;
  private $errorType = '';
  private $params    = array();

  protected $db = null;

  public function build()
  {
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'), array(
      'status'    => $this->status,
      'errorType' => $this->errorType,
      'params'    => $this->params)
    );
  }

  protected function homePageGet()
  {
    return 'index.php';
  }

  public function init()
  {
    parent::init();

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
      $this->paramsReadCheckInternal($lTempErrorType); //! $lTempErrorType - used only to call function
      return;
    }

    $this->db = $this->settings->db;
    $this->status = $this->paramsReadCheckInternal($this->errorType);
    if (!$this->status)
      return;

    $this->db->beginTran();
    try
    {
      $this->userSave($this->params);
      $this->db->commitTran();
    }
    catch (Exception $e)
    {
      $this->db->rollbackTran();
      $this->status = false;
      $this->errorType = 'Error';
      $this->onError($this->errorType);
      throw $e;//!!
    }

    $this->onSuccess();
  }

  abstract protected function loginExist($aLogin);

  protected function onError($aErrorType)
  {
    $this->initScriptAdd('page.logger.error("'.
      $this->localizationTagValueGet($aErrorType).'")');
  }

  protected function onSuccess()//!!redirect on server
  {
    $this->settings->context->login($this->params['login'],
      $this->params['password'], $this->homePageGet());
    $this->initScriptAdd('window.location.href = "'.$this->homePageGet().'"');
  }

  private function paramsReadCheckInternal(&$aErrorType)
  {
    $lCheckResult = $this->paramsReadCheck();

    $this->params = $lCheckResult['params'];

    $aErrorType = $lCheckResult['errorType'];

    return $lCheckResult['status'];
  }

  protected function paramsReadCheck()
  {
    $lParams = array();
    $lErrorType = '';

    $lStatus = $this->paramsReadInternal($lParams);

    if ($lStatus)
    {
      if ($lParams['password'] != $lParams['password_confirm'])
      {
        $lErrorType = 'PasswordConfirmError';
        $this->onError($lErrorType);
        $lStatus = false;
      }

      if ($lStatus && $this->loginExist($lParams['login']))
      {
        $lErrorType = 'LoginError';
        $this->onError($lErrorType);
        $lStatus = false;
      }
    }
    else
      $lErrorType = 'ParamsError';

    return array(
      'status'    => $lStatus,
      'params'    => $lParams,
      'errorType' => $lErrorType
    );
  }

  protected function paramsReadInternal(array &$lParams)
  {
    $lResult = paramPostGetCheck('login', VAR_TYPE_STRING, $lParams['login']);
    $lResult = paramPostGetCheck('name', VAR_TYPE_STRING, $lParams['name'])
      && $lResult;
    $lResult = paramPostGetCheck('password', VAR_TYPE_STRING,
      $lParams['password']) && $lResult;
    $lResult = paramPostGetCheck('password_confirm', VAR_TYPE_STRING,
      $lParams['password_confirm']) && $lResult;

    return $lResult;
  }

  abstract protected function userSave(array $aParams);
}
?>