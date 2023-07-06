<?php

/**
 * Common URL functions.
 *
 * Can be used as static class methods, or as an object.
 */

namespace Lum\Web;

use finfo;

use \Lum\Encode\Safe64;

class Url
{
  const DEF_HTTP_PORT  = 80;
  const DEF_HTTPS_PORT = 443;

  /** 
   * Redirect to another page. This ends the current PHP process.
   *
   * @param String $url    The URL to redirect to.
   * @param Array  $opts   Options:
   *
   *   'relative'         If True, the passed URL is actually a path.
   *   'full'             If True, the passed URL is a full URL.
   *
   * The above two options are mutually exclusive and do the exact opposite.
   * If neither option is set, the URL will be checked for the existence
   * of a colon (':') character, which will determine if it is assumed to be
   * a full URL or a relative URL.
   *
   * If relative is determiend to be true, the following options are added:
   *
   *   'ssl'              If True, force the use of SSL on the site URL.
   *   'port'             If set, use this port on the site URL.
   *
   * See the site_url() function for details on how 'ssl' and 'port' work.
   *
   */
  public static function redirect (string $url, array $opts=[]): never
  {
    // Set if we set an explicit 'relative' or 'full' option.
    if (isset($opts['relative']))
    {
      $relative = $opts['relative'];
    }
    elseif (isset($opts['full']))
    {
      $relative = $opts['full'] ? False : True;
    }
    else
    {
      // No relative or full option set, determine based on passed URL/path.
      $relative = !str_contains($url, ':');
    }

    if ($relative)
    {
      // Determine if we should force SSL.
      $ssl = (isset($opts['ssl']) && is_bool($opts['ssl'])) 
        ? $opts['ssl'] 
        : null;

      // Determine if we should use an alternative port.
      $port = (isset($opts['port']) && is_int($opts['port']))
        ? $opts['port']
        : null;

      // Prepend the site URL to the passed path.
      $url = static::site_url($ssl, $port) . $url;
    }

    // Spit out a 'Location' header, and end the PHP process.
    header("Location: $url");
    exit;
  }

  /** 
   * Return our website's base URL.
   *
   * @param ?bool $ssl  Force the use of SSL?
   *   If `true` we force SSL. If `false` we force non-SS.
   *   If `null` we will auto-detect the current protocol and use that.
   *   Default: `null`
   *
   * @param ?int $port  Force a specific port?
   *   If an `int` we use that as the port.
   *   If `null` we will auto-detect the appropriate port and use that.
   *
   * @return string
   */
  public static function site_url ($ssl=Null, $port=Null): string
  { 
    if (isset($port))
    {
      if (is_numeric($port))
      {
        $port = ':' . $port;
      }
    }
    if (isset($ssl))
    { // We're using explicit SSL settings.
      if ($ssl)
      {
        $proto   = 'https';
      }
      else
      {
        $proto   = 'http';
      }
      if (is_null($port))
      {
        $port = ''; // Force the use of the default port.
      }
    }
    else
    { // Auto-detect SSL and port settings.
      if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
      { 
        $defport = 443;
        $proto   = 'https';
      }
      else
      { 
        $defport = 80;
        $proto   = 'http';
      }
      if (is_null($port))
      { // Is our current port a default port or not?
        $port = ($_SERVER['SERVER_PORT'] == $defport) 
          ? '' 
          : (':' . $_SERVER['SERVER_PORT']);
      }
    }
    return $proto.'://'.$_SERVER['SERVER_NAME'].$port;
  }

  /** 
   * Return our current request URI.
   */
  public static function request_uri (bool $withQuery=true): string
  {
    if (isset($_SERVER['REQUEST_URI']))
    {
       $uri = $_SERVER['REQUEST_URI'];
       if (!$withQuery && ($pos = strpos($uri, '?')) !== False)
       {
         $uri = substr($uri, 0, $pos);
       }
    }
    else
    {
      $uri = $_SERVER['SCRIPT_NAME'];
      if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '')
      {
        $uri .= '/' . $_SERVER['PATH_INFO'];
      }
      if ($withQuery && isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '')
      {
        $uri .= '?' . $_SERVER['QUERY_STRING'];
      }
      $uri = '/' . ltrim($uri, '/');
    }

