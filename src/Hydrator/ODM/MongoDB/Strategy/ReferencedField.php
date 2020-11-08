<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

/**
 * Class PersistentCollection.
 */
class ReferencedField extends AbstractMongoStrategy
{
    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        if (!is_object($value)) {
            return $value;
        }

        $idField = $this->metadata->getIdentifier();
        $idField = is_array($idField) ? current($idField) : $idField;
        $getter = 'get' . ucfirst($idField);

        // Validate object:
        $rc = new \ReflectionClass($value);
        if (!$rc->hasMethod($getter)) {
            return $value;
        }

        return $value->$getter();
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, $data)
    {
        if (is_object($value)) {
            return $value;
        }

        $mapping = $this->metadata->fieldMappings[$this->collectionName];
        $targetDocument = $mapping['targetDocument'];

        return $this->findTargetDocument($targetDocument, $value);
    }
}
