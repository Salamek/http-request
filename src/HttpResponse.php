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
    const FORMAT_XML = 'xml';
    const FORMAT_HTML = 'html';
    const FORMAT_FILE = 'file';
    const FORMAT_AUTODETECT = 'autodetect';

    private $mimeTypesSupported = [
        self::FORMAT_RAW => [
            'application/plain'
        ],
        self::FORMAT_JSON => [
            'application/json'
        ],
        self::FORMAT_XML => [
            'text/xml'
        ],
        self::FORMAT_HTML => [
            'text/html'
        ],
        self::FORMAT_FILE => [
            'application/pdf'
        ],
    ];

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

    private function autoDetectFormat()
    {
        foreach($this->mimeTypesSupported AS $format => $supportedMimes)
        {
            if (in_array($this->info['content_type'], $supportedMimes))
            {
                return $this->getBody($format);
            }
        }

        return $this->getRawBody();
    }

    /**
     * Returns body, formated by $format FORMAT_RAW, FORMAT_JSON, FORMAT_HTML
     * @param string $format
     * @return \DOMXPath|mixed
     * @throws \Exception
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

            case self::FORMAT_FILE:
                $body = [
                    'file' => $this->body,
                    'size' => $this->info['size_download'],
                    'mime' => $this->info['content_type']
                ];

                if (array_key_exists('Content-Disposition', $this->headers['all']))
                {
                    $contentDisposition = $this->headers['all']['Content-Disposition'];
                    $contentDispositionParts = [];
                    foreach(explode(';', $contentDisposition) AS $part)
                    {
                        if (strpos($part, '=') !== false)
                        {
                            list($key, $value) = explode('=', $part);
                            $contentDispositionParts[$key] = trim($value, '"');
                        }
                        else
                        {
                            $contentDispositionParts[$part] = '';
                        }
                    }

                    if (array_key_exists('filename', $contentDispositionParts))
                    {
                        $filename = $contentDispositionParts['filename'];
                        $body['name'] = $filename;
                        $body['basename'] = $filename;
                        $body['extension'] = null;
                        if (strpos($filename, '.') !== false)
                        {
                            $body['basename'] = pathinfo($filename, PATHINFO_FILENAME);
                            $body['extension'] = pathinfo($filename, PATHINFO_EXTENSION);
                        }
                    }
                }

                break;

            case self::FORMAT_XML:
                throw new \Exception('Not implemented yet, please send PR or issue request to implement this feature');
                break;

            case self::FORMAT_AUTODETECT:
                $body = $this->autoDetectFormat();
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