    return $uri;
  }

  /** 
   * Return the current URL (full URL path)
   */
  public static function current_url (): string
  {
    return static::site_url() . static::request_uri();
  }

  /** 
   * Return the name of the current script.
   *
   * @param bool $full   If set to True, return the full path.
   */
  public static function script_name (bool $full=False): string
  {
    if ($full)
    {
      return $_SERVER['SCRIPT_NAME'];
    }
    return basename($_SERVER['SCRIPT_NAME']);
  }

  /** 
   * Send a file download to the client browser.
   * This ends the current PHP process.
   *
   * @param Mixed $file    See below for possibly values.
   * @param Array $opts    See below for a list of options.
   *
   * The $file variable will be one of two values, depending on
   * options. If the 'content' option exists, the $file variable will
   * be used as the filename for the download. Otherwise, the $file variable
   * is the path name on the server to the file we are sending the client.
   *
   * Options:
   *
   *   'type'      If specified, will be used as the MIME type, see below.
   *   'content'   Use this as the file content, see below.
   *   'filename'  If not using content, this sets the filename, see below.
   *
   * The 'type' option if set determines the MIME type. If there is a slash
   * character, it is assumed to be a full MIME type declaration. If there
   * isn't, we assume it's a query for Lum\File\Types, and look up the
   * MIME type from there. If it isn't specified at all, we use finfo to look
   * up the MIME type based on the file content.
   *
   * If the 'content' option is set, then it will be used as the content of
   * the file. It alters the meaning of the $file parameter as mentioned above.
   * If it is not set, then the $file parameter must point to a valid file on
   * the server.
   *
   * If the 'filename' option is set (only valid if 'content' is not set),
   * then its value will be used as the name of the file being sent to the
   * client. If it is not set, then the basename of the existing file on the
   * server will be used (as specified in the $file parameter.)
   *
   */
  public static function download (mixed $file, array $opts=[]): never
  {
    // First off, get the file type. We have a few common aliases
    // available, which may be faster than using finfo?
    if (isset($opts['type']))
    {
      $type = $opts['type'];
      if (! is_numeric(strpos($type, '/')))
      {
        $detectType = \Lum\File\Types::get($type);
        if (isset($detectType))
        {
          $type = $detectType;
        }
      }
    }
    elseif (isset($opts['content']))
    { // We have explicit content.
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $type = $finfo->buffer($opts['content']);
    }
    else
    { // We're reading from a file.
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $type  = $finfo->file($file);
    }

    // Next, get the filename and filesize.
    if (isset($opts['content']))
    {
      $filename = $file; // Assume they are the same thing.
      $filesize = strlen($opts['content']);
    }
    else
    {
      if (isset($opts['filename']))
        $filename = $opts['filename'];
      else
        $filename = basename($file); // Chop the directory portion.
      $filesize = filesize($file);   // Get the filesize.
    }

    $inline = isset($opts['inline']) ? (bool)$opts['inline'] : false;

    if ($inline)
    {
      header('Content-Type: $type');
    }
    else
    {
      header('Content-Description: File Transfer');
      header("Content-Type: $type;" . 'name="' . $filename . '"');
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header("Content-Length: $filesize");
    }

    if (ob_get_level() > 0)
      ob_clean();
    flush();

    if (isset($opts['content']))
    {
      echo $opts['content'];
    }
    else
    {
      // Read the file into the output.
      readfile($file);

      // Remove the file if requested.
      if (isset($opts['delete']) && $opts['delete'])
      {
        unlink($file);
      }
    }

    // Now leaving PHP-land.
    exit;
  }

  /**
   * Encode data with `Safe64`.
   *
   * @param mixed $data  Data to encode.
   * @param array $opts  (Optional) Constructor options for `Safe64`.
   * @return string  The encoded data.
   */
  public static function encodeData(mixed $data, array $opts=[]): string
  {
    return Safe64::encodeData($data, $opts);
  }

  /**
   * Decode a `Safe64` string into the original data.
   *
   * @param string $safe64string  The `Safe64` serialized string.
   * @param array $opts  (Optional) Constructor options for `Safe64`.
   * @return mixed  The decoded data.
   */
  public static function decodeData(string $string, array $opts=[]): mixed
  {
    return Safe64::decodeData($string, $opts);
  }

}
