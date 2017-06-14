<?php
namespace \Netsuite\Oauth;

class Oauth {
    public function __construct($json_data, $url, $ckey, $csecret, $tkey, $tsecret, $account_number)
    {
        $consumer = new OauthConsumer($ckey, $csecret);
        $token = new OauthToken($tkey, $tsecret);
        $sig = new OauthSignatureMethod_HMAC_SHA1();

        $params = array(
            'oauth_nonce' => $this->generateRandomString(),
            'oauth_timestamp' => idate('U'),
            'oauth_version' => '1.0',
            'oauth_token' => $tkey,
            'oauth_consumer_key' => $ckey,
            'oauth_signature_method' => $sig->get_name()
        );

        $req = new OauthRequest('POST', $url, $params);
        $req->set_parameter('oauth_signature', $req->build_signature($sig, $consumer, $token));
        $req->set_parameter('realm', $account_number);

        $option = array(
            'http'=>array(
                'method'=>"POST",
                'header' => $req->to_header() . ',realm="' . $account_number . '"'. " \r\n" . "Host: rest.na2.netsuite.com \r\n" . "Content-Type: application/json",
                'content' => $json_data
            )
        );
        $context = stream_context_create($option);
        return $context;
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
