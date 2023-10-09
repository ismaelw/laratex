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

    /**
     * Number of times to compile the TeX file. (for TOC generation for example)
     *
     * @var integer
     */
    private $compileAmount = 1;

    /**
     * Should we run BibTeX before generating?
     */
    public bool $generateBibtex = false;


    protected $binPath;
    protected $bibTexPath;
    protected $tempPath;
    protected $doTeardown = true;

    /**
     * Construct the instance
     *
     * @param string $stubPath
     * @param mixed $metadata
     */
    public function __construct($stubPath = null, $metadata = null)
    {
        $this->binPath = config('laratex.binPath');
        $this->tempPath = config('laratex.tempPath');
        $this->bibTexPath = config('laratex.bibTexPath');
        $this->doTeardown = config('laratex.teardown');
        if ($stubPath instanceof RawTex) {
            $this->isRaw = true;
            $this->renderedTex = $stubPath->getTex();
        } else {
            $this->stubPath = $stubPath;
        }
        $this->metadata = $metadata;
    }

    /**
     * Set the number of times to compile
     *
     * @param  integer $compileAmount
     *
     * @return LaraTeX
     */
    public function compileAmount($compileAmount){

        if(is_integer($compileAmount)){
            $this->compileAmount = $compileAmount;
        }

        return $this;
    }

    public function renderBibtex()
    {
        $this->generateBibtex = true;
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

        for ($i = 1; $i <= $this->compileAmount; $i++) {

            // BibTeX must be run after the first generation of the LaTeX file.
            if ($i === 2 && $this->generateBibtex) {
                $bibtex = new Process([$this->bibTexPath, basename($tmpfname)], $tmpDir);
                $bibtex->run();
            }

            $process = new Process($cmd);
            $process->run();
            if (!$process->isSuccessful()) {
                \Event::dispatch(new LaratexPdfFailed($fileName, 'download', $this->metadata));
                $this->parseError($tmpfname, $process);
            }
        }

        if ($this->doTeardown) {
            $this->teardown($tmpfname);
        }

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

        $extensions = ['aux', 'log', 'out', 'bbl', 'blg', 'toc', 'tex'];

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
        $logFile = $tmpfname . '.log';
        $texFileNoExtension = $tmpfname;
        $texFileExtension = $tmpfname . '.tex';

        if (!File::exists($logFile)) {
            throw new LaratexException($process->getErrorOutput() . ' - ' . $process->getOutput());
        }

        if (File::exists($texFileNoExtension)) {
            $texFileContent = File::get($texFileNoExtension);
        } elseif (File::exists($texFileExtension)) {
            $texFileContent = File::get($texFileExtension);
        } else {
            $texFileContent = 'Tex file not found';
        }

        $error = File::get($logFile);
        
        throw LaratexException::detailed($error, $texFileContent);
    }

    /**
     * Encodes speical characters inside of a HTML String
     *
     * @param $HTMLString
     * @param $ENT
     * 
     */
    private function htmlEntitiesFix($HTMLString, $ENT)
    {
        $Matches = array();
        $Separator = '###UNIQUEHTMLTAG###';

        preg_match_all(":</{0,1}[a-z]+[^>]*>:i", $HTMLString, $Matches);

        $Temp = preg_replace(":</{0,1}[a-z]+[^>]*>:i", $Separator, $HTMLString);
        $Temp = explode($Separator, $Temp);

        for ($i = 0; $i < count($Temp); $i++)
            $Temp[$i] = htmlentities($Temp[$i], $ENT, 'UTF-8', false);

        $Temp = join($Separator, $Temp);

        for ($i = 0; $i < count($Matches[0]); $i++)
            $Temp = preg_replace(":$Separator:", $Matches[0][$i], $Temp, 1);

        return $Temp;
    }

    /**
     * Convert HTML String to LaTeX String
     *
     * @param string $Input
     * @param array $override
     * 
     */
    public function convertHtmlToLatex(string $Input, array $Override = NULL)
    {
        $Input = $this->htmlEntitiesFix($Input, ENT_QUOTES | ENT_HTML401);

        $ReplaceDictionary = array(
            array('tag' => 'p', 'extract' => 'value', 'replace' => '$1 \newline '),
            array('tag' => 'b', 'extract' => 'value', 'replace' => '\textbf{$1}'),
            array('tag' => 'strong', 'extract' => 'value', 'replace' => '\textbf{$1}'),
            array('tag' => 'i', 'extract' => 'value', 'replace' => '\textit{$1}'),
            array('tag' => 'em', 'extract' => 'value', 'replace' => '\textit{$1}'),
            array('tag' => 'u', 'extract' => 'value', 'replace' => '\underline{$1}'),
            array('tag' => 'ins', 'extract' => 'value', 'replace' => '\underline{$1}'),
            array('tag' => 'br', 'extract' => 'value', 'replace' => '\newline '),
            array('tag' => 'sup', 'extract' => 'value', 'replace' => '\textsuperscript{$1}'),
            array('tag' => 'sub', 'extract' => 'value', 'replace' => '\textsubscript{$1}'),
            array('tag' => 'h1', 'extract' => 'value', 'replace' => '\section{$1}'),
            array('tag' => 'h2', 'extract' => 'value', 'replace' => '\subsection{$1}'),
            array('tag' => 'h3', 'extract' => 'value', 'replace' => '\subsubsection{$1}'),
            array('tag' => 'h4', 'extract' => 'value', 'replace' => '\paragraph{$1} \mbox{} \\\\'),
            array('tag' => 'h5', 'extract' => 'value', 'replace' => '\subparagraph{$1} \mbox{} \\\\'),
            array('tag' => 'h6', 'extract' => 'value', 'replace' => '\subparagraph{$1} \mbox{} \\\\'),
            array('tag' => 'li', 'extract' => 'value', 'replace' => '\item $1'),
            array('tag' => 'ul', 'extract' => 'value', 'replace' => '\begin{itemize}$1\end{itemize}'),
            array('tag' => 'ol', 'extract' => 'value', 'replace' => '\begin{enumerate}$1\end{enumerate}'),
            array('tag' => 'img', 'extract' => 'src', 'replace' => '\includegraphics[scale=1]{$1}'),
        );

        if (isset($Override)) {
            foreach ($Override as $OverrideArray) {
                $FindExistingTag = array_search($OverrideArray['tag'], array_column($ReplaceDictionary, 'tag'));
                if ($FindExistingTag !== false) {
                    $ReplaceDictionary[$FindExistingTag] = $OverrideArray;
                } else {
                    array_push($ReplaceDictionary, $OverrideArray);
                }
            }
        }

        libxml_use_internal_errors(true);
        $Dom = new \DOMDocument();
        $Dom->loadHTML($Input);

        $AllTags = $Dom->getElementsByTagName('*');
        $AllTagsLength = $AllTags->length;

        for ($i = $AllTagsLength - 1; $i > -1; $i--) {
            $CurrentTag = $AllTags->item($i);
            $CurrentReplaceItem = array_search($CurrentTag->nodeName, array_column($ReplaceDictionary, 'tag'));

            if ($CurrentReplaceItem !== false) {
                $CurrentReplace = $ReplaceDictionary[$CurrentReplaceItem];

                switch ($CurrentReplace['extract']) {
                    case 'value':
                        $ExtractValue = $CurrentTag->nodeValue;
                        break;
                    case 'src':
                        $ExtractValue = $CurrentTag->getAttribute('src');
                        break;
                    default:
                        $ExtractValue = "";
                }

                $NewNode = $Dom->createElement('div', str_replace('$1', $ExtractValue, $CurrentReplace['replace']));
                $CurrentTag->parentNode->replaceChild($NewNode, $CurrentTag);
            }
        }

        return html_entity_decode(strip_tags($Dom->saveHTML()));
    }
}
