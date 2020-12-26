<?php

namespace Ismaelw\LaraTeX;

class RawTex
{
	/**
     * Content of tex file
     * @var string
     */
    private $tex;

    /**
     * Construct the instance
     *
     * @param string $tex
     */
    public function __construct($tex){

    	$this->tex = $tex;
    }

    /**
     * Get tex content
     *
     * @return string
     */
    public function getTex(){

    	return $this->tex;
    }
}
