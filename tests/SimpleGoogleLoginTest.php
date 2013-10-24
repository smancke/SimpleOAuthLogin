<?php
require 'SimpleGoogleLogin.php';
require 'SimpleHttp.php';


class SimpleGoogleLoginTest extends PHPUnit_Framework_TestCase {
    
    private $config = [
                       'redirect_uri' => '-uri-',
                       'client_id' => '-id-',
                       'client_secret' => '-secret-'
                       ];
    
    public function testAuthUrl() {
        $googeLogin = new SimpleGoogleLogin($this->config, new SimpleHttp());
        $url = $googeLogin->getAuthUrl();
        
        $this->assertContains('redirect_uri=-uri-', $url);
        $this->assertContains('client_id=-id-', $url);        
    }

    public function testExchangeAuthCode() {
        $http = new HttpMock();
        $googeLogin = new SimpleGoogleLogin($this->config, $http);

        $auth_token = $googeLogin->exchangeAuthCode('code');
        $this->assertEquals('faketoken', $auth_token->access_token);

        $this->assertEquals('https://accounts.google.com/o/oauth2/token', $http->url);
        $this->assertEquals('-secret-', $http->params['client_secret']);
        $this->assertEquals('-uri-', $http->params['redirect_uri']);
        $this->assertEquals('-id-', $http->params['client_id']);
        $this->assertEquals('code', $http->params['code']);
    }

    public function testGetTokenInfo() {
        $http = new HttpMock();
        $googeLogin = new SimpleGoogleLogin($this->config, $http);
        $auth_token = $googeLogin->exchangeAuthCode('code');

        $tokenInfo = $googeLogin->getTokenInfo();

        $this->assertEquals('value', $tokenInfo->fake);
        $this->assertEquals('https://www.googleapis.com/oauth2/v1/tokeninfo', $http->url);
        $this->assertContains('authorization: Bearer '.$auth_token->access_token, $http->headerList);
    }
}

class HttpMock {
    public $url;
    public $params;
    public $headerList;

    public function postRequest($url, $params=[], $headerList=[]) {
        $this->url = $url;
        $this->params = $params;
        $this->headerList = $headerList;
        
        return '{"access_token": "faketoken", "id_token": "fakeidtoken"}';
    }

    public function getRequest($url, $params=[], $headerList=[]) {
        $this->url = $url;
        $this->params = $params;
        $this->headerList = $headerList;
        
        return '{"fake": "value"}';
    }
}
?>