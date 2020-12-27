<?php

namespace Ismaelw\LaraTeX;

use Ismaelw\LaraTeX\LaraTeX;
use Ismaelw\LaraTeX\LaratexEmptyCollectionException;
use Ismaelw\LaraTeX\LaratexException;
use Ismaelw\LaraTeX\LaratexZipFailedException;
use Illuminate\Support\Str;

class LaratexCollection
{
    /**
     * Collection of Latex instances
     *
     * @var array
     */
    private $collection = [];

    /**
     * PDF collection
     *
     * @var array
     */
    private $pdfCollection = [];

    /**
     * Temp directory of collection files
     *
     * @var string
     */
    private $collectionDir;

    /**
     * Add latex instance to collection
     * @param  LaraTeX $latex
     *
     * @return void
     */
    public function add(LaraTeX $latex)
    {
        $this->collection[] = $latex;

        return $this;
    }

    /**
     * Download zip of generated pdfs
     *
     * @param  string $fileName
     *
     * @return Illuminate\Http\Response
     */
    public function downloadZip($fileName)
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
     * @param  string $location
     *
     * @return boolean
     */
    public function saveZip($location)
    {
        $this->generate();

        $zipFile = $this->makeArchive(basename($location));

        $fileMoved = \File::move($zipFile, $location);

        return $fileMoved;
    }

    /**
     * Make zip archive
     *
     * @param  string $fileName
     *
     * @return string
     */
    private function makeArchive($fileName)
    {
        $zip = new \ZipArchive;

        $zipFile = $this->collectionDir . DIRECTORY_SEPARATOR . $fileName;

        touch($zipFile);
        chmod($zipFile, 0755);

        if($zip->open($zipFile, \ZipArchive::OVERWRITE) === TRUE) {

            foreach ($this->pdfCollection as $pdf) {

                if(\File::exists($pdf)){

                    $zip->addFile($pdf, basename($pdf));
                }
            }

            $zip->close();
        }
        else{
            throw new LaratexZipFailedException('Could not generate zip file.');
        }

        return $zipFile;
    }

    /**
     * PPdf generation
     *
     * @return void
     * @throws LaratexEmptyCollectionException
     */
    private function generate(){

        if(count($this->collection) == 0){

            throw new LaratexEmptyCollectionException('No latex documents added in latex collection. Nothing to generate.');
        }

        $this->moveToCollectionDir();

        return $this;
    }

    /**
     * Move generated files to collection temp dir
     *
     * @return void
     */
    private function moveToCollectionDir(){

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
     * @return void
     */
    private function makeCollectionDir(){
        $tmpDir = sys_get_temp_dir();

        $this->collectionDir = $tmpDir . DIRECTORY_SEPARATOR .'texcollection'.Str::random(10);
        \File::makeDirectory($this->collectionDir, 0755, true, true);

        register_shutdown_function(function(){
            if(\File::exists($this->collectionDir)){
                \File::deleteDirectory($this->collectionDir);
            }
        });

        return $this;
    }
}
