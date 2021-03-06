<?php declare(strict_types=1);

namespace Test\CmsBundle\Functional\ExEss\Cms\Component\Flow\Event\Listeners;

use ExEss\Bundle\CmsBundle\Entity\Flow;
use ExEss\Bundle\CmsBundle\Component\Flow\Event\FlowEvent;
use ExEss\Bundle\CmsBundle\Component\Flow\Event\Listeners\PreValidationSubscriber;
use ExEss\Bundle\CmsBundle\Component\Flow\Request\FlowAction;
use ExEss\Bundle\CmsBundle\Component\Flow\Response\Model;
use ExEss\Bundle\CmsBundle\Component\Flow\Suggestions\OverrideDefaultHandler;
use Test\CmsBundle\Helper\Testcase\FunctionalTestCase;
use Mockery;
use Test\CmsBundle\Functional\ExEss\Cms\Service\GridServiceTest;

class PreValidationSubscriberTest extends FunctionalTestCase
{
    private PreValidationSubscriber $subscriber;

    /**
     * @var OverrideDefaultHandler|Mockery\Mock
     */
    private $overrideDefaultHandler;

    public function _before(): void
    {
        $this->overrideDefaultHandler = Mockery::mock(OverrideDefaultHandler::class);
        $this->tester->mockService(OverrideDefaultHandler::class, $this->overrideDefaultHandler);

        $this->subscriber = $this->tester->grabService(PreValidationSubscriber::class);
        $this->tester->generateUser("user 1", ["id" => "123"]);
        $this->tester->generateUser("user 2", ["id" => "abc"]);

        $this->tester->loadJsonFixturesFrom(__DIR__ . '/resources/repeatable.fixtures.json');
    }

    public function testOnModel(): void
    {
        // setup
        $event = new FlowEvent(
            'flow-key',
            new FlowAction(['event' => FlowAction::EVENT_INIT]),
            new Model()
        );
        $event->setFlow(new Flow());

        // assert
        $this->overrideDefaultHandler->shouldReceive('shouldHandle')->once();

        // run test
        $this->subscriber->runSuggestionsOnModel($event);
    }

    public function nestedModelDataProvider(): array
    {
        $parentLineStatus = 'PRICED';

        $emptyModel = new Model();

        $expectedOnEmpty = [
            'packageProduct' => [],
        ];

        $modelWithOneChild = new Model([
            'packageProduct' => [
                'product-id-1' => [
                    'up_start_date_c' => '2017-08-09',
                    'line_status_c' => 'NOT PRICED',
                ],
            ],
            'packageProduct|addresses_aos_products_quotes|is_main' => true,
            'packageProduct|advance_base_c' => 'EAV',
            'packageProduct|line_status_c' => $parentLineStatus,
            GridServiceTest::FIELD_NAME => [
                'product-id-1',
            ],
        ]);

        $expectedWithOneChild = [
            'packageProduct' => [
                'product-id-1' => [
                    'addresses_aos_products_quotes|is_main' => true,
                    'advance_base_c' => 'EAV',
                    'line_status_c' => $parentLineStatus,
                    'up_start_date_c' => '2017-08-09',
                ],
            ],
            'packageProduct|addresses_aos_products_quotes|is_main' => true,
            'packageProduct|advance_base_c' => 'EAV',
            'packageProduct|line_status_c' => $parentLineStatus,
            GridServiceTest::FIELD_NAME => [
                'product-id-1',
            ],
        ];

        $modelWithNoneSelected = clone $modelWithOneChild;
        unset($modelWithNoneSelected->{GridServiceTest::FIELD_NAME});

        $expectedWithNoneSelected = [
            'packageProduct' => [],
            'packageProduct|addresses_aos_products_quotes|is_main' => true,
            'packageProduct|advance_base_c' => 'EAV',
            'packageProduct|line_status_c' => $parentLineStatus,
        ];

        $modelWithTwoChildren = new Model([
            'packageProduct' => [
                'product-id-1' => [
                    'up_start_date_c' => '2017-08-09',
                    'line_status_c' => 'NOT PRICED',
                ],
                'product-id-2' => [
                    'up_start_date_c' => '2018-08-09',
                    'line_status_c' => 'NOT PRICED EITHER',
                ],
                'product-id-3' => [
                    'up_start_date_c' => '2019-08-09',
                    'line_status_c' => 'NOT PRICED AT ALL',
                ],
            ],
            'packageProduct|addresses_aos_products_quotes|is_main' => true,
            'packageProduct|advance_base_c' => 'EAV',
            'packageProduct|line_status_c' => $parentLineStatus,
            GridServiceTest::FIELD_NAME => [
                'product-id-1',
                'product-id-2',
            ],
        ]);

        $expectedWithTwoChildren = [
            'packageProduct' => [
                'product-id-1' => [
                    'addresses_aos_products_quotes|is_main' => true,
                    'advance_base_c' => 'EAV',
                    'line_status_c' => $parentLineStatus,
                    'up_start_date_c' => '2017-08-09',
                ],
                'product-id-2' => [
                    'addresses_aos_products_quotes|is_main' => true,
                    'advance_base_c' => 'EAV',
                    'line_status_c' => $parentLineStatus,
                    'up_start_date_c' => '2018-08-09',
                ],
            ],
            'packageProduct|addresses_aos_products_quotes|is_main' => true,
            'packageProduct|advance_base_c' => 'EAV',
            'packageProduct|line_status_c' => $parentLineStatus,
            GridServiceTest::FIELD_NAME => [
                'product-id-1',
                'product-id-2',
            ],
        ];

        return [
            'adds the key holding children' =>
                [$emptyModel, 1, $expectedOnEmpty],
            'copies data from parent ro child and enriches child with parent model excluding itself' =>
                [$modelWithOneChild, 2, $expectedWithOneChild],
            'cleans out non selected children' =>
                [$modelWithNoneSelected, 1, $expectedWithNoneSelected],
            'works with multiple children' =>
                [$modelWithTwoChildren, 3, $expectedWithTwoChildren],
        ];
    }

    /**
     * @dataProvider nestedModelDataProvider
     */
    public function testOnNestedModel(Model $model, int $expectedRuns, array $expectedModel): void
    {
        $this->overrideDefaultHandler->shouldReceive('shouldHandle')->times($expectedRuns);
        $flow = $this->tester->grabEntityFromRepository(Flow::class, ['key' => 'my-flow-key']);

        $event = new FlowEvent(
            'does-not-matter',
            new FlowAction(['event' => FlowAction::EVENT_CONFIRM]),
            $model
        );
        $event->getResponse()->setModel(new Model($model->toArray()));
        $event->setFlow($flow);

        // run test
        $this->subscriber->runSuggestionsOnParentAndChildModels($event);

        // asserts
        $this->tester->assertEquals($expectedModel, $event->getResponse()->getModel()->toArray());
    }
}
