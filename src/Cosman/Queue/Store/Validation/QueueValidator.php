<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation;

use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Validation\Constraint\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;
use Cosman\Queue\Store\Model\Client;
use Cosman\Queue\Store\Model\Project;
use Cosman\Queue\Store\Validation\Constraint\QueueUniqueName;

/**
 *
 * @author cosman
 *        
 */
class QueueValidator extends BaseValidator
{

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Validation\BaseValidator::getDefinedConstraints()
     */
    protected function getDefinedConstraints(array $data = [], Client $client = null, Project $project = null, int $queueId = null): Collection
    {
        $options = [];
        
        $options['name'] = array(
            new Assert\NotBlank(array(
                'message' => 'Queue name required.'
            )),
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Queue name must be a string.'
            )),
            new Assert\Length(array(
                'max' => 255,
                'maxMessage' => 'Queue name must not be longer than {{ limit }} characters.'
            )),
            new QueueUniqueName(array(
                'id' => $queueId,
                'client' => $client,
                'project' => $project,
                'message' => 'A queue with the given name already exists in the specified project.'
            ))
        );
        
        $options['description'] = array(
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Queue description must be a string.'
            )),
            new Assert\Length(array(
                'max' => 500,
                'maxMessage' => 'Queue description must not be longer than {{ limit }} characters.'
            ))
        );
        
        $options['delay'] = array(
            new Assert\Type(array(
                'type' => 'integer',
                'message' => 'Queue delay must be an integer.'
            )),
            new Assert\GreaterThanOrEqual(array(
                'value' => 0,
                'message' => 'Queue delay must be greather or equal to {{ compared_value }}.'
            ))
        );
        
        $options['retry_delay'] = array(
            new Assert\Type(array(
                'type' => 'integer',
                'message' => 'Queue retry delay must be an integer.'
            )),
            new Assert\GreaterThanOrEqual(array(
                'value' => 0,
                'message' => 'Queue retry delay must be greather or equal to {{ compared_value }}.'
            ))
        );
        
        $options['retries'] = array(
            new Assert\Type(array(
                'type' => 'integer',
                'message' => 'Queue retry must be an integer.'
            )),
            new Assert\GreaterThanOrEqual(array(
                'value' => Job::MIN_JOB_RETRIES,
                'message' => 'Queue retry must be greather or equal to {{ compared_value }}.'
            )),
            new Assert\LessThanOrEqual(array(
                'value' => Job::MAX_JOB_RETRIES,
                'message' => 'Queue retry must be less than or equal to {{ compared_value }}.'
            ))
        );
        
        $options['callback_url'] = array(
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Queue callback URL must be a string.'
            )),
            new Assert\Url(array(
                'message' => 'Provided Queue callback URL is not a valid URL.'
            ))
        );
        
        $options['request_method'] = array(
            new Assert\Type(array(
                'type' => 'string',
                'message' => 'Queue request method must be a string.'
            )),
            new Assert\Choice(array(
                'choices' => array(
                    Request::METHOD_GET,
                    Request::METHOD_POST,
                    Request::METHOD_PUT,
                    Request::METHOD_DELETE
                ),
                'message' => 'Provided Queue request method is not currently supported.'
            ))
        );
        
        $options['headers'] = array(
            new Assert\Type(array(
                'type' => 'array',
                'message' => 'Queue headers must a map or an associative array.'
            ))
        );
        
        return new Collection($options);
    }

    /**
     *
     * {@inheritdoc}
     * @see \Cosman\Queue\Store\Validation\BaseValidator::validate()
     */
    public function validate(array $model, Client $client = null, Project $project = null, int $queueId = null): bool
    {
        if (! $client) {
            throw new \Exception(sprintf('Argument 2 passed to %s must an instanceof %s null given.', __METHOD__, Client::class));
        }
        
        if (! $project) {
            throw new \Exception(sprintf('Argument 3 passed to %s must an instanceof %s null given.', __METHOD__, Project::class));
        }
        
        $violations = $this->validator->validate($model, $this->getDefinedConstraints($model, $client, $project, $queueId));
        
        $this->errors = $this->formatErrors($violations);
        
        return 0 === count($this->errors);
    }
}