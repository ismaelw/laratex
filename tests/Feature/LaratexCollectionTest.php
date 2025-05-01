<?php

namespace Ismaelw\LaraTeX\Tests\Feature;

use Ismaelw\LaraTeX\LaratexCollection;
use Ismaelw\LaraTeX\Tests\TestCase;
use ZipArchive;

class LaratexCollectionTest extends TestCase
{
    public function testDownloadZipSuccessful()
    {
        $collection = new LaratexCollection();
        $collection = $collection->add($this->latex);

        $response = $collection->downloadZip('test.zip');

        $filePathName = $response->getFile()->getRealPath();

        $this->assertFileExists($filePathName);

        $zip = new ZipArchive();
        $opened = $zip->open($filePathName);

        $this->assertTrue($opened);

        $pdfFileName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION)) === 'pdf') {
                $pdfFileName = $stat['name'];
                break;
            }
        }

        $this->assertNotNull($pdfFileName);

        $pdfGenerated = '/tmp/' . basename($pdfFileName);
        $zip->extractTo('/tmp', $pdfFileName);
        $zip->close();

        $expectedText = file_get_contents(__DIR__ . '/../files/example.txt');
        $actualText = shell_exec("pdftotext -layout $pdfGenerated -");

        $this->assertSame(trim($expectedText), trim($actualText));
    }
}
