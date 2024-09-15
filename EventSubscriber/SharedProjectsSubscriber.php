<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\EventSubscriber;

use App\Event\PageActionsEvent;
use App\EventSubscriber\Actions\AbstractActionsSubscriber;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;

class SharedProjectsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'shared_projects';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addCreate($this->path('create_shared_project_timesheets'));

        $event->addActionToSubmenu('create', 'project', [
            'url' => $this->path('create_shared_project_timesheets', ['type' => SharedProjectTimesheet::TYPE_PROJECT]),
            'class' => 'action-create modal-ajax-form',
            'title' => 'project',
        ]);

        $event->addActionToSubmenu('create', 'customer', [
            'url' => $this->path('create_shared_project_timesheets', ['type' => SharedProjectTimesheet::TYPE_CUSTOMER]),
            'class' => 'action-create modal-ajax-form',
            'title' => 'customer',
        ]);
    }
}
