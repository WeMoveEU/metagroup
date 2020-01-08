<?php
namespace KenSh\MetabaseApi\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use KenSh\MetabaseApi\Exception\AuthFailedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class AuthMiddleware
{
    /**
     * @var string@
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * AuthMiddleware constructor.
     * @param string $baseUri
     * @param string $user
     * @param string $password
     * @param CacheInterface $cache
     */
    public function __construct(string $baseUri, string $user, string $password, CacheInterface $cache)
    {
        $this->user = $user;
        $this->password = $password;
        $this->client = new Client(['base_uri' => $baseUri]);
        $this->cache = $cache;
    }

    /**
     * @param callable $handler
     * @return Closure
     */
    public function __invoke(callable $handler)
    {
        $fetchSessionTokenID = $this->fetchSessionTokenID();
        $client = $this->client;
        $cache = $this->cache;

        return function (
            RequestInterface $request,
            array $options
        ) use ($handler, $fetchSessionTokenID, $client, $cache) {

            if (!$cache->has('metabase_session_token_id')) {
                /**
                 * @var $responseSessionTokenID ResponseInterface
                 */
                $responseSessionTokenID = $fetchSessionTokenID($client);

                if ($responseSessionTokenID->getStatusCode() > 200) {
                    throw new AuthFailedException();
                }

                $sessionIDResult = json_decode($responseSessionTokenID->getBody()->getContents());
                $cache->set('metabase_session_token_id', $sessionIDResult->id);
            }

            return $handler($request->withHeader(
                'X-Metabase-Session',
                $cache->get('metabase_session_token_id')
            ), $options);
        };
    }

    /**
     * @return callable|__anonymous@1174
     */
    public function fetchSessionTokenID()
    {
        return new class($this->user, $this->password) {

            /**
             * @var string
             */
            private $p;

            /**
             * @var string
             */
            private $u;

            public function __construct($user, $password)
            {
                $this->u = $user;
                $this->p = $password;
            }

            public function __invoke(Client $client)
            {
                $request = new Request(
                    "POST",
                    "api/session",
                    ["Content-Type" => "application/json; charset=utf-8"],
                    json_encode([
                        'username' => $this->u,
                        'password' => $this->p
                    ])
                );

                return $client->send($request);
            }
        };
    }
}