<?php
/**
 * @author idmultiship
 */

namespace Yandex\Webmaster;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use JMS\Serializer\SerializerBuilder;
use Yandex\Common\AbstractServiceClient;
use Yandex\Common\Exception\FailedRequestException;
use Yandex\Common\Exception\MissedArgumentException;
use Yandex\Common\Exception\ProfileNotFoundException;
use Yandex\Common\Exception\YandexException;
use Yandex\Webmaster\Entity\Hosts\HostList;
use Yandex\Webmaster\Entity\OriginalText;
use Yandex\Webmaster\Exception\InvalidHostException;

/**
 * Class WebmasterClient
 * @todo refactor all the mess, use JmsSerializer
 */
class WebmasterClient extends AbstractServiceClient
{
    /**
     * @var string
     */
    private $version = 'v2';

    /**
     * @var string
     */
    protected $serviceDomain = 'webmaster.yandex.ru';

    /**
     * @var int|null
     */
    private $host;

    /**
     * @var HostList|null
     */
    private $hostList;

    /**
     * @param string $token access token
     */
    public function __construct($token = '')
    {
        $this->setAccessToken($token);
    }

    /**
     * @param string $version
     *
     * @return self
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @inheritdoc
     */
    public function getServiceUrl($resource = '')
    {
        return parent::getServiceUrl($resource) . 'api/' . $this->version . '/';
    }

    /**
     * @return null|string
     * @throws FailedRequestException
     * @throws YandexException
     * @todo why use this
     */
    public function getServiceDocument()
    {
        $request = $this->getClient()->createRequest('GET', '');
        $response = $this->sendRequest($request);
        $xml = $response->xml();

        return (isset($xml->workspace) && isset($xml->workspace->collection) && isset($xml->workspace->collection['href']))
            ? (string)$xml->workspace->collection['href']
            : null;
    }

    /**
     * @return HostList
     * @throws FailedRequestException
     * @throws YandexException
     */
    public function getHosts()
    {
        $request = $this->getClient()->createRequest('GET', 'hosts');
        $response = $this->sendRequest($request);

        $this->hostList = $this->getSerializer()->deserialize($response->getBody(), HostList::class, 'xml');

        return $this->hostList;
    }

    /**
     * @param int $hostId
     * @throws InvalidHostException
     * @return static
     */
    public function setHost($hostId)
    {
        if (!$this->checkHostAvailable($hostId)) {
            throw new InvalidHostException('Can\'t set host - probably you don\'t have access to it');
        }
        $this->host = $hostId;
        return $this;
    }

    /**
     * @param $id
     * @return bool
     */
    public function checkHostAvailable($id)
    {
        if (!$this->hostList) {
            $this->getHosts();
        }
        return $this->hostList->hasHost($id);
    }

    /**
     * @return int|null
     * @throws YandexException
     */
    public function getHost()
    {
        if (!$this->host) {
            throw new InvalidHostException('Can\'t do call method on host when host is not set!');
        }
        return $this->host;
    }

    public function addOriginalText($content)
    {
        $text = new OriginalText($content);

        $request = $this->getClient()->createRequest(
            'POST',
            sprintf('hosts/%d/original-texts', $this->getHost()),
            ['body' => urlencode($this->getSerializer()->serialize($text, 'xml'))]
        );
        $response = $this->sendRequest($request);

        return $this->getSerializer()->deserialize($response->getBody(), OriginalText::class, 'xml');
    }

    /**
     * Sends a request
     *
     * @param RequestInterface $request
     *
     * @throws MissedArgumentException
     * @throws ProfileNotFoundException
     * @throws YandexException
     * @return Response
     */
    protected function sendRequest(RequestInterface $request)
    {
        try {
            $request = $this->prepareRequest($request);
            $response = $this->getClient()->send($request);
        } catch (RequestException $e) {

            // get error from response
            if (!$e->hasResponse()) {
                throw new FailedRequestException('Service responded with error "' . $e->getMessage() . '".', 0, $e);
            }
            $result = $e->getResponse()->xml();

            // handle a service error message
            if ($result instanceof \SimpleXMLElement) {
                $code = isset($result['code']) ? $result['code'] : 'UNKNOWN_CODE';
                $message = isset($result->message) ? $result->message : 'Unknown error';
            }
            else {
                $code = 'UNKNOWN_CODE';
                $message = 'Unknown error';
            }

            $message = $code . ': ' . $message;

            throw new YandexException($message);
        }

        return $response;
    }

    /**
     * @return \JMS\Serializer\Serializer
     */
    protected function getSerializer()
    {
        return SerializerBuilder::create()->build();
    }
}