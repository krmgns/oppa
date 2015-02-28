<?php namespace Oppa\Shablon\Database\Connector\Agent;

use \Oppa\Exception\Database as Exception;

abstract class Agent
    implements ConnectionInterface, StreamFilterInterface, StreamWrapperInterface
{
    protected $link;
    protected $batch;
    protected $result;
    protected $logger;
    protected $profiler;
    protected $configuration;

    public function getLink() {
        return $this->link;
    }

    public function getBatch() {
        return $this->batch;
    }

    public function getResult() {
        return $this->result;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getProfiler() {
        if (!$this->profiler) {
            throw new \ErrorException(
                'Profiler is not found, did you set `profiling` option as true?');
        }
        return $this->profiler;
    }

    public function getConfiguration() {
        return $this->configuration;
    }

    // auto dedect agent class name
    final public function getName() {
        $className = get_called_class();
        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }

    final public function id($all = false) {
        return $this->result->getId($all);
    }

    final public function rowsCount() {
        return $this->result->getRowsCount();
    }

    final public function rowsAffected() {
        return $this->result->getRowsAffected();
    }

    final public function prepare($input, array $params = null) {
        if (!empty($params)) {
            preg_match_all('~%[sdfF]|\?|:[a-zA-Z0-9_]+~', $input, $match);
            if (isset($match[0])) {
                if (count($match[0]) != count($params)) {
                    throw new Exception\ArgumentException(
                        "Both modifiers and params count must be same, e.g: prepare('id = ?', [1]) or ".
                        "prepare('id IN(?,?)', [1,2]). If you have no prepare modifiers, then pass NULL or [] as \$params."
                    );
                }
                $i = 0; // Indexes could be string, e.g: array(':id' => 1, ...)
                foreach ($params as $key => $value) {
                    $key = $match[0][$i++];
                    $value = $this->escape($value, $key);
                    if (false !== ($pos = strpos($input, $key))) {
                        $input = substr_replace($input, $value, $pos, strlen($key));
                    }
                }
            }
        }

        return $input;
    }

    abstract public function where($where, array $params = null);
    abstract public function limit($limit);
}
