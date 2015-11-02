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
}
