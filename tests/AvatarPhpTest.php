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
            'border'      => ['size' => 1, 'color' => '#999999', 'radius' => 15],
        ];

        $avatar = new \Laravolt\Avatar\Avatar($config);

        $this->assertEquals(2, $avatar->getAttribute('chars'));
        $this->assertEquals('circle', $avatar->getAttribute('shape'));
        $this->assertEquals(200, $avatar->getAttribute('width'));
        $this->assertEquals(200, $avatar->getAttribute('height'));
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
    public function it_can_override_attributes_after_set_name()
    {
        $config = ['backgrounds' => ['#000000', '#111111'], 'foregrounds' => ['#EEEEEE', '#FFFFFF']];

        $avatar = new \Laravolt\Avatar\Avatar($config);
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

        $avatar = new \Laravolt\Avatar\Avatar($config);
        $name = 'A';
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

        $name1 = 'AA';
        $name2 = 'AAA';

        $avatar1 = new \Laravolt\Avatar\Avatar($config);
        $avatar1->create($name1)->buildAvatar();

        $avatar2 = new \Laravolt\Avatar\Avatar($config);
        $avatar2->create($name2)->buildAvatar();

        $this->assertEquals('#000000', $avatar1->getAttribute('background'));
        $this->assertEquals('#111111', $avatar2->getAttribute('background'));
    }

    /**
     * @test
     */
    public function it_can_resolve_random_themes_and_then_overrides()
    {
        $config = [
            'theme' => '*',
            'themes' => [
                'dark' => [
                    'backgrounds' => ['#000000', '#111111'],
                    'foregrounds' => ['#EEEEEE', '#FFFFFF'],
                ],
                'light' => [
                    'backgrounds' => ['#FFFFFF', '#EEEEEE'],
                    'foregrounds' => ['#000000', '#111111'],
                ],
            ]
        ];

        $name1 = 'Bay';

        $avatar1 = new \Laravolt\Avatar\Avatar($config);
        $avatar1->create($name1)->buildAvatar();

        $this->assertEquals('#000000', $avatar1->getAttribute('background'));
        $this->assertEquals('#EEEEEE', $avatar1->getAttribute('foreground'));

        $avatar1->setTheme('light')->buildAvatar();
        $this->assertEquals('#FFFFFF', $avatar1->getAttribute('background'));
        $this->assertEquals('#000000', $avatar1->getAttribute('foreground'));
    }

    /**
     * @test
     */
    public function it_can_handle_invalid_theme()
    {
        $config = [
            'foregrounds' => ['#000000', '#111111'],
            'backgrounds' => ['#111111', '#000000'],
        ];

        $avatar = new \Laravolt\Avatar\Avatar($config);
        $name = 'A';
        $avatar->create($name)->setTheme('zombie')->buildAvatar();

        $this->assertEquals('#000000', $avatar->getAttribute('background'));
        $this->assertEquals('#111111', $avatar->getAttribute('foreground'));

        $avatar->setTheme(new stdClass())->buildAvatar();
        $avatar->setTheme(['satu', 'dua'])->buildAvatar();
    }

    /**
     * @test
     */
    public function it_can_create_initials_from_name()
    {
        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->create('Bayu Hendra')->buildAvatar();

        $this->assertEquals('Bayu Hendra', $avatar->getAttribute('name'));
        $this->assertEquals('BH', $avatar->getAttribute('initials'));
    }

    /**
     * @test
     */
    public function it_can_set_chars_length()
    {
        $avatar = new \Laravolt\Avatar\Avatar();

        $avatar->create('Bayu Hendra Winata')->setChars(1)->buildAvatar();
        $this->assertEquals('B', $avatar->getAttribute('initials'));

        $avatar->create('Bayu Hendra Winata')->setChars(3)->buildAvatar();
        $this->assertEquals('BHW', $avatar->getAttribute('initials'));
    }

    /**
     * @test
     */
    public function it_accept_valid_font_file()
    {
        $font = __DIR__.'/fonts/rockwell.ttf';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFont($font);

        $this->assertEquals($font, $avatar->getAttribute('font'));
    }

    /**
     * @test
     */
    public function it_cannot_accept_invalid_font_file()
    {
        $font = __DIR__.'/fonts/invalid-font.ttf';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFont($font);

        $this->assertNotEquals($font, $avatar->getAttribute('font'));
    }

    /**
     * @test
     */
    public function it_can_generate_base64()
    {
        $expected = $this->sampleBase64String();
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
        $expected = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">';
        $expected .= '<circle cx="50" cy="50" r="45" stroke="yellow" stroke-width="10" fill="red" />';
        $expected .= '<text x="50" y="50" font-size="24" fill="white" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">AB</text>';
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
        $expected = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">';
        $expected .= '<rect x="5" y="5" width="90" height="90" stroke="yellow" stroke-width="10" rx="15" fill="red" />';
        $expected .= '<text x="50" y="50" font-size="24" fill="white" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">AB</text>';
        $expected .= '</svg>';

        $avatar = new \Laravolt\Avatar\Avatar();
        $svg = $avatar->create('Andi Budiman')
                      ->setShape('square')
                      ->setFontSize(24)
                      ->setDimension(100, 100)
                      ->setForeground('white')
                      ->setBorder(10, 'yellow')
                      ->setBorderRadius(15)
                      ->setBackground('red')
                      ->toSvg();

        $this->assertEquals($expected, $svg);
    }

    /**
     * @test
     */
    public function it_can_generate_svg_with_custom_font_family()
    {
        $expected = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">';
        $expected .= '<circle cx="50" cy="50" r="45" stroke="yellow" stroke-width="10" fill="red" />';
        $expected .= '<text x="50" y="50" font-size="24" font-family="Lato" fill="white" alignment-baseline="middle" text-anchor="middle" dominant-baseline="central">AB</text>';
        $expected .= '</svg>';

        $avatar = new \Laravolt\Avatar\Avatar();
        $svg = $avatar->create('Andi Budiman')
                      ->setShape('circle')
                      ->setFontSize(24)
                      ->setFontFamily('Lato')
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
    public function it_can_generate_gravatar()
    {
        $expected = 'https://www.gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879';

        $avatar = new \Laravolt\Avatar\Avatar();
        $url = $avatar
            ->setDimension(null)
            ->create('uyab.exe@gmail.com')
            ->toGravatar();

        $this->assertEquals($expected, $url);
    }

    /**
     * @test
     */
    public function it_can_generate_gravatar_with_size()
    {
        $expected = 'https://www.gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879?s=100';

        $avatar = new \Laravolt\Avatar\Avatar();
        $url = $avatar->create('uyab.exe@gmail.com')
            ->setDimension(100)
            ->toGravatar();

        $this->assertEquals($expected, $url);
    }

    /**
     * @test
     */
    public function it_can_generate_gravatar_with_default()
    {
        $expected = 'https://www.gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879?d=identicon&s=100';

        $avatar = new \Laravolt\Avatar\Avatar();
        $url = $avatar->create('uyab.exe@gmail.com')
            ->setDimension(100)
            ->toGravatar(['d' => 'identicon']);

        $this->assertEquals($expected, $url);
    }

    /**
     * @test
     */
    public function it_can_generate_gravatar_with_default_and_rating()
    {
        $expected = 'https://www.gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879?d=identicon&r=pg&s=100';

        $avatar = new \Laravolt\Avatar\Avatar();
        $url = $avatar->create('uyab.exe@gmail.com')
            ->setDimension(100)
            ->toGravatar(['d' => 'identicon', 'r' => 'pg']);

        $this->assertEquals($expected, $url);
    }

    /**
     * @test
     */
    public function it_can_generate_gravatar_with_size_overriden()
    {
        $expected = 'https://www.gravatar.com/avatar/0dcae7d6d76f9a3b14588e9671c45879?s=300';

        $avatar = new \Laravolt\Avatar\Avatar();
        $url = $avatar->create('uyab.exe@gmail.com')
            ->setDimension(100)
            ->toGravatar(['s' => 300]);

        $this->assertEquals($expected, $url);
    }

    /**
     * @test
     */
    public function it_can_set_background()
    {
        $hex = '#ffffff';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setBackground($hex);

        $this->assertEquals($hex, $avatar->getAttribute('background'));
    }

    /**
     * @test
     */
    public function it_can_set_foreground()
    {
        $hex = '#ffffff';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setForeground($hex);

        $this->assertEquals($hex, $avatar->getAttribute('foreground'));
    }

    /**
     * @test
     */
    public function it_can_set_dimension()
    {
        $avatar = new \Laravolt\Avatar\Avatar();

        $avatar->setDimension(4, 5);
        $this->assertEquals(4, $avatar->getAttribute('width'));
        $this->assertEquals(5, $avatar->getAttribute('height'));

        $avatar->setDimension(10);
        $this->assertEquals(10, $avatar->getAttribute('width'));
        $this->assertEquals(10, $avatar->getAttribute('height'));
    }

    /**
     * @test
     */
    public function it_can_set_font_size()
    {
        $size = 12;

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFontSize($size);

        $this->assertEquals($size, $avatar->getAttribute('fontSize'));
    }

    /**
     * @test
     */
    public function it_can_set_font_family()
    {
        $font = 'Lato';

        $avatar = new \Laravolt\Avatar\Avatar();
        $avatar->setFontFamily($font);

        $this->assertEquals($font, $avatar->getAttribute('fontFamily'));
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

            $this->assertEquals($borderSize, $avatar->getAttribute('borderSize'));
            $this->assertEquals($color, $avatar->getAttribute('borderColor'));
            $this->assertEquals(0, $avatar->getAttribute('borderRadius'));
        }
    }

    /**
     * @test
     */
    public function it_can_set_border_radius()
    {
        $borderSize = 1;
        $borderColors = ['#ffffff', 'foreground', 'background'];

        $avatar = new \Laravolt\Avatar\Avatar();
        foreach ($borderColors as $color) {
            $avatar->setBorder($borderSize, $color, 10)->buildAvatar();

            $this->assertEquals($borderSize, $avatar->getAttribute('borderSize'));
            $this->assertEquals($color, $avatar->getAttribute('borderColor'));
            $this->assertEquals(10, $avatar->getAttribute('borderRadius'));
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
            $this->assertEquals($shape, $avatar->getAttribute('shape'));
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
        $expected = $this->sampleBase64String();
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

    protected function sampleBase64String()
    {
        if (version_compare(phpversion(), '7.2', '>=')) {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAALUlEQVQImU2MsQ0AAAjCiv+/xk24qJGlhKQoCZMAAqg3HGuL7TM0+n0AWl2fDaErDmjZIJEtAAAAAElFTkSuQmCC';
        } else {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAALUlEQVQImU2MsQ0AAAjCiv+/xk24qJGlhKQoCZMAAqg3HGuL7TM0+n0AWl2fDaErDmjZIJEtAAAAAElFTkSuQmCC';
        }
    }
}

class FooGenerator implements \Laravolt\Avatar\Generator\GeneratorInterface
{
    public function make($name, $length, $uppercase, $ascii, $rtl)
    {
        return 'foo';
    }
}
