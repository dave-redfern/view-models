<?php

namespace AppBundle\ViewModels;

use AppBundle\Entities\ValueObjects\Commenter;
use AppBundle\Entities\ValueObjects\EmailAddress;

/**
 * Class CommentModel
 *
 * @package    AppBundle\ViewModels
 * @subpackage AppBundle\ViewModels\CommentModel
 */
class CommentModel
{

    /**
     * @var Commenter
     */
    private $commenter;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var \DateTimeImmutable
     */
    private $date;

    /**
     * Constructor.
     *
     * @param string             $commenterName
     * @param string             $commenterEmail
     * @param string             $comment
     * @param \DateTimeImmutable $date
     */
    public function __construct($commenterName, $commenterEmail, $comment, $date)
    {
        $this->commenter = new Commenter($commenterName, new EmailAddress($commenterEmail));
        $this->comment   = $comment;
        $this->date      = $date;
    }

    /**
     * @return Commenter
     */
    public function commenter(): Commenter
    {
        return $this->commenter;
    }

    /**
     * @return string
     */
    public function comment(): string
    {
        return $this->comment;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function postedAt(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @param string $keyword
     *
     * @return bool
     */
    public function contains(string $keyword): bool
    {
        return stripos($this->comment, $keyword) !== false;
    }
}
