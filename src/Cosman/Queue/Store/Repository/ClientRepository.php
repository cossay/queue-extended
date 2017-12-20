<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\Client;
use Illuminate\Database\Query\Builder;
use Exception;
use Cosman\Queue\Store\Model\BaseModel;
use Cosman\Queue\Store\Table\ClientTable;
use Cosman\Queue\Support\DateTime\DateTime;

/**
 *
 * @author cosman
 *        
 */
class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{

    /**
     *
     * @param string[] $selectableFields
     * @return \Illuminate\Database\Query\Builder
     */
    protected function withJoins(array $selectableFields = []): Builder
    {
        $query = $this->connection->table(ClientTable::NAME);
        
        if (empty($selectableFields)) {
            $selectableFields = $this->createSelectableFieldList();
        }
        
        return $query->select($selectableFields);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::count()
     */
    public function count(): int
    {
        return $this->withJoins()->count();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::fetch()
     */
    public function fetch(int $limit, int $offset): iterable
    {
        $models = $this->withJoins()
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        return $this->formatCollection($models);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::fetchById()
     */
    public function fetchById(int $id): ?Client
    {
        $model = $this->withJoins()
            ->where(ClientTable::FIELD_ID, $id)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::fetchByToken()
     */
    public function fetchByToken(string $token): ?Client
    {
        $model = $this->withJoins()
            ->where(ClientTable::FIELD_TOKEN, $token)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::fetchByEmail()
     */
    public function fetchByEmail(string $email): ?Client
    {
        $model = $this->withJoins()
            ->where(ClientTable::FIELD_EMAIL, $email)
            ->first();
        
        return $this->format($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::create()
     */
    public function create(Client $client): int
    {
        $attributes = array(
            ClientTable::FIELD_NAME => $client->getName(),
            ClientTable::FIELD_EMAIL => $client->getEmail(),
            ClientTable::FIELD_TOKEN => $client->getToken(),
            ClientTable::FIELD_BLOCKED => ClientTable::BOOLEAN_FALSE,
            ClientTable::FIELD_CREATED_AT => new DateTime()
        );
        
        return $this->connection->table(ClientTable::NAME)->insertGetId($attributes);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::update()
     */
    public function update(Client ...$clients): int
    {
        $affectedRows = 0;
        
        try {
            
            $this->connection->beginTransaction();
            
            foreach ($clients as $client) {
                if ($client->getId()) {
                    $attributes = array(
                        ClientTable::FIELD_NAME => $client->getName(),
                        ClientTable::FIELD_EMAIL => $client->getEmail(),
                        ClientTable::FIELD_TOKEN => $client->getToken(),
                        ClientTable::FIELD_BLOCKED => $client->isBlocked() ? ClientTable::BOOLEAN_TRUE : ClientTable::BOOLEAN_FALSE,
                        ClientTable::FIELD_UPDATED_AT => new DateTime()
                    );
                    
                    $affectedRows += $this->connection->table(ClientTable::NAME)
                        ->where(ClientTable::FIELD_ID, '=', $client->getId())
                        ->update($attributes);
                }
            }
            
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            
            throw $e;
        }
        
        return $affectedRows;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\ClientRepositoryInterface::delete()
     */
    public function delete(Client ...$clients): int
    {
        $ids = [];
        
        foreach ($clients as $client) {
            if ($client->getId()) {
                $ids[] = $client->getId();
            }
        }
        
        if (! count($ids)) {
            return 0;
        }
        
        return $this->connection->table(ClientTable::NAME)
            ->whereIn(ClientTable::FIELD_ID, $ids)
            ->delete();
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::format()
     */
    protected function format($model, array $relations = []): ?BaseModel
    {
        return Client::createInstance($model);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Repository\BaseRepository::getSelectableFields()
     */
    protected function getSelectableFields(): array
    {
        return ClientTable::definition()->getFields()->all();
    }
}