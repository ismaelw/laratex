<?php declare(strict_types=1);

namespace Websta\LaraTeX;

use Websta\LaraTeX\LaraTeX;
use Websta\LaraTeX\LaratexException;
use Illuminate\Support\Str;
use Websta\LaraTeX\LaratexEmptyCollectionException;
use Websta\LaraTeX\LaratexZipFailedException;

class LaratexCollection
{
    /**
     * Collection of Latex instances
     *
     * @var array
     */
    private array $collection = [];

    /**
     * PDF collection
     *
     * @var array
     */
    private array $pdfCollection = [];

    /**
     * Temp directory of collection files
     *
     * @var string
     */
    private string $collectionDir;

    /**
     * Add latex instance to collection
     *
     * @param LaraTeX $latex
     *
     * @return LaratexCollection
     */
    public function add(LaraTeX $latex): static
    {
        $this->collection[] = $latex;

        return $this;
    }

    /**
     * Download zip of generated pdfs
     *
     * @param string $fileName
     *
     * @return Illuminate\Http\Response
     * @throws LaratexEmptyCollectionException
     * @throws LaratexZipFailedException
     */
    public function downloadZip(string $fileName): Illuminate\Http\Response
    {
        $this->generate();

        $zipFile = $this->makeArchive($fileName);

        return \Response::download($zipFile, $fileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Save generated zip
     *
     * @param string $location
     *
     * @return boolean
     * @throws LaratexEmptyCollectionException
     * @throws LaratexZipFailedException
     */
    public function saveZip(string $location): bool
    {
        $this->generate();

        $zipFile = $this->makeArchive(basename($location));

        return \File::move($zipFile, $location);
    }

    /**
     * Make zip archive
     *
     * @param string $fileName
     *
     * @return string
     * @throws LaratexZipFailedException
     */
    private function makeArchive(string $fileName): string
    {
        $zip = new \ZipArchive;

        $zipFile = $this->collectionDir . DIRECTORY_SEPARATOR . $fileName;

        touch($zipFile);
        chmod($zipFile, 0755);

        if ($zip->open($zipFile, \ZipArchive::OVERWRITE) === true) {

            foreach ($this->pdfCollection as $pdf) {

                if (\File::exists($pdf)) {

                    $zip->addFile($pdf, basename($pdf));
                }
            }

            $zip->close();
        } else {
            throw new LaratexZipFailedException('Could not generate zip file.');
        }

        return $zipFile;
    }

    /**
     * PPdf generation
     *
     * @return LaratexCollection
     * @throws LaratexEmptyCollectionException
     */
    private function generate(): LaratexCollection
    {
        if (count($this->collection) == 0) {
            throw new LaratexEmptyCollectionException('No latex documents added in latex collection. Nothing to generate.');
        }

        $this->moveToCollectionDir();

        return $this;
    }

    /**
     * Move generated files to collection temp dir
     *
     * @return LaratexCollection
     */
    private function moveToCollectionDir(): LaratexCollection
    {

        $this->makeCollectionDir();

        foreach ($this->collection as $latex) {

            $name = $latex->getName() ? $latex->getName() : Str::random(4) . '.pdf';
            $pdf = $this->collectionDir . DIRECTORY_SEPARATOR . $name;
            $latex->savePdf($pdf);

            $this->pdfCollection[] = $pdf;
        }

        return $this;
    }

    /**
     * Make temp collection dir
     *
     * @return LaratexCollection
     */
    private function makeCollectionDir(): LaratexCollection
    {
        $tmpDir = sys_get_temp_dir();

        $this->collectionDir = $tmpDir . DIRECTORY_SEPARATOR . 'texcollection' . Str::random(10);
        \File::makeDirectory($this->collectionDir, 0755, true, true);

        register_shutdown_function(function () {
            if (\File::exists($this->collectionDir)) {
                \File::deleteDirectory($this->collectionDir);
            }
        });

        return $this;
    }
}
