<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 13:53
 */

namespace AppBundle\Probe;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;

class MessageQueue implements MessageQueueInterface
{
    use Lockable;

    protected $topic;
    protected $messages;

    public function __construct(string $topic)
    {
        if (empty($topic)) {
            throw new \Exception("Topic should not be empty.");
        }

        $this->topic = $topic;
        $this->messages = new \SplQueue();
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function addMessage(Message $message)
    {
        $this->messages[] = $message;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function process(PosterInterface $poster)
    {
        if ($this->isLocked()) {
            throw new \Exception("Queue is locked.");
        }

        $this->lock();

        while (!$this->messages->isEmpty()) {
            $message = $this->messages->shift();
            try {
                $response = $poster->post($message);
                yield $response;
            } catch (ClientException $exception) {
                yield new Message(
                    Message::SERVER_ERROR,
                    'Bad Request - Specific!',
                    array(
                        'input' => json_encode($message->asArray()),
                        'exception' => $exception->getMessage(),
                    )
                );
            } catch (TransferException $exception) {
                yield new Message(
                    Message::SERVER_ERROR,
                    'Bad Request',
                    array(
                        'input' => json_encode($message->asArray()),
                        'exception' => $exception->getMessage(),
                    )
                );
                $this->messages->unshift($message);
                break;
            }
        }

        $this->release();
    }
}