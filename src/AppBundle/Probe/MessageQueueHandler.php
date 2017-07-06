<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 13:21
 */

namespace AppBundle\Probe;


class MessageQueueHandler
{
    protected $queues = array();

    /* @var Poster */
    protected $poster;

    protected $posters = array();

    public function __construct(PosterInterface $poster = null)
    {
        $this->poster = $poster ?: new EchoPoster();
        $this->queues = array();
    }

    public function addQueue(MessageQueue $queue, PosterInterface $specificPoster = null)
    {
        $topic = $queue->getTopic();
        $this->queues[$topic] = $queue;

        if ($specificPoster) {
            $this->posters[$topic] = $specificPoster;
        }
    }

    public function getTopics()
    {
        return array_keys($this->queues);
    }

    public function getMessages(string $topic)
    {
        if (!isset($this->queues[$topic])) {
            throw new \Exception("Attempted to get messages from unknown queue.");
        }
        return $this->queues[$topic]->getMessages();
    }

    public function addMessage(string $topic, Message $message)
    {
        if (!isset($this->queues[$topic])) {
            throw new \Exception("Attempted to send message to unknown queue.");
        }
        $this->queues[$topic]->addMessage($message);
    }

    public function processQueues()
    {
        foreach ($this->getTopics() as $topic) {
            yield array('topic' => $topic, 'responses' => $this->processQueue($topic));
        }
    }

    public function processQueue($topic)
    {
        if (!isset($this->queues[$topic])) {
            throw new \Exception("Attempted to process unknown queue.");
        }

        $poster = isset($this->posters[$topic]) ? $this->posters[$topic] : $this->poster;
        foreach ($this->queues[$topic]->process($poster) as $responses) {
            yield $responses;
        }
    }


}