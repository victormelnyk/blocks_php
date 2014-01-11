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

    $this->db = $this->settings->db;
    $this->status = $this->paramsReadCheck($this->errorType);
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
      $this->errorType = 'Error';
    }

    $this->settings->context->login($this->params['login'],
      $this->params['password'], $this->homePageGet());
    $this->onSuccess();
  }

  abstract protected function loginExist($aLogin);

  protected function onError($aErrorType)
  {
    $this->initScriptAdd('page.logger.error("'.
      $this->localizationTagValueGet($aErrorType).'")');
  }

  protected function onSuccess()
  {
    $this->initScriptAdd('window.location.href = "'.$this->homePageGet().'"');
  }

  private function paramsReadCheck(&$aErrorType)
  {
    $lReadedParamCount = 0;
    if (paramPostGetGetCheck('login', VAR_TYPE_STRING, $this->params['login']))
      $lReadedParamCount++;
    if (paramPostGetGetCheck('name', VAR_TYPE_STRING, $this->params['name']))
      $lReadedParamCount++;
    if (paramPostGetGetCheck('password', VAR_TYPE_STRING,
      $this->params['password']))
      $lReadedParamCount++;
    if (paramPostGetGetCheck('password_confirm', VAR_TYPE_STRING,
      $this->params['password_confirm']))
      $lReadedParamCount++;

    if ($lReadedParamCount != 4)
    {
      if ($lReadedParamCount > 0)
      {
        $aErrorType = 'Params';
        $this->onError($aErrorType);
      }
      return false;
    }

    if ($this->params['password'] != $this->params['password_confirm'])
    {
      $aErrorType = 'PasswordConfirmError';
      $this->onError($aErrorType);
      return false;
    }

    if ($this->loginExist($this->params['login']))
    {
      $aErrorType = 'LoginError';
      $this->onError($aErrorType);
      return false;
    }

    return true;
  }

  abstract protected function userSave(array $aParams);
}
?>