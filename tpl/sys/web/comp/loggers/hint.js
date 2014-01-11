page.cLoggerHint = function(aBlockName, aClassNames)
{
  var
    self = page.logger,

    fBlockName = aBlockName,
    fClassNames = aClassNames ? aClassNames : {},

    fCurrentType = null,

    fBlock            = null,
    fContainerElement = null,

    fTmrClose = null;

  function _constructor()
  {
    init(); //!! must be here because it can raise

    self.close = close;
    self.log   = log;

    return self;
  }

  function classNameByTypeGet(aType)
  {
    switch (aType) {
    case self.ltInformation:
      return fClassNames.information ? fClassNames.information
        : fBlockName + '-information';
    case self.ltWarning:
      return fClassNames.warning ? fClassNames.warning
        : fBlockName + '-warning';
    case self.ltError:
      return fClassNames.error ? fClassNames.error
        : fBlockName + '-error';
    default:
      throw new Error('Not supported LogType: ' + aType);
    }
  }

  function close()
  {
    fBlock.stop(true, false);
    fBlock.animate({opacity: 0}, 500, function()
    {
      fContainerElement.html('');
      fBlock.css('display', 'none');
      if (fCurrentType)
        fBlock.removeClass(classNameByTypeGet(fCurrentType));
      fCurrentType = null;
      fTmrClose = null;
    });
  }

  function init()
  {
    fBlock            = page.$elementGetById(fBlockName);
    fContainerElement = page.$elementGetById(fBlockName + '-container');
  }

  function log(aMessage, aType)
  {
    if (!aType)
      aType = self.ltInformation;

    if (fCurrentType)
      fBlock.removeClass(classNameByTypeGet(fCurrentType));

    fBlock.stop(true, false).css({display: 'block', opacity: 0}).
      addClass(classNameByTypeGet(aType))
    fContainerElement.html(aMessage);

    if (fTmrClose)
      clearTimeout(fTmrClose);
    fBlock.animate({opacity: 1}, 500, function()
    {
      fTmrClose = setTimeout(self.close, 5000)
    });
    fCurrentType = aType;
  }

  return _constructor();
}