'use strict';

window.page = {
  isTestMode: false,
  rootPath:   'http://' + document.location.hostname  + '/',

  logger:
  {
    ltInformation: 'i',
    ltWarning:     'w',
    ltError:       'e',

    crlf: '/n',
    log: function(aMessage, aType)
    {
      alert(aMessage);
    },

    error: function(aMessage)
    {
      this.log(aMessage, this.ltError);
    },
    info: function(aMessage)
    {
      this.log(aMessage, this.ltInformation);
    },
    warn: function(aMessage)
    {
      this.log(aMessage, this.ltWarning);
    }
  },

  abstractError: function(aFuncName)
  {
    page.errorRaise(aFuncName + ': abstract');
  },

  elementAnimateWithStep: function(aElement, aCssParams,
    aAnimationDuration, aStepFunction, aCallBackFunction)
  {
    aElement.css(aCssParams).animate(
      {
        stub: 0
      },
      {
        duration: aAnimationDuration,
        step:     aStepFunction,
        complete: aCallBackFunction
      }
    );
  },

  elementLeftGet: function(aElement)
  {
    return aElement.offset().left - aElement.parent().offset().left;
  },

  elementTopGet: function(aElement)
  {
    return aElement.offset().top - aElement.parent().offset().top;
  },

  $elementGetById: function(aId)
  {
    var lResult = this.$elementGetCheckById(aId);
    if (!lResult)
      this.errorRaise('Not found element by ID '+ aId);
    return lResult;
  },

  elementGetById: function(aId)
  {
    var lResult = this.elementGetCheckById(aId);
    if (!lResult)
      this.errorRaise('Not found element by ID '+ aId);
    return lResult;
  },

  elementGetCheckById: function(aId)
  {
    return document.getElementById(aId) || false;
  },

  $elementGetCheckById: function(aId)
  {
    var lResult = $('#' + aId);
    return lResult.length ? lResult : false;
  },

  errorRaise: function(aMessage)
  {
    this.logger.error(aMessage);
    if (this.isTestMode)
      debugger;
    throw new Error(aMessage);
  },

  eventGet: function(aEvent)
  {
    return aEvent ? aEvent : window.event;
  },

  parseFloatDef: function(aValue, aDefault)
  {
    var lValue = parseFloat(aValue.replace(',', '.'));
    return ((typeof(lValue) == 'number') ? parseFloat(lValue.toFixed(2))
      : aDefault);
  },

  stopPropagation: function(aEvent)
  {
    var
      lEvent = page.eventGet(aEvent);

    lEvent.cancelBubble = true;
    if (lEvent.stopPropagation)
      lEvent.stopPropagation();
  },

  urlParamValueGet: function(aParamName)
  {
    var
      lParams = window.location.search.substring(1).split('&'),
      lParam = null;

    for(var i = 0, l = lParams.length; i < l; i++)
    {
      lParam = lParams[i].split('=');
      if (lParam[0] == aParamName) {
        return unescape(lParam[1]);
      }
    }

    return null;
  }
};