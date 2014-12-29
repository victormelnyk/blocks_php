<?php
//!!$aLogin -> $aUserLogin
abstract class cBlocks_Auth_PasswordRestore extends cBlock
{
  protected $errorType = '';
  protected $status = false;

  protected $db = null;

  public function build()
  {
    if (!$this->cache->isValid)//!for cache
      $this->fileFirstExistDataGet('report.htm');
    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
      array(
        'status'    => $this->status,
        'errorType' => $this->errorType
      ));
  }

  public function init()
  {
    parent::init();
    $this->db = $this->settings->db;
    $this->initInternal();
  }

  protected function initInternal()
  {
    $lLogin = '';
    $this->status = $this->paramsReadCheck($lUserId, $lLogin);
    if ($this->status)
      $this->passwordRestore($lUserId, $lLogin);
  }

  abstract protected function mailSend($aLogin, $aPasswordNew, $aReportHtml);//!!$aLogin <> email

  protected function onError($aErrorType)
  {
    $this->initScriptAdd('page.logger.error("'.
      $this->localizationTagValueGet($aErrorType).'")');
  }

  protected function onSuccess()
  {
    $this->initScriptAdd('page.logger.log("'.
      $this->localizationTagValueGet('Success').'")');
  }

  private function paramsReadCheck(&$aUserId, &$aLogin)
  {
    $lResult = paramPostGetGetCheck('login', V_STRING, $aLogin);

    if ($lResult && !$this->userIdGetCheck($aLogin, $aUserId))
    {
      $this->errorType = 'LoginError';
      $this->onError($this->errorType);
      return false;
    }

    return $lResult;
  }

  protected function passwordRestore($aUserId, $aEmail)
  {
    $lPasswordNew = cCryptHelper::randomStringGenerate(6);
    $lRandomPart = cCryptHelper::dynamicRandomPartGenerate();
    $lPasswordHash = cCryptHelper::stringCrypt($lPasswordNew,
      STATIC_RANDOM_PART, $lRandomPart);

    $lParams = array(
      'password'    => $lPasswordHash,
      'random_part' => $lRandomPart
    );

    $this->db->beginTran();
    try
    {
      $this->passwordUpdate($aUserId, $lParams);

      $this->mailSend($aEmail, $lPasswordNew, $this->templateProcess(
        $this->fileFirstExistDataGet('report.htm'),
        array('passwordNew' => $lPasswordNew)));

      $this->db->commitTran();

      $this->onSuccess();
    }
    catch (Exception $e)
    {
      $this->db->rollbackTran();
      $this->errorType = 'Error';
      $this->onError($this->errorType);
    }
  }

  abstract protected function passwordUpdate($aUserId, $aParams);

  abstract protected function userIdGetCheck($aEmail, &$aUserId);
}
?>