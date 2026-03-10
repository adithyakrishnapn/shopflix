<?php

namespace Webkul\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Shetabit\Visitor\Visitor as BaseVisitor;
use Webkul\Core\Jobs\UpdateCreateVisitIndex;

class Visitor extends BaseVisitor
{
    /**
     * Create a visit log.
     *
     * @return void
     */
    public function visit(?Model $model = null)
    {
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return;
            }
        }

        try {
            $log = $this->prepareLog();
        } catch (\Throwable $exception) {
            // Visitor analytics should never break storefront requests.
            return;
        }

        UpdateCreateVisitIndex::dispatch($model, $log);
    }

    /**
     * Retrieve request's url
     */
    public function url(): string
    {
        return $this->request->url();
    }

    /**
     * Prepare log's data.
     *
     *
     * @throws \Exception
     */
    protected function prepareLog(): array
    {
        $log = parent::prepareLog();

        $currentChannel = core()->getCurrentChannel();

        $visitTable = config('visitor.table_name', 'visits');

        if (
            $currentChannel?->id
            && Schema::hasTable($visitTable)
            && Schema::hasColumn($visitTable, 'channel_id')
        ) {
            $log['channel_id'] = $currentChannel->id;
        }

        return $log;
    }

    /**
     * Returns logs
     *
     * @return array
     */
    public function getLog()
    {
        return $this->prepareLog();
    }
}
