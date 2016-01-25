<?php


class AvatarTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function it_can_instantiated()
    {
        $cache = Mockery::mock('Illuminate\Cache\CacheManager');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('getInitial')->andReturn('AB');

        new \Laravolt\Avatar\Avatar([], $cache, $generator);
    }

    /**
     * @test
     */
    public function it_can_override_attributes_when_instantiated()
    {
        $config = [
            'ascii'       => false,
            'shape'       => 'circle',
            'width'       => 100,
            'height'      => 100,
            'chars'       => 2,
            'fontSize'    => 48,
            'fonts'       => ['arial.ttf'],
            'foregrounds' => ['#FFFFFF'],
            'backgrounds' => ['#000000'],
            'border'      => ['size' => 1, 'color' => '#999999'],
        ];

        $cache = Mockery::mock('Illuminate\Cache\CacheManager');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('getInitial')->andReturn('AB');

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);

        $this->assertAttributeEquals(2, 'chars', $avatar);
        $this->assertAttributeEquals('circle', 'shape', $avatar);
        $this->assertAttributeEquals(100, 'width', $avatar);
        $this->assertAttributeEquals(100, 'height', $avatar);
        $this->assertAttributeEquals(['#000000'], 'availableBackgrounds', $avatar);
        $this->assertAttributeEquals(['#FFFFFF'], 'availableForegrounds', $avatar);
        $this->assertAttributeEquals(['arial.ttf'], 'fonts', $avatar);
        $this->assertAttributeEquals(48, 'fontSize', $avatar);
        $this->assertAttributeEquals(1, 'borderSize', $avatar);
        $this->assertAttributeEquals('#999999', 'borderColor', $avatar);
        $this->assertAttributeEquals(false, 'ascii', $avatar);

    }

    /**
     * @test
     */
    public function it_can_override_attributes_after_set_name()
    {
        $cache = Mockery::mock('Illuminate\Cache\CacheManager');
        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('setName')->andReturnSelf();
        $generator->shouldReceive('setLength');
        $generator->shouldReceive('getInitial')->andReturn('A');
        $generator->shouldReceive('base_path');
        $config = ['backgrounds' => ['#000000', '#111111'], 'foregrounds' => ['#EEEEEE', '#FFFFFF']];

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);
        $avatar->setFontFolder(['fonts/']);
        $avatar->create('A');

        $this->assertAttributeEquals('#FFFFFF', 'foreground', $avatar);
    }
}
