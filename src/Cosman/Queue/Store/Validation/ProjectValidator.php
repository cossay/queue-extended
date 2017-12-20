<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation;

use Cosman\Queue\Store\Validation\Constraint\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Validation\Constraint\ProjectUniqueName;

/**
 *
 * @author cosman
 *        
 */
class ProjectValidator extends BaseValidator
{

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Validation\BaseValidator::getDefinedConstraints()
     */
    protected function getDefinedConstraints(array $data = [], Client $client = null, int $projectId = null): Collection
    {
        $options = [];
        
        $options['name'] = array(
            new Assert\NotBlank(array(
                'message' => 'Project name required.'
            )),
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Project name must be a string.'
            )),
            new Assert\Length(array(
                'max' => 50,
                'maxMessage' => 'Project name must not be longer than {{ limit }} characters.'
            )),
            new ProjectUniqueName(array(
                'id' => $projectId,
                'client' => $client,
                'message' => 'A project with the given name already exists.'
            ))
        );
        
        $options['description'] = array(
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Project description must be a string.'
            )),
            new Assert\Length(array(
                'max' => 255,
                'maxMessage' => 'Project description must not be longer than {{ limit }} characters.'
            ))
        );
        
        return new Collection($options);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Validation\BaseValidator::validate()
     */
    public function validate(array $model, Client $client = null, int $projectId = null): bool
    {
        if (! $client) {
            throw new \Exception(sprintf('Argument 2 passed to %s must an instanceof %s null given.', __METHOD__, Client::class));
        }
        
        $violations = $this->validator->validate($model, $this->getDefinedConstraints($model, $client, $projectId));
        
        $this->errors = $this->formatErrors($violations);
        
        return 0 === count($this->errors);
    }
}