<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

/**
 * Class PersistentCollection.
 */
class DefaultRelation extends AbstractMongoStrategy
{
    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, $data)
    {
        // Beware of the collection strategies:
        $collection = $this->collectionName;
        if ($this->metadata->isCollectionValuedAssociation($collection)) {
            $value = $this->hydrateCollection($value);
        }

        return $value;
    }
}
