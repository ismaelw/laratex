[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]

<br />
<p align="center">
  <a href="https://github.com/ismaelw/laratex">
    <img alt="Laratex" src="laratex.png" width="600">
  </a>

  <h3 align="center">LaraTeX</h3>

  <p align="center">
    A laravel package to generate PDFs using LaTeX
    <br />
    <br />
    ·
    <a href="https://github.com/ismaelw/laratex/issues">Report Bug</a>
    ·
    <a href="https://github.com/ismaelw/laratex/issues">Request Feature</a>
  </p>
  <p align="center">
    For better visualization you can find a small <strong>Demo</strong> and the <strong>HTML to LaTeX converter</strong> <a href="https://laratest.wismann.ch">here</a>.
  </p>
</p>

<details open="open">
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
        <li><a href="#configuration">Configuration</a></li>
      </ul>
    </li>
    <li>
        <a href="#usage">Usage</a>
        <ul>
            <li><a href="#dry-run">Dry Run</a></li>
            <li><a href="#preparing-a-laravel-view-with-latex-content">Preparing a Laravel View with LaTeX Content</a></li>
            <li><a href="#using-the-blade-directive">Using the blade directive</a></li>
            <li><a href="#using-graphics-inside-of-your-latex-files">Using graphics inside of your LaTeX files</a></li>
            <li><a href="#download-a-pdf-file">Download a PDF File</a></li>
            <li><a href="#save-a-pdf-file">Save a PDF file</a></li>
            <li><a href="#return-the-pdf-content">Return the PDF content</a></li>
            <li><a href="#return-the-pdf-inline">Return the PDF inline</a></li>
            <li><a href="#return-the-tex-data">Return the TeX data</a></li>
            <li><a href="#using-raw-tex">Using Raw TeX</a></li>
            <li><a href="#compile-multiple-times">Compile multiple times</a></li>
            <li><a href="#compile-using-bibtex">Compile using bibtex</a></li>
            <li><a href="#bulk-download-in-a-zip-archive">Bulk download in a ZIP archive</a></li>
        </ul>
    </li>
    <li><a href="#convert-html-to-latex-beta">Convert HTML to LaTeX BETA</a></li>
    <li><a href="#garbage-collection">Garbage Collection</a></li>
    <li><a href="#error-handling">Error Handling</a></li>
    <li><a href="#contribution">Contribution</a></li>
    <li><a href="#credits">Credits</a></li>
    <li><a href="#changelog">Changelog</a></li>
    <li><a href="#license">License</a></li>
  </ol>
</details>

## Getting Started

### Important information about your environment

This package was developed and tested on Unix (FreeBSD) servers and has been tested successfully on a Windows machine both running pdflatex.
Always make sure to write your paths correctly :)

This package makes use of the `storage_path()` function. On Windows it is possible that the absolute path will be written out with backslashes.
Windows is really good with paths using both forward & backslashes but just keep this in mind if something doesn't work that well on windows.

### Prerequisites

You need to have `texlive-full` installed on your server. This program has tex packages and language libraries which help you generate documents.
Note: You can also choose to install `textlive` which is the lighter version of the package.

The difference is:

-   When you install `textlive` and want to use any additional tex package, you need to install it manually.
-   `texlive-full` comes with these extra packages. As a result it may take up some additional space on your server (to store the package library files).

If you are choosing a hosting provider that doesn't allow you to install applications yourself please make sure that pdflatex, xelatex or lualatex is installed or ask if it can get installed. Also make sure that you have SSH access to the server as you might need it to find out in which path your pdflatex installation is sitting.

### Installation

You can install the package with composer:

```bash
composer require ismaelw/laratex
```

### Configuration

To load the config file with php artisan run the following command:

```bash
php artisan vendor:publish --tag=config
```

After this please make sure to configure your LaraTeX installation.
In your LaraTeX Config file `\config\laratex.php` you can configure two settings:

**binPath**
If your system doesn't allow to just run the command line command "pdflatex" you may specify the correct one.
On Unix systems you can find out which bin path to use by running the command `which pdflatex`

If you are running this package with on a windows system please check this in cmd.exe before.
There you should find out if running the command `pdflatex` works in cmd or if you need to provide the absolute path to your pdflatex application.
If you cannot just run pdflatex you might have to add the path to your pdflatex compiler in your PATH system environment variables.

**bibTexPath**
If your system doesn't allow to just run the command line command "bibtex" you may specify the correct one.
On Unix systems you can find out which bin path to use by running the command `which bibtex`

