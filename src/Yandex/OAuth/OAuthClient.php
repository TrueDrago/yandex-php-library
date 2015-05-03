<?php
/**
 * Yandex PHP Library
 *
 * @copyright NIX Solutions Ltd.
 * @link https://github.com/nixsolutions/yandex-php-library
 */

/**
 * @namespace
 */
namespace Yandex\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Yandex\OAuth\Exception\AuthRequestException;
use Yandex\OAuth\Exception\AuthResponseException;

/**
 * Class OAuthClient implements Yandex OAuth protocol
 *
 * @category Yandex
 * @package  OAuth
 *
 * @author   Eugene Zabolotniy <realbaziak@gmail.com>
 * @created  29.08.13 12:07
 */
class OAuthClient
{
    /*
     * Authentication types constants
     *
     * The "code" type means that the application will use an intermediate code to obtain an access token.
     * The "token" type will result a user is redirected back to the application with an access token in a URL
     */
    const CODE_AUTH_TYPE = 'code';
    const TOKEN_AUTH_TYPE = 'token';

    /**
     * @var string
     */
    private $clientId = '';

    /**
     * @var string
     */
    private $clientSecret = '';

    /**
     * @var string
     */
    protected $serviceScheme = 'https';

    /**
     * @var string
     */
    protected $serviceDomain = 'oauth.yandex.ru';

    /**
     * @var string
     */
    protected $servicePort = '';

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct($clientId = '', $clientSecret = '')
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
    }

    /**
     * @param string $clientId
     *
     * @return self
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     *
     * @return self
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $accessToken
     *
     * @return self
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $serviceDomain
     *
     * @return self
     */
    public function setServiceDomain($serviceDomain)
    {
        $this->serviceDomain = $serviceDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceDomain()
    {
        return $this->serviceDomain;
    }

    /**
     * @param string $servicePort
     *
     * @return self
     */
    public function setServicePort($servicePort)
    {
        $this->servicePort = $servicePort;

        return $this;
    }

    /**
     * @return string
     */
    public function getServicePort()
    {
        return $this->servicePort;
    }

    /**
     * @param string $serviceScheme
     *
     * @return self
     */
    public function setServiceScheme($serviceScheme)
    {
        $this->serviceScheme = $serviceScheme;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceScheme()
    {
        return $this->serviceScheme;
    }

    /**
     * @param string $resource
     * @return string
     */
    public function getServiceUrl($resource = '')
    {
        return $this->serviceScheme . '://' . $this->serviceDomain . '/' . rawurlencode($resource);
    }

    /**
     * @param string $type
     * @param string $state optional string
     *
     * @return string
     */
    public function getAuthUrl($type = self::CODE_AUTH_TYPE, $state = null)
    {
        $url = $this->getServiceUrl('authorize') . '?response_type=' . $type . '&client_id=' . $this->clientId;
        if ($state) {
            $url .= '&state=' . $state;
        }

        return $url;
    }

    /**
     * Sends a redirect to the Yandex authentication page.
     *
     * @param bool   $exit  indicates whether to stop the PHP script immediately or not
     * @param string $type  a type of the authentication procedure
     * @param string $state optional string
     */
    public function authRedirect($exit = true, $type = self::CODE_AUTH_TYPE, $state = null)
    {
        header('Location: ' . $this->getAuthUrl($type, $state));

        if ($exit) {
            exit();
        }
    }

    /**
     * Exchanges a temporary code for an access token.
     *
     * @param $code
     *
     * @throws AuthRequestException on a known request error
     * @throws AuthResponseException on a response format error
     * @throws RequestException on an unknown request error
     *
     * @return self
     */
    public function requestAccessToken($code)
    {
        $client = new Client(
            [
                'base_url' => [$this->getServiceUrl(), []],
            ]
        );

        $request = $client->createRequest('POST', 'token', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret
            ]
        ]);

        try {

            $response = $client->send($request);

        }
        catch (RequestException $ex) {
            if (!$ex->hasResponse())
            {
                throw new AuthRequestException('Service responsed with error "' . $ex->getMessage() . '".', 0, $ex);
            }
            $result = $ex->getResponse()->json();

            if (is_array($result) && isset($result['error'])) {
                // handle a service error message
                $message = 'Service responsed with error code "' . $result['error'] . '".';

                if (isset($result['error_description']) && $result['error_description']) {
                    $message .= ' Description "' . $result['error_description'] . '".';
                }
                throw new AuthRequestException($message, 0, $ex);
            }

            // unknown error. not parsed error
            throw $ex;
        }

        try {
            $result = $response->json();
        } catch (\RuntimeException $ex) {
            throw new AuthResponseException('Server response can\'t be parsed', 0, $ex);
        }

        if (!is_array($result)) {
            throw new AuthResponseException('Server response has unknown format');
        }

        if (!isset($result['access_token'])) {
            throw new AuthResponseException('Server response doesn\'t contain access token');
        }

        $this->setAccessToken($result['access_token']);

        return $this;
    }
}
