<?php

declare(strict_types=1);

namespace OdtTemplateEngine\Template;

/**
 * Parsed representation of the existing classic condition grammar.
 *
 * Parsing/evaluation intentionally preserves TemplateProcessor runtime semantics.
 */
final readonly class ConditionExpression
{
    public function __construct(
        private string $source,
        private string $referenceName,
        private ?string $operator = null,
        private ?string $literal = null
    ) {
    }

    public static function parse(string $expression): self
    {
        if (preg_match('/^(\w+)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', $expression, $match)) {
            return new self(
                $expression,
                $match[1],
                $match[2],
                trim($match[3], '"\'')
            );
        }

        return new self($expression, $expression);
    }

    public function source(): string
    {
        return $this->source;
    }

    public function referenceName(): string
    {
        return $this->referenceName;
    }

    public function operator(): ?string
    {
        return $this->operator;
    }

    public function literal(): ?string
    {
        return $this->literal;
    }

    /** @param array<string, mixed> $values */
    public function evaluate(array $values)
    {
        if ($this->operator !== null) {
            $left = $values[$this->referenceName] ?? null;
            $right = $this->literal;

            if (is_numeric($left) && is_numeric($right)) {
                $left = (float) $left;
                $right = (float) $right;
            }

            return match ($this->operator) {
                '==' => $left == $right,
                '!=' => $left != $right,
                '>' => $left > $right,
                '<' => $left < $right,
                '>=' => $left >= $right,
                '<=' => $left <= $right,
            };
        }

        $value = $values[$this->referenceName] ?? false;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
