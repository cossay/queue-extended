<?php
declare(strict_types = 1);
namespace Cosman\Queue\Store\Table;

/**
 *
 * @author cosman
 *        
 */
class QueueTable extends BaseTable
{

    const NAME = 'queues';

    const FIELD_ID = 'queues.id';

    const FIELD_CODE = 'queues.code';

    const FIELD_NAME = 'queues.name';

    const FIELD_DESCRIPTION = 'queues.description';

    const FIELD_PROJECT_ID = 'queues.project_id';

    const FIELD_HEADERS = 'queues.headers';

    const FIELD_DELAY = 'queues.delay';

    const FIELD_RETRY_DELAY = 'queues.retry_delay';

    const FIELD_RETRIES = 'queues.retries';

    const FIELD_REQUEST_METHOD = 'queues.request_method';

    const FIELD_CALLBACK_URL = 'queues.callback_url';

    const FIELD_CREATED_AT = 'queues.created_at';

    const FIELD_UPDATED = 'queues.updated_at';
}