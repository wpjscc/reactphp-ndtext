<?php

namespace Wpjscc\NDText;

use Evenement\EventEmitter;
use React\Stream\WritableStreamInterface;

/**
 * The Encoder / Serializer can be used to write any value, encode it as a JSON text and forward it to an output stream
 */
class Encoder extends EventEmitter implements WritableStreamInterface
{
    private $output;

    private $closed = false;

    /**
     * @param WritableStreamInterface $output
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function __construct(WritableStreamInterface $output)
    {

        // @codeCoverageIgnoreEnd

        $this->output = $output;

        if (!$output->isWritable()) {
            $this->close();
            return;
        }

        $this->output->on('drain', array($this, 'handleDrain'));
        $this->output->on('error', array($this, 'handleError'));
        $this->output->on('close', array($this, 'close'));
    }

    public function write($data)
    {
        if ($this->closed) {
            return false;
        }
        return $this->output->write($data . "\n");
    }

    public function end($data = null)
    {
        if ($data !== null) {
            $this->write($data);
        }

        $this->output->end();
    }

    public function isWritable()
    {
        return !$this->closed;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->output->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    /** @internal */
    public function handleDrain()
    {
        $this->emit('drain');
    }

    /** @internal */
    public function handleError(\Exception $error)
    {
        $this->emit('error', array($error));
        $this->close();
    }
}