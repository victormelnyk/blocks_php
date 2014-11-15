page.cLogin = function(aRequiredFiledNames)
{
  var
    self = this;

  function _constructor()
  {
    self.login  = login;
    self.logout = logout;

    self.onSubmit = null;

    self.highLightFunc = null;

    return self;
  }

  function login(aForm, aEvent)
  {
    function lFieldHighlight(aFormElement, aIsValid)
    {
      //!! make this code common highlight logic
      if (self.highLightFunc)
        self.highLightFunc(aFormElement, aIsValid);

      var
        lElement = $(aFormElement),
        lBorder  = aIsValid ? '' : lElement.css('border'),
        lRestoreFunc = function()
        {
          lElement.css('border', lBorder);
        };

      if (aIsValid)
        lRestoreFunc();
      else
        lElement.css('border', '1px solid red').one('click', lRestoreFunc)
          .one('focus', lRestoreFunc);
    }

    function lOnSuccessFunc()
    {
      if (self.onSubmit)
        self.onSubmit();

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

    $.Event(aEvent).preventDefault();

    var
      lFormChecker = new page.cFormChecker(),
      lFieldName = '',
      lRequiredFiledNames = aRequiredFiledNames ? aRequiredFiledNames : ['user_login', 'user_password'],
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
      lRequest = $.post(
        document.location.pathname + '?' + 'user_logout=true',
        lOnComplete,
        'json'
      )
      .complete(lOnComplete);

    return false;
  }

  return _constructor();
}