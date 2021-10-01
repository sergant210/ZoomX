<?php
namespace Zoomx\DTO;

class Error
{
    /** @var string $title */
    public $title = '';
    /** @var int $code */
    public $code = 0;
    /** @var string $message */
    public $message = '';
    /** @var string $file */
    public $file;
    /** @var int $line */
    public $line;
    /** @var array $trace */
    public $trace;
    /** @var array $headers */
    public $headers;
    /** @var \Throwable $object */
    public $object;


    public function __construct(array $data = [])
    {
    	$this->fromArray($data);
    }

    public function fromArray(array $data)
    {
        foreach ($data as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }
}