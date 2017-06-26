<?php

namespace AppBundle\Tests\Entities;

use AppBundle\Entities\Post;
use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\EmailAddress;
use AppBundle\Entities\ValueObjects\PostAuthor;
use AppBundle\Entities\ValueObjects\PostContent;
use AppBundle\Entities\ValueObjects\PostTitle;
use AppBundle\Events\CommentLeftOnPost;
use AppBundle\Events\PostContentChanged;
use AppBundle\Events\PostCreated;
use AppBundle\Events\PostPublished;
use AppBundle\Events\PostRemovedFromPublishedList;
use AppBundle\Events\PostTitleChanged;
use AppBundle\Support\Contracts\RaisesDomainEvents;
use AppBundle\Support\EntityAccessor;
use PHPUnit\Framework\TestCase;
use Somnambulist\Collection\Collection;

/**
 * Class PostTest
 *
 * @package    AppBundle\Tests\Entities
 * @subpackage AppBundle\Tests\Entities\PostTest
 */
class PostTest extends TestCase
{

    /**
     * @param RaisesDomainEvents $object
     * @param string             $event
     */
    protected function assertRaisesEvent(RaisesDomainEvents $object, $event)
    {
        $events = $object->releaseAndResetEvents();

        $this->assertTrue($this->assertContainsInstanceOf($events, $event));
    }

    /**
     * @param \Traversable $collection
     * @param string       $class
     *
     * @return bool
     */
    protected function assertContainsInstanceOf($collection, $class)
    {
        return Collection::collect($collection)->filter(function ($ele) use ($class) {
            return $ele instanceof $class;
        })->count() > 0;
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCreate()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );

        $this->assertTrue($pa->equals(EntityAccessor::get($entity, 'author')));
        $this->assertTrue($pt->equals(EntityAccessor::get($entity, 'title')));
        $this->assertTrue($pc->equals(EntityAccessor::get($entity, 'content')));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'createdAt'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'updatedAt'));
        $this->assertNull(EntityAccessor::get($entity, 'publishedAt'));
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCreateAndPublish()
    {
        $entity = Post::createAndPublish(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );

        $this->assertEquals($pa, EntityAccessor::get($entity, 'author'));
        $this->assertEquals($pt, EntityAccessor::get($entity, 'title'));
        $this->assertEquals($pc, EntityAccessor::get($entity, 'content'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'createdAt'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'updatedAt'));
        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'publishedAt'));
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCanPublishAtASpecificDateTime()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->publish(new \DateTimeImmutable('-15 days'));

        $this->assertInstanceOf(\DateTimeImmutable::class, EntityAccessor::get($entity, 'publishedAt'));
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCreatingRaisesDomainEvents()
    {
        $entity = Post::createAndPublish(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );

        $this->assertRaisesEvent($entity, PostCreated::class);
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testPublishingRaisesDomainEvents()
    {
        $entity = Post::createAndPublish(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );

        $this->assertRaisesEvent($entity, PostPublished::class);
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testUnpublishingRaisesDomainEvents()
    {
        $entity = Post::createAndPublish(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->removeFromPublication();

        $this->assertRaisesEvent($entity, PostRemovedFromPublishedList::class);
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testChangingTitleRaisesDomainEvents()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->changeTitle(new PostTitle('Another Title'));

        $this->assertRaisesEvent($entity, PostTitleChanged::class);
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testChangingContentRaisesDomainEvents()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->replaceContentWith(new PostContent('<p>This post has had its content changed.</p>'));

        $this->assertRaisesEvent($entity, PostContentChanged::class);
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testCanLeaveAComment()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->leaveComment(new Commenter('Bob', new EmailAddress('bob@example.com')), 'These are some comments.');

        $this->assertCount(1, EntityAccessor::get($entity, 'comments'));
    }

    /**
     * @group domain
     * @group entities
     * @group entities-post
     */
    public function testLeavingACommentContentRaisesDomainEvents()
    {
        $entity = Post::create(
            $pa = new PostAuthor('bob', new EmailAddress('bob@example.com')),
            $pt = new PostTitle('Test Post'),
            $pc = new PostContent('<p>This is a test post</p>')
        );
        $entity->leaveComment(new Commenter('Bob', new EmailAddress('bob@example.com')), 'These are some comments.');

        $this->assertRaisesEvent($entity, CommentLeftOnPost::class);
    }
}
