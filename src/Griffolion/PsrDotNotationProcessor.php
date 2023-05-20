<?php declare(strict_types=1);

namespace Griffolion\PsrDotNotationProcessor;
use Monolog\Processor\ProcessorInterface;
use Monolog\Utils;

class PsrDotNotationProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record): array
    {
        $matches = [];
        preg_match_all("/{(?<variable>\w+)}/", $record['context'], $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $record;
        }

        $variables = array_map(function(array $match){
            return $match['variable'];
        }, $matches);

        $replacements = [];

        foreach ($variables as $variable) {
            $replacements['{'.$variable.'}'] = $this->getNestedContextValue($record['context'], $variable);
        }

        $record['message'] = preg_replace(array_map('preg_quote', array_keys($replacements)), $replacements, $record['message']);

        return $record;
    }

    protected function getNestedContextValue(array $contextValue, string $variable): string
    {
        if ('' === $variable) {
            return $this->getLoggableValue($contextValue);
        }
        $variables = explode('.', $variable);
        $currentLevel = array_shift($variables);
        if (array_key_exists($currentLevel, $contextValue)) {
            return (is_array($contextValue)) ? $this->getNestedContextValue($contextValue[$currentLevel], implode('.', $variables)) : $this->getLoggableValue($contextValue[$currentLevel]);
        }
        return '<NOT_FOUND>';
    }

    protected function getLoggableValue(mixed $value): string
    {
        if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, "__toString"))) {
            return $value;
        } elseif ($value instanceof \DateTimeInterface) {
            if (!$this->dateFormat && $value instanceof \Monolog\DateTimeImmutable) {
                return (string) $value;
            } else {
                return $value->format($this->dateFormat ?: static::SIMPLE_DATE);
            }
        } elseif ($value instanceof \UnitEnum) {
            return $value instanceof \BackedEnum ? $value->value : $value->name;
        } elseif (is_object($value)) {
            return '[object '.Utils::getClass($value).']';
        } elseif (is_array($value)) {
            return 'array'.Utils::jsonEncode($value, null, true);
        } else {
            return '['.gettype($value).']';
        }
    }
}