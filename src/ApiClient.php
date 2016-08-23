<?php
namespace HopTrip\ApiClient;

use PHPCurl\CurlHttp\HttpClient;
use PHPCurl\CurlHttp\HttpResponse;
use RuntimeException;

class ApiClient
{
    /**
     * @var string
     */
    private $auth_token;

    /**
     * @var string
     */
    private $host;

    /**
     * @var HttpClient
     */
    private $http;

    /**
     * ApiClient constructor.
     *
     * @param string     $host
     * @param HttpClient $http
     */
    public function __construct(string $host, HttpClient $http = null)
    {
        $this->http = $http ?: new HttpClient();
        $this->host = $host;
    }

    /**
     * @param string $auth_token
     */
    public function setAuthToken(string $auth_token = null)
    {
        $this->auth_token = $auth_token;
    }

    /**
     * Register new user
     *
     * @param  array $user (firstName, lastName, email, password, picture)
     * @return object
     *
     * Example: $client->registerUser([
     *  'firstName' => 'Alexander'
     *  'lastName'=> 'Pushkin',
     *  'email' => 'sasha@nashe-vse.ru',
     *  'password' => 'd4n73s l0h',
     * ]);
     */
    public function registerUser(array $user)
    {
        return $this->post('/user', $user);
    }

    /**
     * @param string $email
     * @param string $password
     * @return string Auth token
     */
    public function getTokenByEmail(string $email, string $password)
    {
        return $this->post('/token', ['email' => $email, 'password' => $password])
            ->token;
    }

    /**
     * @param string $fbToken
     * @return string Auth token
     */
    public function getTokenByFacebook(string $fbToken)
    {
        return $this->post('/token', ['fbToken' => $fbToken])
            ->token;
    }

    public function confirmEmail(string $email)
    {
        return $this->post('/email/confirm/' . urlencode($email));
    }

    public function requestPasswordReset(string $email)
    {
        return $this->post('/password/link/' . urlencode($email));
    }

    public function updatePassword(string $token, string $password)
    {
        return $this->post('/password/reset/' . urlencode($token), ['password' => $password]);
    }

    /**
     * Get current user info
     *
     * @return object
     */
    public function getCurrentUser()
    {
        return $this->get('/user');
    }

    /**
     * Update user data
     *
     * @param array $request
     * @return object
     */
    public function updateUser(array $request)
    {
        return $this->put('/user', $request);
    }

    public function getCabEstimates(float $lat1, float $lon1, float $lat2, float $lon2)
    {
        return $this->get("/cab/$lat1/$lon1/$lat2/$lon2");
    }

    /**
     * start search
     *
     * @param  int    $location wego location id
     * @param  string $in       yyyy-mm-dd
     * @param  string $out      yyyy-mm-dd
     * @param  int    $rooms
     * @return int wego search id
     */
    public function startHotelSearch(int $location, string $in, string $out, int $rooms)
    {
        return $this->post("/hotel/search/$location/$in/$out/$rooms");
    }

    /**
     * get search results
     *
     * @param  int $id   wego search id
     * @param  int $page page number
     * @return array
     */
    public function getHotelSearchResults(int $id, int $page = 1)
    {
        return $this->get("/hotel/search-results/$id/$page");
    }

    /**
     * Create a new Travel
     * @param array $travel
     * @return int
     */
    public function createTravel(array $travel)
    {
        return $this->post('/travel', $travel)->id;
    }

    /**
     * Create a new Comment
     *
     * @param int    $id
     * @param string $text
     * @return int
     */
    public function addTravelComment(int $id, string $text)
    {
        return $this->post(sprintf('/travel/%s/comment', urlencode($id)), [
            'text' => $text,
        ])->id;
    }

    /**
     * Delete a comment
     *
     * @param int $id
     * @return mixed
     */
    public function deleteTravelComment(int $id)
    {
        return $this->delete(sprintf('/travel/comment/%s', urlencode($id)));
    }

