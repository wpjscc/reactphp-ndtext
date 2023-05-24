<?php

namespace Wpjscc\NDText;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * The Decoder / Parser reads from a plain stream and emits data objects for each JSON element
 */
class Decoder extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    /** @var int */
    private $maxlength;

    private $buffer = '';
    private $closed = false;

    /**
     * @param ReadableStreamInterface $input
     * @param int $maxlength
     * @throws \BadMethodCallException
     */
    public function __construct(ReadableStreamInterface $input, $maxlength = 65536)
    {
        
        // @codeCoverageIgnoreEnd

        $this->input = $input;

        if (!$input->isReadable()) {
            $this->close();
            return;
        }

        $this->maxlength = $maxlength;

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('end', array($this, 'handleEnd'));
        $this->input->on('error', array($this, 'handleError'));
        $this->input->on('close', array($this, 'close'));
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->buffer = '';

        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    /** @internal */
    public function handleData($data)
    {
        if (!\is_string($data)) {
            $this->handleError(new \UnexpectedValueException('Expected stream to emit string, but got ' . \gettype($data)));
            return;
        }

        $this->buffer .= $data;

        // keep parsing while a newline has been found
        while ((($newline = \strpos($this->buffer, "\n")) !== false || ($newline = \strpos($this->buffer, "\r")) !== false) && $newline <= $this->maxlength) {
            // read data up until newline and remove from buffer
            $data = (string)\substr($this->buffer, 0, $newline);
            $this->buffer = (string)\substr($this->buffer, $newline + 1);
            $this->emit('data', array($data));
        }

        if (isset($this->buffer[$this->maxlength])) {
            $this->handleError(new \OverflowException('Buffer size exceeded'));
        }
    }

    /** @internal */
    public function handleEnd()
    {
        if ($this->buffer !== '') {
            $this->handleData("\n");
        }

        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }

    /** @internal */
    public function handleError(\Exception $error)
    {
        $this->emit('error', array($error));
        $this->close();
    }
}