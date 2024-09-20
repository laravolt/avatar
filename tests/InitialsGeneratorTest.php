<?php

namespace Laravolt\Avatar\Test;

use PHPUnit\Framework\TestCase;
use Laravolt\Avatar\Generator\DefaultGenerator;
use Stringable;
use TypeError;

class InitialsGeneratorTest extends TestCase
{
    protected $generator;

    public function setUp(): void
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
     */
    public function it_accept_stringable_object()
    {
        $this->assertEquals('HE', $this->generator->make(new Hello()));
    }

    /**
     * @test
     */
    public function it_cannot_accept_array()
    {
        $this->expectException(TypeError::class);
        $this->generator->make(['Bayu', 'Hendra']);
    }

    /**
     * @test
     */
    public function it_cannot_accept_object_without_to_string_function()
    {
        $this->expectException(TypeError::class);
        $this->generator->make(new DefaultGenerator());
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
    public function it_can_convert_to_uppercase()
    {
        $this->assertEquals('SA', (string)$this->generator->make('sabil', 2, true));
    }

    /**
     * @test
     */
    public function it_can_generate_rtl_text()
    {
        $this->assertEquals('as', (string)$this->generator->make('sabil', 2, false, false, true));
        $this->assertEquals('ks', (string)$this->generator->make('sabil karim', 2, false, false, true));
        $this->assertEquals('عع', (string)$this->generator->make('عبدالله عبدالعزيز', 2, false, false, true));
        // $this->assertEquals('ال', (string)$this->generator->make('الله', 2, false, false, true));
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

class Hello implements Stringable
{
    public function __toString()
    {
        return "HELLO";
    }
}
