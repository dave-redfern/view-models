<?php

namespace AppBundle\ViewModels;

use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\EmailAddress;
use AppBundle\Entities\ValueObjects\PostAuthor;
use AppBundle\Entities\ValueObjects\PostContent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class PostModel
 *
 * @package    AppBundle\ViewModels
 * @subpackage AppBundle\ViewModels\PostModel
 */
class PostModel
{

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $title;

    /**
     * @var PostAuthor
     */
    private $author;

    /**
     * @var PostContent
     */
    private $content;

    /**
     * @var ArrayCollection
     */
    private $comments;

    /**
     * Constructor.
     *
     * @param string $slug
     * @param string $title
     * @param string $authorName
     * @param string $authorEmail
     * @param string $content
     */
    public function __construct($slug, $title, $authorName, $authorEmail, $content)
    {
        $this->slug     = $slug;
        $this->title    = $title;
        $this->author   = new PostAuthor($authorName, new EmailAddress($authorEmail));
        $this->content  = new PostContent($content);
        $this->comments = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function authorLink()
    {
        return sprintf(
            '<a href="mailto:%s?subject=Re:%s">%s</a>',
            $this->author->email(),
            urlencode($this->title),
            $this->author->name()
        );
    }

    /**
     * @param string $route
     *
     * @return string
     */
    public function postLink($route)
    {
        return sprintf(
            '<a href="%s/%s">%s</a>',
            $route,
            $this->slug,
            $this->title
        );
    }

    /**
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return PostAuthor
     */
    public function author(): PostAuthor
    {
        return $this->author;
    }

    /**
     * @return PostContent
     */
    public function content(): PostContent
    {
        return $this->content;
    }

    /**
     * @return ArrayCollection
     */
    public function comments(): ArrayCollection
    {
        return $this->comments;
    }

    /**
     * @param ArrayCollection $comments
     */
    public function attachComments(ArrayCollection $comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return CommentModel[]|Collection
     */
    public function reverseCommentOrder(): Collection
    {
        return new ArrayCollection(array_reverse($this->comments->toArray()));
    }

    /**
     * @param Commenter $commenter
     *
     * @return Collection
     */
    public function findCommentsBy(Commenter $commenter): Collection
    {
        return $this->comments->filter(function ($comment) use ($commenter) {
            /** @var CommentModel $comment */
            return $comment->commenter()->equals($commenter);
        });
    }

    /**
     * @param string $keyword
     *
     * @return Collection
     */
    public function findCommentsContaining(string $keyword): Collection
    {
        return $this->comments->filter(function ($comment) use ($keyword) {
            /** @var CommentModel $comment */
            return $comment->contains($keyword);
        });
    }
}
