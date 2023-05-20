<?php

namespace Lum\Web;

use Lum\File\MIME;

class Output
{
  const AT = 'Accept: ';
  const CT = 'Content-Type: ';
  const CC = 'Cache-Control: ';

  /**
   * Set a Content-Type header.
   */
  public static function contentType(string $ctype)
  {
    header(static::CT.$ctype);
  }

  /**
   * Set an Accept header quickly.
   */
  public static function accept(string $ctype)
  {
    header(static::AT.$ctype);
  }


  /**
   * Set headers for JSON output.
   * 
   * @return void 
   */
  public static function json ()
  {
    header(static::CT.MIME::APP_JSON);
  }

  /**
   * Set headers for XML output.
   * 
   * @param bool $text - Use 'text/xml' instead of 'application/xml'.
   *   Optional, default: `false`; 
   *   This is not recommended, as pretty much everything supports
   *   the proper MIME type these days. This may go the way of the
   *   IE support did in the `json()` method in the next major version.
   */
  public static function xml ($text=false)
  {
    if ($text)
    {
      header(static::CT.MIME::TEXT_XML);
    }
    else
    {
      header(static::CT.MIME::APP_XML);
    }
  }

  public static function css()
  {
    header(static::CT.MIME::TEXT_CSS);
  }

  public static function js()
  {
    header(static::CT.MIME::TEXT_JS);
  }

  /**
   * Set headers saying not to cache content.
   * 
   * By default simply uses the `Cache-Control` header.
   * 
   * @param bool $expires - Also add an `Expires` header.
   *   Optional, default: `false`;
   *   Generally not needed, but kept for backwards compatibility.
   */
  public static function nocache ($expires=false)
  {
    header('Cache-Control: no-cache, must-revalidate');
    if ($expires)
    {
      if (!is_string($expires))
      { // Use a default that is well expired.
        $expires = 'Thu, 22 Jun 2000 18:45:00 GMT';
      }
      header("Expires: $expires");
    }
  }

} // class Output