If you are running this package with on a windows system please check this in cmd.exe before.
There you should find out if running the command `bibtex` works in cmd or if you need to provide the absolute path to your bibtex application.
If you cannot just run bibtex you might have to add the path to your bibtex compiler in your PATH system environment variables.

**tempPath**
This specifies the folder where temporary files are saved while rendering a tex file into a PDF file.
It is important that you always **start your path without a slash** and **end your path with a slash** (e.g. app/pdf/)

**teardown**
As seen in the section Garbage Collection this package deletes all temp files (log, aux etc.) created while generating the PDF file. When debugging successfully generated PDF files it can be useful to check the generated tex file.
Set this setting to `false` if you don't want LaraTeX to delete those files after generating the PDF.

## Usage

### Dry Run

Before diving into the usage directly, it is important that you make sure that the required programs are installed properly on your server. The package comes with a dryrun method. It will automatically generate a file called `dryrun.pdf` if everything is set up properly on the server. If not please double-check the configuration of the `binPath` above.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Ismaelw\LaraTeX\LaraTeX;

class TestController extends Controller
{
    /**
     * Download PDF generated from latex
     *
     * @return Illuminate\Http\Response
     */
    public function download(){
        return (new LaraTeX)->dryRun();
    }
}
```

Dryrun will download a beautifully clean test pdf if pdflatex is setup properly.

<p align="center">
    <img alt="dryrun.pdf sample" src="dryrun.png" width="450">
</p>

With this package you have multiple options. You can render a PDF file and download it directly, save it somewhere, just get the tex content or bulk download a ZIP file containing multiple generated PDF files.

### Preparing a Laravel View with LaTeX Content

Create a view file inside `resources/views/latex/tex.blade.php`
You are of course free to create your view files wherever you want inside of your resources folder.
Just make sure to define the view to use correctly later.

```php
\documentclass[a4paper,9pt,landscape]{article}
\usepackage{adjustbox}
\usepackage[english]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage[svgnames,table]{xcolor}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{blindtext}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\fancyhead{}
\renewcommand{\headrulewidth}{0pt}
\fancyfoot{}
\fancyfoot[LE,RO]{\thepage}

\fancyfoot[C]{\fontsize{8pt}{8pt}\selectfont The above document is auto-generated.}
\renewcommand{\footrulewidth}{0.2pt}

\begin{document}

    \section*{\centering{LaraTeX Demo Document}}
    
    \begin{center}
        \item[Name :] {{ $Name }}
        \item[Date of Birth :] {{ $Dob }}
    \end{center}
    
    \blindtext
    
    \begin{table}[ht]
        \centering
        \renewcommand{\arraystretch}{2}
        \begin{tabular}{|c|l|} 
             \hline
             \rowcolor[HTML]{E3E3E3}
             \textbf{ID} & \textbf{Language} \\
             \hline\renewcommand{\arraystretch}{1.5}
             
             @foreach($languages as $key => $language)
                {{ $key }} & {{ $language }} \\ \hline
             @endforeach
             
        \end{tabular}
        \caption{Language Summary}
    \end{table}
    
    \begin{center}
        {!! $SpecialCharacters !!}
    \end{center}

\end{document}
```

You can see how we have easily used blade directives for `{{ $name }}` to show a name or `@foreach` to show languages in a table to dynamically generate the content.

For more complex LaTeX files where you may need to use blade directives like `{{ $var }}` inside of a LaTeX command which already uses curly brackets (e.g. `\textbf{}`) you can always use Laravels `@php @endphp` method or plain PHP like `<?php echo $var; ?>` or `<?= $var ?>` (Example: `\textbf{<?= $var ?>}`).

As an addition there is also a `@latex()` Blade directive mentioned in the next chapter.

**Important note when using html characters**

When using the `{{ }}` statement in a blade template, Laravel's blade engine always sends data through the PHP function `htmlspecialchars()` first. This will convert characters like `&` to `&amp;` and `<` to `&lt;` to just mention a few. pdflatex doesn't like those converted string and will throw an error like `Misplaced alignment tab character &.`.

To fix this issue you have to use the `{!! !!}` statement so that unescaped text is written to your tex template.

### Using the Blade directive

Since LaTeX has its own syntax it is not advised to use the standard blade syntax `{{ $variable }}` or `{!! $variable !!}`. Instead you can use `@latex($variable)` in your blade templates instead, which handles the suitable escaping of reserved LaTeX characters.

Create a view file inside `resources/views/latex/tex.blade.php`
You are of course free to create your view files wherever you want inside of your resources folder.
Just make sure to define the view to use correctly later.

```php
\documentclass[a4paper,9pt,landscape]{article}
\usepackage{adjustbox}
\usepackage[english]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage[svgnames,table]{xcolor}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{blindtext}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\fancyhead{}
\renewcommand{\headrulewidth}{0pt}
\fancyfoot{}
\fancyfoot[LE,RO]{\thepage}

