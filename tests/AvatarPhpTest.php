<?php

class AvatarPhpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function it_can_override_attributes_when_instantiated()
    {
        $config = [
            'ascii'       => false,
            'shape'       => 'circle',
            'width'       => 200,
            'height'      => 200,
            'chars'       => 2,
            'fontSize'    => 48,
            'fonts'       => ['arial.ttf'],
            'foregrounds' => ['#FFFFFF'],
            'backgrounds' => ['#000000'],
            'border'      => ['size' => 1, 'color' => '#999999'],
        ];

        $avatar = new \Laravolt\Avatar\Avatar($config);

        $this->assertAttributeEquals(2, 'chars', $avatar);
        $this->assertAttributeEquals('circle', 'shape', $avatar);
        $this->assertAttributeEquals(200, 'width', $avatar);
        $this->assertAttributeEquals(200, 'height', $avatar);
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
        $config = ['backgrounds' => ['#000000', '#111111'], 'foregrounds' => ['#EEEEEE', '#FFFFFF']];

        $avatar = new \Laravolt\Avatar\Avatar($config);
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

        $avatar = new \Laravolt\Avatar\Avatar($config);
        $name = 'A';
        $avatar->create($name);

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

        $name1 = 'AA';
        $name2 = 'AAA';

        $avatar1 = new \Laravolt\Avatar\Avatar($config);
        $avatar1->create($name1);

        $avatar2 = new \Laravolt\Avatar\Avatar($config);
        $avatar2->create($name2);

        $this->assertAttributeEquals('#000000', 'background', $avatar1);
        $this->assertAttributeEquals('#111111', 'background', $avatar2);
    }

    /**
     * @test
     */
    public function it_can_create_initials_from_name()
    {
        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->create('Bayu Hendra')->buildAvatar();

        $this->assertAttributeEquals('Bayu Hendra', 'name', $avatar);
        $this->assertAttributeEquals('BH', 'initials', $avatar);
    }

    /**
     * @test
     */
    public function it_accept_valid_font_file()
    {
        $font = __DIR__.'/fonts/rockwell.ttf';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFont($font);

        $this->assertAttributeEquals($font, 'font', $avatar);
    }

    /**
     * @test
     */
    public function it_cannot_accept_invalid_font_file()
    {
        $font = __DIR__.'/fonts/invalid-font.ttf';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFont($font);

        $this->assertAttributeNotEquals($font, 'font', $avatar);
    }

    /**
     * @test
     */
    public function it_can_generate_base64()
    {
        $expected = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAALUlEQVQImU2MsQ0AAAjCiv+/xk24qJGlhKQoCZMAAqg3HGuL7TM0+n0AWl2fDaErDmjZIJEtAAAAAElFTkSuQmCC';
        $avatar = new \Laravolt\Avatar\Avatar();
        $result = (string)$avatar->create('Citra')->setDimension(5, 5)->toBase64();

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_can_generate_base64_from_cache()
    {
        $cachedAvatar = 'data:image/png;base64,iVBO';

        $cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
        $cache->shouldReceive('get')->andReturn($cachedAvatar);

        $avatar = new \Laravolt\Avatar\Avatar([], $cache);
        $result = (string)$avatar->create('Citra')->setDimension(5, 5)->toBase64();

        $this->assertEquals($cachedAvatar, $result);
    }

    /**
     * @test
     */
    public function it_can_generate_file()
    {
        $file = __DIR__.'/avatar.png';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->create('Citra')->setDimension(5, 5)->save($file);

        $this->assertFileExists($file);

        unlink($file);
    }

    /**
     * @test
     */
    public function it_can_generate_circle_svg()
    {
        $expected = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" width="100" height="100">';
        $expected .= '<circle cx="50" cy="50" r="45" stroke="yellow" stroke-width="10" fill="red" />';
        $expected .= '<text x="50" y="50" font-size="24" fill="white" alignment-baseline="middle" text-anchor="middle">AB</text>';
        $expected .= '</svg>';

        $avatar = new \Laravolt\Avatar\Avatar();
        $svg = $avatar->create('Andi Budiman')
                      ->setShape('circle')
                      ->setFontSize(24)
                      ->setDimension(100, 100)
                      ->setForeground('white')
                      ->setBorder(10, 'yellow')
                      ->setBackground('red')
                      ->toSvg();

        $this->assertEquals($expected, $svg);
    }

    /**
     * @test
     */
    public function it_can_generate_rectangle_svg()
    {
        $expected = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" width="100" height="100">';
        $expected .= '<rect x="5" y="5" width="90" height="90" stroke="yellow" stroke-width="10" fill="red" />';
        $expected .= '<text x="50" y="50" font-size="24" fill="white" alignment-baseline="middle" text-anchor="middle">AB</text>';
        $expected .= '</svg>';

        $avatar = new \Laravolt\Avatar\Avatar();
        $svg = $avatar->create('Andi Budiman')
                      ->setShape('square')
                      ->setFontSize(24)
                      ->setDimension(100, 100)
                      ->setForeground('white')
                      ->setBorder(10, 'yellow')
                      ->setBackground('red')
                      ->toSvg();

        $this->assertEquals($expected, $svg);
    }

    /**
     * @test
     */
    public function it_can_set_background()
    {
        $hex = '#ffffff';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setBackground($hex);

        $this->assertAttributeEquals($hex, 'background', $avatar);
    }

    /**
     * @test
     */
    public function it_can_set_foreground()
    {
        $hex = '#ffffff';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setForeground($hex);

        $this->assertAttributeEquals($hex, 'foreground', $avatar);
    }

    /**
     * @test
     */
    public function it_can_set_dimension()
    {
        $avatar = new \Laravolt\Avatar\Avatar();

        $avatar->setDimension(4, 5);
        $this->assertAttributeEquals(4, 'width', $avatar);
        $this->assertAttributeEquals(5, 'height', $avatar);

        $avatar->setDimension(10);
        $this->assertAttributeEquals(10, 'width', $avatar);
        $this->assertAttributeEquals(10, 'height', $avatar);
    }

    /**
     * @test
     */
    public function it_can_set_font_size()
    {
        $size = 12;

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFontSize($size);

        $this->assertAttributeEquals($size, 'fontSize', $avatar);
    }

    /**
     * @test
     */
    public function it_can_set_border()
    {
        $borderSize = 1;
        $borderColors = ['#ffffff', 'foreground', 'background'];

        $avatar = new \Laravolt\Avatar\Avatar();
        foreach ($borderColors as $color) {
            $avatar->setBorder($borderSize, $color)->buildAvatar();

            $this->assertAttributeEquals($borderSize, 'borderSize', $avatar);
            $this->assertAttributeEquals($color, 'borderColor', $avatar);
        }
    }

    /**
     * @test
     */
    public function it_can_accept_valid_shape()
    {
        $shapes = ['circle', 'square'];

        $avatar = new \Laravolt\Avatar\Avatar();

        foreach ($shapes as $shape) {
            $avatar->setShape($shape)->buildAvatar();
            $this->assertAttributeEquals($shape, 'shape', $avatar);
        }
    }

    /**
     * @test
     */
    public function it_throw_exception_for_invalid_shape()
    {
        $this->expectException(InvalidArgumentException::class);

        $shape = 'triangle';
        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setShape($shape)->buildAvatar();
    }

    /**
     * @test
     */
    public function it_can_get_raw_image_object()
    {
        $avatar = new \Laravolt\Avatar\Avatar();
        $imageObject = $avatar->buildAvatar()->getImageObject();

        $this->assertInstanceOf(\Intervention\Image\Image::class, $imageObject);
    }

    /**
     * @test
     */
    public function it_can_get_initial()
    {
        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->create('Citra Kirana')->buildAvatar();

        $this->assertEquals('CK', $avatar->getInitial());
    }

    /**
     * @test
     */
    public function it_can_cast_to_string()
    {
        $expected = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAALUlEQVQImU2MsQ0AAAjCiv+/xk24qJGlhKQoCZMAAqg3HGuL7TM0+n0AWl2fDaErDmjZIJEtAAAAAElFTkSuQmCC';
        $avatar = new \Laravolt\Avatar\Avatar();
        $result = $avatar->create('Citra')->setDimension(5, 5)->__toString();

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function it_can_set_custom_generator()
    {
        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setGenerator(new FooGenerator());

        $this->assertEquals('foo', $avatar->buildAvatar()->getInitial());
    }
}

class FooGenerator implements \Laravolt\Avatar\Generator\GeneratorInterface
{
    public function make($name, $length, $uppercase, $ascii)
    {
        return 'foo';
    }
}
