<?php

use PHPUnit\Framework\TestCase;
use Laravolt\Avatar\Generator\DefaultGenerator;

class InitialGeneratorTest extends TestCase
{
    protected $generator;

    public function setUp()
    {
        $this->generator = new DefaultGenerator();
    }

    /**
     * @test
     */
    public function it_accept_string()
    {
        $this->assertEquals('BH', $this->generator->make('Bayu Hendra'));
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_array()
    {
        $this->generator->make(['Bayu', 'Hendra']);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_object_without_to_string_function()
    {
        $this->generator->make(new DefaultGenerator(new stdClass()));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_single_word_name()
    {
        $this->assertEquals('Fu', (string)$this->generator->make('Fulan'));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_multi_word_name()
    {
        $this->assertEquals('FD', (string)$this->generator->make('Fulan Doe'));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_shorter_than_expected_length()
    {
        $generator = new DefaultGenerator('Joe');

        $this->assertEquals('Joe', (string)$generator->make('Joe', 4));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_longer_than_expected_length()
    {
        $this->assertEquals('FJ', (string)$this->generator->make('Fulan John Doe', 2));
    }

    /**
     * @test
     */
    public function it_can_handle_empty_name()
    {
        $this->assertEquals('', (string)$this->generator->make(''));
    }

    /**
     * @test
     */
    public function it_allow_non_ascii()
    {
        $this->assertEquals('Bā', (string)$this->generator->make('Bāyu'));
    }

    /**
     * @test
     */
    public function it_can_convert_to_ascii()
    {
        $this->assertEquals('Ba', (string)$this->generator->make('Bāyu', 2, false, true));
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_email()
    {
        $this->assertEquals('ab', $this->generator->make('adi.budi@laravolt.com'));
        $this->assertEquals('ci', $this->generator->make('citra@laravolt.com'));
        $this->assertEquals('DA', $this->generator->make('DANI@laravolt.com'));
    }
}
