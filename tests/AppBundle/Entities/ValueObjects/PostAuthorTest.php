<?php

namespace AppBundle\Tests\Entities\ValueObjects;

use AppBundle\Entities\ValueObjects\EmailAddress;
use AppBundle\Entities\ValueObjects\PostAuthor;
use PHPUnit\Framework\TestCase;

/**
 * Class PostAuthorTest
 *
 * @package    AppBundle\Tests\Entities\ValueObjects
 * @subpackage AppBundle\Tests\Entities\ValueObjects\PostAuthorTest
 */
class PostAuthorTest extends TestCase
{

    /**
     * @group value-objects
     * @group value-objects-post-author
     */
    public function testCreate()
    {
        $vo = new PostAuthor('foo bar', new EmailAddress('foo@example.com'));

        $this->assertEquals('foo bar', $vo->name());
        $this->assertEquals('foo@example.com', (string)$vo->email());
    }

    /**
     * @group value-objects
     * @group value-objects-post-author
     */
    public function testCanCastToString()
    {
        $vo = new PostAuthor('foo bar', new EmailAddress('foo@example.com'));

        $this->assertEquals('foo bar', (string)$vo);
    }

    /**
     * @group value-objects
     * @group value-objects-post-author
     */
    public function testCanCompareInstances()
    {
        $vo1 = new PostAuthor('foo bar', new EmailAddress('foo@example.com'));
        $vo2 = new PostAuthor('bar baz', new EmailAddress('foo@example.com'));
        $vo3 = new PostAuthor('foo bar', new EmailAddress('foo@example.com'));


        $this->assertFalse($vo1->equals($vo2));
        $this->assertTrue($vo1->equals($vo3));
        $this->assertTrue($vo1->equals($vo1));
    }

    /**
     * @group value-objects
     * @group value-objects-post-author
     */
    public function testCantSetArbitraryProperties()
    {
        $vo = new PostAuthor('foo bar', new EmailAddress('foo@example.com'));
        $vo->foo = 'bar';

        $this->assertObjectNotHasAttribute('foo', $vo);
    }
}
