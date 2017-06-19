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
     * @param array  $options the array of params
     *
     * @return \stdClass response object
     */
    public function request($method, $options)
    {
        $url = $this->baseUrl . '?script=' . $this->script_id . '&deploy=' . $this->deploy_id;

        switch ($method) {
            case "GET":
                $url .= '&searchId=' . $options['search_id'];
            break;
        }

        $params = array(
            'oauth_nonce' => $this->generateRandomString(),
            'oauth_timestamp' => idate('U'),
            'oauth_version' => '1.0',
            'oauth_token' => $this->token->key,
            'oauth_consumer_key' => $this->consumer->key,
            'oauth_signature_method' => $this->signatureMethod->get_name()
        );

        $req = new \Eher\OAuth\Request($method, $url, $params);
        $req->set_parameter('oauth_signature', $req->build_signature($this->signatureMethod, $this->consumer, $this->token));
        $req->set_parameter('realm', $this->account_number);

        switch ($method) {
            case "GET":
                $option = array(
                    'http'=>array(
                        'method'=>$method,
                        'header' => $req->to_header() . ',realm="' . $this->account_number . '"'. " \r\n" . "Host: rest.na2.netsuite.com \r\n" . "Content-Type: application/json"
                    )
                );
            break;
            case "POST":
                $option = array(
                    'http'=>array(
                        'method'=>$method,
                        'header' => $req->to_header() . ',realm="' . $this->account_number . '"'. " \r\n" . "Host: rest.na2.netsuite.com \r\n" . "Content-Type: application/json",
                        'content' => $options['json_data']
                    )
                );
            break;
        }
        $context = stream_context_create($option);
        return file_get_contents($url, false, $context);
    }

    /**
     * Provides a random string for our request method
     *
     * @author David Varney <varney@mwesales.com>
     * @return str $randomString
     */
    private function generateRandomString() {
        $length = 20;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
