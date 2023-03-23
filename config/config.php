<?php

return [
	// bin path to your pdflatex installation | use 'which pdflatex' on a linux system to find out which is the path to your pdflatex installation
	'binPath' => 'pdflatex',

	// bin path to your bibtex installation | use 'which bibtex' on a linux system to find out which is the path to your bibtex installation
	'bibTexPath' => 'bibtex',

	// Folder in your storage folder where you would like to store the temp files created by LaraTeX
	'tempPath' => 'app/',

	// boolean to define if log, aux and tex files should be deleted after generating PDF
	'teardown' => true,
];
