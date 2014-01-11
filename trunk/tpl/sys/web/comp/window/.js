page.windowSizeGet = function()
{
  var
    lWidth = 0,
    lHeight = 0;

  if (typeof(window.innerWidth) == 'number')
  {
    //!Non-IE
    lWidth = window.innerWidth;
    lHeight = window.innerHeight;
  }
  else
  if (document.documentElement
    && (document.documentElement.clientWidth
      || document.documentElement.clientHeight))
  {
    //!IE 6+ in 'standards compliant mode'
    lWidth = document.documentElement.clientWidth;
    lHeight = document.documentElement.clientHeight;
  }
  else
  if (document.body && (document.body.clientWidth || document.body.clientHeight))
  {
    //!IE 4 compatible
    lWidth = document.body.clientWidth;
    lHeight = document.body.clientHeight;
  }

  return {width: lWidth, height: lHeight};
}

page.scrollPosGet = function()
{
  var
    lX = 0,
    lY = 0;

  if (typeof(window.pageYOffset) == 'number')
  {
    //!Netscape compliant
    lX = window.pageXOffset;
    lY = window.pageYOffset;
  }
  else
  if (document.body && (document.body.scrollLeft || document.body.scrollTop))
  {
    //!DOM compliant
    lX = document.body.scrollLeft;
    lY = document.body.scrollTop;
  }
  else
  if (document.documentElement && (document.documentElement.scrollLeft
    || document.documentElement.scrollTop))
  {
    //!IE6 standards compliant mode
    lX = document.documentElement.scrollLeft;
    lY = document.documentElement.scrollTop;
  }

  return {x: lX, y: lY};
}