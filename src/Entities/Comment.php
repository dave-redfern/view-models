<?php

namespace AppBundle\Entities;

use AppBundle\Entities\ValueObjects\Commenter;
use Assert\Assert;
use DateTimeImmutable;

/**
 * Class Comment
 *
 * @package    AppBundle\Entities
 * @subpackage AppBundle\Entities\Comment
 */
class Comment
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var Post
     */
    private $post;

    /**
     * @var Commenter
     */
    private $commenter;

    /**
     * @var string
     */
    private $comments;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * Constructor.
     *
     * @param Post      $post
     * @param Commenter $commenter
     * @param string    $comments
     */
    public function __construct(Post $post, Commenter $commenter, string $comments)
    {
        Assert::that($comments, null, 'comments')->notEmpty()->maxLength(4000);

        $this->post      = $post;
        $this->commenter = $commenter;
        $this->comments  = $comments;
        $this->createdAt = new DateTimeImmutable();
    }
}
