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
    private $user;
    private $password;
    private $client;
    private $cache;
    private $nexthandler;

    public static function create(string $baseUri, string $user, string $password, CacheInterface $cache) {
        return function(callable $handler) use ($baseUri, $user, $password, $cache) {
            return new AuthMiddleware($handler, $baseUri, $user, $password, $cache);
        };
    }

    public function __construct(callable $nexthandler, string $baseUri, string $user, string $password, CacheInterface $cache)
    {
        $this->user = $user;
        $this->password = $password;
        $this->client = new Client(['base_uri' => $baseUri]);
        $this->cache = $cache;
        $this->nexthandler = $nexthandler;
        $this->retried = FALSE;
    }

    public function __invoke(RequestInterface $request, array $options) {
        if (!$this->cache->has('metabase_session_token_id')) {
            $this->resetSessionTokenID();
        }

        $fn = $this->nexthandler;
        $promise = $fn($request->withHeader('X-Metabase-Session', $this->cache->get('metabase_session_token_id')), $options);
        return $promise->then(
            function (ResponseInterface $response) use ($request, $options) {
                // If the request is unauthorized, the token must have expired so we clear it and retry the request
                if ($response->getStatusCode() == 401 && !$this->retried) {
                    $this->resetSessionTokenID();
                    $this->retried = TRUE;
                    return $this($request, $options);
                }
                return $response;
            }
        );
    }

    public function resetSessionTokenID()
    {
        $responseSessionTokenID = $this->fetchSessionTokenID();
        if ($responseSessionTokenID->getStatusCode() > 200) {
            throw new AuthFailedException();
        }

        $sessionIDResult = json_decode($responseSessionTokenID->getBody()->getContents());
        $this->cache->set('metabase_session_token_id', $sessionIDResult->id);
        return $sessionIDResult->id;
    }

    public function fetchSessionTokenID()
    {
        $request = new Request(
            "POST",
            "api/session",
            ["Content-Type" => "application/json; charset=utf-8"],
            json_encode([
                'username' => $this->user,
                'password' => $this->password
            ])
        );

        return $this->client->send($request);
    }
}
