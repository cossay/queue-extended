<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\BaseModel;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;
use Illuminate\Database\Query\Builder;
use Exception;
use Cosman\Queue\Store\Table\QueueTable;
use Cosman\Queue\Store\Table\ProjectTable;
use Cosman\Queue\Store\Table\ClientTable;
use Cosman\Queue\Support\DateTime\DateTime;

/**
 *
 * @author cosman
 *        
 */
class QueueRepository extends BaseRepository implements QueueRepositoryInterface
{

    const RELATION_PROJECT = 'project_tb';

    const RELATION_CLIENT = 'client_tb';

    /**
     *
     * @param array $selectableFields
     * @param Client $client
     * @param Project $project
     * @return Builder
     */
    protected function withJoins(array $selectableFields = [], Client $client = null, Project $project = null): Builder
    {
        if (empty($selectableFields)) {
            $selectableFields = $this->createSelectableFieldList();
        }
        
        $query = $this->connection->table(QueueTable::NAME)
            ->join(ProjectTable::NAME, ProjectTable::FIELD_ID, '=', QueueTable::FIELD_PROJECT_ID)
            ->join(ClientTable::NAME, ClientTable::FIELD_ID, '=', ProjectTable::FIELD_CLIENT_ID);
        
        if ($client) {
            $query->where(ClientTable::FIELD_ID, '=', $client->getId());
        }
        
        if ($project) {
            $query->where(ProjectTable::FIELD_ID, '=', $project->getId());
        }
        
        return $query->select($selectableFields);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::count()
     */
    public function count(Client $client = null, Project $project = null): int
    {
        return $this->withJoins([], $client, $project)->count();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::fetch()
     */
    public function fetch(int $limit, int $offset, Client $client = null, Project $project = null): iterable
    {
        $models = $this->withJoins([], $client, $project)
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        return $this->formatCollection($models);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::fetchById()
     */
    public function fetchById(int $id, Client $client = null, Project $project = null): ?Queue
    {
        $model = $this->withJoins([], $client, $project)
            ->where(QueueTable::FIELD_ID, '=', $id)
            ->limit(1)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::fetchByCode()
     */
    public function fetchByCode(string $code, Client $client = null, Project $project = null): ?Queue
    {
        $model = $this->withJoins([], $client, $project)
            ->where(QueueTable::FIELD_CODE, '=', $code)
            ->limit(1)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::fetchByName()
     */
    public function fetchByName(string $name, Client $client = null, Project $project = null): ?Queue
    {
        $model = $this->withJoins([], $client, $project)
            ->where(QueueTable::FIELD_NAME, '=', $name)
            ->limit(1)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::create()
     */
    public function create(Queue $queue): int
    {
        $code = strtoupper(sha1(sprintf('%s-%s', microtime(), $queue->getProject()->getCode())));
        
        $attributes = array(
            QueueTable::FIELD_CODE => $code,
            QueueTable::FIELD_NAME => $queue->getName(),
            QueueTable::FIELD_DESCRIPTION => $queue->getDescription(),
            QueueTable::FIELD_REQUEST_METHOD => $queue->getRequestMethod(),
            QueueTable::FIELD_HEADERS => json_encode($queue->getHeaders()),
            QueueTable::FIELD_CALLBACK_URL => $queue->getCallbackUrl(),
            QueueTable::FIELD_DELAY => $queue->getDelay(),
            QueueTable::FIELD_RETRIES => $queue->getRetries(),
            QueueTable::FIELD_RETRY_DELAY => $queue->getRetryDelay(),
            QueueTable::FIELD_PROJECT_ID => $queue->getProject()->getId(),
            QueueTable::FIELD_CREATED_AT => new DateTime()
        );
        
        return $this->connection->table(QueueTable::NAME)->insertGetId($attributes);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::update()
     */
    public function update(Queue ...$queues): int
    {
        $affetedRows = 0;
        
        $now = new DateTime();
        
        try {
            $this->connection->beginTransaction();
            foreach ($queues as $queue) {
                if ($queue->getId()) {
                    $attributes = array(
                        QueueTable::FIELD_NAME => $queue->getName(),
                        QueueTable::FIELD_DESCRIPTION => $queue->getDescription(),
                        QueueTable::FIELD_REQUEST_METHOD => $queue->getRequestMethod(),
                        QueueTable::FIELD_HEADERS => json_encode($queue->getHeaders()),
                        QueueTable::FIELD_CALLBACK_URL => $queue->getCallbackUrl(),
                        QueueTable::FIELD_DELAY => $queue->getDelay(),
                        QueueTable::FIELD_RETRIES => $queue->getRetries(),
                        QueueTable::FIELD_RETRY_DELAY => $queue->getRetryDelay(),
                        QueueTable::FIELD_UPDATED => $now
                    );
                    
                    $affetedRows += $this->connection->table(QueueTable::NAME)
                        ->where(QueueTable::FIELD_ID, '=', $queue->getId())
                        ->update($attributes);
                }
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            
            throw $e;
        }
        
        return $affetedRows;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\QueueRepositoryInterface::delete()
     */
    public function delete(Queue ...$queues): int
    {
        $ids = [];
        
        foreach ($queues as $queue) {
            if ($queue->getId()) {
                $ids[] = $queue->getId();
            }
        }
        
        if (! count($ids)) {
            return 0;
        }
        
        return $this->connection->table(QueueTable::NAME)
            ->whereIn(QueueTable::FIELD_ID, $ids)
            ->delete();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::format()
     */
    protected function format($model, array $relations = []): ?BaseModel
    {
        $queue = Queue::createInstance($model);
        
        if (! ($queue instanceof Queue)) {
            return null;
        }
        
        $project = Project::createInstance($model, static::RELATION_PROJECT);
        
        if ($project instanceof Project) {
            $client = Client::createInstance($model, static::RELATION_CLIENT);
            
            if ($client) {
                $project->setClient($client);
            }
            
            $queue->setProject($project);
        }
        
        return $queue;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::getSelectableFields()
     */
    protected function getSelectableFields(): array
    {
        $fields = array(
            QueueTable::definition()->getFields()->all(),
            ClientTable::definition()->aliasFields(static::RELATION_CLIENT)->all(),
            ProjectTable::definition()->aliasFields(static::RELATION_PROJECT)->all()
        );
        
        return array_merge(...$fields);
    }
}