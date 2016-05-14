# http-request

[![Build Status](https://travis-ci.org/Salamek/http-request.svg?branch=master)](https://travis-ci.org/Salamek/http-request)

Simple class to perform requests to web apps acting as browser, storing cookies between redirects and so

## Install
```
composer require salamek/http-request dev-master
```

## Usage

```php
<?php
include('vendor/autoload.php');

$httpRequest = new Salamek\HttpRequest('cookie-jar.txt');
$httpResponse = $httpRequest->post('http://example.com/sign/in', ['username' => 'my-username', 'password' => 'my-password']);

echo '<pre>';
print_r($httpResponse);
print_r($httpResponse->getBody(Salamek\HttpResponse::FORMAT_HTML)); //Xpath
echo '</pre>';
```

## Doc

```
/**
 * HttpRequest constructor.
 * @param $cookieJar string to store cookies
 * @param int $maxRedirections redirections allowed (to prevent infinite redirection loop)
 */
public function __construct($cookieJar, $maxRedirections = 10);

/**
 * Sets maxRedirections
 * @param int $maxRedirections max redirections allowed (to prevent infinite redirection loop)
 */
public function setMaxRedirections($maxRedirections);

/**
 * Sends GET Request
 * @param string $url url to load
 * @param array $parameters get parameters as array
 * @return HttpResponse
 */
public function get($url, array $parameters = []);

/**
 * Sends POST Request
 * @param string $url to send post request
 * @param array $parameters post parameters
 * @return HttpResponse
 */
public function post($url, array $parameters = []);

/**
 * Sends PUT Request
 * @param string $url to send put request
 * @param array $parameters put parameters
 * @return HttpResponse
 */
public function put($url, array $parameters = []);

/**
 * Sends DELETE Request
 * @param string $url to send DELETE request
 * @param array $parameters DELETE parameters
 * @return HttpResponse
 */
public function delete($url, array $parameters = []);

/**
 * Absolutizes url
 * @param string $baseUrl base url, usualy url of loaded page
 * @param string $url url to absolutize, usualy url from loaded page
 * @return string absolutized url
 */
public static function absolutizeHtmlUrl($baseUrl, $url);

/**
 * Creates file string for file upload
 * @param string $path path to file
 * @param string $mime mime type of file
 * @param string $name send name of file
 * @return \CURLFile
 */
public static function createFile($path, $mime, $name);


/**
 * HttpResponse constructor.
 * @param $body
 * @param array $info
 * @param $lastUrl
 * @param array $headers
 */
public function __construct($body, array $info, $lastUrl, array $headers);

/**
 * Returns headers
 * @return array
 */
public function getHeaders();

/**
 * Returns body, formated by $format FORMAT_RAW, FORMAT_JSON, FORMAT_HTML
 * @param string $format
 * @return \DOMXPath|mixed
 */
public function getBody($format = self::FORMAT_RAW);

/**
 * Returns rawBody FORMAT_RAW
 * @return mixed
 */
public function getRawBody();

/**
 * Returns info
 * @return array
 */
public function getInfo();

/**
 * Returns last loded URL
 * @return mixed
 */
public function getLastUrl();
```