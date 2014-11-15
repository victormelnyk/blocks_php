function cAJAX(AServerPagePath, AErrorFunc, ASuccessFunc)
{
  var
    self = this,
    FIsRequestActive = false,
    FOnError = AErrorFunc,
    FOnSuccess = ASuccessFunc;

  function _constructor()
  {
    self.requestSend = requestSend;
    self.responseToObject = responseToObject;

    return self;
  }

  function objectToXML(ARequestParams)
  {
    function LNodeAdd(AParentNode, AName, AValue)
    {
      var
        LNode = $('<' + AName + '>');

      LNode.html(AValue);
      AParentNode.append(LNode);
    }

    function LDataXMLBuld(AParentNode, AData)
    {
      var
        LItem = null,
        LItemNode = null;

      for (var i = 0, LLen = AData.length; i < LLen; i++)
      {
        LItem = AData[i];
        LItemNode = $('<row>');
        for(var LProp in LItem)
          LNodeAdd(LItemNode, LProp, LItem[LProp]);

        AParentNode.append(LItemNode);
      }
    }

    var
      LXML = $('<main>'),
      LMainNode = $('<request>'),
      LParamsNode = $('<params>'),
      LDataNode = $('<data>');

    for(var LProp in ARequestParams)
      if (LProp == 'data')
        LDataXMLBuld(LDataNode, ARequestParams[LProp]);
      else
        LNodeAdd(LParamsNode, LProp, ARequestParams[LProp]);

    LMainNode.append(LParamsNode);
    LMainNode.append(LDataNode);
    LXML.append(LMainNode);
    return LXML.html();
  }

  function requestComplete(AResponse)
  {
    if (!FIsRequestActive)
      return;

    FOnError(AResponse)
  }

  function requestError(AResponse)
  {
    FIsRequestActive = false;
    FOnError(AResponse);
  }

  function requestSuccess(AResponse)
  {
    function LErrorFind(ANode)
    {
      //!!
      var LNode = null;
      for(var i = 0, LLen = ANode.childNodes.length; i < LLen; i++)
      {
        LNode = ANode.childNodes[i];
        switch (LNode.nodeName)
        {
          case 'error':
          case 'parseerror':
            return true;
          case 'status':
            if (LNode.childNodes.length == 1)
            {
              if (LNode.childNodes[0].nodeValue == 'error')
                return true;
            }
            else
              alert('Invalid response'); //!! raise; 
        }
        
        if (LErrorFind(LNode))
          return true;        
      }

      return false;
    }

    FIsRequestActive = false;

    if(LErrorFind(AResponse))
      FOnError(AResponse);
    else
      FOnSuccess(AResponse);
  }

  function requestSend(ARequestParams)
  {
    sendXML(objectToXML(ARequestParams));
  }

  function responseToObject(AResponse, AErrorLogFunc)
  {
    function LNodesProcess(ANodes)
    {
      var
        LResult = {},
        LNode = null;

      for(var i = 0, LLen = ANodes.length; i < LLen; i++)
      {
        LNode = ANodes[i];
        LResult[LNode.tagName] = LNode.textContent;
      }

      return LResult;
    }

    var
      LMainNodes = AResponse.childNodes,
      LResponse = null,
      LChildNodes = null,
      LNode = null,
      LData = null,
      LRows = null,
      LRow = null,
      LResult = {params: {}, data: []};

    if (LMainNodes.length != 1)
      LOnErrorFunc('Only one main node supported');

    LResponse = LMainNodes[0];
    if (LResponse.tagName != 'response')
      AErrorLogFunc('No response node');

    LChildNodes = LResponse.children;
    for(var i = 0, LLen = LChildNodes.length; i < LLen; i++)
    {
      LNode = LChildNodes[i];
      switch (LNode.nodeName)
      {
        case 'params':
          LResult.params = LNodesProcess(LNode.children);
          break;
        case 'data':
          LRows = LNode.children;
          for(var LRowIndex = 0, LRowCount = LRows.length; LRowIndex < LRowCount; LRowIndex++)
          {
            LRow = LRows[LRowIndex];
            if (LRow.tagName != 'row')
              AErrorLogFunc('Invalid row node');

            LResult.data.push(LNodesProcess(LRow.children));
          }
          break;
        default:
          AErrorLogFunc('Not supported node: ' + LNode.nodeName);
      }
    }

    return LResult;
  }

  function sendXML(AXML)
  {
    var
      FIsRequestActive = true,
      LPost = $.post(
        AServerPagePath,
        {xml: AXML},
        requestSuccess,
        'xml'
      )
      .error(requestError)
      .complete(requestComplete);
  }

  return _constructor();
}