\fancyfoot[C]{\fontsize{8pt}{8pt}\selectfont The above document is auto-generated.}
\renewcommand{\footrulewidth}{0.2pt}

\begin{document}

    \section*{\centering{LaraTeX Demo Document}}
    
    \begin{center}
        \item[Name :] @latex($Name)
        \item[Date of Birth :] @latex($Dob)
    \end{center}
    
    \blindtext
    
    \begin{table}[ht]
        \centering
        \renewcommand{\arraystretch}{2}
        \begin{tabular}{|c|l|} 
             \hline
             \rowcolor[HTML]{E3E3E3}
             \textbf{ID} & \textbf{Language} \\
             \hline\renewcommand{\arraystretch}{1.5}
             
             @foreach($languages as $key => $language)
                @latex($key) & @latex($language) \\ \hline
             @endforeach
             
        \end{tabular}
        \caption{Language Summary}
    \end{table}

\end{document}
```

You can see how we have easily used the `@latex()` Blade directive to print variables.

### Using graphics inside of your LaTeX files

Where exactly pdflatex looks for graphics included inside of a .tex file I am not really sure.
What helped me the most was to always give the absolute path to a graphic like `\includegraphics[scale=0.06]{/absolute/path/to/www/storage/graphics/file.pdf}` for example.
If you have a better working idea please help me and share your knowledge with me :)

### Download a PDF file

```php
download(string $fileName = null)
```

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Ismaelw\LaraTeX\LaraTeX;

class TestController extends Controller
{
    /**
     * Download PDF generated from LaTex
     *
     * @return Illuminate\Http\Response
     */
    public function download(){

        return (new LaraTeX('latex.tex'))->with([
            'Name' => 'John Doe',
            'Dob' => '01/01/1990',
            'SpecialCharacters' => '$ (a < b) $',
            'languages' => [
                'English',
                'Spanish',
                'Italian'
            ]
        ])->download('test.pdf');
    }
}
```

If you named your blade file differently or you have it in another folder make sure to set the blade file correctly:
`return (new LaraTeX('folder.file'))`

### Save a PDF file

To save a PDF File use the `savePdf` Method.

```php
savePdf(string $location)
```

```php
(new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->savePdf(storage_path('app/export/test.pdf'));
```

Make sure that the destination folder exists inside of your `storage` folder.

### Return the PDF content

To just get the pdf content as RAW or base64 use the `content` Method.

```php
content(string $type = 'raw')
```

The default is `raw`.

```php
(new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->content();
```

or with base64:

```php
(new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->content('base64');
```

### Return the PDF inline

To just get the PDF inline use the `inline` Method.

```php
inline(string $fileName = null)
```

```php
(new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->inline('filename.pdf');
```

This will return the pdf as an inline document stream shown as `filename.pdf`.

### Return the TeX data

```php
render()
```

```php
$tex = new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->render();
```

### Using Raw TeX

If you do not want to use views as tex files, but already have tex content, or are using other libraries to generate tex content, you can use `RawTex` class instead of passing a view path :

```php
use Ismaelw\LaraTeX\LaraTeX;
use Ismaelw\LaraTeX\RawTex;

...

$tex = new RawTex('your_raw_tex_content_string.....');

return (new LaraTeX($tex))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->download('test.pdf');
```

### Compile multiple times

There are a few cases in which it is necessary to compile twice. If you are using a table of contents (TOC) for example, or if you use the package `lastpage` to get a better pagination (`Page n of n`) as another example.

LaraTeX compiles once as a default. If you need to compile twice (or - for whatever reason more than twice) you can use the method `compileAmount()` to achieve this.

```php
return (new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->compileAmount(2)->download('test.pdf');
```

### Compile using bibtex

If you want to use `bibtex`, please make sure that you have the `bibTexPath` property set correctly inside your `laratex.php` config file. 

