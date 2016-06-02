<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Salamek;

/**
 * Class HttpRequest
 * @package Salamek
 */
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
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';

    const FORMAT_FILE = 'file';
    const FORMAT_URL = 'form';
    const FORMAT_JSON = 'json';
    const FORMAT_JSON_RPC = 'json-rpc';
    const FORMAT_XML = 'xml';

    /**
     * HttpRequest constructor.
     * @param $cookieJar string to store cookies
     * @param int $maxRedirections redirections allowed (to prevent infinite redirection loop)
     */
    public function __construct($cookieJar, $maxRedirections = 10)
    {
        $this->cookieJar = $cookieJar;
        $this->setMaxRedirections($maxRedirections);
    }

    /**
     * Creates file string for file upload
     * @param string $path path to file
     * @param string $mime mime type of file
     * @param string $name send name of file
     * @return \CURLFile
     */
    public static function createFile($path, $mime, $name)
    {
        return new \CURLFile($path, $mime, $name);
    }

    /**
     * Sets maxRedirections
     * @param int $maxRedirections max redirections allowed (to prevent infinite redirection loop)
     */
    public function setMaxRedirections($maxRedirections)
    {
        $this->maxRedirections = $maxRedirections;
    }

    /**
     * Absolutizes url
     * @param string $baseUrl base url, usualy url of loaded page
     * @param string $url url to absolutize, usualy url from loaded page
     * @return string absolutized url
     */
    public static function absolutizeHtmlUrl($baseUrl, $url)
    {
        $parsedLoadedUrl = parse_url($baseUrl);
        if (strpos($url, './') === 0)
        {
            $exploded = [];
            if (isset($parsedLoadedUrl['path']))
            {
                $exploded = explode('/', $parsedLoadedUrl['path']);
                array_pop($exploded);
            }
            
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.(!empty($exploded) ? implode('/', $exploded).'/' : '').str_replace('./', '', $url);
        }
        else if (strpos($url, '../') === 0)
        {
            $exploded = [];
            if (isset($parsedLoadedUrl['path']))
            {
                $exploded = explode('/', $parsedLoadedUrl['path']);
                for ($i = 0; $i < substr_count($url, '../'); $i++)
                {
                    array_pop($exploded);
                }
            }
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.(!empty($exploded) ? implode('/', $exploded).'/' : '').str_replace('../', '', $url);
        }
        else if (strpos($url, '/') === 0)
        {
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].$url;
        }
        else
        {
            if (isset($parsedLoadedUrl['path']))
            {
                $exploded = explode('/', trim($parsedLoadedUrl['path'], '/'));
            }
            else
            {
                $exploded = [];
            }
            
            $exploded[] = $url;
            return $parsedLoadedUrl['scheme'].'://'.$parsedLoadedUrl['host'].'/'.(!empty($exploded) ? implode('/', $exploded) : '');
        }
    }

    /**
     * Loads url
     * @param $url
     * @param string $method
     * @param array $parameters
     * @param string $format
     * @return HttpResponse
     * @throws RequestException
     * @throws \Exception
     */
    private function request($url, $method = self::METHOD_GET, array $parameters = [], $format = self::FORMAT_URL)
    {
        //Check if there is a file, then force format to FORMAT_FILE
        foreach ($parameters AS $k => $v)
        {
            if ($v instanceof \CURLFile)
            {
                $format = self::FORMAT_FILE;
                break;
            }
        }


        if (in_array($method, [self::METHOD_GET, self::METHOD_DELETE, self::METHOD_OPTIONS]) && !empty($parameters))
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
        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT]))
        {
            curl_setopt($ch, CURLOPT_POST, true);
            $headers = [];
            switch ($format)
            {
                case self::FORMAT_FILE:
                    $postfields = $parameters;
                    break;

                case self::FORMAT_XML:
                    $headers[] = 'Accept: application/xml';
                    $headers[] = 'Content-Type: application/xml';

                    throw new \Exception('Not yet implemented, fill issue if you need this feature');
                    break;

                case self::FORMAT_JSON:
                    $headers[] = 'Accept: application/json';
                    $headers[] = 'Content-Type: application/json';

                    $postfields = json_encode($parameters);
                    break;

                case self::FORMAT_JSON_RPC:
                    $headers[] = 'Accept: application/json-rpc';
                    $headers[] = 'Content-Type: application/json-rpc';

                    $postfields = json_encode($parameters);
                    break;

                case self::FORMAT_URL:
                    $postfields = http_build_query($parameters);
                    break;
            }


            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

            if (!empty($headers))
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
        }

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);


        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false)
        {
            throw new RequestException($error, $info['http_code']);
        }
        
        $header = substr($result, 0, $info['header_size']);
        $body = substr($result, $info['header_size']);

        $headers = $this->parseHeaders(explode("\n", $header));

        if (!is_null($headers['location']) && $this->redirectionsCount < $this->maxRedirections)
        {
            //We are redirecting
            $this->redirectionsCount++;
            return $this->request(self::absolutizeHtmlUrl($url, $headers['location']), self::METHOD_GET);
        }

        // Huh, response body contains refresh, lets fallow url in it...
        $matches = [];
        if (preg_match('/http-equiv="refresh".+URL=(\S+)"(\s+|)\/>/i', $body, $matches))
        {
            $refreshUrl = self::absolutizeHtmlUrl($urlToGo, $matches[1]);
            $this->redirectionsCount++;
            return $this->request($refreshUrl, self::METHOD_GET);
        }

        return new HttpResponse($body, $info, $url, $headers);
    }

    /**
     * Parses headers
     * @param array $headers headers to parse
     * @return array parsed headers
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
                $location = $matches[1];
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
                    $all[$matches[1]] = $matches[2];
                }
            }
        }
        return ['all' => $all, 'location' => $location];
    }

    /**
     * Sends GET Request
     * @param string $url url to load
     * @param array $parameters get parameters as array
     * @return HttpResponse
     */
    public function get($url, array $parameters = [])
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_GET, $parameters);
    }

    /**
     * Sends POST Request
     * @param string $url to send post request
     * @param array $parameters post parameters
     * @param string $format how to format request
     * @return HttpResponse
     */
    public function post($url, array $parameters = [], $format = self::FORMAT_URL)
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_POST, $parameters, $format);
    }

    /**
     * Sends PUT Request
     * @param string $url to send put request
     * @param array $parameters put parameters
     * @param string $format how to format request
     * @return HttpResponse
     */
    public function put($url, array $parameters = [], $format = self::FORMAT_URL)
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_PUT, $parameters, $format);
    }

    /**
     * Sends DELETE Request
     * @param string $url to send DELETE request
     * @param array $parameters DELETE parameters
     * @return HttpResponse
     */
    public function delete($url, array $parameters = [])
    {
        $this->redirectionsCount = 0;
        return $this->request($url, self::METHOD_DELETE, $parameters);
    }
}
