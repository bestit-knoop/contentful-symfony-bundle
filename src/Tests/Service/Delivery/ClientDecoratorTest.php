<?php

namespace BestIt\ContentfulBundle\Tests\Service\Delivery;

use BestIt\ContentfulBundle\ClientEvents;
use BestIt\ContentfulBundle\Delivery\ResponseParserInterface;
use BestIt\ContentfulBundle\Service\Delivery\ClientDecorator;
use Contentful\Delivery\Client;
use Contentful\Delivery\ContentType;
use Contentful\Delivery\ContentTypeField;
use Contentful\Delivery\DynamicEntry;
use Contentful\Delivery\Query;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Cache\Adapter\DoctrineAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests the service for cache resetting.
 * @author lange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\ContentfulBundle
 * @subpackage Service\Delivery
 * @version $id$
 */
class ClientDecoratorTest extends TestCase
{
    /**
     * The mocked client.
     * @var Client|PHPUnit_Framework_MockObject_MockObject|null
     */
    private $client;

    /**
     * The mocked event dispatcher.
     * @var EventDispatcherInterface|PHPUnit_Framework_MockObject_MockObject|null
     */
    private $eventDispatcher;

    /**
     * The tested class.
     * @var ClientDecorator|null
     */
    private $fixture;

    /**
     * The used parser.
     * @var ResponseParserInterface|PHPUnit_Framework_MockObject_MockObject|null
     */
    private $parser;

    /**
     * Returns an injection parser to test and its response.
     * @return array
     */
    public function getParserToFetch()
    {
        $mockParser = $this->createMock(ResponseParserInterface::class);

        $mockParser
            ->expects($this->once())
            ->method('toArray')
            ->with($mockEntry = $this->createMock(DynamicEntry::class))
            ->willReturn($result = [uniqid()]);

        return [
            [null],
            [$mockParser, $result]
        ];
    }

    /**
     * Sets up the test.
     * @return void
     */
    protected function setUp()
    {
        $this->fixture = new ClientDecorator(
            $this->client = $this->createMock(Client::class),
            new DoctrineAdapter(new ArrayCache()),
            $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $this->parser = $this->createMock(ResponseParserInterface::class)
        );
    }

    /**
     * Checks if the magical getter calls the base client.
     * @return void
     */
    public function testCallSuccess()
    {
        $this->client
            ->expects($this->once())
            ->method('getAsset')
            ->with($id = uniqid())
            ->willReturn($return = uniqid());

        static::assertSame($return, $this->fixture->getAsset($id));
    }

    /**
     * Checks if the entry getter is cached and its response simplified.
     * @param ResponseParserInterface $parser
     * @return void
     */
    public function testGetEntryFull(ResponseParserInterface $parser = null)
    {
        if (!$parser) {
            $this->parser
                ->expects($this->once())
                ->method('toArray')
                ->with($mockEntry = $this->createMock(DynamicEntry::class))
                ->willReturn($result = [uniqid()]);
        }

        $this->client
            ->expects($this->once())
            ->method('getEntry')
            ->with($id = uniqid())
            ->willReturn($mockEntry);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ClientEvents::LOAD_CONTENTFUL_ENTRY, $this->isInstanceOf(GenericEvent::class));

        static::assertSame($result, $this->fixture->getEntry($id), 'The first response was not correct.');
        static::assertSame($result, $this->fixture->getEntry($id), 'The second response should be cached and the same.');
    }

    /**
     * Checks if the entries getter is cached and its response simplified.
     * @param ResponseParserInterface $parser
     * @param array $result
     * @return void
     */
    public function testGetEntriesFullWithCache(ResponseParserInterface $parser = null)
    {
        if (!$parser) {
            $this->parser
                ->expects($this->once())
                ->method('toArray')
                ->with([$mockEntry = $this->createMock(DynamicEntry::class)])
                ->willReturn($result = [uniqid()]);
        }

        $this->client
            ->expects($this->once())
            ->method('getEntries')
            ->willReturn([$mockEntry]);

        $callback = function ($query) {
            static::assertInstanceOf(Query::class, $query);
        };

        $this->eventDispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with(ClientEvents::LOAD_CONTENTFUL_ENTRIES, $this->isInstanceOf(GenericEvent::class));

        $this->eventDispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(ClientEvents::LOAD_CONTENTFUL_ENTRY, $this->isInstanceOf(GenericEvent::class));

        static::assertSame(
            $result,
            $this->fixture->getEntries($callback, $cacheId = uniqid(), $parser),
            'The first response was not correct.'
        );

        static::assertSame(
            $result,
            $this->fixture->getEntries($callback, $cacheId),
            'The second response should be cached and the same.'
        );
    }

    /**
     * Checks if the entries getter is not cached and its response simplified.
     * @param ResponseParserInterface $parser
     * @return void
     */
    public function testGetEntriesFullWithoutCache(ResponseParserInterface $parser = null)
    {
        if (!$parser) {
            $this->parser
                ->expects($this->exactly(2))
                ->method('toArray')
                ->with([$mockEntry = $this->createMock(DynamicEntry::class)])
                ->willReturn($result = [uniqid()]);
        }

        $this->client
            ->expects($this->exactly(2))
            ->method('getEntries')
            ->willReturn([$mockEntry]);

        $callback = function ($query) {
            static::assertInstanceOf(Query::class, $query);
        };

        $this->eventDispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with(ClientEvents::LOAD_CONTENTFUL_ENTRIES, $this->isInstanceOf(GenericEvent::class));

        $this->eventDispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(ClientEvents::LOAD_CONTENTFUL_ENTRY, $this->isInstanceOf(GenericEvent::class));

        static::assertSame(
            $result,
            $this->fixture->getEntries($callback),
            'The first response was not correct.'
        );

        static::assertSame(
            $result,
            $this->fixture->getEntries($callback),
            'The second response should be cached and the same.'
        );
    }

    /**
     * Checks the simplification of the response.
     * @return void
     */
    public function testSimplifyResponse()
    {
        $this->markTestIncomplete('Still needed to check.');

        $cache = new DoctrineAdapter(new ArrayCache());
        $client = $this->createMock(Client::class);

        $this->fixture = new class($client, $cache) extends ClientDecorator
        {
            /**
             * Magical getter to make the protecteds public.
             * @param string $method
             * @param array $args
             * @return mixed
             */
            public function __call(string $method, array $args = [])
            {
                return $this->$method(...$args);
            }
        };

        $deep = [
            $rootEntry = $this->createMock(DynamicEntry::class)
        ];

        $rootEntry
            ->expects($this->once())
            ->method('getContentType')
            ->willReturn(new ContentType(
                uniqid(),
                uniqid(),
                [
                    $simpleField = new ContentTypeField('desc', 'desc', 'text'),
                    $imageField = new ContentTypeField()
                ]
            ));

        static::assertSame(
            [],
            $this->fixture->simplifyResponse($deep)
        );
    }
}
