<?php
require 'SimpleHttp.php';

class SimpleHttpTest extends PHPUnit_Framework_TestCase
{
    private $HTTP_RESULT_FILE = 'http.result';

    public function testArrayFormEncodeBasic() {
        $http = new SimpleHttp();
        $encoded = $http->arrayFormEncode([
                                           'a' => 'b',
                                           'x' => 'y'
                                           ]);
        $this->assertEquals('a=b&x=y', $encoded);
    }

    public function testGet() {
        $http = new SimpleHttp();
        file_put_contents($this->HTTP_RESULT_FILE, '');

        $httpResult = $http->getRequest('http://127.0.0.1:1111/bla', ['param1' => 'value1'], ['bli-bla-header: blibla']);

        time_nanosleep(0, 50000000); //50ms
        $httpLog = file_get_contents($this->HTTP_RESULT_FILE);

        $header = split("\r\n", $httpLog);

        $this->assertEquals(200, $httpResult->code);
        $this->assertTrue(in_array('bli-bla-header: blibla', $header));
        $this->assertEquals('GET /bla?param1=value1 HTTP/1.1', $header[0]);

        $this->assertEquals('hallo', $httpResult->body);
    }

    public function testGetToWrongPort() {
        $http = new SimpleHttp();

        $httpResult = $http->getRequest('http://127.0.0.1:6666/bla');

        $this->assertEquals(0, $httpResult->code);
        $this->assertStringStartsWith('Failed connect to', $httpResult->errorMessage);
    }

    public function testGet404() {
        $http = new SimpleHttp();

        $httpResult = $http->getRequest('http://127.0.0.1:1112/bla');

        $this->assertEquals(404, $httpResult->code);
    }

    public function testPost() {
        $http = new SimpleHttp();
        file_put_contents($this->HTTP_RESULT_FILE, '');

        $httpResult = $http->postRequest('http://127.0.0.1:1111/bla', ['param1' => 'value1'], ['bli-bla-header: blibla']);
        
        time_nanosleep(0, 50000000); //50ms
        $httpLog = file_get_contents($this->HTTP_RESULT_FILE);

        $lines = split("\r\n", $httpLog);

        $this->assertEquals(200, $httpResult->code);
        $this->assertTrue(in_array('bli-bla-header: blibla', $lines));
        $this->assertEquals('POST /bla HTTP/1.1', $lines[0]);
        $this->assertEquals('param1=value1', $lines[count($lines)-1]);

        $this->assertEquals('hallo', $httpResult->body);
    }

    public function testPostToWrongPort() {
        $http = new SimpleHttp();

        $httpResult = $http->postRequest('http://127.0.0.1:6666/bla');

        $this->assertEquals(0, $httpResult->code);
        $this->assertStringStartsWith('Failed connect to', $httpResult->errorMessage);
    }

    public function testPost404() {
        $http = new SimpleHttp();

        $httpResult = $http->postRequest('http://127.0.0.1:1112/bla');

        $this->assertEquals(404, $httpResult->code);
    }
}
?>