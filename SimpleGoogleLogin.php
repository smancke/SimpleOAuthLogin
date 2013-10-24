<?php

class SimpleGoogleLogin {

    private $URLS = array(
                          'token' => 'https://accounts.google.com/o/oauth2/token',
                          'auth' => 'https://accounts.google.com/o/oauth2/auth',
                          'tokenInfo' => 'https://www.googleapis.com/oauth2/v1/tokeninfo'
                          );

    private $config;

    private $authCodeResult;

    private $tokenInfoResult;

    private $http;

    /**
     * Init the API
     * $config should have the keys: client_id, client_secret, redirect_uri
     */
    public function __construct($config, $httpClient) {
        if (! function_exists('curl_init')) {
            throw new Exception('SimpleGoogleLogin requires the CURL PHP extension');
        }
        $this->config = $config;
        $this->http = $httpClient;
    }

    /**
     * Returns the login url for google.
     * (The url to forward to)
     */
    public function getAuthUrl() {
        $authParams = ['response_type' => 'code',
                       'redirect_uri' => $this->config['redirect_uri'],
                       'client_id' => $this->config['client_id'],
                       'scope' => 'https://www.googleapis.com/auth/userinfo.email',
                       'access_type' => 'offline',
                       'approval_prompt' => 'force'];

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
        $this->authCodeResult = json_decode($authCodeResultJson);
        return $this->authCodeResult;
    }

    /**
     * fetches the info from the token, e.g. the email address
     */
    public function getTokenInfo() {
        $header = ['authorization: Bearer '. $this->authCodeResult->access_token];
        $tokenInfoParams = ['id_token' => $this->authCodeResult->id_token];

        $tokenInfoResultJson = $this->http->getRequest($this->URLS['tokenInfo'], $tokenInfoParams, $header);
        $this->tokenInfoResult = json_decode($tokenInfoResultJson);
        return $this->tokenInfoResult;
    }
}