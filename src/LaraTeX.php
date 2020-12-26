<?php

namespace Ismaelw\LaraTeX;

use Ismaelw\LaraTeX\LaratexException;
use Ismaelw\LaraTeX\LaratexPdfWasGenerated;
use Ismaelw\LaraTeX\LaratexPdfFailed;
use Ismaelw\LaraTeX\ViewNotFoundException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class LaraTeX
{
	/**
	 * Stub view file path
	 * @var string
	 */
    private $stubPath;

    /**
     * Data to pass to the stub
     *
     * @var array
     */
    private $data;

    /**
     * Rendered tex file
     *
     * @var string
     */
    private $renderedTex;

    /**
     * If it's a raw tex or a view file
     * @var boolean
     */
    private $isRaw = false;

    /**
     * Metadata of the generated pdf
     * @var mixed
     */
    private $metadata;

    /**
     * File Name inside Zip
     *
     * @var string
     */
    private $nameInsideZip;

	protected $binPath;
	protected $tempPath;

	/**
     * Construct the instance
     *
     * @param string $stubPath
     * @param mixed $metadata
     */
    public function __construct($stubPath = null, $metadata = null){
		$this->binPath = config('laratex.binPath');
		$this->tempPath = config('laratex.tempPath');
		if($stubPath instanceof RawTex){
            $this->isRaw = true;
            $this->renderedTex = $stubPath->getTex();
        } else {
           $this->stubPath = $stubPath;
        }
        $this->metadata = $metadata;
    }

	/**
     * Set name inside zip file
     *
     * @param  string $nameInsideZip
     *
     * @return Latex
     */
    public function setName($nameInsideZip){
        if(is_string($nameInsideZip)){
            $this->nameInsideZip = basename($nameInsideZip);
        }
        return $this;
    }

    /**
     * Get name inside zip file
     *
     * @return string
     */
    public function getName(){
        return $this->nameInsideZip;
    }

    /**
     * Set the with data
     *
     * @param  array $data
     *
     * @return Latex
     */
    public function with($data){
    	$this->data = $data;
    	return $this;
    }

	public function test() {
		return $this->binPath;
	}

    /**
     * Dry run
     *
     * @return Illuminate\Http\Response
     */
    public function dryRun(){
        $this->isRaw = true;
        $process = new Process(["which", "pdflatex"]);
        $process->run();

        // if (!$process->isSuccessful()) {
        //
        //     throw new LatextException($process->getOutput());
        // }

        $this->renderedTex = File::get(dirname(__FILE__).'/dryrun.tex');
        return $this->download('dryrun.pdf');
    }

	/**
     * Render the stub with data
     *
     * @return string
     * @throws ViewNotFoundException
     */
    public function render(){

        if($this->renderedTex){

           return $this->renderedTex;
        }

        if(!view()->exists($this->stubPath)){

            throw new ViewNotFoundException('View ' . $this->stubPath . ' not found.');
        }

    	$this->renderedTex = view($this->stubPath, $this->data)->render();

    	return $this->renderedTex;
    }

    /**
     * Save generated PDF
     *
     * @param  string $location
     *
     * @return boolean
     */
    public function savePdf($location)
    {
        $this->render();
        $pdfPath = $this->generate();
        $fileMoved = File::move($pdfPath, $location);
        \Event::dispatch(new LatexPdfWasGenerated($location, 'savepdf', $this->metadata));
        return $fileMoved;
    }

    /**
     * Download file as a response
     *
     * @param  string|null $fileName
     * @return Illuminate\Http\Response
     */
    public function download($fileName = null)
    {
        if(!$this->isRaw){
           $this->render();
        }

        $pdfPath = $this->generate();
        if(!$fileName){
            $fileName = basename($pdfPath);
        }

        \Event::dispatch(new LatexPdfWasGenerated($fileName, 'download', $this->metadata));

        return \Response::download($pdfPath, $fileName, [
              'Content-Type' => 'application/pdf',
        ]);
    }

	/**
     * Generate the PDF
     *
     * @return string
     */
    private function generate(){

    	$fileName = Str::random(10);
        $tmpfname = tempnam(storage_path($this->tempPath), $fileName);
        $tmpDir = storage_path($this->tempPath);
        chmod($tmpfname, 0755);

        File::put($tmpfname, $this->renderedTex);

        $program    = $this->binPath ? $this->binPath : 'pdflatex';
        $cmd        = [$program, '-output-directory', $tmpDir, $tmpfname];

        $process    = new Process($cmd);
        $process->run();
        if (!$process->isSuccessful()) {
            \Event::dispatch(new LatexPdfFailed($fileName, 'download', $this->metadata));
        	$this->parseError($tmpfname, $process);
        }

        $this->teardown($tmpfname);

        register_shutdown_function(function () use ($tmpfname) {

            if(File::exists($tmpfname . '.pdf')){
                File::delete($tmpfname . '.pdf');
            }
        });

        return $tmpfname.'.pdf';
    }

	/**
     * Teardown secondary files
     *
     * @param  string $tmpfname
     *
     * @return void
     */
    private function teardown($tmpfname)
    {
        if(File::exists(storage_path($this->tempPath . $tmpfname))) {
            File::delete(storage_path($this->tempPath . $tmpfname));
        }
        if(File::exists(storage_path($this->tempPath . $tmpfname . '.aux'))) {
            File::delete(storage_path($this->tempPath . $tmpfname . '.aux'));
        }
        if(File::exists(storage_path($this->tempPath . $tmpfname . '.log'))) {
            File::delete(storage_path($this->tempPath . $tmpfname . '.log'));
        }
        if(File::exists(storage_path($this->tempPath . $tmpfname . '.out'))) {
            File::delete(storage_path($this->tempPath . $tmpfname . '.out'));
        }

        return $this;
    }

	/**
     * Throw error from log gile
     *
     * @param  string $tmpfname
     *
     * @throws \LatextException
     */
    private function parseError($tmpfname, $process){

    	$logFile = storage_path($this->tempPath . $tmpfname . 'log');

    	if(!File::exists($logFile)){
    		throw new LatextException($process->getOutput());
    	}

    	$error = File::get($logFile);
    	throw new LatextException($error);
    }

}
