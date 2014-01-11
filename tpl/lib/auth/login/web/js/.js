page.cLogin = function()
{
  var
    self = this;

  function _constructor()
  {
    self.login  = login;
    self.logout = logout;

    return self;
  }

  function login(aForm)
  {
    function lFieldHighlight(aFormElement, aIsValid)
    {
      /*!!
      aFormElement.className =
        (aIsValid ? 'form-input ' : 'form-input_error ') + 'login-input';
     */
    }

    function lOnSuccessFunc()
    {
      aForm.submit();
    }

    function lOnErrorFunc(aEmptyFieldNames)
    {
      var
        lFieldName = '';

      for(var i = 0, l = aEmptyFieldNames.length; i < l; i++)
      {
        lFieldName = aEmptyFieldNames[i];
        lFieldHighlight(aForm[lFieldName], false);
      }

      page.logger.warn('<~ml|Error~>');
    }

    var
      lFormChecker = new page.cFormChecker(),
      lFieldName = '',
      lRequiredFiledNames = ['user_login', 'user_password'],
      lLabelElement;

    for(var i = 0, l = lRequiredFiledNames.length; i < l; i++)
    {
      lFieldName = lRequiredFiledNames[i];
      lFieldHighlight(aForm[lFieldName], true);
    }

    lFormChecker.process(aForm, lRequiredFiledNames, lOnSuccessFunc, lOnErrorFunc);

    return false;
  }

  function logout(ARootDir)
  {
    function lOnComplete(aResponse)
    {
      window.location.href = ARootDir + 'index.php';
    }

    var
      lRequest = $.get(
        document.location.pathname + '?' + 'user_logout=true',
        lOnComplete,
        'json'
      )
      .complete(lOnComplete);

    return false;
  }

  return _constructor();
}