```php
return (new LaraTeX('latex.tex'))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->renderBibtex()->compileAmount(3)->download('test.pdf');
```

### Bulk download in a ZIP archive

You want to export multiple PDFs inside of a ZIP-Archive? This package has that functionality ready for you. This gives a great flexibility for you. However, make sure you are not passing too many PDFs together, as it is going to consume a good amount of server memory to export those together.

```php
$latexCollection = (new LaratexCollection());
$users = User::limit(10)->get();
foreach ($users as $user) {
    $pdfName = $user->first_name.'-'.$user->last_name.'-'.$user->id.'.pdf';

    // Create LaraTeX instance
    $laratex= (new LaraTeX('latex.report'))->with([
        'user' => $user
    ])->setName($pdfName);

    // Add it to latex collection
    $latexCollection->add($laratex);
}

// Download the zip
return $latexCollection->downloadZip('Users.zip');

// OR you can also save it
$latexCollection->saveZip(storage_path('app/pdf/zips/Users.zip'));
```

## Convert HTML to LaTeX BETA

```php
convertHtmlToLatex(string $Input, array $Override = NULL)
```

As I already had a case where the data sent to the latex view was in HTML format I decided to add a parser that converts basic HTML strings to LaTeX.
Included is a set of HTML Tags and how they should get converted. **Note: At the end of the conversion, all HTML Tags that are not in the default conversion set nor in the override conversion set will be removed with `strip_tags()`.**

If you need the functionality but need a certain HTML Tag converted differently, you can send an override array to the method.
This override array needs to look like this:

```php
    $Override = array(
        array('tag' => 'img', 'extract' => 'src', 'replace' => '\begin{center}\includegraphics[scale=1]{$1}\end{center}'),
        array('tag' => 'body', 'extract' => 'value', 'replace' => '$1 \newline '),
    );
```

Explanation for the array keys:

|Key|Value|
|-|-|
|tag|The HTML Tag to look for|
|extract|Which data to extract from the HTML Dom Node (Possible values: value, src - value would be the innerHTML and src would be the src attribute)|
|replace|The string with which the HTML Tag wrapping gets replaced. **Note: Always use `$1` as the placeholder for the extracted value**|

The next code snippet shows how the process for the conversion works:

```php
    $HTMLString = '<h1>Heading 1</h1> <p>Text</p> <h2>Heading 2</h2> <p>Text</p> <h3>Heading 3</h3> <p>Text</p> <p>Normal text here with some <strong>strong</strong> and <strong>bold</strong> text.</p> <p>There is also text that could be <u>underlined</u>.</p> <p>Or of course we could have <em>em-wrapped</em> or <em>i-wrapped</em> text</p> <p>A special test could be a <u><em><strong>bold, underlined and italic</strong></em></u> text at the same time!</p> <p>For the mathematicians we also have calculations x<sup>2</sup> and chemical stuff H<sub>2</sub>O</p> <p>We also have lists that needs to be shown. For example an unordered and an ordered list.</p> <p>If there is alot of text we might also want to use a line break <br> to continue on the next line.</p> <ul> <li>UL Item 1 <ul> <li>UL Item 1.1</li> <li>UL Item 1.2</li> </ul> </li> <li>UL Item 2</li> <li>UL Item 3</li> </ul> <ol> <li>UL Item 1</li> <li>UL Item 2</li> <li>UL Item 3</li> </ol> <p>Last but not least. We have images.</p> <img src="/images/testimages/image1.png" /> <img src="/images/testimages/image2.png" >';

    $Override = array(
        array('tag' => 'img', 'extract' => 'src', 'replace' => '\begin{center}\includegraphics[scale=1]{$1}\end{center}'),
        array('tag' => 'body', 'extract' => 'value', 'replace' => '$1 \newline '),
    );

    $LatexString = (new LaraTeX)->convertHtmlToLatex($HTMLString, $Override);

```

This example would return the following LaTeX String:

