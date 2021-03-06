<?php
namespace ShlinkioTest\Shlink\CLI\Command\Api;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class ListKeysCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $apiKeyService;

    public function setUp()
    {
        $this->apiKeyService = $this->prophesize(ApiKeyService::class);
        $command = new ListKeysCommand($this->apiKeyService->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function ifEnabledOnlyIsNotProvidedEverythingIsListed()
    {
        $this->apiKeyService->listKeys(false)->willReturn([
            new ApiKey(),
            new ApiKey(),
            new ApiKey(),
        ])->shouldBeCalledTimes(1);
        $this->commandTester->execute([
            'command' => 'api-key:list',
        ]);
    }

    /**
     * @test
     */
    public function ifEnabledOnlyIsProvidedOnlyThoseKeysAreListed()
    {
        $this->apiKeyService->listKeys(true)->willReturn([
            new ApiKey(),
            new ApiKey(),
        ])->shouldBeCalledTimes(1);
        $this->commandTester->execute([
            'command' => 'api-key:list',
            '--enabledOnly' => true,
        ]);
    }
}
