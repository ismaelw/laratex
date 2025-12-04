<?php

namespace Ismaelw\LaraTeX\Tests;

use Ismaelw\LaraTeX\LaraTeX;
use Ismaelw\LaraTeX\LaraTeXServiceProvider;
use Ismaelw\LaraTeX\RawTex;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $latex;
    protected static $latestResponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->latex = new LaraTeX(
            new RawTex(file_get_contents(__DIR__ . '/../src/dryrun.tex'))
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaraTeXServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laratex.binPath', 'pdflatex');
        $app['config']->set('laratex.bibTexPath', 'bibtex');
        $app['config']->set('laratex.tempPath', 'app/');
        $app['config']->set('laratex.teardown', true);
        $app['config']->set('laratex.timeout', 120);
    }
}
