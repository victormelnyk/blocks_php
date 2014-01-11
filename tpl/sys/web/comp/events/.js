page.cFunctionsArray = function()
{
  var
    self = [];

  function _constructor()
  {
    self.clear      = clear;
    self.executeAll = executeAll;

    return self;
  }

  function clear()
  {
    self = [];
  }

  function executeAll()
  {
    var
      lParams = ''

    for(var i = 0, l = self.length; i < l; i++)
    {
      lParams = '';
      for(var j = 0, n = arguments.length; j < n; j++)
        lParams += (j == 0 ? '': ',') + 'arguments[' + j + ']';
      eval('self[i](' + lParams + ')');
    }
  }

  return _constructor();
}