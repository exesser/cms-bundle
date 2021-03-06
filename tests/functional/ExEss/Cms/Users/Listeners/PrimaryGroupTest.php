<?php declare(strict_types=1);

namespace Test\CmsBundle\Functional\ExEss\Cms\Users\Listeners;

use Test\CmsBundle\Helper\Testcase\FunctionalTestCase;
use ExEss\Bundle\CmsBundle\Entity\User;
use ExEss\Bundle\CmsBundle\Entity\SecurityGroupUser;

/**
 * @see PrimaryGroupListener
 */
class PrimaryGroupTest extends FunctionalTestCase
{
    public function testEnsuresPrimaryGroup(): void
    {
        // setup
        $userId = $this->tester->generateUser('tester');
        $groupId1 = $this->tester->generateSecurityGroup('group 1');
        $groupId2 = $this->tester->generateSecurityGroup('group 2');

        $userGroupId = $this->tester->linkUserToSecurityGroup($userId, $groupId1, ['primary_group' => 1]);
        $this->tester->linkUserToSecurityGroup($userId, $groupId2, ['primary_group' => 0]);

        $user = $this->tester->grabEntityFromRepository(User::class, ['id' => $userId]);
        $primaryGroup = $this->tester->grabEntityFromRepository(SecurityGroupUser::class, ['id' => $userGroupId]);

        // run test
        $user->removeUserGroup($primaryGroup);
        $this->tester->grabService('doctrine.orm.entity_manager')->persist($user);
        $this->tester->grabService('doctrine.orm.entity_manager')->flush();

        // assertions
        $this->tester->assertEquals(
            $groupId2,
            $user->getPrimaryGroup()->getId()
        );
    }
}
