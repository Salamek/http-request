<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

use Salamek\HttpRequest;

class RequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function something()
    {
        $httpRequest = new HttpRequest('cookiejar.txt');

        $httpResponse = $httpRequest->get('https://seznam.cz');

        $this->assertNotEmpty($httpResponse->getBody());
    }
}