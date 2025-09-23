<?php

return [

	/*
    |--------------------------------------------------------------------------
    | LaTeX & BibTeX Binaries
    |--------------------------------------------------------------------------
    | Defaults: 'pdflatex' and 'bibtex'
    */
	'binPath'    => env('LARATEX_PATH', 'pdflatex'),
	'bibTexPath' => env('LARATEX_BIBTEX_PATH', 'bibtex'),

	/*
    |--------------------------------------------------------------------------
    | Temporary Storage Path (relative to storage/)
    |--------------------------------------------------------------------------
    | Default 'app/'
    */
	'tempPath' => env('LARATEX_TEMP_PATH', 'app/'),

	/*
    |--------------------------------------------------------------------------
    | Teardown (delete log/aux/tex after build)
    |--------------------------------------------------------------------------
    | Boolean; default true
    */
	'teardown' => (bool) env('LARATEX_TEARDOWN', true),

	/*
    |--------------------------------------------------------------------------
    | Process Timeout (seconds)
    |--------------------------------------------------------------------------
    | For larger documents increase the timeout. default 120s
    */
	'timeout' => (int) env('LARATEX_TIMEOUT', 120),
];