<?php declare(strict_types=1);

namespace Websta\LaraTeX;

class RawTex
{
    /**
     * Content of tex file
     *
     * @var string
     */
    private string $tex;

    /**
     * Construct the instance
     *
     * @param string $tex
     */
    public function __construct(string $tex)
    {

        $this->tex = $tex;
    }

    /**
     * Get tex content
     *
     * @return string
     */
    public function getTex(): string
    {
        return $this->tex;
    }
}
