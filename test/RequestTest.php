<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

use Salamek\HttpRequest;
use Salamek\HttpResponse;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public $httpRequest;

    public function setUp()
    {
        $this->httpRequest = new HttpRequest('tmp/cookiejar.txt');
    }

    /**
     * @test
     */
    public function getRawBody()
    {
        $httpResponse = $this->httpRequest->get('https://google.com');

        $this->assertRegExp('/google/', $httpResponse->getBody());
    }

    /**
     * @test
     */
    public function getHtmlBody()
    {
        $httpResponse = $this->httpRequest->get('https://google.com');

        $this->assertInstanceOf('DOMXPath', $httpResponse->getBody(HttpResponse::FORMAT_HTML));
    }

    /**
     * @test
     */
    public function getJsonBody()
    {
        $httpResponse = $this->httpRequest->get('https://salamek.cz/params2json.php', ['foo' => 'bar']);

        $this->assertObjectHasAttribute('foo', $httpResponse->getBody(HttpResponse::FORMAT_JSON));
    }

    /**
     * @test
     */
    public function getPostJsonBody()
    {
        $httpResponse = $this->httpRequest->post('https://salamek.cz/params2json.php', ['foo' => 'bar']);

        $this->assertObjectHasAttribute('foo', $httpResponse->getBody(HttpResponse::FORMAT_JSON));
    }
}