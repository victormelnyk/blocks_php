<?
//!!$aLogin -> $aUserLogin
abstract class Blocks_Auth_PasswordRestore extends Block
{
  protected $errorType = '';
  protected $status = false;

  protected $db = null;

  public function build()
  {
    if (!$this->cache->isValid)//!for cache
      $this->getFirstExistFileData('report.htm');
    return $this->templateProcess($this->getFirstExistFileData('.htm'),
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
    $this->addInitScript('page.logger.error("'.
      $this->getMlTagValue($aErrorType).'")');
  }

  protected function onSuccess()
  {
    $this->addInitScript('page.logger.log("'.
      $this->getMlTagValue('Success').'")');
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
        $this->getFirstExistFileData('report.htm'),
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