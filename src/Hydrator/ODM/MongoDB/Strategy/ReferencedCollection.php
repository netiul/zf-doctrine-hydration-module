<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

/**
 *
 * Class PersistentCollection
 */
class ReferencedCollection extends AbstractMongoStrategy
{
    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        $strategy = new ReferencedField($this->getObjectManager());
        $strategy->setClassMetadata($this->getClassMetadata());
        $strategy->setCollectionName($this->getCollectionName());

        $result = [];
        if ($value) {
            foreach ($value as $key => $record) {
                $strategy->setObject($record);
                $result[$key] = $strategy->extract($record);
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

        $result = [];
        if ($value) {
            foreach ($value as $documentId) {
                $result[] = $this->hydrateSingle($targetDocument, $documentId);
            }
        }

        return $this->hydrateCollection($result);
    }

    /**
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

        return $this->findTargetDocument($targetDocument, $document);
    }
}
