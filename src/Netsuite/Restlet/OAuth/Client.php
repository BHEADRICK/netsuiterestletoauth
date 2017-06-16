<?php

namespace Netsuite\Restlet\OAuth;

class Client
{
    private $apiKey;

    /**
     * Create a new Client
     *
     * @param string $consumerKey    the consumer key
     * @param string $consumerSecret the consumer secret
     * @param string $token          oauth token
     * @param string $secret         oauth token secret
     * @param string $account_number The number of the NetSuite Account
     * @param string $script_id      ID of the "script" URL attribute
     * @param string $deploy_id      ID of the "deploy" URL attribute
     */
    public function __construct($consumerKey, $consumerSecret, $token, $secret, $account_number, $script_id, $deploy_id)
    {
        $this->requestHandler = new RequestHandler();
        $this->setConsumer($consumerKey, $consumerSecret);
        $this->setToken($token, $secret);
        $this->setAccountNumber($account_number);
        $this->setScript($script);
        $this->setDeploy($deploy);
    }
    /**
     * Set the consumer for this client
     *
     * @param string $consumerKey    the consumer key
     * @param string $consumerSecret the consumer secret
     */
    public function setConsumer($consumerKey, $consumerSecret)
    {
        $this->apiKey = $consumerKey;
        $this->requestHandler->setConsumer($consumerKey, $consumerSecret);
    }

    /**
     * Set the token for this client
     *
     * @param string $token  the oauth token
     * @param string $secret the oauth secret
     */
    public function setToken($token, $secret)
    {
        $this->requestHandler->setToken($token, $secret);
    }

    /**
     * Retrieve RequestHandler instance
     *
     * @return RequestHandler
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

    /**
     * Sets the account number attribute
     *
     * @param str|int $account_number the number of the NetSuite account
     * @return RequestHandler
     */
    public function setAccountNumber($account_number)
    {
        return $this->requestHandler->setAccountNumber($account_number);
    }

    /**
     * Sets the script id URL attribute
     *
     * @param str|int $script_id the id of the script URL attribute
     * @return RequestHandler
     */
    public function setScript($script_id)
    {
        return $this->requestHandler->setScript($script_id);
    }

    /**
     * Sets the deploy id URL attribute
     *
     * @param str|int $deploy_id the id of the deploy URL attribute
     * @return RequestHandler
     */
    public function setDeploy($deploy_id)
    {
        return $this->requestHandler->setDeploy($deploy_id);
    }

    /**
     * Make a GET request to the given endpoint and return the response
     *
     * @param string $path      the path to call on
     * @param array  $options   the options to call with
     * @param bool   $addApiKey whether or not to add the api key
     *
     * @return array the response object (parsed)
     */
    public function getRequest($path, $options, $addApiKey)
    {
        $response = $this->makeRequest('GET', $path, $options, $addApiKey);
        return $this->parseResponse($response);
    }

    /**
     * Make a POST request to the given endpoint and return the response
     *
     * @param string $path      the path to call on
     * @param array  $options   the options to call with
     * @param bool   $addApiKey whether or not to add the api key
     *
     * @return array the response object (parsed)
     */
    public function postRequest($path, $options, $addApiKey)
    {
        if (isset($options['source']) && is_array($options['source'])) {
            $sources = $options['source'];
            unset($options['source']);
            foreach ($sources as $i => $source) {
                $options["source[$i]"] = $source;
            }
        }
        $response = $this->makeRequest('POST', $path, $options, $addApiKey);
        return $this->parseResponse($response);
    }

    /**
     * Parse a response and return an appropriate result
     *
     * @param  \stdClass $response the response from the server
     *
     * @throws RequestException
     * @return array  the response data
     */
    private function parseResponse($response)
    {
        $response->json = json_decode($response->body);
        if ($response->status < 400) {
            return $response->json->response;
        } else {
            throw new RequestException($response);
        }
    }

    /**
     * Make a request to the given endpoint and return the response
     *
     * @param string $method    the method to call: GET, POST
     * @param string $path      the path to call on
     * @param array  $options   the options to call with
     * @param bool   $addApiKey whether or not to add the api key
     *
     * @return \stdClass the response object (not parsed)
     */
    private function makeRequest($method, $path, $options, $addApiKey)
    {
        if ($addApiKey) {
            $options = array_merge(
                array('api_key' => $this->apiKey),
                $options ?: array()
            );
        }
        return $this->requestHandler->request($method, $path, $options);
    }
}