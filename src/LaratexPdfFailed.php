<?php

namespace Ismaelw\LaraTeX;

class LaratexPdfFailed
{
    /**
     * Path of pdf
     *
     * @var string
     */
    public $pdf;

    /**
     * Type of action download|savepdf
     * @var string
     */
    public $action;

    /**
     * Metadata of the generated pdf
     * @var mixed
     */
    public $metadata;

    /**
     * Create a new event instance.
     *
     * @param string $pdf
     * @param string $action
     * @param mixed $metadata
     *
     * @return void
     */
    public function __construct($pdf, $action = 'download', $metadata = null)
    {
        $this->pdf      = $pdf;
        $this->action   = $action;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
