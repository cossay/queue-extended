<?php
declare(strict_types = 1);
namespace Cosman\Queue\Http\Controller;

use Cosman\Queue\Http\Auth\AccessManagerInterface;
use Cosman\Queue\Http\Response\Response;
use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Repository\OutputRepositoryInterface;
use Cosman\Queue\Store\Validation\OutputValidator;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Output;
use Cosman\Queue\Store\Model\Queue;

/**
 * job output controller class
 *
 * @author cosman
 *        
 */
class OutputController extends Controller
{

    /**
     *
     * @var OutputValidator
     */
    protected $validator;

    /**
     *
     * @var OutputRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param Request $request
     * @param Response $response
     * @param AccessManagerInterface $accessManager
     * @param OutputRepositoryInterface $repository
     * @param OutputValidator $validator
     */
    public function __construct(Request $request, Response $response, AccessManagerInterface $accessManager, OutputRepositoryInterface $repository, OutputValidator $validator)
    {
        parent::__construct($request, $response, $accessManager);
        
        $this->validator = $validator;
        
        $this->repository = $repository;
    }

    /**
     * Responses to request to fetch a number of outputs for a given project for a given job for current clients
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getOutputs(Project $project, Queue $queue, Job $job): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue) || ! $queue->ownsJob($job)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $this->verifyPaginationParameters();
            
            $counts = $this->repository->count($client, $project, $queue, $job);
            
            if (0 === $counts) {
                return $this->response->collection();
            }
            
            $outputs = $this->repository->fetch($this->limit, $this->offset, $client, $project, $queue, $job);
            
            return $this->response->collection($outputs, $counts, $this->offset);
            
            return $this->response->respond($client);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to fetch a single of output for a given project for a given jog for current client
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @param Output $output
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function getOutput(Project $project, Queue $queue, Job $job, Output $output): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue) || ! $queue->ownsJob($job) || ! $job->ownsOutput($output)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            return $this->response->respond($output);
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }

    /**
     * Responses to request to delete a single of output for a given project for a given jog for current client
     *
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @param Output $output
     * @return \Cosman\Queue\Http\Response\Response
     */
    public function deleteOutputs(Project $project, Queue $queue, Job $job, Output $output): Response
    {
        try {
            $client = $this->accessManager->getClient($this->request);
            
            if (! $client->ownsProject($project) || ! $project->ownsQueue($queue) || ! $queue->ownsJob($job) || ! $job->ownsOutput($output)) {
                return $this->response->error(null, Response::HTTP_FORBIDDEN, static::MESSAGE_ACCESS_FORBIDDEN);
            }
            
            $this->repository->delete($output);
            
            return $this->response->respond();
        } catch (Exception $e) {
            return $this->response->exception($e);
        }
    }
}