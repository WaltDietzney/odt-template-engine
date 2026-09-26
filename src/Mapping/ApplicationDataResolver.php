<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Resolves ApplicationPath against canonical nested PHP-array data without mutation. */
final class ApplicationDataResolver
{
    /**
     * Resolve a path against the root record. Terminal values are returned without
     * imposing payload/type semantics; arrays are traversed only as records or lists
     * where the path explicitly requires that shape.
     *
     * @param array<mixed> $data Canonical application data tree.
     */
    public function resolve(ApplicationPath $path, array $data): ApplicationDataResolution
    {
        return $this->resolveFromRecord($data, $path->segments(), 0, null);
    }

    /**
     * @param array<mixed> $record
     * @param non-empty-list<ApplicationPathSegment> $segments
     */
    private function resolveFromRecord(
        array $record,
        array $segments,
        int $offset,
        ?int $itemIndex
    ): ApplicationDataResolution {
        if (!$this->isRecord($record)) {
            return new ApplicationDataResolution(
                ApplicationDataResolution::WRONG_SHAPE,
                $record,
                $itemIndex
            );
        }

        $segment = $segments[$offset];
        if (!array_key_exists($segment->name(), $record)) {
            return new ApplicationDataResolution(ApplicationDataResolution::MISSING, null, $itemIndex);
        }

        $value = $record[$segment->name()];
        if ($value === null) {
            return new ApplicationDataResolution(ApplicationDataResolution::NULL, null, $itemIndex);
        }

        $isTerminal = $offset === array_key_last($segments);
        if (!$segment->isCollection()) {
            if ($isTerminal) {
                return new ApplicationDataResolution(ApplicationDataResolution::PRESENT, $value, $itemIndex);
            }

            if (!is_array($value) || !$this->isRecord($value)) {
                return new ApplicationDataResolution(ApplicationDataResolution::WRONG_SHAPE, $value, $itemIndex);
            }

            return $this->resolveFromRecord($value, $segments, $offset + 1, $itemIndex);
        }

        if (!is_array($value) || !array_is_list($value)) {
            return new ApplicationDataResolution(ApplicationDataResolution::WRONG_SHAPE, $value, $itemIndex);
        }

        if ($value === []) {
            return new ApplicationDataResolution(ApplicationDataResolution::EMPTY_COLLECTION, [], $itemIndex);
        }

        $items = [];
        foreach ($value as $index => $item) {
            if ($isTerminal) {
                // A terminal collection accepts any item shape, including null and scalars.
                $items[] = new ApplicationDataResolution(
                    ApplicationDataResolution::PRESENT,
                    $item,
                    $index
                );
                continue;
            }

            if (!is_array($item) || !$this->isRecord($item)) {
                $items[] = new ApplicationDataResolution(
                    ApplicationDataResolution::WRONG_SHAPE,
                    $item,
                    $index
                );
                continue;
            }

            $items[] = $this->resolveFromRecord($item, $segments, $offset + 1, $index);
        }

        $status = $this->containsWrongShape($items)
            ? ApplicationDataResolution::WRONG_SHAPE
            : ApplicationDataResolution::PRESENT;

        return new ApplicationDataResolution($status, $value, $itemIndex, $items);
    }

    /** @param list<ApplicationDataResolution> $items */
    private function containsWrongShape(array $items): bool
    {
        foreach ($items as $item) {
            if ($item->status() === ApplicationDataResolution::WRONG_SHAPE) {
                return true;
            }
        }

        return false;
    }

    /** Empty arrays are valid empty records when the path is in a record context. */
    private function isRecord(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }
}
