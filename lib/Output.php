<?php

namespace Lum\Web;

class Output
{
  /**
   * Set headers for JSON output.
   * 
   * @return void 
   */
  public static function json ()
  {
    header('Content-Type: application/json');
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
      header('Content-Type: text/xml');
    }
    else
    {
      header('Content-Type: application/xml');
    }
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
