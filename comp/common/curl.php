<?php
function dataGetByURL($aURL)
{
  $lAgent = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

  $lCurl = curl_init();//!!check oop implementation

  curl_setopt($lCurl, CURLOPT_USERAGENT,      $lAgent);
  curl_setopt($lCurl, CURLOPT_URL,            $aURL);
  curl_setopt($lCurl, CURLOPT_HEADER,         false);
  curl_setopt($lCurl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($lCurl, CURLOPT_ENCODING,       "UTF-8");

  $lResult = curl_exec($lCurl);

  curl_close($lCurl);

  if (!$lResult)
    throw new Exception('Can not get content by URL: "'.$aURL.'"');

  return $lResult;
}
?>