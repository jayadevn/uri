<?php
/**
 * League.Url (http://url.thephpleague.com)
 *
 * @link      https://github.com/thephpleague/url/
 * @copyright Copyright (c) 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/url/blob/master/LICENSE (MIT License)
 * @version   4.0.0
 * @package   League.url
 */
namespace League\Url\Services;

use InvalidArgumentException;
use League\Url;
use League\Url\Interfaces;
use League\Url\Utilities;

/**
 * A class to manipulate an URL and URL components output
 *
 * @package League.url
 * @since   4.0.0
 */
class Formatter implements Interfaces\SchemeRegistryAccess
{
    const HOST_AS_UNICODE = 1;
    const HOST_AS_ASCII   = 2;

    /**
     * A trait to format a path in a URL string
     */
    use Utilities\PathFormatter;

    /**
     * host encoding property
     *
     * @var int
     */
    protected $hostEncoding = self::HOST_AS_UNICODE;

    /**
     * query encoding property
     *
     * @var int
     */
    protected $queryEncoding = PHP_QUERY_RFC3986;

    /**
     * The Scheme Registry object
     *
     * @var Interfaces\SchemeRegistry
     */
    protected $registry;

    /**
     * query separator property
     *
     * @var string
     */
    protected $querySeparator = '&';

    public function __construct(Interfaces\SchemeRegistry $registry = null)
    {
        $this->registry = $registry ?: new SchemeRegistry();
    }

    /**
     * Host encoding setter
     *
     * @param int $encode a predefined constant value
     */
    public function setHostEncoding($encode)
    {
        if (!in_array($encode, [self::HOST_AS_UNICODE, self::HOST_AS_ASCII])) {
            throw new InvalidArgumentException('Unknown Host encoding rule');
        }
        $this->hostEncoding = $encode;
    }

    /**
     * Host encoding getter
     *
     * @return int
     */
    public function getHostEncoding()
    {
        return $this->hostEncoding;
    }

    /**
     * Return the specified registry
     *
     * @return Interfaces\SchemeRegistry
     */
    public function setSchemeRegistry(Interfaces\SchemeRegistry $registry)
    {
        return $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemeRegistry()
    {
        return $this->registry;
    }

    /**
     * Query encoding setter
     *
     * @param int $encode  a predefined constant value
     */
    public function setQueryEncoding($encode)
    {
        if (!in_array($encode, [PHP_QUERY_RFC3986, PHP_QUERY_RFC1738])) {
            throw new InvalidArgumentException('Unknown Query encoding rule');
        }
        $this->queryEncoding = $encode;
    }

    /**
     * Query encoding getter
     *
     * @return int
     */
    public function getQueryEncoding()
    {
        return $this->queryEncoding;
    }

    /**
     * Query separator setter
     *
     * @param string $separator
     */
    public function setQuerySeparator($separator)
    {
        $separator = filter_var($separator, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        $this->querySeparator = trim($separator);
    }

    /**
     * Query separator getter
     *
     * @return string
     */
    public function getQuerySeparator()
    {
        return $this->querySeparator;
    }

    /**
     * Format an object according to the formatter properties
     *
     * @param Interfaces\Url|Interfaces\UrlPart|string $input
     *
     * @return string
     */
    public function format($input)
    {
        if ($input instanceof Interfaces\UrlPart) {
            return $this->formatUrlPart($input);
        }

        if (!$input instanceof Interfaces\Url) {
            $input = Url\Url::createFromUrl($input, $this->registry);
        }

        return $this->formatUrl($input);
    }

    /**
     * Format a League\Url\Interfaces\UrlPart according to the Formatter properties
     *
     * @param Interfaces\UrlPart $part
     *
     * @return string
     */
    protected function formatUrlPart(Interfaces\UrlPart $part)
    {
        if ($part instanceof Interfaces\Query) {
            return Url\Query::build($part->toArray(), $this->querySeparator, $this->queryEncoding);
        }

        if ($part instanceof Interfaces\Host) {
            return $this->formatHost($part);
        }

        return $part->__toString();
    }

    /**
     * Format a League\Url\Interfaces\Host according to the Formatter properties
     *
     * @param Interfaces\Host $host
     *
     * @return string
     */
    protected function formatHost(Interfaces\Host $host)
    {
        if (self::HOST_AS_ASCII == $this->hostEncoding) {
            return $host->__toString();
        }

        return $host->toUnicode();
    }

    /**
     * Format a Url according to the Formatter properties
     *
     * @param Interfaces\Url $url
     *
     * @return string
     */
    protected function formatUrl(Interfaces\Url $url)
    {
        $query = $this->formatUrlPart($url->query);
        if (!empty($query)) {
            $query = '?'.$query;
        }

        $auth = $this->formatAuthority($url);

        return $url->scheme->getUriComponent().$auth
            .$this->formatPath($url->path, !empty($auth)).$query
            .$url->fragment->getUriComponent();
    }

    /**
     * Format a URL authority according to the Formatter properties
     *
     * @param Interfaces\Url $url
     *
     * @return string
     */
    protected function formatAuthority(Interfaces\Url $url)
    {
        if ('' == $url->getHost()) {
            return '';
        }

        $port = $url->port->getUriComponent();
        if ($url->hasStandardPort()) {
            $port = '';
        }

        return '//'.$url->userInfo->getUriComponent()
            .$this->formatHost($url->host).$port;
    }
}