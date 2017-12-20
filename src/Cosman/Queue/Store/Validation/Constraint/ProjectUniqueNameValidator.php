<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Cosman\Queue\Store\Repository\ProjectRepositoryInterface;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Project;

/**
 *
 * @author cosman
 *        
 */
class ProjectUniqueNameValidator extends ConstraintValidator
{

    /**
     *
     * @var ProjectRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param ProjectRepositoryInterface $repository
     */
    public function __construct(ProjectRepositoryInterface $repository)
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
        
        $project = $this->repository->fetchByName($value, $constraint->client);
        
        if (! ($project instanceof Project)) {
            return;
        }
        
        if ($constraint->id && (int)$constraint->id === $project->getId()) {
            return;
        }
        
        $this->context->addViolation($constraint->message);
    }
}