<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Cosman\Queue\Store\Repository\ClientRepositoryInterface;
use Cosman\Queue\Store\Model\Client;

class ClientUniqueEmailValidator extends ConstraintValidator
{

    /**
     *
     * @var ClientRepositoryInterface
     */
    protected $repository;

    /**
     *
     * @param ClientRepositoryInterface $repository
     */
    public function __construct(ClientRepositoryInterface $repository)
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
        if (! ($this->repository instanceof ClientRepositoryInterface) || ! $value || ! is_string($value)) {
            return;
        }
        
        $client = $this->repository->fetchByEmail($value);
        
        if (! ($client instanceof Client)) {
            return;
        }
        
        if ($constraint->clientId && (int) $constraint->clientId !== $client->getId()) {
            return;
        }
        
        $this->context->addViolation($constraint->message);
    }
}