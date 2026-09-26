<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Mapping;

/** Immutable path into external application data; it performs no data access. */
final readonly class ApplicationPath
{
    /** @param non-empty-list<ApplicationPathSegment> $segments */
    private function __construct(private array $segments)
    {
    }

    public static function parse(string $path): self
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Application path must not be empty.');
        }

        $segments = [];
        foreach (explode('.', $path) as $part) {
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_-]*)(\[\])?$/D', $part, $match) !== 1) {
                throw new \InvalidArgumentException(sprintf('Invalid application path segment "%s".', $part));
            }

            $segments[] = new ApplicationPathSegment(
                $match[1],
                ($match[2] ?? '') === ''
                    ? ApplicationPathSegment::VALUE
                    : ApplicationPathSegment::COLLECTION
            );
        }

        return new self($segments);
    }

    /** @return non-empty-list<ApplicationPathSegment> */
    public function segments(): array
    {
        return $this->segments;
    }

    public function canonical(): string
    {
        return implode('.', array_map(
            static fn (ApplicationPathSegment $segment): string => $segment->toString(),
            $this->segments
        ));
    }

    public function terminalKind(): string
    {
        return $this->segments[array_key_last($this->segments)]->kind();
    }

    public function hasCollections(): bool
    {
        foreach ($this->segments as $segment) {
            if ($segment->isCollection()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> Collection path prefixes, outermost first. */
    public function collectionPrefixes(): array
    {
        $prefixes = [];
        $parts = [];
        foreach ($this->segments as $segment) {
            $parts[] = $segment->toString();
            if ($segment->isCollection()) {
                $prefixes[] = implode('.', $parts);
            }
        }

        return $prefixes;
    }

    public function __toString(): string
    {
        return $this->canonical();
    }
}
