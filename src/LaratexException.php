<?php

namespace Ismaelw\LaraTeX;

use Exception;

class LaratexException extends Exception
{
    protected $texcontent;

    public static function detailed($message, $texcontent)
    {
        $instance = new static($message);
        $instance->texcontent = $texcontent;

        return $instance;
    }

    public function context()
    {
        return [
            'user_id' => auth()?->user()?->id ?? '',
            'tex_content' => $this->texcontent,
        ];
    }
}
