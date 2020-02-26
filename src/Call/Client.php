<?php
/**
 * Nexmo Client Library for PHP
 *
 * @copyright Copyright (c) 2016 Nexmo, Inc. (http://nexmo.com)
 * @license   https://github.com/Nexmo/nexmo-php/blob/master/LICENSE.txt MIT License
 */

namespace Nexmo\Call;

use Nexmo\Client\APIResource;
use Nexmo\Entity\FilterInterface;
use Nexmo\Client\ClientAwareTrait;
use Nexmo\Entity\CollectionInterface;
use Nexmo\Client\ClientAwareInterface;
use Nexmo\Entity\IterableServiceShimTrait;
use Nexmo\Entity\Hydrator\HydratorInterface;
use Nexmo\Entity\IterableAPICollection;

class Client implements ClientAwareInterface, CollectionInterface, \ArrayAccess
{
    use ClientAwareTrait;
    use IterableServiceShimTrait;

    /**
     * @var APIResource
     */
    protected $api;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    public function __construct(APIResource $api, HydratorInterface $hydrator)
    {
        $this->api = $api;
        $this->hydrator = $hydrator;
    }

    public static function getCollectionName()
    {
        return 'calls';
    }

    public static function getCollectionPath()
    {
        return '/v1/' . self::getCollectionName();
    }

    /**
     * @deprecated Use search() instead
     *
     * @param null $callOrFilter
     * @return $this|Call
     */
    public function __invoke(Filter $filter = null)
    {
        trigger_error('Array access to Nexmo\Call\Collection::__invoke() is deprecated, please use search() instead');
        if (!is_null($filter)) {
            $this->setFilter($filter);
        }

        return $this;
    }

    /**
     * Creates a new call
     */
    public function create($call) : Call
    {
        if ($call instanceof Call) {
            $body = $call->getRequestData();
        } else {
            trigger_error('Passing an array to Nexmo\Application\Client::create() has been deprecated, please pass a Call object instead.');
            $body = $call;
        }

        $response = $this->api->create($body);

        return $this->hydrator->hydrate($response);
    }

    /**
     * @deprecated See update()
     */
    public function put($payload, $idOrCall)
    {
        trigger_error('Passing an array to Nexmo\Application\Client::put() has been deprecated, please use update() instead');
        return $this->update($payload, $idOrCall);
    }

    /**
     * Update an existing call 
     */
    public function update($payload, $idOrCall)
    {
        if (!($idOrCall instanceof Call)) {
            $idOrCall = new Call($idOrCall);
        }

        $idOrCall->setClient($this->getClient());
        $idOrCall->put($payload);
        return $idOrCall;
    }

    /**
     * Stream audio into a call
     * 
     * @param array<string> $urls Array of URLs to stream
     */
    public function streamAudio(Call $call, array $urls, int $loop = 1, float $volumeLevel = 0.0)
    {
        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());

        $api->update('stream', [
            'stream_url' => $urls,
            'loop' => $loop,
            'level' => $volumeLevel,
        ]);
    }

    /**
     * Play DTMF into an existing call
     */
    public function dtmf(Call $call, string $digits)
    {
        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());

        $api->update('dtmf', ['digits' => $digits]);
    }

    /**
     * Play TTS into an existing call
     */
    public function talk(
        Call $call,
        string $text,
        string $voiceName = 'Kimberly',
        int $loop = 1,
        float $volumeLevel = 0.0
    )
    {
        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());

        $api->update('talk', [
            'text' => $text,
            'voice_name' => $voiceName,
            'loop' => $loop,
            'level' => $volumeLevel,
        ]);
    }

    /**
     * @deprecated See streamAudioStop() or talkStop() instead
     */
    public function delete($call = null, $type)
    {
        trigger_error('Nexmo\Call\Collection::delete() is deprecated, please use streamAudioStop() or talkStop() instead');

        if (is_object($call) and is_callable([$call, 'getId'])) {
            $call = $call->getId();
        }

        if (!($call instanceof Call)) {
            $call = new Call($call);
        }

        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());
        $api->delete($type);

        return $call;
    }

    /**
     * Stop currently streaming audio in a call
     */
    public function streamAudioStop(Call $call) : void
    {
        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());

        $api->delete('stream');
    }

    /**
     * Stops any TTS in an existing call
     */
    public function talkStop(Call $call) : void
    {
        $api = clone $this->api;
        $api->setBaseUri($this->api->getBaseUri() . '/' . $call->getId());

        $api->delete('talk');
    }

    /**
     * @deprecated See create() instead
     */
    public function post($call)
    {
        trigger_error('Nexmo\Call\Collection::post() has been deprecated, please use create() instead.');
        return $this->create($call);
    }

    public function get($call)
    {
        if (!($call instanceof Call)) {
            $call = new Call($call);
        } else {
            trigger_error('Passing a Call object to Nexmo\Call\Collection::get() is deprecated, please pass a string id');
        }

        $response = $this->api->get($call->getId());
        return $this->hydrator->hydrateObject($response, $call);
    }

    /**
     * @deprecated Please use the get() method instead
     */
    public function offsetExists($offset)
    {
        //todo: validate form of id
        return true;
    }

    /**
     * @deprecated Please use the get() method instead
     * @param mixed $call
     * @return Call
     */
    public function offsetGet($call)
    {
        trigger_error('Array access to Nexmo\Call\Collection::get() is deprecated, please use search() instead');
        if (!($call instanceof Call)) {
            $call = new Call($call);
        }

        $call->setClient($this->getClient());
        return $call;
    }

    /**
     * @deprecated Will not be implemented and will be removed in future releases
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('can not set collection properties');
    }

    /**
     * @deprecated Will not be implemented and will be removed in future releases
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('can not unset collection properties');
    }

    /**
     * Search and return calls that match the filter criteroa
     */
    public function search(FilterInterface $filter = null) : IterableAPICollection
    {
        $collection = $this->api->search($filter);
        $collection->setHydrator($this->hydrator);

        return $collection;
    }
}
