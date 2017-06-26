<?php

namespace AppBundle\Entities;

use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\PostAuthor;
use AppBundle\Entities\ValueObjects\PostContent;
use AppBundle\Entities\ValueObjects\PostTitle;
use AppBundle\Events;
use AppBundle\Support\Contracts\RaisesDomainEvents as RaisesDomainEventsContract;
use AppBundle\Support\Traits\RaisesDomainEvents;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class Post
 *
 * @package    AppBundle\Entities
 * @subpackage AppBundle\Entities\Post
 */
class Post implements RaisesDomainEventsContract
{

    use RaisesDomainEvents;

    const NUM_ITEMS = 10;

    /**
     * @var int
     */
    private $id;

    /**
     * @var PostAuthor
     */
    private $author;

    /**
     * @var PostTitle
     */
    private $title;

    /**
     * @var PostContent
     */
    private $content;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var DateTimeImmutable
     */
    private $updatedAt;

    /**
     * @var DateTimeImmutable|null
     */
    private $publishedAt;

    /**
     * @var Collection|Comment[]
     */
    private $comments;



    /**
     * Constructor.
     *
     * @param PostAuthor  $author
     * @param PostTitle   $title
     * @param PostContent $content
     */
    private function __construct(PostAuthor $author, PostTitle $title, PostContent $content)
    {
        $this->author    = $author;
        $this->title     = $title;
        $this->content   = $content;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->comments  = new ArrayCollection();
    }

    /**
     * @param PostAuthor  $author
     * @param PostTitle   $title
     * @param PostContent $content
     *
     * @return static
     */
    public static function create(PostAuthor $author, PostTitle $title, PostContent $content)
    {
        $entity = new static($author, $title, $content);
        $entity->raise(new Events\PostCreated([
            'author' => $author, 'title' => $title, 'created_at' => $entity->createdAt,
        ]));

        return $entity;
    }

    /**
     * @param PostAuthor  $author
     * @param PostTitle   $title
     * @param PostContent $content
     *
     * @return static
     */
    public static function createAndPublish(PostAuthor $author, PostTitle $title, PostContent $content)
    {
        $entity = static::create($author, $title, $content);
        $entity->publish();

        return $entity;
    }

    /**
     * @param DateTimeImmutable|null $publishedAt
     */
    public function publish(DateTimeImmutable $publishedAt = null)
    {
        $this->publishedAt = ($publishedAt ?: new DateTimeImmutable());
        $this->updatedAt   = new DateTimeImmutable();

        $this->raise(new Events\PostPublished([
            'author' => $this->author, 'title' => $this->title, 'published_at' => $this->publishedAt,
        ]));
    }

    /**
     * Remove this post from the published posts
     */
    public function removeFromPublication()
    {
        $this->publishedAt = null;
        $this->updatedAt   = new DateTimeImmutable();

        $this->raise(new Events\PostRemovedFromPublishedList([
            'author' => $this->author, 'title' => $this->title, 'removed_at' => new DateTimeImmutable(),
        ]));
    }

    /**
     * @param PostTitle $title
     */
    public function changeTitle(PostTitle $title)
    {
        $this->title     = $title;
        $this->updatedAt = new DateTimeImmutable();

        $this->raise(new Events\PostTitleChanged([
            'author' => $this->author, 'title' => $this->title, 'updated_at' => new DateTimeImmutable(),
        ]));
    }

    /**
     * @param PostContent $content
     */
    public function replaceContentWith(PostContent $content)
    {
        $this->content   = $content;
        $this->updatedAt = new DateTimeImmutable();

        $this->raise(new Events\PostContentChanged([
            'author' => $this->author, 'title' => $this->title, 'updated_at' => new DateTimeImmutable(),
        ]));
    }

    /**
     * @param Commenter $commenter
     * @param string    $comment
     */
    public function leaveComment(Commenter $commenter, string $comment)
    {
        $this->comments->add(new Comment($this, $commenter, $comment));
        $this->updatedAt = new DateTimeImmutable();

        $this->raise(new Events\CommentLeftOnPost([
            'title'      => $this->title,
            'commenter'  => $commenter,
            'comment'    => $comment,
            'created_at' => new DateTimeImmutable(),
        ]));
    }
}
