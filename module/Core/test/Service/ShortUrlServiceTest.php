<?php
namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Service\ShortUrlService;

class ShortUrlServiceTest extends TestCase
{
    /**
     * @var ShortUrlService
     */
    protected $service;
    /**
     * @var ObjectProphecy|EntityManagerInterface
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->em->persist(Argument::any())->willReturn(null);
        $this->em->flush()->willReturn(null);
        $this->service = new ShortUrlService($this->em->reveal());
    }

    /**
     * @test
     */
    public function listedUrlsAreReturnedFromEntityManager()
    {
        $list = [
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
        ];

        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findList(Argument::cetera())->willReturn($list)->shouldBeCalledTimes(1);
        $repo->countList(Argument::cetera())->willReturn(count($list))->shouldBeCalledTimes(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $list = $this->service->listShortUrls();
        $this->assertEquals(4, $list->getCurrentItemCount());
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\InvalidShortCodeException
     */
    public function exceptionIsThrownWhenSettingTagsOnInvalidShortcode()
    {
        $shortCode = 'abc123';
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(null)
                                                     ->shouldBeCalledTimes(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->service->setTagsByShortCode($shortCode);
    }

    /**
     * @test
     */
    public function providedTagsAreGetFromRepoAndSetToTheShortUrl()
    {
        $shortUrl = $this->prophesize(ShortUrl::class);
        $shortUrl->setTags(Argument::any())->shouldBeCalledTimes(1);
        $shortCode = 'abc123';
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn($shortUrl->reveal())
                                                     ->shouldBeCalledTimes(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $tagRepo = $this->prophesize(EntityRepository::class);
        $tagRepo->findOneBy(['name' => 'foo'])->willReturn(new Tag())->shouldbeCalledTimes(1);
        $tagRepo->findOneBy(['name' => 'bar'])->willReturn(null)->shouldbeCalledTimes(1);
        $this->em->getRepository(Tag::class)->willReturn($tagRepo->reveal());

        $this->service->setTagsByShortCode($shortCode, ['foo', 'bar']);
    }
}
