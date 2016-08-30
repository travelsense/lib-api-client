<?php
namespace HopTrip\ApiClient;

use PHPCurl\CurlHttp\HttpClient;
use PHPCurl\CurlHttp\HttpResponse;

class ApiClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \HopTrip\ApiClient\ApiClientException
     * @expectedExceptionMessage foo
     * @expectedExceptionCode    42
     */
    public function testException()
    {
        $http = $this->getMockBuilder(HttpClient::class)
            ->getMock();

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
        $client = new ApiClient('http://localhost', $http);

        $http->method('get')->willReturn($response);
        $client->getCurrentUser();
    }
}
