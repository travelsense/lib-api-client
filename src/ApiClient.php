<?php
namespace HopTrip\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ApiClient
{

    /**
     * @var string
     */
    private $auth_token;

    /**
     * @var Client
     */
    private $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    public function setAuthToken(string $auth_token = null)
    {
        $this->auth_token = $auth_token;
    }

    public function registerUser(array $user)
    {
        return $this->post('/user', ['json' => $user]);
    }

    public function getTokenByEmail(string $email, string $password): string
    {
        return $this->post('/token', ['json' => ['email' => $email, 'password' => $password]])
            ->token;
    }

    public function getTokenByFacebook(string $fbToken): string
    {
        return $this->post('/token', ['json' => ['fbToken' => $fbToken]])
            ->token;
    }

    public function confirmEmail(string $email)
    {
        return $this->post($this->formatUri('/email/confirm/%s', $email));
    }

    public function requestPasswordReset(string $email)
    {
        return $this->post($this->formatUri('/password/link/%s', $email));
    }

    public function updatePassword(string $token, string $password)
    {
        return $this->post(
            $this->formatUri('/password/reset/%s', $token),
            ['json' => ['password' => $password]]
        );
    }

    public function getCurrentUser()
    {
        return $this->get('/user');
    }

    public function getPublishedByAuthor(
        int $author_id,
        bool $minimized = true,
        int $limit = 10,
        int $offset = 0
    ): array {
        return $this->get(
            $this->formatUri('/user/%s/travels', $author_id),
            [
                'query' => [
                    'minimized' => $minimized,
                    'limit'     => $limit,
                    'offset'    => $offset,
                ],
            ]
        );
    }

    public function getCabEstimates(float $lat1, float $lon1, float $lat2, float $lon2)
    {
        return $this->get($this->formatUri('/cab/%s/%s/%s/%s', $lat1, $lon1, $lat2, $lon2));
    }

    public function startHotelSearch(int $location, string $in, string $out, int $rooms)
    {
        return $this->post($this->formatUri('/hotel/search/%s/%s/%s/%s', $location, $in, $out, $rooms));
    }

    public function getHotelSearchResults(int $id, int $page = 1)
    {
        return $this->get($this->formatUri('/hotel/search-results/%s/%s', $id, $page));
    }

    public function createTravel(array $travel): int
    {
        return $this->post('/travel', ['json' => $travel])->id;
    }

    public function addTravelComment(int $id, string $text): int
    {
        return $this->post(
            $this->formatUri('/travel/%s/comment', $id),
            [
                'json' => [
                    'text' => $text,
                ],
            ]
        )->id;
    }

    public function deleteTravelComment(int $id)
    {
        return $this->delete($this->formatUri('/travel/comment/%s', $id));
    }

    public function getTravelComments(int $id, int $limit, int $offset): array
    {
        return $this->get(
            $this->formatUri('/travel/%d/comments', $id),
            [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            ]
        );
    }

    public function createCategory(string $name): int
    {
        return $this->post('/category', ['json' => ['name' => $name]])->id;
    }

    public function getTravelCategories(string $name = null)
    {
        return $this->get('/travel/categories', $name ? ['query' => ['name' => $name]] : []);
    }

    public function getTravel(int $id)
    {
        return $this->get($this->formatUri('/travel/%s', $id));
    }

    public function getTravelsByCategory(string $name)
    {
        return $this->get($this->formatUri('/travel/by-category/%s', $name));
    }

    public function getMyTravels()
    {
        return $this->get('/travel/by-user');
    }

    public function updateTravel(int $id, array $travel)
    {
        return $this->put($this->formatUri('/travel/%s', $id), ['json' => $travel]);
    }

    public function deleteTravel(int $id)
    {
        return $this->delete($this->formatUri('/travel/%s', $id));
    }

    public function addTravelToFavorites(int $id)
    {
        return $this->post($this->formatUri('/travel/%s/favorite', $id));
    }

    public function removeTravelFromFavorites(int $id)
    {
        return $this->delete($this->formatUri('/travel/%s/favorite', $id));
    }

    public function getFavoriteTravels()
    {
        return $this->get('/travel/favorite');
    }

    public function getFeatured()
    {
        return $this->get('/travel/featured');
    }

    public function registerBooking(int $id, array $details)
    {
        return $this->post($this->formatUri("/travel/%s/book", $id), ['json' => $details]);
    }

    public function getStats()
    {
        return $this->get('/stats');
    }

    public function uploadImage(string $content)
    {
        return $this->post('/image', ['body' => $content]);
    }

    private function formatUri(string $format, ...$args): string
    {
        return sprintf($format, ...array_map('urlencode', $args));
    }

    private function get(string $uri, array $options = [])
    {
        return $this->call('GET', $uri, $options);
    }

    private function post(string $uri, array $options = [])
    {
        return $this->call('POST', $uri, $options);
    }

    private function put(string $uri, array $options = [])
    {
        return $this->call('PUT', $uri, $options);
    }

    private function delete(string $uri, array $options = [])
    {
        return $this->call('DELETE', $uri, $options);
    }

    private function call(string $method, string $uri, array $options = [])
    {
        if ($this->auth_token) {
            $options['headers']['Authorization'] = "Token {$this->auth_token}";
        }
        try {
            /** @var \Psr\Http\Message\StreamInterface $body */
            $body = $this->http
                ->request($method, $uri, $options)
                ->getBody();
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $error = json_decode($e->getResponse()->getBody()->getContents());
                if (isset($error->error, $error->code)) {
                    throw new ApiClientException($error->error, $error->code);
                }
            }
            throw $e;
        }

        return json_decode($body); // StreamInterface gets converted to string implicitly
    }
}
