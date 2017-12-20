<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;
use Cosman\Queue\Store\Model\Client;

/**
 *
 * @author cosman
 *        
 */
interface QueueRepositoryInterface extends RepositoryInterface
{

    /**
     * Returns number of queues.
     * If a project is provided, number of queues under project is returned
     *
     * @param Client $client
     * @param Project $project
     * @return int
     */
    public function count(Client $client = null, Project $project = null): int;

    /**
     * Fetches a number of queues.
     *
     * If a project is provided, only queues under the given project is returned
     *
     * @param int $limit
     * @param int $offset
     * @param Client $client
     * @param Project $project
     * @return \Cosman\Queue\Store\Model\Queue[]
     */
    public function fetch(int $limit, int $offset, Client $client = null, Project $project = null): iterable;

    /**
     * Fetches a single queue by its unique Id
     *
     * @param int $id
     * @param Client $client
     * @param Project $project
     * @return \Cosman\Queue\Store\Model\Queue|NULL
     */
    public function fetchById(int $id, Client $client = null, Project $project = null): ?Queue;

    /**
     * Fetches a single queue by its unique code
     *
     * @param string $code
     * @param Client $client
     * @param Project $project
     * @return \Cosman\Queue\Store\Model\Queue|NULL
     */
    public function fetchByCode(string $code, Client $client = null, Project $project = null): ?Queue;
    
    /**
     * Fetches a single queue by its name
     *
     * @param string $name
     * @param Client $client
     * @param Project $project
     * @return \Cosman\Queue\Store\Model\Queue|NULL
     */
    public function fetchByName(string $name, Client $client = null, Project $project = null): ?Queue;

    /**
     * Creates a single queue
     *
     * @param Queue $queue
     * @return int
     */
    public function create(Queue $queue): int;

    /**
     * Updates a collection of queues
     *
     * @param Queue ...$queues
     * @return int
     */
    public function update(Queue ...$queues): int;

    /**
     * Deletes a collection of queues
     *
     * @param Queue ...$queues
     * @return int
     */
    public function delete(Queue ...$queues): int;
}