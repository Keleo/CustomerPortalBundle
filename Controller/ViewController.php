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
    #[Route(path: '/{id}/{shareKey}', name: 'view_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function indexAction(
        Project $project,
        string $shareKey,
        Request $request,
        ProjectStatisticService $statisticsService,
        ViewService $viewService,
        SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
    ): Response
    {
        $givenPassword = $request->get('spt-password');
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');

        // Get project.
        $sharedProject = $sharedProjectTimesheetRepository->findByProjectAndShareKey(
            $project->getId(),
            $shareKey
        );

        if ($sharedProject === null) {
            throw $this->createNotFoundException('Project not found');
        }

        // Check access.
        if (!$viewService->hasAccess($sharedProject, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedProject->getProject(),
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        // Get time records.
        $timeRecords = $viewService->getTimeRecords($sharedProject, $year, $month);

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

        $statsPerMonth = $annualChartVisible ? $viewService->getAnnualStats($sharedProject, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $viewService->getMonthlyStats($sharedProject, $year, $month) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $project->getCustomer()->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));

        $stats = $statisticsService->getBudgetStatisticModel($project, $date);

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
