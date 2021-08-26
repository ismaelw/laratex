# A laravel package to generate pdfs using latex

<p align="center">
    <img alt="Laratex" src="laratex.jpg">
</p>

## NOTE

This package was tested in two different environments while using the package for those two special processes.
If you experience any issues in all the time you are using it please open an issue so I can make this package better with every update :)  

## Important information about your environment

This package was developed and tested on Unix (FreeBSD) servers and has been tested successfully on a Windows machine both running pdflatex.
Always make sure to write your paths correctly :)

This package makes use of the `storage_path()` function. On Windows it is possible that the absolute path will be written out with backslashes.
Windows is really good with paths using both forward & backslashes but just keep this in mind if something doesn't work that well on windows.

## Pre-requisites :

You need to have `texlive-full` installed on your server. This program has tex packages and language libraries which help you generate documents.
Note : You can also choose to install `textlive` which is the lighter version of the package.

The difference is:

-   When you install `textlive` and want to use any additional tex package, you need to install it manually.
-   `texlive-full` comes with these extra packages. As a result it may take up some additional space on your server (to store the package library files).

If you are choosing a hosting provider that doesn't allow you to install applications yourself please make sure that pdflatex is installed or ask if it can get installed. Also make sure that you have SSH access to the server as you might need it to find out in which path your pdflatex installation is sitting.

## Installation

You can install the package with composer:

```bash
composer require ismaelw/laratex
```

## Configuration

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

**tempPath**
This specifies the folder where temporary files are saved while rendering a tex file into a PDF file.
It is important that you always **start your path without a slash** and **end your path with a slash** (e.g. app/pdf/)

## Using graphics inside of your LaTeX files

Where exactly pdflatex looks for graphics included inside of a .tex file I am not really sure.
What helped me the most was to always give the absolute path to a graphic like `\includegraphics[scale=0.06]{/absolute/path/to/www/storage/graphics/file.pdf}` for example.
If you have a better working idea please help me and share your knowledge with me :)

## Dry Run :

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

## Usage

With this package you have multiple options. You can render a PDF file and download it directly, save it somewhere, just get the tex content or bulk download a ZIP file containing multiple generated PDF files.

### Preparing a Laravel View with our LaTeX Content

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

You can see how we have easily used blade directives for `{{ $name }}` to show a name or `@foreach` to show addresses in a table to dynamically generate the content.

For more complex LaTeX files where you may need to use blade directives like `{{ $var }}` inside of a LaTeX command which already uses curly brackets (e.g. `\textbf{}`) you can always use Laravels `@php @endphp` method or plain PHP like `<?php echo $var; ?>` or `<?= $var ?>` (Example: `\textbf{<?= $var ?>}`).

**Important note when using html characters**

When using the `{{ }}` statement in a blade template, Laravel's blade engine always sends data through the PHP function `htmlspecialchars()` first. This will convert characters like `&` to `&amp;` and `<` to `&lt;` to just mention a few. pdflatex doesn't like those converted string and will throw an error like `Misplaced alignment tab character &.`.

To fix this issue you have to use the `{!! !!}` statement so that unescaped text is written to your tex template.


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

### Just get the PDF content

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

### Get the PDF inline

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

### Just render the tex data

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

### Using Raw Tex :

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

### Bulk download in a ZIP archive :

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

## Listening to events :

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
        // Path  of pdf in case in was saved
        // OR
        // Downloaded name of pdf file in case it was downloaded in response directly
        $pdf = $event->pdf;

        // download OR savepdf
        $action = $event->action;

        // metadata $user in this example
        $user = $event->metadata;

        // Perform desired actions
    }
}
```

## Garbage Collection :

When you export the PDF, a few extra files are generated by `pdflatex` along with your PDF (e.g. `.aux`, `.log`, `.out` etc.). The package takes care of the garbage collection process internally. It makes sure that no files are remaining when the PDF is generated or even when any error occures.

This makes sure the server does not waste it's space keeping those files.

## Error Handling :

We are using the application `pdflatex` from `texlive` to generate PDFs. If a syntax error occures in your tex file, it logs the error into a log file. Or if it is turned off, it shows the output in the console.

The package takes care of the same logic internally and throws `ViewNotFoundException`. The exception will have the entire information about the error easily available for you to debug.

## Contribution

Please feel free to contribute if you want to add new functionalities to this package.

## Credits

This Package was inspired alot by the `laravel-php-latex` package created by [Techsemicolon](https://github.com/techsemicolon/laravel-php-latex)
Later I started my own version of `laravel-php-latex` [ismaelw/laravel-php-latex](https://github.com/ismaelw/laravel-php-latex) because of missing support on the other package.

For better compatibility and better configuration handling I decided to create this package.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about any major change.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
