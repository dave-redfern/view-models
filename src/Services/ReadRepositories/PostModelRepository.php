<?php

namespace AppBundle\Services\ReadRepositories;

use AppBundle\Repositories\PostRepository;
use AppBundle\ViewModels\CommentModel;
use AppBundle\ViewModels\PostModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;

/**
 * Class PostModelRepository
 *
 * @package    AppBundle\Services\ReadRepositories
 * @subpackage AppBundle\Services\ReadRepositories\PostModelRepository
 */
class PostModelRepository
{

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * Constructor.
     *
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function findLatestPosts($limit = 10)
    {
        $qb = $this->posts->createQueryBuilder('p');
        $qb
            ->select(
                sprintf(
                    'NEW %s(p.title.slug, p.title.title, p.author.name, p.author.email.email, p.content.content)',
                    PostModel::class
                )
            )
            ->where('p.publishedAt >= :now')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter(':now', new \DateTime('-14 days'))
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $slug
     *
     * @return PostModel
     * @throws EntityNotFoundException
     */
    public function findPostBySlugWithComments($slug)
    {
        $qb = $this->posts->createQueryBuilder('p');
        $qb
            ->select(
                sprintf(
                    'NEW %s(p.title.slug, p.title.title, p.author.name, p.author.email.email, p.content.content)',
                    PostModel::class
                )
            )
            ->where('p.title.slug = :slug')
            ->setMaxResults(1)
            ->setParameter(':slug', $slug)
        ;

        /** @var PostModel $post */
        if (null === $post = $qb->getQuery()->getOneOrNullResult()) {
            throw new EntityNotFoundException(sprintf('No post with slug "%s" found', $slug));
        }

        return $this->loadPostWithComments($post);
    }

    /**
     * @param PostModel $post
     *
     * @return mixed
     */
    protected function loadPostWithComments(PostModel $post)
    {
        $qb = $this->posts->createQueryBuilder('p');
        $qb
            ->select(
                sprintf(
                    'NEW %s(c.commenter.name, c.commenter.email.email, c.comments, c.createdAt)',
                    CommentModel::class
                )
            )
            ->innerJoin('p.comments', 'c')
            ->where('p.title.slug = :slug')
            ->orderBy('c.createdAt', 'ASC')
            ->setParameter(':slug', $post->slug())
        ;

        $post->attachComments(new ArrayCollection($qb->getQuery()->getResult()));

        return $post;
    }
}
