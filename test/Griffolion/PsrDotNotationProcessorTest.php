<?php declare(strict_types=1);

namespace Griffolion\Test;

use Griffolion\PsrDotNotationProcessor;
use Monolog\Test\TestCase;
use Monolog\Logger;

class PsrDotNotationProcessorTest extends TestCase
{
    public function testJustRight(): void
    {
        $record = $this->getRecord(Logger::WARNING, '{test.test}', ['test' => ['test' => 'passing']]);
        $processor = new PsrDotNotationProcessor();
        $record = $processor($record);
        $this->assertEquals('passing', $record['message']);
    }

    public function testTooLongNotationResultsInNoOp(): void
    {
        $record = $this->getRecord(Logger::WARNING, '{test.test.test}', ['test' => ['test' => 'passing']]);
        $processor = new PsrDotNotationProcessor();
        $record = $processor($record);
        $this->assertEquals('{test.test.test}', $record['message']);
    }

    public function testShortNotationGivesAppropriateValue(): void
    {
        $record = $this->getRecord(Logger::WARNING, '{test}', ['test' => ['test' => 'passing']]);
        $processor = new PsrDotNotationProcessor();
        $record = $processor($record);
        $this->assertEquals('array{"test":"passing"}', $record['message']);
    }

    public function testNumericArrayKeysAreAcceptable(): void
    {
        $record = $this->getRecord(Logger::WARNING, '{test.0.test}', ['test' => [['test' => 'passing']]]);
        $processor = new PsrDotNotationProcessor();
        $record = $processor($record);
        $this->assertEquals('passing', $record['message']);
    }
}