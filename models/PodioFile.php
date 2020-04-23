<?php
/**
 * @see https://developers.podio.com/doc/files
 */
class PodioFile extends PodioObject {
  public function __construct($attributes = array()) {
    $this->property('file_id', 'integer', array('id' => true));
    $this->property('link', 'string');
    $this->property('perma_link', 'string');
    $this->property('thumbnail_link', 'string');
    $this->property('hosted_by', 'string');
    $this->property('name', 'string');
    $this->property('description', 'string');
    $this->property('mimetype', 'string');
    $this->property('size', 'integer');
    $this->property('context', 'hash');
    $this->property('created_on', 'datetime');
    $this->property('rights', 'array');

    $this->has_one('created_by', 'ByLine');
    $this->has_one('created_via', 'Via');
    $this->has_many('replaces', 'File');

    $this->init($attributes);
  }

  private function get_download_link($size = null) {
    return $size ? ($this->link + '/' + $size) : $this->link;
  }

  /**
   * Returns the raw bytes of a file. Beware: This is not a static method.
   * It can only be used after you have a PodioFile object.
   */
  public function get_raw($size = null) {
    return Podio::get($this->get_download_link($size), array(), array('file_download' => true))->body;
  }

  /**
   * Returns the raw bytes of a file. Beware: This is not a static method.
   * It can only be used after you have a PodioFile object.
   *
   * In contrast to get_raw this method does use minimal memory (the result is stored in php://temp)
   * @return resource pointing at start of body (use fseek($resource, 0) to get headers as well)
   */
  public function get_raw_as_resource($size = null) {
    return Podio::get($this->get_download_link($size), array(), array('file_download' => true, 'return_raw_as_resource_only' => true));
  }

  /**
   * @see https://developers.podio.com/doc/files/upload-file-1004361
   */
  public static function _upload($file_path, $file_name) {
    $source = defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 5 ? new CurlFile(realpath($file_path)) : '@'.realpath($file_path);
    return self::member(Podio::post("/file/v2/", array('source' => $source, 'filename' => $file_name), array('upload' => TRUE, 'filesize' => filesize($file_path))));
  }
    
    /**
     * @param $file_path
     * @param $file_name
     *
     * NOTES: This has been implemented to avoid network issue encountered on kubernetes when tryiing to upload files > 64Kb
     * Got "[PodioConnectionError] Connection to Podio API failed: [52] Empty reply from server at /www/vendor/podio/podio-php/lib/Podio.php:287"
     * when using the one above
     *
     * @TODO: find out another way to implement such a hotfix
     *      - tried to use another instance of curl (added ::reinitCurl method) -> not working
     *      - only direct curl works so far
     *
     * @return mixed
     * @throws Exception
     */
  public static function upload($file_path, $file_name) {
    $source = defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 5 ? new CurlFile(realpath($file_path)) : '@'.realpath($file_path);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.podio.com:443/file/v2/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Podio PHP Client/4.4.3');
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    
    $data = [
      "source" => $source,
      "filename" => $file_name,
    ];
    
    $headers = array();
    $headers[] = 'Authorization: Bearer '. Podio::$oauth->access_token;
    $headers[] = 'Content-Type: multipart/form-data';
    $headers[] = 'Accept: application/json';
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $raw_response = curl_exec($ch);
    
    if ($raw_response === false) {
      throw new Exception('Connection to Podio API failed: [' . curl_errno($ch) . '] ' . curl_error($ch), curl_errno($ch));
    }
    $raw_headers_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $response = new PodioResponse();
    $response->body = substr($raw_response, $raw_headers_size);
    $response->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response->headers = Podio::parse_headers(substr($raw_response, 0, $raw_headers_size));
    
    curl_close($ch);
    return self::member($response);
    
  }

  /**
   * @see https://developers.podio.com/doc/files/get-file-22451
   */
  public static function get($file_id) {
    return self::member(Podio::get("/file/{$file_id}"));
  }

  /**
   * @see https://developers.podio.com/doc/files/get-files-on-app-22472
   */
  public static function get_for_app($app_id, $attributes = array()) {
    return self::listing(Podio::get("/file/app/{$app_id}/", $attributes));
  }

  /**
   * @see https://developers.podio.com/doc/files/get-files-on-space-22471
   */
  public static function get_for_space($space_id, $attributes = array()) {
    return self::listing(Podio::get("/file/space/{$space_id}/", $attributes));
  }

  /**
   * @see https://developers.podio.com/doc/files/attach-file-22518
   */
  public static function attach($file_id, $attributes = array(), $options = array()) {
    $url = Podio::url_with_options("/file/{$file_id}/attach", $options);
    return Podio::post($url, $attributes);
  }

  /**
   * @see https://developers.podio.com/doc/files/replace-file-22450
   */
  public static function replace($file_id, $attributes = array()) {
    return Podio::post("/file/{$file_id}/replace", $attributes);
  }

  /**
   * @see https://developers.podio.com/doc/files/copy-file-89977
   */
  public static function copy($file_id) {
    return self::member(Podio::post("/file/{$file_id}/copy"));
  }

  /**
   * @see https://developers.podio.com/doc/files/get-files-4497983
   */
  public static function get_all($attributes = array()) {
    return self::listing(Podio::get("/file/", $attributes));
  }

  /**
   * @see https://developers.podio.com/doc/files/delete-file-22453
   */
  public static function delete($file_id) {
    return Podio::delete("/file/{$file_id}");
  }

}
