<?php
declare(strict_types = 1);
namespace Cosman\Queue\Http\Controller;

use Cosman\Queue\Http\Auth\AccessManagerInterface;
use Cosman\Queue\Http\Response\Response;
use Cosman\Queue\Store\Repository\QueueRepositoryInterface;
use Cosman\Queue\Store\Validation\QueueValidator;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;

/**
 *
 * @author cosman
 *        
 */
class QueueController extends Controller
{

    /**
     *
     * @var QueueRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @var QueueValidator
     */
    protected $validator;

    /**
     *
     * @param Request $request
     * @param Response $response
     * @param AccessManagerInterface $accessManager
     * @param QueueRepositoryInterface $repository
     * @param QueueValidator $validator
     */
    public function __construct(Request $request, Response $response, AccessManagerInterface $accessManager, QueueRepositoryInterface $repository, QueueValidator $validator)
    {
        parent::__construct($request, $response, $accessManager);
        
        $this->repository = $repository;
        
        $this->validator = $validator;
    }

    /**
     * Responses to request to create a single queue on a given project
     *
     * @param Project $project
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function postQueues(Project $project): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $attributes = array(
                'name' => $this->request->request->get('name'),
                'description' => $this->request->request->get('description'),
                'delay' => $this->request->request->get('delay', 0),
                'retries' => $this->request->request->get('retries', 3),
                'retry_delay' => $this->request->request->get('retry_delay', 1800), // 30 minutes dalay after execution failure
                'callback_url' => $this->request->request->get('callback_url'),
                'request_method' => $this->request->request->get('request_method', Request::METHOD_GET),
                'headers' => $this->request->request->get('headers', [])
            );
            
            if (! $this->validator->validate($attributes)) {
                return $this->response->error($this->validator->getErrors(), Response::HTTP_UNPROCESSABLE_ENTITY, $this->validator->getFirstError());
            }
            
            $queue = new Queue();
            $queue->setProject($project);
            $queue->setName($attributes['name']);
            $queue->setDescription($attributes['description']);
            $queue->setDelay($attributes['delay']);
            $queue->setRetries($attributes['retries']);
            $queue->setRetryDelay($attributes['retry_delay']);
            $queue->setCallbackUrl($attributes['callback_url']);
            $queue->setRequestMethod($attributes['request_method']);
            $queue->setHeaders($attributes['headers']);
            
            $queueId = $this->repository->create($queue);
            
            return $this->response->respond($this->repository->fetchById($queueId, $client, $project));
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to update a given queue on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function putQueues(Project $project, Queue $queue): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $attributes = array(
                'name' => $this->request->request->get('name', $queue->getName()),
                'description' => $this->request->request->get('description', $queue->getDescription()),
                'delay' => $this->request->request->get('delay', $queue->getDelay()),
                'retries' => $this->request->request->get('retries', $queue->getRetries()),
                'retry_delay' => $this->request->request->get('retry_delay', $queue->getRetryDelay()),
                'callback_url' => $this->request->request->get('callback_url', $queue->getCallbackUrl()),
                'request_method' => $this->request->request->get('request_method', $queue->getRequestMethod()),
                'headers' => $this->request->request->get('headers', $queue->getHeaders())
            );
            
            if (! $this->validator->validate($attributes)) {
                return $this->response->error($this->validator->getErrors(), Response::HTTP_UNPROCESSABLE_ENTITY, $this->validator->getFirstError());
            }
            
            $clonedQueue = clone $queue;
            $clonedQueue->setProject($project);
            $clonedQueue->setName($attributes['name']);
            $clonedQueue->setDescription($attributes['description']);
            $clonedQueue->setDelay($attributes['delay']);
            $clonedQueue->setRetries($attributes['retries']);
            $clonedQueue->setRetryDelay($attributes['retry_delay']);
            $clonedQueue->setCallbackUrl($attributes['callback_url']);
            $clonedQueue->setRequestMethod($attributes['request_method']);
            $clonedQueue->setHeaders($attributes['headers']);
            
            $this->repository->update($clonedQueue);
            
            return $this->response->respond($this->repository->fetchById($queue->getId(), $client, $project));
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to fetch a number of queues on a given project
     *
     * @param Project $project
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getQueues(Project $project): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            $this->verifyPaginationParameters();
            
            $counts = $this->repository->count($client, $project);
            
            if (0 === $counts) {
                return $this->response->collection();
            }
            
            $queues = $this->repository->fetch($this->limit, $this->offset, $client, $project);
            
            return $this->response->collection($queues, $counts, $this->offset);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to fetch a single queue on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getQueue(Project $project, Queue $queue): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            return $this->response->respond($queue);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to delete a given queue on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function deleteQueues(Project $project, Queue $queue): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $this->repository->delete($queue);
            
            return $this->response->respond();
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }
}