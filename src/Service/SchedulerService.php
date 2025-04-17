<?php

namespace App\Service;

use App\Controller\SyncFeedController;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask("0 * * * *")]
class SchedulerService
{
    public function __construct(
        private SyncFeedController $syncFeedController
    ) {}

    public function __invoke(): void
    {
        $this->syncFeedController->syncAllAdmin();
    }
}
