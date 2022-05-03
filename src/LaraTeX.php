<?php

namespace Ismaelw\LaraTeX;

use Ismaelw\LaraTeX\LaratexException;
use Ismaelw\LaraTeX\LaratexPdfFailed;
use Ismaelw\LaraTeX\LaratexPdfWasGenerated;
use Ismaelw\LaraTeX\ViewNotFoundException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class LaraTeX
{
    /**
     * Should we re-render to general bibtex?
     * @var bool
     */
    public bool $generateBibtex = false;

    /**
     * Should we re-render for TOC?
     * @var bool
     */
    public bool $reRender = false;

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
    protected $bibTexPath;
    protected $tempPath;

    /**
     * Construct the instance
     *
     * @param string $stubPath
     * @param mixed $metadata
     */
    public function __construct($stubPath = null, $metadata = null)
    {
        $this->binPath = config('laratex.binPath');
        $this->bibTexPath = config('laratex.bibTexPath');
        $this->tempPath = config('laratex.tempPath');
        if ($stubPath instanceof RawTex) {
            $this->isRaw = true;
            $this->renderedTex = $stubPath->getTex();
        } else {
            $this->stubPath = $stubPath;
        }
        $this->metadata = $metadata;
    }

    public function renderBibtex()
    {
        $this->generateBibtex = true;
        return $this;
    }

    public function renderTOC()
    {
        $this->reRender = true;
        return $this;
    }

    /**
     * Set name inside zip file
     *
     * @param  string $nameInsideZip
     *
     * @return LaraTeX
     */
    public function setName($nameInsideZip)
    {
        if (is_string($nameInsideZip)) {
            $this->nameInsideZip = basename($nameInsideZip);
        }
        return $this;
    }

    /**
     * Get name inside zip file
     *
     * @return string
     */
    public function getName()
    {
        return $this->nameInsideZip;
    }

    /**
     * Set the with data
     *
     * @param  array $data
     *
     * @return LaraTeX
     */
    public function with($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Dry run
     *
     * @return Illuminate\Http\Response
     */
    public function dryRun()
    {
        $this->isRaw = true;
        $process = new Process(["which", "pdflatex"]);
        $process->run();

        // if (!$process->isSuccessful()) {
        //
        //     throw new LaratexException($process->getOutput());
        // }

        $this->renderedTex = File::get(dirname(__FILE__) . '/dryrun.tex');
        return $this->download('dryrun.pdf');
    }

    /**
     * Render the stub with data
     *
     * @return string
     * @throws ViewNotFoundException
     */
    public function render()
    {

        if ($this->renderedTex) {

            return $this->renderedTex;
        }

        if (!view()->exists($this->stubPath)) {

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
        \Event::dispatch(new LaratexPdfWasGenerated($location, 'savepdf', $this->metadata));
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
        if (!$this->isRaw) {
            $this->render();
        }

        $pdfPath = $this->generate();
        if (!$fileName) {
            $fileName = basename($pdfPath);
        }

        \Event::dispatch(new LaratexPdfWasGenerated($fileName, 'download', $this->metadata));

        return \Response::download($pdfPath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Get the file as a inline response
     *
     * @param  string|null $fileName
     * @return Illuminate\Http\Response
     */
    public function inline($fileName = null)
    {
        if (!$this->isRaw) {
            $this->render();
        }

        $pdfPath = $this->generate();
        if (!$fileName) {
            $fileName = basename($pdfPath);
        }

        \Event::dispatch(new LaratexPdfWasGenerated($fileName, 'inline', $this->metadata));

        return \Response::file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    /**
     * Get the content of the file
     *
     * @param  string|null $fileName
     * @return Illuminate\Http\Response
     */
    public function content($type = 'raw')
    {
        if ($type == 'raw' || $type == 'base64') {
            if (!$this->isRaw) {
                $this->render();
            }

            $pdfPath = $this->generate();
            $fileName = basename($pdfPath);

            \Event::dispatch(new LaratexPdfWasGenerated($fileName, 'content', $this->metadata));

            if ($type == 'raw') {
                $pdfContent = file_get_contents($pdfPath);
            } elseif ($type == 'base64') {
                $pdfContent = chunk_split(base64_encode(file_get_contents($pdfPath)));
            }

            return $pdfContent;
        } else {
            \Event::dispatch(new LaratexPdfFailed($fileName, 'content', 'Wrong type set'));
            return response()->json(['message' => 'Wrong type set. Use raw or base64.'], 400);
        }
    }

    /**
     * Generate the PDF
     *
     * @return string
     */
    private function generate()
    {
        $fileName = Str::random(10);
        $basetmpfname = tempnam(storage_path($this->tempPath), $fileName);
        $tmpfname = preg_replace('/\\.[^.\\s]{3,4}$/', '', $basetmpfname);
        rename($basetmpfname, $tmpfname);
        $tmpDir = storage_path($this->tempPath);
        chmod($tmpfname, 0755);

        File::put($tmpfname, $this->renderedTex);

        $program    = $this->binPath ? $this->binPath : 'pdflatex';
        $cmd        = [$program, '-output-directory', $tmpDir, $tmpfname];

        $process    = new Process($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            \Event::dispatch(new LaratexPdfFailed($fileName, 'download', $this->metadata));
            $this->parseError($tmpfname, $process);
        }

        if ($this->generateBibtex) {
            $bibtex = new Process([$this->bibTexPath, basename($tmpfname)], $tmpDir);
            $bibtex->run();

            $reProcess = new Process($cmd);
            $reProcess->run();
        }

        if ($this->reRender || $this->generateBibtex) {
            $finalProcess = new Process($cmd);
            $finalProcess->run();
        }

        $this->teardown($tmpfname);

        register_shutdown_function(function () use ($tmpfname) {
            if (File::exists($tmpfname . '.pdf')) {
                File::delete($tmpfname . '.pdf');
            }
        });

        return $tmpfname . '.pdf';
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
        if (File::exists($tmpfname)) {
            File::delete($tmpfname);
        }

        $extensions = ['aux', 'log', 'out', 'bbl', 'blg', 'toc'];

        foreach ($extensions as $extension) {
            if (File::exists($tmpfname . '.' . $extension)) {
                File::delete($tmpfname . '.' . $extension);
            }
        }

        return $this;
    }

    /**
     * Throw error from log file
     *
     * @param  string $tmpfname
     *
     * @throws \LaratexException
     */
    private function parseError($tmpfname, $process)
    {

        $logFile = $tmpfname . 'log';

        if (!File::exists($logFile)) {
            throw new LaratexException($process->getOutput());
        }

        $error = File::get($logFile);
        throw new LaratexException($error);
    }
}
