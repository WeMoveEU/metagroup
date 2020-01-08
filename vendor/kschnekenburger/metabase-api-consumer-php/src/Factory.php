<?php

namespace KenSh\MetabaseApi;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use KenSh\MetabaseApi\Exception\InvalidResourceException;
use KenSh\MetabaseApi\Exception\ResourceNotFoundException;
use KenSh\MetabaseApi\Middleware\AuthMiddleware;
use KenSh\MetabaseApi\Resources\Activity;
use KenSh\MetabaseApi\Resources\Alert;
use KenSh\MetabaseApi\Resources\Card;
use KenSh\MetabaseApi\Resources\Dashboard;
use KenSh\MetabaseApi\Resources\Resource;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class Factory
 * @package KenSh\MetabaseApi
 *
 * @method Activity activity
 * @method Card card
 * @method Alert alert
 */
class Factory
{
    private $resources = [
        'activity' => Activity::class,
        'alert' => Alert::class,
        'card' => Card::class,
        'dashboard' => Dashboard::class
    ];

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $baseApiUri;
    /**
     * @var FilesystemAdapter
     */

    private $cache;

    /**
     * Factory constructor.
     *
     * @param string $metabaseBaseUrl
     * @param string $user
     * @param string $password
     * @param array $options
     */
    public function __construct(string $metabaseBaseUrl, string $user, string $password, $options = [])
    {
        $this->user = $user;
        $this->password = $password;
        $this->baseApiUri = "$metabaseBaseUrl/";

        if (!isset($options['cache'])) {
            $this->cache = $this->getDefaultCacheConfiguration();
        }

        $this->client = new Client([
            'base_uri' => $this->baseApiUri,
            'handler' => $this->getHandlerStack()
        ]);
    }

    /**
     * Create an instance of the service with an API key.
     *
     * @param string $metabaseBaseUrl
     * @param string $user
     * @param string $password
     * @param array $options
     * @return static
     */
    public static function create(string $metabaseBaseUrl, string $user, string $password, $options = [])
    {
        return new static($metabaseBaseUrl, $user, $password, $options);
    }

    /**
     * @return HandlerStack
     */
    public function getHandlerStack() : HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push(new AuthMiddleware($this->baseApiUri, $this->user, $this->password, $this->cache));

        return $stack;
    }

    /**
     * @return CacheInterface
     */
    private function getDefaultCacheConfiguration() : CacheInterface
    {
        $defaultPath = dirname(__DIR__);

        $filesystemAdapter = new Local("$defaultPath/");
        $filesystem = new Filesystem($filesystemAdapter);

        return new FilesystemCachePool($filesystem);
    }

    /**
     * add a resource.
     *
     * @param string $name identifier of resource
     * @param string $ns namespace of class resource
     * @return Factory
     */
    public function addResource(string $name, string $ns)
    {
        $this->resources[$name] = $ns;
        return $this;
    }

    /**
     * add multiple resources
     *
     * @param array $resources array of resource to add.
     * @return $this
     */
    public function addResources(array $resources)
    {
        $this->resources = array_merge($this->resources, $resources);
        return $this;
    }

    /**
     * Return an instance of a Resource based on the method called.
     * throw exception when resource not found (class doesn't exist) or resource doesn't extend of Resource Abstract class.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return Resource
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     */
    public function __call($name, $arguments = null)
    {
        if (!isset($this->resources[strtolower($name)]) || !class_exists($this->resources[strtolower($name)])) {
            throw new ResourceNotFoundException('Resources is not defined or class doesn\'t exist');
        }

        $resource = $this->resources[strtolower($name)];
        $resourceClass = new $resource($this->client, $arguments);

        if (!$resourceClass instanceof Resource) {
            throw new InvalidResourceException('Resource must be extend of Resource.');
        }

        return $resourceClass;
    }
}
