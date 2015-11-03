<?php

class DefaultThemeTest extends PHPUnit_Framework_TestCase
{
    public function testHandleNameProperly()
    {
        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Bayu Hendra Winata');
        $this->assertEquals('BH', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Jokowi');
        $this->assertEquals('Jo', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('');
        $this->assertEquals('', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Ěmon', true);
        $this->assertEquals('Em', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Ěmon', false);
        $this->assertNotEquals('Em', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Bayu Hendra Winata', true, 3);
        $this->assertEquals('BHW', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Jokowi', true, 1);
        $this->assertEquals('J', $theme->getText());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('Jokowi', true, 999);
        $this->assertEquals('Jokowi', $theme->getText());
    }

    public function testDefaultBackgroundColor()
    {
        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('A');
        $this->assertEquals('#999999', $theme->getBackground());
    }

    public function testDefaultForegroundColor()
    {
        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('A');
        $this->assertEquals('#FFFFFF', $theme->getForeground());
    }

    public function testCustomBackgroundColor()
    {
        $backgrounds = ['#000000', '#111111'];
        $foregrounds = ['#FFFFFF', '#EEEEEE'];

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('A', true, 1, $backgrounds, $foregrounds);
        $this->assertEquals('#111111', $theme->getBackground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('B', true, 1, $backgrounds, $foregrounds);
        $this->assertEquals('#000000', $theme->getBackground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('AA', true, 2, $backgrounds, $foregrounds);
        $this->assertEquals('#000000', $theme->getBackground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('AB', true, 2, $backgrounds, $foregrounds);
        $this->assertEquals('#111111', $theme->getBackground());
    }

    public function testCustomForegroundColor()
    {
        $backgrounds = ['#000000', '#111111'];
        $foregrounds = ['#FFFFFF', '#EEEEEE'];

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('A', true, 1, $backgrounds, $foregrounds);
        $this->assertEquals('#EEEEEE', $theme->getForeground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('B', true, 1, $backgrounds, $foregrounds);
        $this->assertEquals('#FFFFFF', $theme->getForeground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('AA', true, 2, $backgrounds, $foregrounds);
        $this->assertEquals('#FFFFFF', $theme->getForeground());

        $theme = new \Laravolt\Avatar\Themes\DefaultTheme('BA', true, 2, $backgrounds, $foregrounds);
        $this->assertEquals('#EEEEEE', $theme->getForeground());
    }
}
