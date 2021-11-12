##Usage
    use Netsuite\Restlet\OAuth\Client;

    $oauth = new Client(
      $consumer_key,
      $consumer_secret,
      $access_token,
      $token_secret,
      $ns_account_id,
      #restlet_script_id,
      $restlet_deploy_id);

    $oauth->requestHandler->setBaseUrl($baseurl);

     $resp = $oauth->getRequest();
