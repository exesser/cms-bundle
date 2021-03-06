<?php declare(strict_types=1);

namespace Test\CmsBundle\Api\ListDynamic;

use Test\CmsBundle\ApiTester;
use ExEss\Bundle\CmsBundle\Entity\User;
use ExEss\Bundle\CmsBundle\Component\Flow\Action\Command;

class ExportListToCsvCest
{
    private const INTERNAL_LIST_NAME = 'Users';
    private const EXTERNAL_LIST_NAME = 'Combined_Users';

    /**
     * @var array
     */
    private array $users = [];

    public function _before(ApiTester $I): void
    {
        // create some accounts
        for ($i = 1; $i <= 5; $i++) {
            $this->users[] = $I->generateUser('User_' . $i, [
                'date_entered' => '2017-01-05 00:00:00',
            ]);
        }

        $listId = $I->generateDynamicList([
            'name' => self::INTERNAL_LIST_NAME,
            'items_per_page' => 10,
            'base_object' => User::class,
        ]);

        $I->generateDynamicList([
            'name' => self::EXTERNAL_LIST_NAME,
            'combined' => 1,
            'external_object_id' => $externalObjectId = $I->generateListExternalObject(),
        ]);
        $I->generateExternalLinkField([
            'list_id' => $listId,
            'external_object_id' => $externalObjectId,
        ]);
        $I->generateExternalLinkField([
            'list_id' => $listId,
            'external_object_id' => $externalObjectId,
        ]);
    }

    public function exportDefaultList(ApiTester $I): void
    {
        // Given
        $I->getAnApiTokenFor('adminUser');

        // When
        $I->sendPOST('/Api/list/' . self::INTERNAL_LIST_NAME . '/export/csv', ['recordIds' => $this->users]);

        // Then
        $I->seeResponseIsDwpResponse(200);
        $I->seeAssertPathsInJson([
            '$.data.command' => Command::COMMAND_TYPE_OPEN_LINK,
        ]);
    }

    public function exportCombinedList(ApiTester $I): void
    {
        // Given
        $I->getAnApiTokenFor('adminUser');

        // When
        $I->sendPOST('/Api/list/' . self::EXTERNAL_LIST_NAME . '/export/csv');

        // Then
        $I->seeResponseIsDwpResponse(200);
        $I->seeAssertPathsInJson([
            '$.data.command' => Command::COMMAND_TYPE_OPEN_LINK,
        ]);
    }
}
