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
            } catch (PosterException $exception) {
                switch ($exception->getCode()) {
                    case Message::E_REJECT_RETRY_PRIORITY:
                        $this->messages->unshift($message);
                        yield new Message(
                            Message::SERVER_ERROR,
                            'A message was rejected and requeued with priority.',
                            array(
                                'input' => "$message",
                                'message' => $exception->getMessage(),
                            )
                        );
                        break;
                    case Message::E_REJECT_RETRY:
                        $this->addMessage($message);
                        yield new Message(
                            Message::SERVER_ERROR,
                            'A message was rejected and requeued.',
                            array(
                                'input' => "$message",
                                'message' => $exception->getMessage(),
                            )
                        );
                        break;
                    case PosterInterface::E_REJECT:
                        yield new Message(
                            Message::SERVER_ERROR,
                            'A message was rejected and requeued.',
                            array(
                                'input' => "$message",
                                'message' => $exception->getMessage(),
                            )
                        );
                        break;
                    case PosterInterface::E_ABORT:
                        yield new Message(
                            Message::SERVER_ERROR,
                            'Message rejected and queue processing aborted.',
                            array(
                                'input' => "$message",
                                'message' => $exception->getMessage(),
                            )
                        );
                        break 2;
                    case PosterInterface::E_UNHANDLED:
                        yield new Message(
                            Message::SERVER_ERROR,
                            'Message rejected for error not specifically handled.',
                            array(
                                'input' => "$message",
                                'message' => $exception->getMessage(),
                            )
                        );
                        break;
                    default:
                        break;
                }
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