<?php

declare(strict_types=1);

namespace Phpro\DoctrineHydrationModule\Hydrator\ODM\MongoDB\Strategy;

use Laminas\Hydrator\Strategy\StrategyInterface;

/**
 * Class DateTimeField.
 */
class DateTimeField implements StrategyInterface
{
    /**
     * @var bool
     */
    protected $isTimestamp;

    /**
     * @param $isTimestamp
     */
    public function __construct($isTimestamp = false)
    {
        $this->isTimestamp = $isTimestamp;
    }

    /**
     * {@inheritDoc}
     */
    public function extract($value, ?object $object = null)
    {
        if (!($value instanceof \DateTime)) {
            return $value;
        }

        return $value->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function hydrate($value, $data)
    {
        $datetime = $this->convertToDateTime($value);
        if (!$datetime) {
            return null;
        }

        if ($this->isTimestamp) {
            return $datetime->getTimestamp();
        }

        return $datetime;
    }

    /**
     * Convert any value to date time.
     *
     * @param $value
     *
     * @return \DateTime|null
     */
    protected function convertToDateTime($value)
    {
        if ($value instanceof \DateTime) {
            return clone $value;
        }

        if ($value instanceof \MongoDate) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($value->sec);

            return $datetime;
        }

        if (is_numeric($value)) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($value);

            return $datetime;
        }

        if (is_string($value) && !empty($value)) {
            $datetime = new \DateTime($value);

            return $datetime;
        }

        return;
    }
}
