<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\BaseModel;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Table\ClientTable;
use Cosman\Queue\Store\Table\JobTable;
use Cosman\Queue\Store\Table\ProjectTable;
use Cosman\Queue\Support\DateTime\DateTime;
use Illuminate\Database\Query\Builder;
use Cosman\Queue\Store\Model\Queue;
use Cosman\Queue\Store\Table\QueueTable;

/**
 * Job repository
 *
 * @author cosman
 *        
 */
class JobRepository extends BaseRepository implements JobRepositoryInterface
{

    const RELATION_CLIENT = 'job_client_tb';

    const RELATION_PROJECT = 'job_project_tb';

    const RELATION_QUEUE = 'job_queue_tb';

    /**
     *
     * @param array $selectableFields
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @return Builder
     */
    protected function withJoins(array $selectableFields = [], Client $client = null, Project $project = null, Queue $queue = null): Builder
    {
        if (empty($selectableFields)) {
            $selectableFields = $this->createSelectableFieldList();
        }
        
        $query = $this->connection->table(JobTable::NAME)
            ->leftJoin(QueueTable::NAME, QueueTable::FIELD_ID, '=', JobTable::FIELD_QUEUE_ID)
            ->leftJoin(ProjectTable::NAME, ProjectTable::FIELD_ID, '=', QueueTable::FIELD_PROJECT_ID)
            ->leftJoin(ClientTable::NAME, ClientTable::FIELD_ID, '=', ProjectTable::FIELD_CLIENT_ID);
        
        if (! empty($selectableFields)) {
            $query->select($selectableFields);
        }
        
        if ($client) {
            $query->where(ProjectTable::FIELD_CLIENT_ID, '=', $client->getId());
        }
        
        if ($project) {
            $query->where(ProjectTable::FIELD_ID, '=', $project->getId());
        }
        
        if ($queue) {
            $query->where(QueueTable::FIELD_ID, '=', $queue->getId());
        }
        
        return $query;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::count()
     */
    public function count(Client $client = null, Project $project = null, Queue $queue = null): int
    {
        return $this->withJoins([], $client, $project, $queue)->count();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::fetch()
     */
    public function fetch(int $limit, int $offset, Client $client = null, Project $project = null, Queue $queue = null): iterable
    {
        $query = $this->withJoins([], $client, $project, $queue)
            ->limit($limit)
            ->offset($offset);
        
        return $this->formatCollection($query->get());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::fetchById()
     */
    public function fetchById(int $id, Client $client = null, Project $project = null, Queue $queue = null): ?Job
    {
        $query = $this->withJoins([], $client, $project, $queue);
        
        $query->where(JobTable::FIELD_ID, '=', $id);
        
        return $this->format($query->first());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::fetchByCode()
     */
    public function fetchByCode(string $code, Client $client = null, Project $project = null, Queue $queue = null): ?Job
    {
        $query = $this->withJoins([], $client, $project, $queue);
        
        $query->where(JobTable::FIELD_CODE, '=', $code);
        
        return $this->format($query->first());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::countWaitingJobs()
     */
    public function countWaitingJobs(Client $client = null, Project $project = null, Queue $queue = null): int
    {
        $query = $this->withJoins([], $client, $project, $queue);
        
        $query->whereRaw(sprintf('%s != %s', JobTable::FIELD_RETRIES, JobTable::FIELD_RETRY_COUNTS));
        
        $query->where(JobTable::FIELD_NEXT_EXECUTION, '<=', new DateTime());
        
        return $query->count();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::fetchWaitingJobs()
     */
    public function fetchWaitingJobs(int $limit, int $offset, Client $client = null, Project $project = null, Queue $queue = null): iterable
    {
        $query = $this->withJoins([], $client, $project, $queue)
            ->limit($limit)
            ->offset($offset);
        
        $query->whereRaw(sprintf('%s > %s', JobTable::FIELD_RETRIES, JobTable::FIELD_RETRY_COUNTS));
        
        $query->where(JobTable::FIELD_NEXT_EXECUTION, '<=', new DateTime());
        
        return $this->formatCollection($query->get());
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::create()
     */
    public function create(Job $job): int
    {
        $code = strtoupper(sha1(sprintf('%s-%s', microtime(), $job->getQueue()->getCode())));
        
        $attributes = array(
            JobTable::FIELD_CODE => $code,
            JobTable::FIELD_QUEUE_ID => $job->getQueue()->getId(),
            JobTable::FIELD_IS_EXECUTED => JobTable::BOOLEAN_FALSE,
            JobTable::FIELD_IS_SUCCESSFUL => JobTable::BOOLEAN_FALSE,
            JobTable::FIELD_IS_PROCESSING => JobTable::BOOLEAN_FALSE,
            JobTable::FIELD_TITLE => $job->getTitle(),
            JobTable::FIELD_DESCRIPTION => $job->getDescription(),
            JobTable::FIELD_DELAY => $job->getDelay(),
            JobTable::FIELD_RETRIES => $job->getRetries(),
            JobTable::FIELD_RETRY_DELAY => $job->getRetryDelay(),
            JobTable::FIELD_RETRY_COUNTS => $job->getTriedCounts(),
            JobTable::FIELD_CALLBACK_URL => $job->getCallbackUrl(),
            JobTable::FIELD_REQUEST_METHOD => $job->getRequestMethod(),
            JobTable::FIELD_PAYLOAD => json_encode($job->getPayload()),
            JobTable::FIELD_HEADERS => json_encode($job->getHeaders()),
            JobTable::FIELD_NEXT_EXECUTION => new DateTime(sprintf('+%d seconds', $job->getDelay())),
            JobTable::FIELD_CREATED_AT => new DateTime(),
            JobTable::FIELD_UPDATED => null
        );
        
        return $this->connection->table(JobTable::NAME)->insertGetId($attributes);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::createMany()
     */
    public function createMany(Job ...$jobs): bool
    {
        $now = new DateTime();
        
        $jobArray = [];
        
        foreach ($jobs as $job) {
            
            $code = strtoupper(sha1(sprintf('%s-%s-%s', microtime(), $job->getQueue()->getCode(), rand())));
            
            $jobArray[] = array(
                JobTable::FIELD_CODE => $code,
                JobTable::FIELD_QUEUE_ID => $job->getQueue()->getId(),
                JobTable::FIELD_IS_EXECUTED => JobTable::BOOLEAN_FALSE,
                JobTable::FIELD_IS_SUCCESSFUL => JobTable::BOOLEAN_FALSE,
                JobTable::FIELD_IS_PROCESSING => JobTable::BOOLEAN_FALSE,
                JobTable::FIELD_TITLE => $job->getTitle(),
                JobTable::FIELD_DESCRIPTION => $job->getDescription(),
                JobTable::FIELD_DELAY => $job->getDelay(),
                JobTable::FIELD_RETRIES => $job->getRetries(),
                JobTable::FIELD_RETRY_DELAY => $job->getRetryDelay(),
                JobTable::FIELD_RETRY_COUNTS => $job->getTriedCounts(),
                JobTable::FIELD_CALLBACK_URL => $job->getCallbackUrl(),
                JobTable::FIELD_REQUEST_METHOD => $job->getRequestMethod(),
                JobTable::FIELD_PAYLOAD => json_encode($job->getPayload()),
                JobTable::FIELD_HEADERS => json_encode($job->getHeaders()),
                JobTable::FIELD_NEXT_EXECUTION => new DateTime(sprintf('+%d seconds', $job->getDelay())),
                JobTable::FIELD_CREATED_AT => $now,
                JobTable::FIELD_UPDATED => null
            );
        }
        
        return $this->connection->table(JobTable::NAME)->insert($jobArray);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::update()
     */
    public function update(Job ...$jobs): int
    {
        $affectedRows = 0;
        
        try {
            
            $this->connection->beginTransaction();
            
            foreach ($jobs as $job) {
                if ($job->getId()) {
                    $attributes = array(
                        // JobTable::FIELD_QUEUE_ID => $job->getQueue()->getId(),
                        JobTable::FIELD_IS_EXECUTED => $job->isExecuted() ? JobTable::BOOLEAN_TRUE : JobTable::BOOLEAN_FALSE,
                        JobTable::FIELD_IS_SUCCESSFUL => $job->isSuccessful() ? JobTable::BOOLEAN_TRUE : JobTable::BOOLEAN_FALSE,
                        JobTable::FIELD_TITLE => $job->getTitle(),
                        JobTable::FIELD_DESCRIPTION => $job->getDescription(),
                        JobTable::FIELD_DELAY => $job->getDelay(),
                        JobTable::FIELD_RETRY_DELAY => $job->getRetryDelay(),
                        JobTable::FIELD_RETRIES => $job->getRetries(),
                        JobTable::FIELD_RETRY_COUNTS => $job->getTriedCounts(),
                        JobTable::FIELD_CALLBACK_URL => $job->getCallbackUrl(),
                        JobTable::FIELD_REQUEST_METHOD => $job->getRequestMethod(),
                        JobTable::FIELD_PAYLOAD => json_encode($job->getPayload()),
                        JobTable::FIELD_HEADERS => json_encode($job->getHeaders()),
                        JobTable::FIELD_NEXT_EXECUTION => $job->getNextExecution(),
                        JobTable::FIELD_UPDATED => new DateTime()
                    );
                    
                    $this->connection->table(JobTable::NAME)
                        ->where(JobTable::FIELD_ID, '=', $job->getId())
                        ->update($attributes);
                    
                    $affectedRows ++;
                }
            }
            
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            
            throw $e;
        }
        
        return $affectedRows;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\JobRepositoryInterface::delete()
     */
    public function delete(Job ...$jobs): int
    {
        $ids = [];
        
        foreach ($jobs as $job) {
            if ($job->getId()) {
                $ids[] = $job->getId();
            }
        }
        
        if (empty($ids)) {
            return 0;
        }
        
        return $this->connection->table(JobTable::NAME)
            ->whereIn(JobTable::FIELD_ID, $ids)
            ->delete();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::format()
     */
    protected function format($model, array $relations = []): ?BaseModel
    {
        $job = Job::createInstance($model);
        
        if (! ($job instanceof Job)) {
            return null;
        }
        
        $queue = Queue::createInstance($model, static::RELATION_QUEUE);
        
        if ($queue instanceof Queue) {
            
            $project = Project::createInstance($model, static::RELATION_PROJECT);
            
            if ($project instanceof Project) {
                
                $client = Client::createInstance($model, static::RELATION_CLIENT);
                
                if ($client instanceof Client) {
                    $project->setClient($client);
                }
                
                $queue->setProject($project);
            }
            $job->setQueue($queue);
        }
        
        return $job;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::getSelectableFields()
     */
    protected function getSelectableFields(): array
    {
        $fields = array(
            JobTable::definition()->getFields()->all(),
            QueueTable::definition()->aliasFields(static::RELATION_QUEUE)->all(),
            ClientTable::definition()->aliasFields(static::RELATION_CLIENT)->all(),
            ProjectTable::definition()->aliasFields(static::RELATION_PROJECT)->all()
        );
        
        return array_merge(...$fields);
    }
}