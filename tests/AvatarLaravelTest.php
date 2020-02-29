<?php

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
        $this->assertAttributeEquals(15, 'borderRadius', $avatar);
        $this->assertAttributeEquals(false, 'ascii', $avatar);
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

        $this->assertAttributeEquals(0, 'borderRadius', $avatar);
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

        $this->assertAttributeEquals('#FFFFFF', 'foreground', $avatar);
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

        $this->assertAttributeEquals('#000000', 'background', $avatar);
        $this->assertAttributeEquals('#111111', 'foreground', $avatar);
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

        $this->assertAttributeEquals('#000000', 'background', $avatar1);
        $this->assertAttributeEquals('#111111', 'background', $avatar2);
    }
}
