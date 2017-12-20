<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation\Constraint;

use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Project;
use Symfony\Component\Validator\Constraint;

/**
 *
 * @author cosman
 *        
 */
class QueueUniqueName extends Constraint
{

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var Client
     */
    public $client;

    /**
     *
     * @var Project
     */
    public $project;

    /**
     *
     * @var string
     */
    public $message = 'A queue with the same name already exists on the specified project.';

    /**
     *
     * {@inheritdoc}
     * @see \Symfony\Component\Validator\Constraint::validatedBy()
     */
    public function validatedBy()
    {
        return 'cosman.queue.validator.constraint.queue.name.unique';
    }
}