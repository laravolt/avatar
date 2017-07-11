<?php

class InitialGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function it_accept_string()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('BH', $generator->make('Bayu Hendra'));
    }

    /**
     * @test
     */
    public function it_accept_stringy()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('BH', $generator->make(new \Stringy\Stringy('Bayu Hendra')));
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_array()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();
        $generator->make(['Bayu', 'Hendra']);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_object_without_to_string_function()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();
        $generator->make(new \Laravolt\Avatar\Generator\DefaultGenerator(new stdClass()));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_single_word_name()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('Fu', (string)$generator->make('Fulan'));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_multi_word_name()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('FD', (string)$generator->make('Fulan Doe'));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_shorter_than_expected_length()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator('Joe');

        $this->assertEquals('Joe', (string)$generator->make('Joe', 4));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_longer_than_expected_length()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('FJ', (string)$generator->make('Fulan John Doe', 2));
    }

    /**
     * @test
     */
    public function it_can_handle_empty_name()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('', (string)$generator->make(''));
    }

    /**
     * @test
     */
    public function it_allow_non_ascii()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('Bā', (string)$generator->make('Bāyu'));
    }

    /**
     * @test
     */
    public function it_can_convert_to_ascii()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('Ba', (string)$generator->make('Bāyu', 2, false, true));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_email()
    {
        $generator = new \Laravolt\Avatar\Generator\DefaultGenerator();

        $this->assertEquals('ab', $generator->make('adi.budi@laravolt.com'));
        $this->assertEquals('ci', $generator->make('citra@laravolt.com'));
        $this->assertEquals('DA', $generator->make('DANI@laravolt.com'));
    }
}
