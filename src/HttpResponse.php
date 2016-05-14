<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Salamek;

/**
 * Class HttpResponse
 * @package Salamek
 */
class HttpResponse
{
    private $body;
    private $info = [];
    private $lastUrl;
    private $headers = [];

    const FORMAT_RAW = 'raw';
    const FORMAT_JSON = 'json';
    const FORMAT_HTML = 'html';

    /**
     * HttpResponse constructor.
     * @param $body
     * @param array $info
     * @param $lastUrl
     * @param array $headers
     */
    public function __construct($body, array $info, $lastUrl, array $headers)
    {
        $this->body = $body;
        $this->info = $info;
        $this->lastUrl = $lastUrl;
        $this->headers = $headers;
    }

    /**
     * Parses html and retursn DOMXPath
     * @param $html
     * @return \DOMXPath
     */
    private function parseHtml($html)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    /**
     * Parses json string to object
     * @param $json
     * @return mixed
     */
    private function parseJson($json)
    {
        return json_decode($json);
    }

    /**
     * Returns headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns body, formated by $format FORMAT_RAW, FORMAT_JSON, FORMAT_HTML
     * @param string $format
     * @return \DOMXPath|mixed
     */
    public function getBody($format = self::FORMAT_RAW)
    {
        switch ($format) {
            case self::FORMAT_HTML:
                $body = $this->parseHtml($this->body);
                break;

            case self::FORMAT_JSON:
                $body = $this->parseJson($this->body);
                break;

            default:
            case self::FORMAT_RAW:
                $body = $this->body;
                break;
        }
        return $body;
    }

    /**
     * Returns rawBody FORMAT_RAW
     * @return mixed
     */
    public function getRawBody()
    {
        return $this->body;
    }

    /**
     * Returns info
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Returns last loded URL
     * @return mixed
     */
    public function getLastUrl()
    {
        return $this->lastUrl;
    }
}
