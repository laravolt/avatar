<?php


class InitialGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_accept_string()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Bayu Hendra');

        $this->assertEquals('BH', $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_accept_stringy()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator(new \Stringy\Stringy('Bayu Hendra'));

        $this->assertEquals('BH', $generator->getInitial());
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_array()
    {
        new \Laravolt\Avatar\InitialGenerator(['Bayu', 'Hendra']);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_cannot_accept_object_without_to_string_function()
    {
        new \Laravolt\Avatar\InitialGenerator(new stdClass());
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_single_word_name()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Fulan');

        $this->assertEquals('Fu', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_multi_word_name()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Fulan Doe');

        $this->assertEquals('FD', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_shorter_than_expected_length()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Joe');
        $generator->setLength(4);

        $this->assertEquals('Joe', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_generate_initials_if_name_longer_than_expected_length()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Fulan John Doe');
        $generator->setLength(2);

        $this->assertEquals('FJ', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_handle_empty_name()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('');

        $this->assertEquals('', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_allow_non_ascii()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Bāyu');

        $this->assertEquals('Bā', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_convert_to_ascii()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('Bāyu');
        $generator->setAscii(true);

        $this->assertEquals('Ba', (string) $generator->getInitial());
    }

    /**
     * @test
     */
    public function it_can_generate_initials_from_email()
    {
        $generator = new \Laravolt\Avatar\InitialGenerator('adi.budi@laravolt.com');
        $this->assertEquals('ab', $generator->getInitial());

        $generator = new \Laravolt\Avatar\InitialGenerator('citra@laravolt.com');
        $this->assertEquals('ci', $generator->getInitial());

        $generator = new \Laravolt\Avatar\InitialGenerator('DANI@laravolt.com');
        $this->assertEquals('DA', $generator->getInitial());
    }
}
