<?php
namespace Netsuite\Restlet\OAuth;
/**
 * A request handler for NetSuite authentication
 * and requests
 */
class RequestHandler
{
    private $consumer;
    private $token;
    private $signatureMethod;
    private $baseUrl;
    private $version;
    private $script_id;
    private $deploy_id;
    private $account_number;
    /**
     * Instantiate a new RequestHandler
     */
    public function __construct()
    {
        $this->baseUrl = 'https://rest.na2.netsuite.com/app/site/hosting/restlet.nl';
        $this->version = '1.0';
        $this->signatureMethod = new \Eher\OAuth\HmacSha1();
        /*$this->client = new \GuzzleHttp\Client(array(
            'allow_redirects' => false,
        ));*/
    }

    /**
     * Set the consumer for this request handler
     *
     * @param string $key    the consumer key
     * @param string $secret the consumer secret
     */
    public function setConsumer($key, $secret)
    {
        $this->consumer = new \Eher\OAuth\Consumer($key, $secret);
    }

    /**
     * Set the token for this request handler
     *
     * @param string $token  the oauth token
     * @param string $secret the oauth secret
     */
    public function setToken($token, $secret)
    {
        $this->token = new \Eher\OAuth\Token($token, $secret);
    }

    /**
     * Sets the account number attribute
     *
     * @param str|int $account_number
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
    }

    /**
     * Sets the script id URL attribute
     *
     * @param str|int $script_id
     */
    public function setScript($script_id)
    {
        $this->script_id = $script_id;
    }

    /**
     * Sets the deploy id URL attribute
     *
     * @param str|int $deploy_id
     */
    public function setDeploy($deploy_id)
    {
        $this->deploy_id = $deploy_id;
    }

    /**
     * Set the base url for this request handler.
     *
     * @param string $url The base url (e.g. https://api.tumblr.com)
     */
    public function setBaseUrl($url)
    {
        // Ensure we have a trailing slash since it is expected in {@link request}.
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        $this->baseUrl = $url;
    }
    /**
     * Make a request with this request handler
     *
     * @param string $method  one of GET, POST
     * @param string $path    the path to hit
     * @param array  $options the array of params
     *
     * @return \stdClass response object
     */
    public function request($method, $path, $options)
    {
        // Ensure we have options
        $options = $options ?: array();
        // Take off the data param, we'll add it back after signing
        $file = isset($options['data']) ? $options['data'] : false;
        unset($options['data']);
        // Get the oauth signature to put in the request header
        $url = $this->baseUrl . $path;
        $oauth = \Eher\OAuth\Request::from_consumer_and_token(
            $this->consumer,
            $this->token,
            $method,
            $url,
            $options
        );
        $oauth->sign_request($this->signatureMethod, $this->consumer, $this->token);
        $authHeader = $oauth->to_header();
        $pieces = explode(' ', $authHeader, 2);
        $authString = $pieces[1];
        // Set up the request and get the response
        $uri = new \GuzzleHttp\Psr7\Uri($url);
        $guzzleOptions = [
            'headers' => [
                'Authorization' => $authString,
                'User-Agent' => 'tumblr.php/' . $this->version,
            ],
            // Swallow exceptions since \Tumblr\API\Client will handle them
            'http_errors' => false,
        ];
        if ($method === 'GET') {
            $uri = $uri->withQuery(http_build_query($options));
        } elseif ($method === 'POST') {
            if (!$file) {
                $guzzleOptions['form_params'] = $options;
            } else {
                // Add the files back now that we have the signature without them
                $content_type = 'multipart';
                $form = [];
                foreach ($options as $name => $contents) {
                    $form[] = [
                        'name'      => $name,
                        'contents'  => $contents,
                    ];
                }
                foreach ((array) $file as $idx => $path) {
                    $form[] = [
                        'name'      => "data[$idx]",
                        'contents'  => file_get_contents($path),
                        'filename'  => pathinfo($path, PATHINFO_FILENAME),
                    ];
                }
                $guzzleOptions['multipart'] = $form;
            }
        }
        $response = $this->client->request($method, $uri, $guzzleOptions);
        // Construct the object that the Client expects to see, and return it
        $obj = new \stdClass;
        $obj->status = $response->getStatusCode();
        // Turn the stream into a string
        $obj->body = $response->getBody()->__toString();
        $obj->headers = $response->getHeaders();
        return $obj;
    }
}
