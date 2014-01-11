page.animation_Toggle = function(aItem, aItemActive, aSettings)
{
  function lItemProcess(aItem, aPropsToChange)
  {
    for (var lProp in aPropsToChange)
      aItem[lProp](aPropsToChange[lProp]);
  }

  var
    lSettings = $.extend({propsToChange: {}} , aSettings);

   if (aItem)
     lItemProcess(aItem, lSettings.propsToChange.a || {});

   if (aItemActive)
     lItemProcess(aItemActive, lSettings.propsToChange.i || {});
}