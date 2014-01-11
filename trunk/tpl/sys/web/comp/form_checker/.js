page.cFormChecker = function()
{
  var
    self = this;

  function _constructor()
  {
    self.process = process;

    return self;
  }

  function formElementsEnumerate(aForm)
  {
    var
      lFormElement = null,
      lResult = {};

    for(var i = 0, l = aForm.length; i < l; i++)
    {
      lFormElement = aForm.elements[i];
      if (lFormElement.name == '')
        continue;

      if (lResult[lFormElement.name])
        page.errorRaise('Form has more than one elements with name ' +
          lFormElement.name);

      lResult[lFormElement.name] = lFormElement;
    }

    return lResult;
  }

  function process(aForm, aRequiredFieldNames, aOnSuccessFunc,
    aOnErrorFunc)
  {
    function lOnComplete(aIsValid, aEmptyFieldNames)
    {
      if (lIsValid)
      {
        if (aOnSuccessFunc)
          aOnSuccessFunc();
      }
      else
      {
        if (aOnErrorFunc)
          aOnErrorFunc(aEmptyFieldNames);
      }

      return aIsValid;
    }

    var
     lIsValid = true,
     lEmptyFieldNames = [],
     lFormElementsByName = null,
     lRequiredFieldName = '',
     lFormElement,//!!add types
     lLabel,
     lProducts;

    if (!aRequiredFieldNames.length)
      return lOnComplete(lIsValid, lEmptyFieldNames);

    lFormElementsByName = formElementsEnumerate(aForm);
    for (var i = 0, l = aRequiredFieldNames.length; i < l; i++)
    {
      lRequiredFieldName = aRequiredFieldNames[i];
      lFormElement = lFormElementsByName[lRequiredFieldName];
      if(!lFormElement)
        page.errorRaise('Form does not have field with name ' + lRequiredFieldName);

      if (lFormElement.value != '')
        continue;

      lIsValid = false;
      lEmptyFieldNames.push(lRequiredFieldName);
    }

    lOnComplete(lIsValid, lEmptyFieldNames);
  }

  return _constructor();
}