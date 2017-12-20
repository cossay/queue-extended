<?php
declare(strict_types = 1);
namespace Cosman\Queue\Http\Converter;

use Cosman\Queue\Http\Exception\QueueException;
use Cosman\Queue\Http\Response\Response;
use Cosman\Queue\Store\Repository\QueueRepositoryInterface;
use Cosman\Queue\Store\Model\Queue;

/**
 *
 * @author cosman
 *        
 */
class QueueConverter
{

    /**
     *
     * @var QueueRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param QueueRepositoryInterface $repository
     */
    public function __construct(QueueRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Converts a given id into a queue instances
     *
     * @param int $id
     * @throws QueueException
     * @return \Cosman\Queue\Store\Model\Queue
     */
    public function id(int $id): Queue
    {
        $queue = $this->repository->fetchById($id);
        
        if (! $queue instanceof Queue) {
            throw new QueueException('Requested queue not found', Response::HTTP_NOT_FOUND);
        }
        
        return $queue;
    }

    /**
     * Converts a given code into a queue instances
     * 
     * @param string $code
     * @throws QueueException
     * @return \Cosman\Queue\Store\Model\Queue
     */
    public function code(string $code): Queue
    {
        $queue = $this->repository->fetchByCode($code);
        
        if (! $queue instanceof Queue) {
            throw new QueueException('Requested queue not found', Response::HTTP_NOT_FOUND);
        }
        
        return $queue;
    }
}