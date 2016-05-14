# http-request

https://travis-ci.org/Salamek/http-request.svg
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

$httpRequest =  new Salamek\HttpRequest('cookie-jar.txt');
$httpResponse = $httpRequest->post('http://example.com/sign/in', ['username' => 'my-username', 'password' => 'my-password']);

echo '<pre>';
print_r($httpResponse);
print_r($httpResponse->getBody(Salamek\HttpResponse::FORMAT_HTML)); //Xpath
echo '</pre>';


## Doc
