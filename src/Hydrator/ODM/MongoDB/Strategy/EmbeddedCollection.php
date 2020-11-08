<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Instantiator\Instantiator;

/**
 * Class PersistentCollection.
 */
class EmbeddedCollection extends AbstractMongoStrategy
{
    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        // Embedded Many
        if (!($value instanceof Collection)) {
            throw new \Exception('Embedded collections should be a doctrine collection.');
        }

        $mapping = $this->getClassMetadata()->fieldMappings[$this->getCollectionName()];
        $result = [];
        if ($value) {
            foreach ($value as $index => $object) {
                $hydrator = $this->getDoctrineHydrator();
                $result[$index] = $hydrator->extract($object);

                // Add discrimator field if it can be found.
                if (isset($mapping['discriminatorMap'])) {
                    $discriminatorName = array_search(get_class($object), $mapping['discriminatorMap']);
                    if ($discriminatorName) {
                        $result[$index][$mapping['discriminatorField']] = $discriminatorName;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, $data)
    {
        $mapping = $this->metadata->fieldMappings[$this->collectionName];
        $targetDocument = $mapping['targetDocument'];
        $discriminator = isset($mapping ['discriminatorField']) ? $mapping ['discriminatorField'] : false;
        $discriminatorMap = isset($mapping['discriminatorMap']) ? $mapping['discriminatorMap'] : [];

        $result = [];
        if ($value) {
            foreach ($value as $key => $data) {
                // Use configured discriminator as discriminator class:
                if ($discriminator && is_array($data)) {
                    if (isset($data[$discriminator]) && isset($discriminatorMap[$data[$discriminator]])) {
                        $targetDocument = $discriminatorMap[$data[$discriminator]];
                    }
                }

                $result[$key] = $this->hydrateSingle($targetDocument, $data);
            }
        }

        return $this->hydrateCollection($result);
    }

    /**
     * Note: do not use EmbeddedField strategy. Discriminators will not work.
     *
     * @param $targetDocument
     * @param $document
     *
     * @return object
     */
    protected function hydrateSingle($targetDocument, $document)
    {
        if (is_object($document)) {
            return $document;
        }

        $instantiator = new Instantiator();
        $object = $instantiator->instantiate($targetDocument);

        $hydrator = $this->getDoctrineHydrator();
        $hydrator->hydrate($document, $object);

        return $object;
    }
}
