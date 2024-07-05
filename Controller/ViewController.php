<?php

/*
 * This file is part of the "Shared Project Timesheets Bundle" for Kimai.
 * All rights reserved by Fabian Vetter (https://vettersolutions.de).
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\SharedProjectTimesheetsBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Project\ProjectStatisticService;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ViewService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/auth/shared-project-timesheets')]
class ViewController extends AbstractController
{
    public function __construct(
        private ViewService $viewService,
        private SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
    ) {
    }

    #[Route(path: '/{id}/{shareKey}', name: 'view_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function indexAction(Project $project, string $shareKey, Request $request, ProjectStatisticService $statisticsService): Response
    {
        $givenPassword = $request->get('spt-password');
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');

        // Get project.
        $sharedProject = $this->sharedProjectTimesheetRepository->findByProjectAndShareKey(
            $project->getId(),
            $shareKey
        );

        if ($sharedProject === null) {
            throw $this->createNotFoundException('Project not found');
        }

        // Check access.
        if (!$this->viewService->hasAccess($sharedProject, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedProject->getProject(),
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        // Get time records.
        $timeRecords = $this->viewService->getTimeRecords($sharedProject, $year, $month);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Define currency.
        $currency = 'EUR';
        $customer = $sharedProject->getProject()?->getCustomer();
        if ($customer !== null) {
            $currency = $customer->getCurrency();
        }

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();

        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedProject, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedProject, $year, $month) : null;

        $stats = $statisticsService->getBudgetStatisticModel($project, $this->getDateTimeFactory()->createDateTime());

        return $this->render('@SharedProjectTimesheets/view/timesheet.html.twig', [
            'sharedProject' => $sharedProject,
            'timeRecords' => $timeRecords,
            'rateSum' => $rateSum,
            'durationSum' => $durationSum,
            'year' => $year,
            'month' => $month,
            'currency' => $currency,
            'statsPerMonth' => $statsPerMonth,
            'monthlyChartVisible' => $monthlyChartVisible,
            'statsPerDay' => $statsPerDay,
            'detailsMode' => $detailsMode,
            'stats' => $stats,
        ]);
    }
}
