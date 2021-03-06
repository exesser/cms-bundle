<?php declare(strict_types=1);

namespace Test\CmsBundle\Helper\Testcase;

if (!\class_exists('\Test\CmsBundle\FunctionalTester')) {
    throw new \Exception('The FunctionalTester class should exist (check the tests/_support folder)');
}

use Codeception\TestCase\Test;
use ExEss\Bundle\CmsBundle\Helper\DataCleaner;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @method markTestSkipped(string $message)
 */
class FunctionalTestCase extends Test
{
    use MockeryPHPUnitIntegration;

    protected \Test\CmsBundle\FunctionalTester $tester;

    /**
     * @return array
     */
    public function getResources(string $location, string $name): array
    {
        $data = [];

        $testFiles = \glob($location . $name . '*.json');
        if (!empty($testFiles)) {
            foreach ($testFiles as $file) {
                $testValues = DataCleaner::jsonDecode(\file_get_contents($file), false);
                \preg_match('/' . $name . '([^\/]*?).json/', $file, $match);
                $key = $match[1] . (!empty($testValues->_description) ? ' - ' . $testValues->_description : '');
                $data[$key] = [$testValues];
            }
        }

        return $data;
    }
}
