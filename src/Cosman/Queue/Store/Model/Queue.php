<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Model;

use Cosman\Queue\Store\Table\QueueTable;
use Cosman\Queue\Store\Table\Schema\Field;
use Cosman\Queue\Support\Reader\PropertyReader;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @author cosman
 *        
 */
class Queue extends BaseModel
{

    /**
     *
     * @var Project
     */
    protected $project;

    /**
     *
     * @var string
     */
    protected $code;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var string[][]
     */
    protected $headers = [];

    /**
     *
     * @var string
     */
    protected $request_method = Request::METHOD_GET;

    /**
     *
     * @var int
     */
    protected $retries = 1;

    /**
     *
     * @var int
     */
    protected $delay = 0;

    /**
     *
     * @var int
     */
    protected $retry_delay = 0;

    /**
     *
     * @var string
     */
    protected $callback_url;

    /**
     * Returns job unique code
     *
     * @return string|NULL
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Sets job unique code
     *
     * @param string $code
     * @return self
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;
        
        return $this;
    }

    /**
     * Returns queue name
     *
     * @return string|NULL
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets queue name
     *
     * @param string $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        
        return $this;
    }

    /**
     * Returns queue descripion
     *
     * @return string|NULL
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets queue description
     *
     * @param string $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        
        return $this;
    }

    /**
     * Returns a queue's project
     *
     * @return \Cosman\Queue\Store\Model\Project|NULL
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Sets a queue's project
     *
     * @param Project $project
     * @return self
     */
    public function setProject(?Project $project): self
    {
        $this->project = $project;
        
        return $this;
    }

    /**
     * Returns queue headers
     *
     * @return string[][]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets headers for queue
     *
     * @param string[][] $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        
        return $this;
    }
    
    /**
     * Returns request method
     *
     * @return string|NULL
     */
    public function getRequestMethod(): ?string
    {
        return $this->request_method;
    }
    
    /**
     * Sets request method
     *
     * @param string $callback_url
     * @return self
     */
    public function setRequestMethod(?string $method): self
    {
        $this->request_method = $method;
        
        return $this;
    }

    /**
     * Returns callback url
     *
     * @return string|NULL
     */
    public function getCallbackUrl(): ?string
    {
        return $this->callback_url;
    }

    /**
     * Sets callback url
     *
     * @param string $callback_url
     * @return self
     */
    public function setCallbackUrl(?string $callback_url): self
    {
        $this->callback_url = $callback_url;
        
        return $this;
    }
    
    /**
     * Returns number of retries for a job in this queue
     *
     * @return int|NULL
     */
    public function getRetries(): ?int
    {
        return $this->retries;
    }
    
    /**
     * Sets number of retries for a job in this queue
     *
     * @param int $retries
     * @return self
     */
    public function setRetries(?int $retries): self
    {
        $this->retries = $retries;
        
        return $this;
    }
    
    /**
     * Returns number of seconds a job should be delayed before being executed
     *
     * @return int|NULL
     */
    public function getDelay(): ?int
    {
        return $this->delay;
    }
    
    /**
     * Sets number of seconds a job should be delayed before being executed
     *
     * @param int $delay
     * @return self
     */
    public function setDelay(?int $delay): self
    {
        $this->delay = $delay;
        
        return $this;
    }
    
    /**
     * Returns number of seconds a failed job in this queue should be delayed before being executed
     *
     * @return int|NULL
     */
    public function getRetryDelay(): ?int
    {
        return $this->retry_delay;
    }
    
    /**
     * Sets number of seconds a failed job in this queue should be delayed before being executed
     *
     * @param int $delay
     * @return self
     */
    public function setRetryDelay(?int $delay): self
    {
        $this->retry_delay = $delay;
        
        return $this;
    }
    

    /**
     * Checks if a given job belongs to queue
     *
     * @param Job $job
     * @return bool
     */
    public function ownsJob(?Job $job): bool
    {
        return (null !== $job) && ($job->getQueue() instanceof Queue) && ($job->getQueue()->getId() === $this->getId());
    }

    /**
     *
     * @param mixed $model
     * @param string $prefix
     * @param string $suffix
     * @param mixed $default
     * @return BaseModel|NULL
     */
    public static function createInstance($model, string $prefix = '', string $suffix = '', $default = null): ?BaseModel
    {
        if (! $model) {
            return null;
        }
        
        $table = QueueTable::definition()->getFields();
        $fieldDefault = new Field('');
        
        $idField = $table->get(QueueTable::FIELD_ID, $fieldDefault)->getName();
        
        $reader = new PropertyReader($model, $prefix, $suffix);
        
        $id = $reader->read($idField);
        
        if (empty($id)) {
            return null;
        }
        
        $codeField = $table->get(QueueTable::FIELD_CODE, $fieldDefault)->getName();
        $nameField = $table->get(QueueTable::FIELD_NAME, $fieldDefault)->getName();
        $descriptionField = $table->get(QueueTable::FIELD_DESCRIPTION, $fieldDefault)->getName();
        $callbackUrlField = $table->get(QueueTable::FIELD_CALLBACK_URL, $fieldDefault)->getName();
        $delayField = $table->get(QueueTable::FIELD_DELAY, $fieldDefault)->getName();
        $retryDelayField = $table->get(QueueTable::FIELD_RETRY_DELAY, $fieldDefault)->getName();
        $headersField = $table->get(QueueTable::FIELD_HEADERS, $fieldDefault)->getName();
        $methodField = $table->get(QueueTable::FIELD_REQUEST_METHOD, $fieldDefault)->getName();
        $retriesField = $table->get(QueueTable::FIELD_RETRIES, $fieldDefault)->getName();
        $createdAtField = $table->get(QueueTable::FIELD_CREATED_AT, $fieldDefault)->getName();
        $updatedAtField = $table->get(QueueTable::FIELD_UPDATED, $fieldDefault)->getName();
        
        $instance = new static();
        
        $instance->setId((int) $id);
        $instance->setCode((string) $reader->read($codeField));
        $instance->setName((string) $reader->read($nameField));
        $instance->setDescription((string) $reader->read($descriptionField));
        $instance->setDelay((int) $reader->read($delayField));
        $instance->setRetryDelay((int) $reader->read($retryDelayField));
        
        $headers = $reader->read($headersField);
        
        if (is_string($headers)) {
            $headers = json_decode($headers, true);
            
            if (! is_array($headers)) {
                $headers = (array) $headers;
            }
            
            $instance->setHeaders($headers);
        }
        
        $instance->setCallbackUrl((string) $reader->read($callbackUrlField));
        $instance->setRequestMethod((string) $reader->read($methodField));
        $instance->setRetries((int) $reader->read($retriesField));
        $instance->setCreatedAt($instance->createDatetime((string) $reader->read($createdAtField)));
        $instance->setUpdatedAt($instance->createDatetime((string) $reader->read($updatedAtField)));
        
        return $instance;
    }
}