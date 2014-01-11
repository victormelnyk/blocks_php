page.cSignUp = function(aBlockName, aRequiredFiledNames, aErrorClassName)
{
  var
    self = this;

  function _constructor()
  {
    self.submitCheck = submitCheck;

    return self;
  }

  function fieldHighlight(aFormElement, aIsValid)
  {
    if (!aErrorClassName)
      return;

    var
      lElement = page.$elementGetById(aBlockName + '-' + aFormElement.name);

    if (aIsValid)
      lElement.removeClass(aErrorClassName);
    else
      lElement.addClass(aErrorClassName);
  }

  function submitCheck(aForm)
  {

    function lOnSuccessFunc()
    {
      if (aForm.password.value == aForm.password_confirm.value)
        aForm.submit();
      else
      {
        fieldHighlight(aForm.password, false);
        fieldHighlight(aForm.password_confirm, false);
        page.logger.error("<~ml|PasswordConfirmError~>");
      }
    }

    function lOnErrorFunc(aEmptyFieldNames)
    {
      var
        lFieldName = '';

      for(var i = 0, l = aEmptyFieldNames.length; i < l; i++)
      {
        lFieldName = aEmptyFieldNames[i];
        fieldHighlight(aForm[lFieldName], false);
      }

      page.logger.error("<~ml|ParamsError~>");
    }

    var
      lFormChecker = new page.cFormChecker(),
      lFieldName = '',
      lLabelElement;

    for(var i = 0, l = aRequiredFiledNames.length; i < l; i++)
    {
      lFieldName = aRequiredFiledNames[i];
      fieldHighlight(aForm[lFieldName], true);
    }

    lFormChecker.process(aForm, aRequiredFiledNames, lOnSuccessFunc, lOnErrorFunc);
    return false;
  }

  return _constructor();
}