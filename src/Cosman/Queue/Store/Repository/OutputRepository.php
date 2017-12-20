<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\BaseModel;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Model\Output;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;
use Cosman\Queue\Store\Table\ClientTable;
use Cosman\Queue\Store\Table\JobTable;
use Cosman\Queue\Store\Table\OutputTable;
use Cosman\Queue\Store\Table\ProjectTable;
use Cosman\Queue\Support\DateTime\DateTime;
use Illuminate\Database\Query\Builder;
use Exception;
use Cosman\Queue\Store\Table\QueueTable;

/**
 *
 * @author cosman
 *        
 */
class OutputRepository extends BaseRepository implements OutputRepositoryInterface
{

    const RELATION_JOB = 'output_job_tb';

    const RELATION_CLIENT = 'job_client_tb';

    const RELATION_PROJECT = 'job_project_tb';

    const RELATION_QUEUE = 'job_queue_tb';

    /**
     *
     * @param array $selectableFields
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return Builder
     */
    protected function withJoins(array $selectableFields = [], Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): Builder
    {
        $query = $this->connection->table(OutputTable::NAME)
            ->leftJoin(JobTable::NAME, JobTable::FIELD_ID, '=', OutputTable::FIELD_JOB_ID)
            ->leftJoin(QueueTable::NAME, QueueTable::FIELD_ID, '=', JobTable::FIELD_QUEUE_ID)
            ->leftJoin(ProjectTable::NAME, ProjectTable::FIELD_ID, '=', QueueTable::FIELD_PROJECT_ID)
            ->leftJoin(ClientTable::NAME, ClientTable::FIELD_ID, '=', ProjectTable::FIELD_CLIENT_ID);
        
        if (empty($selectableFields)) {
            $selectableFields = $this->createSelectableFieldList();
        }
        
        if (null !== $job) {
            $query->where(OutputTable::FIELD_JOB_ID, '=', $job->getId());
        }
        
        if (null !== $client) {
            $query->where(ClientTable::FIELD_ID, '=', $client->getId());
        }
        
        if (null !== $project) {
            $query->where(ProjectTable::FIELD_ID, '=', $project->getId());
        }
        
        return $query->select($selectableFields);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::count()
     */
    public function count(Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): int
    {
        return $this->withJoins([], $client, $project, $queue, $job)->count();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::fetch()
     */
    public function fetch(int $limit, int $offset, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): iterable
    {
        $models = $this->withJoins([], $client, $project, $queue, $job)
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        return $this->formatCollection($models);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::fetchById()
     */
    public function fetchById(int $id, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): ?Output
    {
        $model = $this->withJoins([], $client, $project, $queue, $job)
            ->where(OutputTable::FIELD_ID, '=', $id)
            ->limit(1)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::fetchByCode()
     */
    public function fetchByCode(string $code, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): ?Output
    {
        $model = $this->withJoins([], $client, $project, $queue, $job)
            ->where(OutputTable::FIELD_CODE, '=', $code)
            ->limit(1)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::create()
     */
    public function create(Output $output): int
    {
        $code = strtoupper(sha1(sprintf('%s-%s', microtime(), $output->getJob()->getCode())));
        
        $attributes = array(
            OutputTable::FIELD_CODE => $code,
            OutputTable::FIELD_JOB_ID => $output->getJob()->getId(),
            OutputTable::FIELD_CONTENT => json_encode($output->getContent()),
            OutputTable::FIELD_STATUS_CODE => $output->getStatusCode(),
            OutputTable::FIELD_STATUS_MESSAGE => $output->getStatusMessage(),
            OutputTable::FIELD_HEADERS => json_encode($output->getHeaders()),
            OutputTable::FIELD_CREATED_AT => new DateTime(),
            OutputTable::FIELD_UPDATED_AT => null
        );
        
        return $this->connection->table(OutputTable::NAME)->insertGetId($attributes);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::createMany()
     */
    public function createMany(Output ...$outputs): bool
    {
        $outputsArrays = [];
        
        $now = new DateTime();
        
        foreach ($outputs as $output) {
            
            $code = strtoupper(sha1(sprintf('%s-%s-%s', microtime(), $output->getJob()->getCode(), rand())));
            
            $outputsArrays[] = array(
                OutputTable::FIELD_CODE => $code,
                OutputTable::FIELD_JOB_ID => $output->getJob()->getId(),
                OutputTable::FIELD_CONTENT => json_encode($output->getContent()),
                OutputTable::FIELD_STATUS_CODE => $output->getStatusCode(),
                OutputTable::FIELD_STATUS_MESSAGE => $output->getStatusMessage(),
                OutputTable::FIELD_HEADERS => json_encode($output->getHeaders()),
                OutputTable::FIELD_CREATED_AT => $now,
                OutputTable::FIELD_UPDATED_AT => null
            );
        }
        
        return $this->connection->table(OutputTable::NAME)->insert($outputsArrays);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::update()
     */
    public function update(Output ...$outputs): int
    {
        try {
            $this->connection->beginTransaction();
            
            $affectedRows = 0;
            
            foreach ($outputs as $output) {
                if ($output->getId()) {
                    $attributes = array(
                        OutputTable::FIELD_JOB_ID => $output->getJob()->getId(),
                        OutputTable::FIELD_CONTENT => json_encode($output->getContent()),
                        OutputTable::FIELD_STATUS_CODE => $output->getStatusCode(),
                        OutputTable::FIELD_STATUS_MESSAGE => $output->getStatusMessage(),
                        OutputTable::FIELD_CREATED_AT => new DateTime(),
                        OutputTable::FIELD_UPDATED_AT => null
                    );
                    
                    $affectedRows ++;
                    
                    $this->connection->table(OutputTable::NAME)
                        ->where(OutputTable::FIELD_ID, '=', $output->getId())
                        ->update($attributes);
                }
            }
            
            $this->connection->commit();
            
            return $affectedRows;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\OutputRepositoryInterface::delete()
     */
    public function delete(Output ...$outputs): int
    {
        $ids = [];
        
        foreach ($outputs as $output) {
            if ($output->getId()) {
                $ids[] = $output->getId();
            }
        }
        
        if (! count($ids)) {
            return 0;
        }
        
        return $this->connection->table(OutputTable::NAME)
            ->orWhereIn(OutputTable::FIELD_ID, $ids)
            ->delete();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::format()
     */
    protected function format($model, array $relations = []): ?BaseModel
    {
        $output = Output::createInstance($model);
        
        if (! ($output instanceof Output)) {
            return null;
        }
        
        $job = Job::createInstance($model, static::RELATION_JOB);
        
        if ($job instanceof Job) {
            
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
            $output->setJob($job);
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::getSelectableFields()
     */
    protected function getSelectableFields(): array
    {
        $fields = array(
            OutputTable::definition()->getFields()->all(),
            JobTable::definition()->aliasFields(static::RELATION_JOB)->all(),
            ClientTable::definition()->aliasFields(static::RELATION_CLIENT)->all(),
            ProjectTable::definition()->aliasFields(static::RELATION_PROJECT)->all(),
            QueueTable::definition()->aliasFields(static::RELATION_QUEUE)->all()
        );
        
        return array_merge(...$fields);
    }
}