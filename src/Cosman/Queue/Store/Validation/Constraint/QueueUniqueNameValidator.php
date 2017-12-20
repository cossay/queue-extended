<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Repository\QueueRepositoryInterface;
use Cosman\Queue\Store\Model\Project;

/**
 *
 * @author cosman
 *        
 */
class QueueUniqueNameValidator extends ConstraintValidator
{

    /**
     *
     * @var QueueRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param QueueRepositoryInterface $repository
     */
    public function __construct(QueueRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     *
     * {@inheritdoc}
     * @see \Symfony\Component\Validator\ConstraintValidatorInterface::validate()
     */
    public function validate($value, Constraint $constraint)
    {
        if (! $value || ! is_string($value)) {
            return;
        }
        
        if (! ($constraint->client instanceof Client)) {
            throw new \Exception(sprintf('Option "client" for %s must be an instance of %s.', get_class($constraint), Client::class));
        }
        
        if (! ($constraint->project instanceof Project)) {
            throw new \Exception(sprintf('Option "project" for %s must be an instance of %s.', get_class($constraint), Project::class));
        }
        
        $queue = $this->repository->fetchByName($value, $constraint->client, $constraint->project);
        
        if (! $queue) {
            return;
        }
        
        if ($constraint->id && (int) $constraint->id === $queue->getId()) {
            return;
        }
        
        $this->context->addViolation($constraint->message);
    }
}