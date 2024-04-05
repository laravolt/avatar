<?php

namespace Laravolt\Avatar\Test;

use Mockery;

class AvatarLaravelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function it_can_override_attributes_when_instantiated()
    {
        $config = [
            'ascii' => false,
            'shape' => 'circle',
            'width' => 100,
            'height' => 100,
            'chars' => 2,
            'fontSize' => 48,
            'fonts' => ['arial.ttf'],
            'foregrounds' => ['#FFFFFF'],
            'backgrounds' => ['#000000'],
            'border' => ['size' => 1, 'color' => '#999999', 'radius' => 15],
        ];

        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('make')->andReturn('AB');
        $generator->shouldReceive('setUppercase');
        $generator->shouldReceive('setAscii');

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);

        $this->assertEquals(2, $avatar->getAttribute('chars'));
        $this->assertEquals('circle', $avatar->getAttribute('shape'));
        $this->assertEquals(100, $avatar->getAttribute('width'));
        $this->assertEquals(100, $avatar->getAttribute('height'));
        $this->assertEquals(['#000000'], $avatar->getAttribute('availableBackgrounds'));
        $this->assertEquals(['#FFFFFF'], $avatar->getAttribute('availableForegrounds'));
        $this->assertEquals(['arial.ttf'], $avatar->getAttribute('fonts'));
        $this->assertEquals(48, $avatar->getAttribute('fontSize'));
        $this->assertEquals(1, $avatar->getAttribute('borderSize'));
        $this->assertEquals('#999999', $avatar->getAttribute('borderColor'));
        $this->assertEquals(15, $avatar->getAttribute('borderRadius'));
        $this->assertEquals(false, $avatar->getAttribute('ascii'));
    }

    /**
     * @test
     */
    public function it_have_no_border_radius_as_default()
    {
        $config = [
            'border' => ['size' => 1, 'color' => '#999999'],
        ];

        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('make')->andReturn('AB');
        $generator->shouldReceive('setUppercase');
        $generator->shouldReceive('setAscii');

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);

        $this->assertEquals(0, $avatar->getAttribute('borderRadius'));
    }

    /**
     * @test
     */
    public function it_can_override_attributes_after_set_name()
    {
        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('setName')->andReturnSelf();
        $generator->shouldReceive('setLength');
        $generator->shouldReceive('make')->andReturn('A');
        $generator->shouldReceive('setUppercase');
        $generator->shouldReceive('setAscii');
        $generator->shouldReceive('base_path');
        $config = ['backgrounds' => ['#000000', '#111111'], 'foregrounds' => ['#EEEEEE', '#FFFFFF']];

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);
        $avatar->create('A');

        $this->assertEquals('#FFFFFF', $avatar->getAttribute('foreground'));
    }

    /**
     * @test
     */
    public function it_has_correct_random_background()
    {
        $config = [
            'foregrounds' => ['#000000', '#111111'],
            'backgrounds' => ['#111111', '#000000'],
        ];

        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('setUppercase');
        $generator->shouldReceive('setAscii');

        $avatar = new \Laravolt\Avatar\Avatar($config, $cache, $generator);

        $name = 'A';

        $generator->shouldReceive('setLength')->andReturn(1);
        $generator->shouldReceive('setName')->andReturn($name);
        $generator->shouldReceive('setUppercase')->andReturnSelf();
        $generator->shouldReceive('make')->andReturn('A');
        $avatar->create($name)->buildAvatar();

        $this->assertEquals('#000000', $avatar->getAttribute('background'));
        $this->assertEquals('#111111', $avatar->getAttribute('foreground'));
    }

    /**
     * @test
     */
    public function it_has_different_random_background()
    {
        $config = [
            'backgrounds' => ['#000000', '#111111'],
        ];

        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');

        $generator = Mockery::mock('Laravolt\Avatar\InitialGenerator');
        $generator->shouldReceive('setUppercase');
        $generator->shouldReceive('setAscii');

        $name1 = 'B';
        $name2 = 'C';

        $generator->shouldReceive('setLength')->andReturn(2);
        $generator->shouldReceive('setName')->andReturn($name1);
        $generator->shouldReceive('make')->andReturn('AA');

        $avatar1 = new \Laravolt\Avatar\Avatar($config, $cache, $generator);
        $avatar1->create($name1)->buildAvatar();

        $generator->shouldReceive('setName')->andReturn($name2);

        $avatar2 = new \Laravolt\Avatar\Avatar($config, $cache, $generator);
        $avatar2->create($name2)->buildAvatar();

        $this->assertEquals('#000000', $avatar1->getAttribute('background'));
        $this->assertEquals('#111111', $avatar2->getAttribute('background'));
    }
}
