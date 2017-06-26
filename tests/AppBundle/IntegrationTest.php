<?php

namespace AppBundle\Tests;

use AppBundle\Entities\Comment;
use AppBundle\Entities\Post;
use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\EmailAddress;
use AppBundle\Entities\ValueObjects\PostAuthor;
use AppBundle\Entities\ValueObjects\PostContent;
use AppBundle\Entities\ValueObjects\PostTitle;
use AppBundle\Support\Doctrine\Types\DateTimeType;
use AppBundle\Support\EntityAccessor;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * Class IntegrationTest
 *
 * @package    AppBundle\Tests
 * @subpackage AppBundle\Tests\IntegrationTest
 */
class IntegrationTest extends TestCase
{

    /**
     * @group database
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCanPersistPost()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );

        $this->em->persist($entity);
        $this->em->flush();

        /** @var Post $result */
        $result = $this->em->getRepository(Post::class)->findOneBySlug('test-post');

        $this->assertInstanceOf(Post::class, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($result, 'createdAt'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($result,'updatedAt'));
    }

    /**
     * @group database
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCanPersistPostAndComments()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->leaveComment(new Commenter('Bob', new EmailAddress('bob@example.com')), 'These are some comments.');
        $entity->leaveComment(new Commenter('Bob Marley', new EmailAddress('bob@example.com')), 'These are some more comments.');
        $entity->leaveComment(new Commenter('Bob Bar', new EmailAddress('bob@example.com')), 'These are some other comments.');
        $entity->leaveComment(new Commenter('Bob', new EmailAddress('bob@example.com')), 'Comments.');

        $this->em->persist($entity);
        $this->em->flush();

        /** @var Post $result */
        $result = $this->em->getRepository(Post::class)->findOneBySlug('test-post');

        $this->assertInstanceOf(Post::class, $result);
        $this->assertTrue(EntityAccessor::get($result, 'author')->equals($pa));
        $this->assertTrue(EntityAccessor::get($result, 'title')->equals($pt));
        $this->assertTrue(EntityAccessor::get($result, 'content')->equals($pc));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($result, 'createdAt'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($result, 'updatedAt'));

        $this->assertCount(4, EntityAccessor::get($result, 'comments'));
    }


    /**
     * @var EntityManager
     */
    protected $em;

    protected function setUp()
    {
        $evm = new EventManager();

        $conn = [
            'driver'   => $GLOBALS['DOCTRINE_DRIVER'],
            'memory'   => $GLOBALS['DOCTRINE_MEMORY'],
            'dbname'   => $GLOBALS['DOCTRINE_DATABASE'],
            'user'     => $GLOBALS['DOCTRINE_USER'],
            'password' => $GLOBALS['DOCTRINE_PASSWORD'],
            'host'     => $GLOBALS['DOCTRINE_HOST'],
        ];

        $driver = new YamlDriver([
            __DIR__ . '/../../config/posts',
            __DIR__ . '/../../config/embeds',
        ]);
        $config = new Configuration();
        $config->setMetadataCacheImpl(new ArrayCache());
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('AppBundle\Tests\Proxies');
        $config->setMetadataDriverImpl($driver);

        Type::overrideType(Type::DATETIME, DateTimeType::class);

        $em = EntityManager::create($conn, $config, $evm);

        $schemaTool = new SchemaTool($em);

        try {
            $schemaTool->createSchema([
                $em->getClassMetadata(Post::class),
                $em->getClassMetadata(Comment::class),
            ]);
        } catch (\Exception $e) {
            if (
                $GLOBALS['DOCTRINE_DRIVER'] != 'pdo_mysql' ||
                !($e instanceof \PDOException && strpos($e->getMessage(), 'Base table or view already exists') !== false)
            ) {
                throw $e;
            }
        }

        $this->em = $em;
    }

    protected function tearDown()
    {
        $this->em = null;
    }
}
