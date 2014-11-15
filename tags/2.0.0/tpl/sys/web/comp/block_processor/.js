/*!
aBlockParams
{
  sys:
  {
    is_test: true,
    is_profile: true
  },
  block1:
  {
    param1: value1,
    param2: value2
  },
  block2:
  {
    param1: value1,
    param2: value2
  }
}

aResponse - Success
{
  blocks:
  {
    block1:
    {
      data: 'html'|{json}
      initJs: ''
    },
    block2:
    {
      error: 'error_message'
    }
  }
}

aResponse - Error
{
  error: 'error_message'
}
*/
page.cBlockProcessor = function()
{
  var
    self = this;

  function _constructor()
  {
    self.requestSend       = requestSend;
    self.requestSendBlocks = requestSendBlocks;

    return self;
  }

  function paramsBuild(aBlockParams)
  {
    var
      lSysParams   = null,
      lResult      = {},
      lParamName   = '',
      lBlockName   = '',
      lBlockNames  = [],
      lBlockParams = null;

    lSysParams = aBlockParams.sys;

    if (lSysParams)
      for (lParamName in lSysParams)
        lResult[lParamName] = lSysParams[lParamName];

    if (aBlockParams)
    {
      lResult['blocks'] = '';

      for (lBlockName in aBlockParams)
      {
        if (lBlockName == 'sys')
          continue;

        lBlockParams = aBlockParams[lBlockName];
        lBlockNames.push(lBlockName);
        lResult['b'] = lBlockName;//!!

        for (lParamName in lBlockParams)
          lResult[lParamName] = lBlockParams[lParamName];//!! Not supported params with identical names
      }

      lResult['blocks'] = lBlockNames.join();
    }

    return lResult;
  }

  function paramsBuildFromUrl(aUrlParams, aBlockParams)
  {
    var
      lNameValues  = null,
      lNameValue   = '',
      lSysParams   = {},
      lBlockParams = {},
      lParamName   = '',
      lParamValue  = '',
      lBlockName   = '';

    if (aUrlParams)
    {
      lNameValues = aUrlParams.split('&')

      for (var i = 0, l = lNameValues.length; i < l; i++)
      {
        lNameValue = lNameValues[i].split('=');

        lParamName  = lNameValue[0];
        lParamValue = lNameValue[1];

        if (lParamName == 'b')
        {
          lBlockName = lParamValue;
          lBlockParams[lBlockName] = {};
        }
        else
        if (lBlockName)
          lBlockParams[lBlockName][lParamName] = lParamValue;
        else
        {
          if (!lBlockParams.sys)
            lBlockParams.sys = {};
          lBlockParams.sys[lParamName] = lParamValue;
        }
      }
    }

    if (aBlockParams && aBlockParams.sys)
      for (lParamName in aBlockParams.sys)
        lBlockParams.sys[lParamName] = aBlockParams.sys[lParamName];

    if (aBlockParams)
      for (lParamName in aBlockParams)
      {
        if (lParamName == 'sys')
          continue;

        lBlockParams[lParamName] = aBlockParams[lParamName];
      }

    return paramsBuild(lBlockParams);
  }

  function requestSend(aUrl, aParams, aOnSuccessFunc, aOnErrorFunc)
  {

    function lOnSuccess(aResponse)
    {
      var
        lResponse = eval(aResponse);

      if (lResponse.error)
        lOnError(lResponse.error);
      else
      if (aResponse.blocks)
        aOnSuccessFunc(aResponse.blocks)
      else
        page.errorRaise('Invalid Response format: "' + aResponse + '"');
    }

    function lOnError(aError)
    {
      if (aOnErrorFunc)
        aOnErrorFunc(aError);
      else
        page.errorRaise(aError);
    }

    $.post(aUrl, aParams, lOnSuccess, 'json').fail(
      function(aResponse)
      {
        lOnError(aResponse.responseText);
      });
  }

  function requestSendBlocks(aUrl, aBlockParams, aOnSuccessFunc, aOnErrorFunc)
  {
    var
      lUrlParts = aUrl.split('?');

    self.requestSend(
      lUrlParts[0],
      paramsBuildFromUrl(lUrlParts.length == 2 ? lUrlParts[1] : '',
        aBlockParams),
      aOnSuccessFunc,
      aOnErrorFunc
    );
  }

  return _constructor();
}

page.cPageBlockProcessor = function()
{
  var
    self = this,
    fBlockProcessor = new page.cBlockProcessor();

  function _constructor()
  {
    self.requestSend = requestSend;

    return self;
  }

  function requestSend(aUrl, aBlockParams, aOnSuccessFunc, aOnErrorFunc)
  {
    function lOnSuccess(aBlocks)
    {
      var
        lBlockResponce = null,
        lBlockElement = null;

      for(var lBlockName in aBlocks)
      {
        lBlockResponce = aBlocks[lBlockName];

        if (lBlockResponce.error)
          lOnError(lBlockResponce.error);
        else
        {
          lBlockElement = page.$elementGetById(lBlockName);
          lBlockElement.replaceWith(lBlockResponce.data);
          if (lBlockResponce.initJs)
            eval(lBlockResponce.initJs);
        }
      }

      if (aOnSuccessFunc)
        aOnSuccessFunc(aBlocks);
    }

    fBlockProcessor.requestSendBlocks(aUrl || window.location.href,
      aBlockParams, lOnSuccess, aOnErrorFunc);
  }

  return _constructor();
}

page.blocksRefresh = function(aBlockNames, aBlocksParams, aOnComplete, aOnError)
{
  var
    lBlockProcessor = new page.cPageBlockProcessor(),
    lParams = {},
    lBlocksParams = aBlocksParams || {};

  for(var i = 0, l = aBlockNames.length; i < l; i++)
    lParams[aBlockNames[i]] = lBlocksParams[aBlockNames[i]] || null;

  lBlockProcessor.requestSend(document.location.href, lParams,
    aOnComplete, aOnError);
}