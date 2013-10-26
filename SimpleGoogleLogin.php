<?php

require 'SimpleHttp.php';

class SimpleGoogleLogin {

    private $URLS = array(
                          'token' => 'https://accounts.google.com/o/oauth2/token',
                          'auth' => 'https://accounts.google.com/o/oauth2/auth',
                          'tokenInfo' => 'https://www.googleapis.com/oauth2/v1/tokeninfo',
                          'mePeople' => 'https://www.googleapis.com/plus/v1/people/me/people/visible',
                          'me' => 'https://www.googleapis.com/plus/v1/people/me'
                          );

    private $config;

    private $authCodeResult;

    private $tokenInfoResult;

    private $http;

    /**
     * Init the API
     * $config should have the keys: client_id, client_secret, redirect_uri
     */
    public function __construct($config, $httpClient=null) {
        if (! function_exists('curl_init')) {
            throw new Exception('SimpleGoogleLogin requires the CURL PHP extension');
        }
        $this->config = $config;
        if ($httpClient == null) {
            $httpClient = new SimpleHttp();
        }
        $this->http = $httpClient;
    }

    /**
     * Returns the login url for google.
     * (The url to forward to)
     */
    public function getAuthUrl($plusScope=false) {
        $authParams = ['response_type' => 'code',
                       'redirect_uri' => $this->config['redirect_uri'],
                       'client_id' => $this->config['client_id'],
                       'scope' => 'https://www.googleapis.com/auth/userinfo.email',
                       'access_type' => 'offline',
                       'approval_prompt' => 'force'];

        if ($plusScope)
            $authParams['scope'] .= ' https://www.googleapis.com/auth/plus.login';
        return $this->URLS['auth'] .'?'. $this->http->arrayFormEncode($authParams);
    }

    /**
     * exchanges code supplied to the redirect entpoint with the access token
     */
    public function exchangeAuthCode($code) {
        $post_data = ["code" => $code,
                      "grant_type" => 'authorization_code',
                      "redirect_uri" => $this->config['redirect_uri'],
                      "client_id" => $this->config['client_id'],
                      "client_secret" => $this->config['client_secret']
                      ];
        $authCodeResultJson = $this->http->postRequest($this->URLS['token'], $post_data);

        $this->authCodeResult = json_decode($authCodeResultJson->body);
        return ($authCodeResultJson->code == 200);
    }

    /**
     * fetches the info from the token, e.g. the email address
     */
    public function getTokenInfo() {
        $header = ['authorization: Bearer '. $this->authCodeResult->access_token];
        $tokenInfoParams = ['id_token' => $this->authCodeResult->id_token];

        $tokenInfoResultJson = $this->http->getRequest($this->URLS['tokenInfo'], $tokenInfoParams, $header);
        $this->tokenInfoResult = json_decode($tokenInfoResultJson->body);
        return $this->tokenInfoResult;
    }

    /**
     * fetches all the people, I know
     */
    public function getMePeople() {
        $header = ['authorization: Bearer '. $this->authCodeResult->access_token];

        $tokenInfoResultJson = $this->http->getRequest($this->URLS['mePeople'], [], $header);
        return json_decode($tokenInfoResultJson->body);
    }

    /**
     * fetches the me ressource
     */
    public function getMe() {
        $header = ['authorization: Bearer '. $this->authCodeResult->access_token];

        $tokenInfoResultJson = $this->http->getRequest($this->URLS['me'], [], $header);
        return json_decode($tokenInfoResultJson->body);
    }
}