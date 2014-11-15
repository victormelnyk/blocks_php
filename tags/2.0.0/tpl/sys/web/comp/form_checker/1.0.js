page.formCheckerRulles = {
  'number': new RegExp(/^[0-9]+$/),
  'phone':  new RegExp(/^[+]?[0-9]+$/),
  'email':  new RegExp(/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/),
  'date':   new RegExp(/^\d{2}[.]\d{2}[.]\d{4}$/)
}

page.cFormChecker = function(aParams)
{
  var
    self = this,

    fParams = aParams && aParams.length ? aParams : null;

  function _constructor()
  {
    self.highlightFunc    = highlightFunc;
    self.liveCheckInit    = liveCheckInit;
    self.valueByTypeCheck = valueByTypeCheck;
    self.process          = process;

    return self;
  }

  function highlightFunc(aElement, aIsActive)
  {
    //!!change to class add/remove
    if (aIsActive)
      $(aElement).css({border: '1px solid #FF0000'}).click(function(){
        aElement.unbind('click').css({border: 'auto'});
      });
    else
      aElement.css({border: 'auto'});
  }

  function fieldCheckProcess(aElement, aParams, aFormData)
  {
    var
     lResult      = true,
     lIsReguired  = aParams['isReguired'] && true,
     lIsCheck     = aParams['isCheck'] && true,
     lIsLiveCheck = aParams['isLive'] && true,
     lValue       = lIsLiveCheck ? aParams['newValue'] : aElement.val(),
     lLength      = lValue.length,
     lFieldName   = aParams['name'],
     lType        = aParams['type'];

    if (lFieldName)
      if (aElement.attr('type') == 'file')
        aFormData && aFormData.append(lFieldName, aElement[0].files[0]);
      else
        aFormData && aFormData.append(lFieldName, lValue);

    if (!lIsReguired && !lIsCheck)
      return lResult;

    var
      lValueLength = aParams['length'] || 0,
      lMaxLength   = aParams['maxLength'] || 0;

    if((lIsReguired && (lLength == 0) && !lIsLiveCheck)
      || (lMaxLength && (lLength > lMaxLength))
      || (lValueLength && lLength
          && (lIsLiveCheck ? (lLength > lValueLength) : (lLength != lValueLength)))
      || (lType && (lLength && !self.valueByTypeCheck(lValue, lType)))
    )
    {
      if (!lIsLiveCheck || aParams['issAllowTypeOnError'])
        self.highlightFunc(aElement, true);

      lResult = false;
    }
    else
      self.highlightFunc(aElement, false);

    return lResult;
  }

  function fieldLiveCheck(aEvent, aElement, aParams)
  {
    if ((aEvent.keyCode < 48)
      && (aEvent.keyCode != 32)
      && (aEvent.keyCode != 0)
    )
      return true;
    else
    {
      var
        lChar = String.fromCharCode(aEvent.keyCode);

      if (((aEvent.keyCode = 107) && (aEvent.key == 'Add'))
        || ((aEvent.keyCode = 61) && (aEvent.shiftKey))
      )
        lChar = '+';

      aParams['newValue'] = aElement.val() + lChar;

      return !aIsAllowTypeOnError && fieldCheckProcess(aElement, aParams, null);
    }
  }

  function fieldParamsFromElementGet(aElement)
  {
     return {
       isReguired:         aElement.hasClass('validate') && true,
       isCheck:            aElement.hasClass('check') && true,
       isLive:             aElement.hasClass('live_check') && true,
       type:               aElement.attr('valueType'),
       name:               aElement.attr('name'),
       length:             aElement.attr('valueLength'),
       maxlength:          aElement.attr('maxValueLength'),
       isAllowTypeOnError: false//!!read from attr
    };
  }

  function liveCheckInit(aFormElement)
  {
    if (fParams)
      page.errorRaise('LiveCheck can not be initialized if Params set');

    $(aFormElement).on('keydown', '.form_field .live_check', function(aEvent)
    {
      fieldLiveCheck(aEvent, $(this), fieldParamsFromElementGet($(this)));
    });
  }

  function process(aFormElement, aFormData)
  {
    var
      lResult = true;

    if (fParams)
    {
      var
        lFieldParams = '',
        lElement     = null;

      if (aFormElement.length)
        aFormElement = aFormElement[0];

      for(var i = 0, l = fParams.length; i < l; i++)
      {
        lFieldParams = fParams[i];
        lElement     = aFormElement[lFieldParams['name']];

        if (!lElement)
          page.errorRaise('Can not find element: "' + lFieldParams['name'] +
            '" in form');

        lFieldParams['isCheck'] = true;

        if (!fieldCheckProcess($(lElement), lFieldParams, aFormData))
          lResult = false;
      }
    }
    else
      (aFormElement && aFormElement.length
        ? aFormElement.find('.form_field') : $('.form_field')).each(
        function()
        {
          if (!fieldCheckProcess($(this), fieldParamsFromElementGet($(this)),
            aFormData)
          )
            lResult = false;
        }
      );

    return lResult;
  }

  function valueByTypeCheck(aValue, aType)
  {
    var
      lRegExp = page.formCheckerRulles[aType];

    if (!lRegExp)
      page.errorRaise('Not supported value type: ' + aType);

    return lRegExp.test(aValue);
  }

  return _constructor();
}