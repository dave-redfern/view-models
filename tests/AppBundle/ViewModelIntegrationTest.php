<?php

namespace AppBundle\Tests;

use AppBundle\Entities\Comment;
use AppBundle\Entities\Post;
use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\EmailAddress;
use AppBundle\Entities\ValueObjects\PostAuthor;
use AppBundle\Entities\ValueObjects\PostContent;
use AppBundle\Entities\ValueObjects\PostTitle;
use AppBundle\Services\ReadRepositories\PostModelRepository;
use AppBundle\Support\Doctrine\Types\DateTimeType;
use AppBundle\ViewModels\CommentModel;
use AppBundle\ViewModels\PostModel;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

/**
 * Class ViewModelIntegrationTest
 *
 * @package    AppBundle\Tests
 * @subpackage AppBundle\Tests\ViewModelIntegrationTest
 */
class ViewModelIntegrationTest extends TestCase
{

    /**
     * @group database
     * @group view-models
     * @group view-models-post
     */
    public function testCanFetchPostModels()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity2 = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Another Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->publish(new \DateTimeImmutable());
        $entity2->publish(new \DateTimeImmutable());

        $this->em->persist($entity);
        $this->em->persist($entity2);
        $this->em->flush();


        $repo = new PostModelRepository($this->em->getRepository(Post::class));

        $results = $repo->findLatestPosts();

        $this->assertCount(2, $results);
        $this->assertInstanceOf(PostModel::class, $results[0]);
    }

    /**
     * @group database
     * @group view-models
     * @group view-models-post
     */
    public function testCanFetchPostModelWithComments()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->leaveComment($cc = new Commenter('Bob', new EmailAddress('bob@example.com')), $com = 'These are some comments.');
        $entity->leaveComment(new Commenter('Bob Marley', new EmailAddress('bob@example.com')), 'These are some more comments.');
        $entity->leaveComment(new Commenter('Bob Bar', new EmailAddress('bob@example.com')), 'These are some other comments.');
        $entity->leaveComment(new Commenter('Bob', new EmailAddress('bob@example.com')), 'Comments.');

        $this->em->persist($entity);
        $this->em->flush();


        $repo = new PostModelRepository($this->em->getRepository(Post::class));

        $postModel = $repo->findPostBySlugWithComments('test-post');

        $this->assertInstanceOf(PostModel::class, $postModel);
        $this->assertEquals('Test Post', $postModel->title());
        $this->assertEquals('test-post', $postModel->slug());
        $this->assertTrue($postModel->author()->equals($pa));
        $this->assertTrue($postModel->content()->equals($pc));
        $this->assertCount(4, $postModel->comments());

        $this->assertInstanceOf(CommentModel::class, $comment = $postModel->comments()->first());
        $this->assertInstanceOf(\DateTimeImmutable::class, $comment->postedAt());
        $this->assertTrue($comment->commenter()->equals($cc));
        $this->assertEquals($com, $comment->comment());
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
