<?php declare(strict_types=1);

namespace Griffolion;
use Monolog\Processor\ProcessorInterface;
use Monolog\Utils;

class PsrDotNotationProcessor implements ProcessorInterface
{
    public const DEFAULT_DATE_FORMAT = "Y-m-d\TH:i:s.uP";

    /** @var ?string */
    private $userDateFormat = null;

    public function __construct(?string $userDateFormat = null)
    {
        $this->userDateFormat = $userDateFormat;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record): array
    {
        $matches = [];
        preg_match_all("/{(?<variable>[\w\.]+)}/", $record['message'], $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $record;
        }

        $variables = array_map(function(array $match){
            return $match['variable'];
        }, $matches);

        $replacements = [];

        foreach ($variables as $variable) {
            if (null !== $replacementValue = $this->getNestedContextValue($record['context'], $variable)) {
                $replacements['{'.$variable.'}'] = $replacementValue;
            }
        }

        $record['message'] = preg_replace(
            array_map(
                function($quote) {
                    return "/$quote/";
                }, array_map('preg_quote', array_keys($replacements))), 
                $replacements, 
                $record['message']
            );

        return $record;
    }

    protected function getNestedContextValue($contextValue, string $variable): ?string
    {
        if ('' === $variable) {
            return $this->getLoggableValue($contextValue);
        }
        $variables = explode('.', $variable);
        $currentLevel = array_shift($variables);
        if (is_array($contextValue) && array_key_exists($currentLevel, $contextValue)) {
            return $this->getNestedContextValue($contextValue[$currentLevel], implode('.', $variables));
        }
        return null;
    }

    protected function getLoggableValue(mixed $value): string
    {
        if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, "__toString")) || is_string($value)) {
            return $value;
        } elseif ($value instanceof \DateTimeInterface) {
            if (!$this->userDateFormat && $value instanceof \Monolog\DateTimeImmutable) {
                return (string) $value;
            } else {
                return $value->format($this->userDateFormat ?: static::DEFAULT_DATE_FORMAT);
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