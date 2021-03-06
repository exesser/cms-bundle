<?php declare(strict_types=1);

namespace Test\CmsBundle\Unit\ExEss\Cms\Component\Logger\Processor;

use ExEss\Bundle\CmsBundle\Component\Logger\Processor\EnvironmentProcessor;
use Test\CmsBundle\Helper\Testcase\UnitTestCase;

class EnvironmentProcessorTest extends UnitTestCase
{
    /**
     * @dataProvider getValues
     */
    public function testInvoke(?string $tag, array $expectedRecord): void
    {
        // Given
        $processor = new EnvironmentProcessor($tag);

        // When
        $record = $processor([]);

        // Then
        $this->tester->assertEquals($expectedRecord, $record);
    }

    public function getValues(): array
    {
        return [
            'tag exists' => [
                'tag' => $tag = 'tag1',
                'expectedRecord' => [
                    'extra' => [
                        'tag' => $tag,
                    ],
                ],
            ],
            'tag is null' => [
                'tag' => null,
                'expectedRecord' => [],
            ],
        ];
    }
}
