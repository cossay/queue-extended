<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Cosman\Queue\Store\Model\Client;

/**
 *
 * @author cosman
 *        
 */
class ProjectUniqueName extends Constraint
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
     * @var string
     */
    public $message = 'A project with the given already exists.';

    /**
     *
     * {@inheritdoc}
     * @see \Symfony\Component\Validator\Constraint::validatedBy()
     */
    public function validatedBy()
    {
        return 'cosman.queue.validator.constraint.project.name.unique';
    }
}