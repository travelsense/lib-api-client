<?php
namespace HopTrip\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    public function schema()
    {
        return [
            [
                'registerUser',
                [['foo' => 'bar']],
                new Response(200),
                null,
                'POST',
                '/user',
                '{"foo":"bar"}',
            ],
            [
                'getTokenByEmail',
                ['my_email', 'my_pass'],
                new Response(200, [], '{"token":"yo"}'),
                'yo',
                'POST',
                '/token',
                '{"email":"my_email","password":"my_pass"}'
            ],
            [
                'getTokenByFacebook',
                ['my_fb_token'],
                new Response(200, [], '{"token":"yo"}'),
                'yo',
                'POST',
                '/token',
                '{"fbToken":"my_fb_token"}'
            ],
            [
                'confirmEmail',
                ['user@example.com'],
                new Response(200, [], '{"foo":"bar"}'),
                (object) ['foo'=> 'bar'],
                'POST',
                '/email/confirm/user%40example.com',
            ],
            [
                'requestPasswordReset',
                ['user@example.com'],
                new Response(200, [], '{"foo":"bar"}'),
                (object) ['foo'=> 'bar'],
                'POST',
                '/password/link/user%40example.com',
            ],
            [
                'updatePassword',
                ['my_token', 'my_pass'],
                new Response(200, [], '{"foo":"bar"}'),
                (object) ['foo'=> 'bar'],
                'POST',
                '/password/reset/my_token',
                '{"password":"my_pass"}'
            ],
            [
                'getCurrentUser',
                [],
                new Response(200, [], '{"foo":"bar"}'),
                (object) ['foo'=> 'bar'],
                'GET',
                '/user',
            ],
            [
                'getPublishedByAuthor',
                [42, true, 1, 2],
                new Response(200, [], '[{"foo":"bar"}]'),
                [(object) ['foo'=> 'bar']],
                'GET',
                '/user/42/travels?minimized=1&limit=1&offset=2',
            ],
            [
                'updateUser',
                [['aaa'=> 'bbb']],
                new Response(200, [], '[{"foo":"bar"}]'),
                [(object) ['foo'=> 'bar']],
                'PUT',
                '/user',
                '{"aaa":"bbb"}'
            ],
        ];
    }

    /**
     * @dataProvider schema
     * @param string   $method
     * @param array    $args
     * @param Response $response
     * @param          $expected_result
     * @param string   $request_method
     * @param string   $request_uri
     * @param string   $request_body
     */
    public function testAll(
        string $method,
        array $args,
        Response $response,
        $expected_result,
        string $request_method,
        string $request_uri,
        string $request_body = ''
    ) {
        $mock = new MockHandler([
            $response,
            new Response(401, [], '{"error":"omg","code":42}')
        ]);
        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $guzzle = new Client([
            'base_url' => 'https://example.com',
            'handler' => $handler
        ]);
        $client = new ApiClient($guzzle);
        $client->setAuthToken('mytoken');

        $result = call_user_func_array([$client, $method], $args);
        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals($expected_result, $result);
        $this->assertEquals($request_method, $request->getMethod());
        $this->assertEquals($request_body, $request->getBody()->getContents());
        $this->assertEquals($request_uri, (string) $request->getUri());
        $this->assertEquals('Token mytoken', $request->getHeaderLine('Authorization'));

        try {
            call_user_func_array([$client, $method], $args);
            $this->fail('Exception expected');
        } catch (ApiClientException $e) {
            $this->assertEquals('omg', $e->getMessage());
            $this->assertEquals(42, $e->getCode());
        }
    }
}
