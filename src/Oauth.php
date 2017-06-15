<?php
namespace Mwe;

class Oauth {
    protected $_signature_method;
    protected $_consumer;
    protected $_token;
    protected $_params;
    protected $_url;
    protected $_account_number;

    public function __construct($url, $ckey, $csecret, $tkey, $tsecret, $account_number)
    {
        $this->_consumer = new OauthConsumer($ckey, $csecret);
        $this->_token = new OauthToken($tkey, $tsecret);
        $this->_signature_method = new OauthSignatureMethod_HMAC_SHA1();
        $this->_url = $url;
        $this->_account_number = $account_number;

        $this->_params = array(
            'oauth_nonce' => $this->generateRandomString(),
            'oauth_timestamp' => idate('U'),
            'oauth_version' => '1.0',
            'oauth_token' => $tkey,
            'oauth_consumer_key' => $ckey,
            'oauth_signature_method' => $this->_signature->get_name()
        );
    }

    public function get()
    {
        $req = new OauthRequest('GET', $this->_url, $this->_params);
        $req->set_parameter('oauth_signature', $req->build_signature($this->_signature_method, $this->_consumer, $this->_token));
        $req->set_parameter('realm', $this->_account_number);

        $header = array(
          'http'=>array(
            'method'=>"GET",
            'header' => $req->to_header() . ',realm="' . $this->_account_number . '"'. " \r\n" . "Host: rest.na2.netsuite.com \r\n" . "Content-Type: application/json"
          )
        );

        $context = stream_context_create($header);
        try {
            $data = file_get_contents($this->_url, false, $context);
        } catch (Exception $e) {
            echo($e->getMessage());
        }

        if ($data) {
            return json_decode($data, true);
        }

        return false;
    }

    public function post($json_data)
    {
        $req = new OauthRequest('POST', $this->_url, $this->_params);
        $req->set_parameter('oauth_signature', $req->build_signature($this->_signature_method, $this->_consumer, $this->_token));
        $req->set_parameter('realm', $this->_account_number);

        $option = array(
            'http'=>array(
                'method'=>"POST",
                'header' => $req->to_header() . ',realm="' . $this->_account_number . '"'. " \r\n" . "Host: rest.na2.netsuite.com \r\n" . "Content-Type: application/json",
                'content' => $json_data
            )
        );
        $context = stream_context_create($option);

        try {
            $data_from_netsuite = file_get_contents($this->_url, false, $context);
        } catch (Exception $e) {
            echo($e->getMessage());
        }

        if ($data_from_netsuite) {
            return $data_from_netsuite;
        }

        return false;
    }

    protected function generateRandomString()
    {
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
