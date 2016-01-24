<?php


class AvatarTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function it_accept_string()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Bayu Hendra');

        $this->assertEquals('BH', $generator->getInitial());
    }

}
