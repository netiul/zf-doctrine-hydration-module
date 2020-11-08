<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

/**
 * Class PersistentCollection.
 */
class EmbeddedReferenceField extends AbstractMongoStrategy
{
    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        if (!$value) {
            return $value;
        }

        $strategy = new EmbeddedField($this->getObjectManager());
        $strategy->setClassMetadata($this->getClassMetadata());
        $strategy->setCollectionName($this->getCollectionName());
        $strategy->setObject($value);

        return $strategy->extract($value);
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, $data)
    {
        $strategy = new ReferencedField($this->getObjectManager());
        $strategy->setClassMetadata($this->getClassMetadata());
        $strategy->setCollectionName($this->getCollectionName());
        if ($this->getObject()) {
            $strategy->setObject($this->getObject());
        }

        return $strategy->hydrate($value);
    }
}
