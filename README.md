# http-request
Simple class to perform requests to web apps acting as browser, storing cookies between redirects and so

## Install
```
composer require salamek/http-request dev-master
```

## Usage

```php
<?php
// Sign in to some web page example:
$httpRequest =  new Salamek\HttpRequest('cookie-jar.txt');
list($body, $info, $lastUrl) = $httpRequest->post('http://example.com/sign/in', ['username' => 'my-username', 'password' => 'my-password'])
echo $body; //Received body
echo '<pre>';
print_r($info); //Curl info from curl_getinfo
echo '</pre>';

echo $lastUrl; //Last loaded url where redirections (if any) take us

```

## Doc
