<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Validation;

use Cosman\Queue\Store\Model\Job;
use Cosman\Queue\Store\Validation\Constraint\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;

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
    protected function getDefinedConstraints(array $data = []): Collection
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
}