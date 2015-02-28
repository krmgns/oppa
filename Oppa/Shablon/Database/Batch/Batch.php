<?php namespace Oppa\Shablon\Database\Batch;

abstract class Batch
{
    protected $agent;
    protected $queue = [];
    protected $result = [];
    protected $totalTime = 0;

    public function reset() {
        $this->queue = [];
        $this->result = [];
        $this->totalTime = 0;
    }

    public function getQueue() {
        return $this->queue;
    }

    public function getResult() {
        return $this->result;
    }

    abstract public function lock();
    abstract public function unlock();
    abstract public function queue($query, array $params = null);
    abstract public function run();
    abstract public function cancel();
}
