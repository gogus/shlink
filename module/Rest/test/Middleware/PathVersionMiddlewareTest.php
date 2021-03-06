<?php
namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Rest\Middleware\PathVersionMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class PathVersionMiddlewareTest extends TestCase
{
    /**
     * @var PathVersionMiddleware
     */
    protected $middleware;

    public function setUp()
    {
        $this->middleware = new PathVersionMiddleware();
    }

    /**
     * @test
     */
    public function whenVersionIsProvidedRequestRemainsUnchanged()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/v2/foo'));
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function ($req) use ($request, $test) {
            $test->assertSame($request, $req);
        });
    }

    /**
     * @test
     */
    public function versionOneIsPrependedWhenNoVersionIsDefined()
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/bar/baz'));
        $test = $this;
        $this->middleware->__invoke($request, new Response(), function (Request $req) use ($request, $test) {
            $test->assertNotSame($request, $req);
            $this->assertEquals('/v1/bar/baz', $req->getUri()->getPath());
        });
    }
}
