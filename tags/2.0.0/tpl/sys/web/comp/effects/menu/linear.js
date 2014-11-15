page.cMenuLinear = function(aBlockName, aSettings, aItemActiveIndex)
{
  var
    self = this,
    fSettings = {
      action: 'click',
      animation: 'Toggle',
      animationSettings : null,
      deactivateOnLeave: false
    },
    fContainer = page.$elementGetById(aBlockName),
    fActiveItemRec = null,
    ftmrDeactivate = null;

  function _constructor()
  {
    fSettings = $.extend(fSettings, aSettings);

    init();

    return self;
  }

  function animate(aItemRec, aIsAnimate)//!!aIsAnimate - not used
  {
    var
      lAnimationFunc = page['animation_' + fSettings.animation];

    if (!lAnimationFunc)
      page.errorRaise('Undefined animation function: "' + fSettings.animation +
        '"');

    lAnimationFunc(aItemRec ? aItemRec.item : null,
      fActiveItemRec ? fActiveItemRec.item : null, fSettings.animationSettings);
    lAnimationFunc(aItemRec ? aItemRec.content : null,
      fActiveItemRec ? fActiveItemRec.content : null, {
        propsToChange: {
          a: {css: {display: 'block'}},
          i: {css: {display: 'none'}}
        }
       });

    fActiveItemRec = aItemRec;
  }

  function init()
  {
    function LEventsInit(aItem, aItemContent)
    {
      aItem[fSettings.action](
        function()
        {
          if (fSettings.deactivateOnLeave && ftmrDeactivate)
            clearTimeout(ftmrDeactivate);

          if (fActiveItemRec && fActiveItemRec.item == aItem)
            animate(null, true);
          else
            animate({item: aItem, content: aItemContent},  true);
        });

      if (fSettings.deactivateOnLeave)
        lItem.mouseout(
          function()
          {
            ftmrDeactivate = setTimeout(function()
            {
              animate(null, true);
            }, 200);
          });
    }

    var
      i = 0,
      lItem = null,
      lItemContent = null,
      lIdPrefix = aBlockName + '-',
      lActiveItemRec = null;

    while(true)
    {
      lItem = page.$elementGetCheckById(lIdPrefix + i);
      if (!lItem)
        break;

      lItemContent = page.$elementGetCheckById(lIdPrefix + i + '-content');

      if (lItemContent)
         lItemContent.appendTo(lItem);

      LEventsInit(lItem, lItemContent);

      if (i == aItemActiveIndex || lItem.attr('isActive'))
        lActiveItemRec = {
          item: lItem
        };

      i++;
    }

    if (lActiveItemRec)
      animate(lActiveItemRec, false);
  }

  return _constructor();
}