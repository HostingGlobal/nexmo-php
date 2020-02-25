<?php

namespace Nexmo\Entity\Hydrator;

class ArrayHydrator implements HydratorInterface
{
    /**
     * @var ArrayHydratorInterface
     */
    protected $prototype;

    public function hydrate(array $data)
    {
        $object = clone $this->prototype;
        $object->createFromArray($data);

        return $object;
    }

    public function setPrototype(ArrayHydrateInterface $prototype)
    {
        $this->prototype = $prototype;
    }
}
