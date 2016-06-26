<?
abstract class Blocks_Auth_PasswordChange extends Block
{
  private $errorType = '';
  private $status = false;

  protected $db = null;

  public function build()
  {
    return $this->processTemplate($this->getFirstExistFileData('.htm'),
      array('status' => $this->status, 'errorType' => $this->errorType));
  }

  public function init()
  {
    $this->status = $this->paramsProcess($this->errorType);

    if ($this->status)
      $this->onSuccess();
  }

  abstract protected function onError($aErrorType);

  abstract protected function onSuccess();

  private function paramsProcess(&$aErrorType)
  {
    $lReadedParamCount = 0;
    if (paramPostGetGetCheck('password_old', V_STRING, $lPasswordOld))
      $lReadedParamCount++;
    if (paramPostGetGetCheck('password_new', V_STRING, $lPasswordNew))
      $lReadedParamCount++;
    if (paramPostGetGetCheck('password_new_confirm', V_STRING,
      $lPasswordNewConfirm))
      $lReadedParamCount++;

    if ($lReadedParamCount != 3)
    {
      if ($lReadedParamCount > 0)
      {
        $aErrorType = 'params';
        $this->onError($aErrorType);
      }
      return false;
    }

    $lDb = $this->settings->db;

    $lUserId = $this->settings->context->userId;

    if ($lPasswordNew != $lPasswordNewConfirm)
    {
      $aErrorType = 'confirm';
      $this->onError($aErrorType);
      return false;
    }

    eAssert($this->passwordInfoReadCheck($lDb, $lUserId, $lPassword,
      $lRandomPart), 'User not logged');

    $lPaswordOldHash = cCryptHelper::stringCrypt($lPasswordOld,
      STATIC_RANDOM_PART, $lRandomPart);
    if ($lPaswordOldHash != $lPassword)
    {
      $aErrorType = 'check';
      $this->onError($aErrorType);
      return false;
    }

    $lRandomPartNew = cCryptHelper::dynamicRandomPartGenerate();
    $this->passwordInfoSave($lDb, $lUserId, cCryptHelper::stringCrypt(
      $lPasswordNew, STATIC_RANDOM_PART, $lRandomPartNew), $lRandomPartNew);

    return true;
  }

  abstract protected function passwordInfoReadCheck($aDb, $aLogin, &$aPassword,
    &$aRandomPart);
}
?>