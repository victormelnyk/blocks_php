page.cPasswordChange = function(aBlockName, aRequiredFiledNames, aIsHasLabels, aTextConsts) //!! aTextConsts - use Vacabulary
{
  var
    self = this;

  function _constructor()
  {
    self.submitCheck = submitCheck;

    return self;
  }

  function submitCheck(aForm)
  {
    function lLabelElementGet(aFieldName)
    {
      var
        lParams = {id: aBlockName + '-' + aFieldName + '_label'},
        LElement = null;

      if (aIsHasLabels)
        LElement = page.elementGetById(lParams.id);

      return LElement;
    }

    function lFieldHighlight(aLabelElement, aFormElement, aIsValid)
    {
      if (aIsValid)
      {
        if (aLabelElement)
          aLabelElement.className = 'form-label';
        aFormElement.className = 'form-input';
      }
      else
      {
        if (aLabelElement)
          aLabelElement.className = 'form-label_error';
        aFormElement.className = 'form-input_error';
      }
    }

    function lOnSuccessFunc()
    {
      if (aForm.password_new.value == aForm.password_new_confirm.value)
        aForm.submit();
      else
      {
        lFieldHighlight(lLabelElementGet('password_new'), aForm.password_new, false);
        lFieldHighlight(lLabelElementGet('password_new_confirm'),
          aForm.password_new_confirm, false);
        page.logger.log(aTextConsts.passwordConfirmError);
      }
    }

    function lOnErrorFunc(aEmptyFieldNames)
    {
      var
        lFieldName = '';

      for(var i = 0, l = aEmptyFieldNames.length; i < l; i++)
      {
        lFieldName = aEmptyFieldNames[i];
        lFieldHighlight(lLabelElementGet(lFieldName), aForm[lFieldName], false);
      }

      page.logger.log(aTextConsts.error);
    }

    var
      lFormChecker = new page.cFormChecker(),
      lFieldName = '',
      lLabelElement;

    for(var i = 0, l = aRequiredFiledNames.length; i < l; i++)
    {
      lFieldName = aRequiredFiledNames[i];
      lFieldHighlight(lLabelElementGet(lFieldName), aForm[lFieldName], true);
    }

    lFormChecker.process(aForm, aRequiredFiledNames, lOnSuccessFunc, lOnErrorFunc);
    return false;
  }

  return _constructor();
}