```TeX
\section{Heading 1} Text \newline \subsection{Heading 2} Text \newline \subsubsection{Heading 3} Text \newline Normal text here with some \textbf{strong} and \textbf{bold} text. \newline There is also text that could be \underline{underlined}. \newline Or of course we could have \textit{em-wrapped} or \textit{i-wrapped} text \newline A special test could be a \underline{\textit{\textbf{bold, underlined and italic}}} text at the same time! \newline For the mathematicians we also have calculations x\textsuperscript{2} and chemical stuff H\textsubscript{2}O \newline We also have lists that needs to be shown. For example an unordered and an ordered list. \newline If there is alot of text we might also want to use a line break \newline to continue on the next line. The br tag can have a leading slash too. \newline \begin{itemize} \item UL Item 1 \begin{itemize} \item UL Item 1.1 \item UL Item 1.2 \end{itemize} \item UL Item 2 \item UL Item 3 \end{itemize} \begin{enumerate} \item UL Item 1 \item UL Item 2 \item UL Item 3 \end{enumerate} Last but not least. We have images. \newline \begin{center}\includegraphics[scale=1]{/images/testimages/image1.png}\end{center} \begin{center}\includegraphics[scale=1]{/images/testimages/image2.png}\end{center} \newline
```

## Listening to events

Whenever a pdf is succesfully generated, it fires the event `LaratexPdfWasGenerated`. Similarly whenever the PDF generation fails it fires the event `LaratexPdfFailed`.

These events are important if you need to perform some actions depending on the generation status, like updating the database. But mostly these PDF files have some metadata like the user the PDF belongs to or to which order the PDF belongs. You can pass these metadata while instantiating `LaraTeX` as a second argument.

This metadata is then passed back to you from the fired event, which makes it much more meaningful to listen. The metadata can be anything, it can be a string, numeric, an array, an object, a collection and so on. You can pass the metadata depending on your desired logic.

```php
// $user will be our metadata in this example
$user = Auth::user();

(new LaraTeX('latex.tex', $user))->with([
    'Name' => 'John Doe',
    'Dob' => '01/01/1990',
    'SpecialCharacters' => '$ (a < b) $',
    'languages' => [
        'English',
        'Spanish',
        'Italian'
    ]
])->savePdf(storage_path('app/pdf/test.pdf'));
```

Then you can define a listener like :

```php
<?php

namespace App\Listeners;

use Ismaelw\LaraTeX\LaratexPdfWasGenerated;

class LaratexPdfWasGeneratedConfirmation
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LatexPdfWasGenerated  $event
     * @return void
     */
    public function handle(LaratexPdfWasGenerated$event)
    {
        // Path of PDF in case it was saved
        // OR
        // Downloaded name of PDF file in case it was downloaded in response directly
        $pdf = $event->pdf;

        // download OR savepdf
        $action = $event->action;

        // metadata $user in this example
        $user = $event->metadata;

        // Perform desired actions
    }
}
```

## Garbage Collection

When you export the PDF, a few extra files are generated by `pdflatex` along with your PDF (e.g. `.aux`, `.log`, `.out` etc.). The package takes care of the garbage collection process internally. It makes sure that no files are remaining when the PDF is generated or even when any error occures.

This makes sure the server does not waste it's space keeping those files.

## Error Handling

We are using the application `pdflatex` from `texlive` to generate PDFs. If a syntax error occures in your tex file, it logs the error into a log file. Or if it is turned off, it shows the output in the console.

The package takes care of the same logic internally and throws `ViewNotFoundException`. The exception will have the entire information about the error easily available for you to debug.

## Contribution

Please feel free to contribute if you want to add new functionalities to this package.

## Credits

This Package was inspired alot by the `laravel-php-latex` package created by [Techsemicolon](https://github.com/techsemicolon/laravel-php-latex)
Later I started my own version of `laravel-php-latex` [ismaelw/laravel-php-latex](https://github.com/ismaelw/laravel-php-latex) because of missing support on the other package.

For better compatibility and better configuration handling I decided to create this package.

Thanks to the contribution of [@koona-labs](https://github.com/koona-labs) you can now use Blade directives.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about any major change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[contributors-shield]: https://img.shields.io/github/contributors/ismaelw/laratex.svg?style=for-the-badge
[contributors-url]: https://github.com/ismaelw/laratex/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/ismaelw/laratex.svg?style=for-the-badge
[forks-url]: https://github.com/ismaelw/laratex/network/members
[stars-shield]: https://img.shields.io/github/stars/ismaelw/laratex.svg?style=for-the-badge
[stars-url]: https://github.com/ismaelw/laratex/stargazers
[issues-shield]: https://img.shields.io/github/issues/ismaelw/laratex.svg?style=for-the-badge
[issues-url]: https://github.com/ismaelw/laratex/issues
[license-shield]: https://img.shields.io/github/license/ismaelw/laratex.svg?style=for-the-badge
[license-url]: https://github.com/ismaelw/laratex/blob/master/LICENSE.md
