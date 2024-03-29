<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     *
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $desiredCapabilities = DesiredCapabilities::chrome();

        $options = new ChromeOptions();
        $options->addArguments([
            'headless',
            'disable-gpu',
        ]);

        $desiredCapabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        return RemoteWebDriver::create(
            'http://localhost:9515', $desiredCapabilities
        );
    }
}