    public function getTravelComments(int $id, int $limit, int $offset)
    {
        $url = sprintf('/travel/%d/comments?', urlencode($id))
            . http_build_query([
                'limit' => $limit,
                'offset' => $offset,
            ]);

        return $this->get($url);
    }

    /**
     * Create a new Category
     *
     * @param string $name
     * @return mixed
     */
    public function createCategory(string $name)
    {
        return $this->post('/category', ['name' => $name,])->id;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getCategories(string $name = null)
    {
        $url = '/categories';
        if ($name !== null) {
            $url .= '?' . http_build_query(['name' => $name]);
        }
        return $this->get($url);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getTravelCategories(string $name = null)
    {
        $url = '/travel/categories';
        if ($name !== null) {
            $url .= '?' . http_build_query(['name' => $name]);
        }
        return $this->get($url);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTravel(int $id)
    {
        return $this->get('/travel/' . urlencode($id));
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getTravelsByCategory(string $name)
    {
        return $this->get('/travel/by-category/' . urlencode($name));
    }

    /**
     * @return mixed
     */
    public function getMyTravels()
    {
        return $this->get('/travel/by-user');
    }

    /**
     * @param int $id
     * @param array $travel
     */
    public function updateTravel(int $id, array $travel)
    {
        $this->put('/travel/' . urlencode($id), $travel);
    }

    /**
     * @param int $id
     * @return void
     */
    public function deleteTravel(int $id)
    {
        $this->delete('/travel/' . urlencode($id));
    }

    /**
     * @param int $id
     * @return object
     */
    public function addTravelToFavorites(int $id)
    {
        return $this->post(sprintf('/travel/%s/favorite', urlencode($id)));
    }

    /**
     * @param int $id
     * @return object
     */
    public function removeTravelFromFavorites(int $id)
    {
        return $this->delete(sprintf('/travel/%s/favorite', urlencode($id)));
    }

    /**
     * @return array
     */
    public function getFavoriteTravels()
    {
        return $this->get('/travel/favorite');
    }

    /**
     * @return array
     */
    public function getFeatured()
    {
        return $this->get('/travel/featured');
    }

    /**
     * @param int $id Travel id
     * @return mixed
     */
    public function registerBooking(int $id)
    {
        return $this->post(sprintf("/travel/%s/book", urldecode($id)));
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->get('/stats');
    }

    private function addAuth(array $headers): array
    {
        if (!empty($this->auth_token)) {
            $headers[] = 'Authorization: Token ' . $this->auth_token;
        }
        return $headers;
    }

    /**
     * @param HttpResponse $response
     * @return mixed
     */
    private function parse(HttpResponse $response)
    {
        if ($response->getCode() === 200) {
            return json_decode($response->getBody());
        }
        $error = @json_decode($response->getBody());
        if (!empty($error)) {
            throw new ApiClientException($error->error, $error->code);
        }
        $message = "HTTP ERROR {$response->getCode()}\n"
            . implode("\n", $response->getHeaders())
            . "\n\n" . $response->getBody();
        throw new RuntimeException($message, $response->getCode());
    }

    private function get($url, array $headers = [])
    {
        $headers = $this->addAuth($headers);
        $response = $this->http->get($this->host . $url, $headers);
        return $this->parse($response);
    }

    private function post($url, array $body = [], array $headers = [])
    {
        $headers = $this->addAuth($headers);
        $response = $this->http->post($this->host . $url, json_encode($body), $headers);
        return $this->parse($response);
    }

    private function put($url, array $body = [], array $headers = [])
    {
        $headers = $this->addAuth($headers);
        $response = $this->http->put($this->host . $url, json_encode($body), $headers);
        return $this->parse($response);
    }

    private function delete($url, array $headers = [])
    {
        $headers = $this->addAuth($headers);
        $response = $this->http->delete($this->host . $url, $headers);
        return $this->parse($response);
    }
}
