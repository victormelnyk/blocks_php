<?php
//!!$aLogin -> $aUserLogin
abstract class cBlocks_Auth_PasswordRestore extends cBlock
{
  private $errorType = '';
  private $status = false;

  protected $db = null;

  public function build()
  {
    //!for cache
    if (!$this->cache->isValid)
      $this->fileFirstExistDataGet('report.htm');

    return $this->templateProcess($this->fileFirstExistDataGet('.htm'),
      array(
        'status'    => $this->status,
        'errorType' => $this->errorType
      ));
  }

  public function init()
  {
    $lLogin = '';
    $this->db = $this->settings->db;
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
    $lResult = paramPostGetGetCheck('login', VAR_TYPE_STRING, $aLogin);

    if ($lResult && !$this->userIdGetCheck($aLogin, $aUserId))
    {
      $this->errorType = 'LoginError';
      $this->onError($this->errorType);
      return false;
    }

    return $lResult;
  }

  private function passwordRestore($aUserId, $aLogin)
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

      $this->mailSend($aLogin, $lPasswordNew, $this->templateProcess(
        $this->fileFirstExistDataGet('report.htm'),
        array('passwordNew' => $lPasswordNew)));

      $this->db->commitTran();
    }
    catch (Exception $e)
    {
      $this->db->rollbackTran();
      $this->errorType = 'Error';
      $this->onError($this->errorType);
    }
    $this->onSuccess();
  }

  abstract protected function passwordUpdate($aUserId, $aParams);

  abstract protected function userIdGetCheck($aLogin, &$aUserId);
}
?>