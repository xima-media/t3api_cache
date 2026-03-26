<?php

namespace Xima\T3ApiCache\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiCacheRoundDatetime
{
    /**
     * Map of parameter names to rounding precision.
     * Allowed precision values: "minute", "hour", "day", "year"
     *
     * @var array<string, string>
     */
    protected array $parameters = [];

    /**
     * Rounding direction: "floor" (round down) or "ceil" (round up)
     */
    protected string $direction = 'floor';

    private const ALLOWED_PRECISIONS = ['minute', 'hour', 'day', 'year'];
    private const ALLOWED_DIRECTIONS = ['floor', 'ceil'];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['parameters'])) {
            foreach ($options['parameters'] as $parameterName => $precision) {
                if (!in_array($precision, self::ALLOWED_PRECISIONS, true)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Invalid precision "%s" for parameter "%s". Allowed values: %s',
                            $precision,
                            $parameterName,
                            implode(', ', self::ALLOWED_PRECISIONS)
                        )
                    );
                }
            }
            $this->parameters = $options['parameters'];
        }
        if (isset($options['direction'])) {
            if (!in_array($options['direction'], self::ALLOWED_DIRECTIONS, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid direction "%s". Allowed values: %s',
                        $options['direction'],
                        implode(', ', self::ALLOWED_DIRECTIONS)
                    )
                );
            }
            $this->direction = $options['direction'];
        }
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * Round a datetime string value according to the given precision and direction.
     *
     * Supports Unix timestamps and common datetime formats (ISO 8601, date-only, etc.).
     * Returns the rounded value in the same format as the input.
     */
    public static function roundDatetime(string $value, string $precision, string $direction = 'floor'): string
    {
        $isTimestamp = ctype_digit($value);

        if ($isTimestamp) {
            $dateTime = new \DateTimeImmutable('@' . $value);
        } else {
            try {
                $dateTime = new \DateTimeImmutable($value);
            } catch (\Exception | \Error) {
                return $value;
            }
        }

        $rounded = self::applyRounding($dateTime, $precision, $direction);

        if ($isTimestamp) {
            return (string)$rounded->getTimestamp();
        }

        // Detect date-only format (e.g. "2025-03-26")
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $rounded->format('Y-m-d');
        }

        return $rounded->format('c');
    }

    private static function applyRounding(\DateTimeImmutable $dateTime, string $precision, string $direction): \DateTimeImmutable
    {
        $isFloor = $direction === 'floor';

        return match ($precision) {
            'minute' => self::roundToMinute($dateTime, $isFloor),
            'hour' => self::roundToHour($dateTime, $isFloor),
            'day' => self::roundToDay($dateTime, $isFloor),
            'year' => self::roundToYear($dateTime, $isFloor),
            default => $dateTime,
        };
    }

    private static function roundToMinute(\DateTimeImmutable $dateTime, bool $isFloor): \DateTimeImmutable
    {
        $floored = $dateTime->setTime(
            (int)$dateTime->format('H'),
            (int)$dateTime->format('i'),
            0
        );

        if ($isFloor || $floored->getTimestamp() === $dateTime->getTimestamp()) {
            return $floored;
        }

        return $floored->modify('+1 minute');
    }

    private static function roundToHour(\DateTimeImmutable $dateTime, bool $isFloor): \DateTimeImmutable
    {
        $floored = $dateTime->setTime(
            (int)$dateTime->format('H'),
            0,
            0
        );

        if ($isFloor || $floored->getTimestamp() === $dateTime->getTimestamp()) {
            return $floored;
        }

        return $floored->modify('+1 hour');
    }

    private static function roundToDay(\DateTimeImmutable $dateTime, bool $isFloor): \DateTimeImmutable
    {
        $floored = $dateTime->setTime(0, 0, 0);

        if ($isFloor || $floored->getTimestamp() === $dateTime->getTimestamp()) {
            return $floored;
        }

        return $floored->modify('+1 day');
    }

    private static function roundToYear(\DateTimeImmutable $dateTime, bool $isFloor): \DateTimeImmutable
    {
        $floored = $dateTime->setDate(
            (int)$dateTime->format('Y'),
            1,
            1
        )->setTime(0, 0, 0);

        if ($isFloor || $floored->getTimestamp() === $dateTime->getTimestamp()) {
            return $floored;
        }

        return $floored->modify('+1 year');
    }
}
