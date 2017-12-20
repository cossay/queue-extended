<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Repository;

use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Model\Output;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Model\Queue;

/**
 *
 * @author cosman
 *        
 */
interface OutputRepositoryInterface extends RepositoryInterface
{

    /**
     * Counts number of outputs available
     * 
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return int
     */
    public function count(Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): int;

    /**
     * Fetches a number of outputs
     * 
     * @param int $limit
     * @param int $offset
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Store\Model\Job[]
     */
    public function fetch(int $limit, int $offset, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): iterable;

    /**
     * Fetches a single output by Id
     * 
     * @param int $id
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Store\Model\Job|NULL
     */
    public function fetchById(int $id, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): ?Output;

    /**
     * Fetches a single output by code
     * 
     * @param string $code
     * @param Client $client
     * @param Project $project
     * @param Queue $queue
     * @param Job $job
     * @return \Cosman\Queue\Store\Model\Job|NULL
     */
    public function fetchByCode(string $code, Client $client = null, Project $project = null, Queue $queue = null, Job $job = null): ?Output;

    /**
     * Creates a single output
     *
     * @param Output $output
     * @return int Unique Id of newly created output
     */
    public function create(Output $output): int;

    /**
     * Creates at least one output
     *
     * @param Output ...$outputs
     * @return bool
     */
    public function createMany(Output ...$outputs): bool;

    /**
     * Updates a collection of outputs
     *
     * @param Job ...$jobs
     * @return int Number of jobs updated
     */
    public function update(Output ...$outputs): int;

    /**
     * Deletes a collection of outputs
     *
     * @param Output ...$outputs
     * @return int Number of jobs deleted
     */
    public function delete(Output ...$outputs): int;
}