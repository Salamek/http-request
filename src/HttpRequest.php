<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Extensions\PackageBot;

class HttpRequest
{
    /** @var string */
    private $cookieJar;

    /** @var int */
    private $maxRedirections = 10;

    /** @var int */
    private $redirectionsCount = 0;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * HttpRequest constructor.
     * @param $cookieJar
     * @param int $maxRedirections
     */
    public function __construct($cookieJar, $maxRedirections = 10)
    {
        $this->cookieJar = $cookieJar;
        $this->setMaxRedirections($maxRedirections);
    }

    /**
     * @param $path
     * @param $mime
     * @param $name
     * @return \CURLFile
     */
    public static function createFile($path, $mime, $name)
    {
        return new \CURLFile($path, $mime, $name);
    }

    /**
     * @param $maxRedirections
     */
    public function setMaxRedirections($maxRedirections)
    {
        $this->maxRedirections = $maxRedirections;
    }

    /**
     * @param $loadedUrl
     * @param $url
     * @return string
     */
    public static function absolutizeHtmlUrl($loadedUrl, $url)
    {
        $parsedLoadedUrl = parse_url($loadedUrl);
        if (strpos($url, './') === 0)
        {
            $exploded = explode('/', $parsedLoadedUrl['path']);
            array_pop($exploded);
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.implode('/', $exploded).'/'.str_replace('./', '', $url);
        }
        else if (strpos($url, '../') === 0)
        {
            $exploded = explode('/', $parsedLoadedUrl['path']);
            for ($i = 0; $i < substr_count($url, '../'); $i++)
            {
                array_pop($exploded);
            }
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.implode('/', $exploded).'/'.str_replace('../', '', $url);
        }
        else if (strpos($url, '/') === 0)
        {
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].$url;
        }
        else
        {
            $exploded = explode('/', trim($parsedLoadedUrl['path'], '/'));
            $exploded[] = $url;
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.implode('/', $exploded);
        }
    }

    /**
     * @param $url
     * @param $method
     * @param array $parameters
     * @return array
     */
    private function request($url, $method, array $parameters = [])
    {
        if ($method == 'GET' && !empty($parameters))
        {
            $urlToGo = $url.(strpos($url, '?') !== false ? '&' : '?').http_build_query($parameters);
        }
        else
        {
            $urlToGo = $url;
        }
        $ch = curl_init($urlToGo);

        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/45.0.2454.101 Chrome/45.0.2454.101 Safari/537.36');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        if ($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/x-www-form-urlencoded"]);
        }

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);


        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $header = substr($result, 0, $info['header_size']);
        $body = substr($result, $info['header_size']);

        $headers = $this->parseHeaders(explode("\n", $header));

        if (!is_null($headers['location']) && $this->redirectionsCount < $this->maxRedirections)
        {
            //We are redirecting
            $this->redirectionsCount++;
            return $this->request($headers['location'], self::METHOD_GET);
        }

        // Huh, response body contains refresh, lets fallow url in it...
        $matches = [];
        if (preg_match('/http-equiv="refresh".+URL=(\S+)"(\s+|)\/>/i', $body, $matches))
        {
            $refreshUrl = self::absolutizeHtmlUrl($urlToGo, $matches[1]);
            $this->redirectionsCount++;
            return $this->request($refreshUrl, self::METHOD_GET);
        }

        return [$body, $info, $url];
    }

    /**
     * @param $headers
     * @return array
     */
    private function parseHeaders(array $headers)
    {
        $startParse =  false;
        $all = [];
        $location = null;
        foreach ($headers AS $header)
        {
            $header = trim($header);
            // Parse Location
            $matches = [];
            if (preg_match('/^^Location:\s+(\S+)$/i', $header, $matches))
            {
                list($m, $locationFound) = $matches;
                $location = $locationFound;
            }

            //Start parsing after HTTP code 200
            if (preg_match('/^HTTP\/\d\.\d\s200\sOK$/i', $header))
            {
                $startParse = true;
            }
            if ($startParse)
            {
                $matches = [];
                if (preg_match('/^(\S+):\s(.+)$/i', $header, $matches))
                {
                    list($m, $headerKey, $headerValue) = $matches;
                    $all[$headerKey] = $headerValue;
                }
            }
        }
        return ['all' => $all, 'location' => $location];
    }

    /**
     * @param $url
     * @param array $parameters
     * @return array
     */
    public function get($url, array $parameters = [])
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_GET, $parameters);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return array
     */
    public function post($url, array $parameters = [])
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_POST, $parameters);
    }
}