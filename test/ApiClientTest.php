<?php
namespace HopTrip\ApiClient;

use PHPCurl\CurlHttp\HttpClient;
use PHPCurl\CurlHttp\HttpResponse;

class ApiClientTest extends \PHPUnit_Framework_TestCase
{
    private $http;

    /**
     * @var ApiClient
     */
    private $client;

    public function setUp()
    {
        $this->http = $this->getMockBuilder(HttpClient::class)->getMock();
        $this->client = new ApiClient('http://localhost', $this->http);

    }

    /**
     * @expectedException \HopTrip\ApiClient\ApiClientException
     * @expectedExceptionMessage foo
     * @expectedExceptionCode    42
     */
    public function testException()
    {
        $response = $this->getMockBuilder(HttpResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getBody')
            ->willReturn(json_encode(
                [
                    'error' => 'foo',
                    'code'  => 42,
                ]
            ));

        $response->method('getCode')->willReturn(500);

        $this->http->method('get')->willReturn($response);
        $this->client->getCurrentUser();
    }

    public function testRegisterBooking()
    {
        $response = $this->getMockBuilder(HttpResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->method('getHeaders')->willReturn([]);
        $response->method('getBody')->willReturn('{}');
        $response->method('getCode')->willReturn(200);

        $this->http
            ->expects($this->once())
            ->method('post')
            ->with('http://localhost/travel/42/book', '{"foo":"bar"}')
            ->willReturn($response);

        $this->assertEquals(
            (object) [],
            $this->client->registerBooking(42, ['foo' => 'bar'])
        );
    }
}
