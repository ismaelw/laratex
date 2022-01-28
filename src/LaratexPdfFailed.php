<?php declare(strict_types=1);

namespace Websta\LaraTeX;

class LaratexPdfFailed
{
    use \Illuminate\Foundation\Events\Dispatchable;
    
    /**
     * Path of pdf
     *
     * @var string
     */
    public string $pdf;

    /**
     * Type of action download|savepdf
     * @var string
     */
    public string $action;

    /**
     * Metadata of the generated pdf
     * @var mixed
     */
    public mixed $metadata;

    /**
     * Create a new event instance.
     *
     * @param string $pdf
     * @param string $action
     * @param mixed|null $metadata
     *
     * @return void
     */
    public function __construct(string $pdf, string $action = 'download', mixed $metadata = null)
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
    public function broadcastOn(): array
    {
        return [];
    }
}
