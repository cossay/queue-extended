<?php
namespace Cosman\Queue\Http\Controller;

use Cosman\Queue\Http\Auth\AccessManagerInterface;
use Cosman\Queue\Http\Response\Response;
use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Repository\JobRepositoryInterface;
use Cosman\Queue\Store\Validation\JobValidator;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;

/**
 * Job controller
 *
 * @author cosman
 *        
 */
class JobController extends Controller
{

    /**
     *
     * @var JobValidator
     */
    protected $validator;

    /**
     *
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param Request $request
     * @param Response $response
     * @param AccessManagerInterface $accessManager
     * @param JobRepositoryInterface $repository
     * @param JobValidator $validator
     */
    public function __construct(Request $request, Response $response, AccessManagerInterface $accessManager, JobRepositoryInterface $repository, JobValidator $validator)
    {
        parent::__construct($request, $response, $accessManager);
        
        $this->repository = $repository;
        
        $this->validator = $validator;
    }

    /**
     * Responses to request to create a single job for current client on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function postJobs(Project $project, Queue $queue): Response
    {
        try {
            
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $jobDetails = array(
                'title' => $this->request->request->get('title'),
                'description' => $this->request->request->get('description'),
                'payload' => $this->request->request->get('payload'),
                'delay' => $this->request->request->get('delay', $queue->getDelay()),
                'retries' => $this->request->request->get('retries', $queue->getRetries()),
                'retry_delay' => $this->request->request->get('retry_delay', $queue->getRetryDelay()),
                'callback_url' => $this->request->request->get('callback_url', $queue->getCallbackUrl()),
                'request_method' => $this->request->request->get('request_method', $queue->getRequestMethod()),
                'headers' => $this->request->request->get('headers', $queue->getHeaders())
            );
            
            if (is_string($jobDetails['request_method'])) {
                $jobDetails['request_method'] = strtoupper($jobDetails['request_method']);
            }
            
            if (! $this->validator->validate($jobDetails)) {
                return $this->response->error($this->validator->getErrors(), Response::HTTP_UNPROCESSABLE_ENTITY, $this->validator->getFirstError());
            }
            
            $job = new Job();
            $job->setQueue($queue);
            $job->setTitle($jobDetails['title']);
            $job->setDescription($jobDetails['description']);
            $job->setPayload($jobDetails['payload']);
            $job->setDelay($jobDetails['delay']);
            $job->setRetries($jobDetails['retries']);
            $job->setRetryDelay($jobDetails['retry_delay']);
            $job->setCallbackUrl($jobDetails['callback_url']);
            $job->setRequestMethod($jobDetails['request_method']);
            $job->setHeaders($jobDetails['headers']);
            
            $jobId = $this->repository->create($job);
            
            return $this->response->respond($this->repository->fetchById($jobId, $client));
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to updates a single job for current client on a given queue for a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function putJobs(Project $project, Queue $queue, Job $job): Response
    {
        try {
            
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            // Use existing job attributes as default so that clients provide only properties they wish to update
            $jobDetails = array(
                'title' => $this->request->request->get('title', $job->getTitle()),
                'description' => $this->request->request->get('description', $job->getDescription()),
                'payload' => $this->request->request->get('payload', $job->getPayload()),
                'delay' => $this->request->request->get('delay', $job->getDelay()),
                'retries' => $this->request->request->get('retries', $job->getRetries()),
                'retry_delay' => $this->request->request->get('retry_delay', $job->getRetryDelay()),
                'callback_url' => $this->request->request->get('callback_url', $job->getCallbackUrl()),
                'request_method' => $this->request->request->get('request_method', $job->getRequestMethod()),
                'headers' => $this->request->request->get('headers', $job->getHeaders())
            );
            
            if (is_string($jobDetails['request_method'])) {
                $jobDetails['request_method'] = strtoupper($jobDetails['request_method']);
            }
            
            if (! $this->validator->validate($jobDetails)) {
                return $this->response->error($this->validator->getErrors(), Response::HTTP_UNPROCESSABLE_ENTITY, $this->validator->getFirstError());
            }
            
            $job->setTitle($jobDetails['title']);
            $job->setDescription($jobDetails['description']);
            $job->setPayload($jobDetails['payload']);
            $job->setDelay($jobDetails['delay']);
            $job->setRetries($jobDetails['retries']);
            $job->setCallbackUrl($jobDetails['callback_url']);
            $job->setRequestMethod($jobDetails['request_method']);
            $job->setHeaders($jobDetails['headers']);
            
            $this->repository->update($job);
            
            return $this->response->respond($this->repository->fetchById($job->getId(), $client, $project, $queue));
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to fetch a number of jobs for current client for a given queue on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getJobs(Project $project, Queue $queue): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $this->verifyPaginationParameters();
            
            $counts = $this->repository->count($client, $project, $queue);
            
            if (0 === $counts) {
                return $this->response->collection();
            }
            
            $jobs = $this->repository->fetch($this->limit, $this->offset, $client, $project, $queue);
            
            return $this->response->collection($jobs, $counts, $this->offset);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to fetch a single job for current client for a given queue on a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getJob(Project $project, Queue $queue, Job $job): Response
    {
        try {
            
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue) || ! $queue->ownsJob($job)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            return $this->response->respond($job);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to delete a single job for current client on a given queue for a given project
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function deleteJobs(Project $project, Queue $queue, Job $job): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue) || ! $queue->ownsJob($job)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $this->repository->delete($job);
            
            return $this->response->respond();
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }
}