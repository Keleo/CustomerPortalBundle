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
use App\Customer\CustomerStatisticService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Project\ProjectStatisticService;
use KimaiPlugin\SharedProjectTimesheetsBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\SharedProjectTimesheetsBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\SharedProjectTimesheetsBundle\Service\ViewService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/auth/shared-project-timesheets')]
class ViewController extends AbstractController
{
    #[Route(path: '/{project}/{shareKey}', name: 'view_shared_project_timesheets', methods: ['GET', 'POST'])]
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

        return $this->renderProjectView(
            $sharedProject,
            $sharedProject->getProject(),
            $request,
            $viewService,
            $statisticsService,
        );
    }

    #[Route(path: '/customer/{customer}/{shareKey}', name: 'view_shared_project_timesheets_customer', methods: ['GET', 'POST'])]
    public function viewCustomerAction(
        Customer $customer,
        string $shareKey,
        Request $request,
        CustomerStatisticService $customerStatisticsService,
        ProjectStatisticService $projectStatisticService,
        ViewService $viewService,
        SharedProjectTimesheetRepository $sharedProjectTimesheetRepository,
    ): Response
    {
        $givenPassword = $request->get('spt-password');
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');
        $sharedProject = $sharedProjectTimesheetRepository->findByCustomerAndShareKey(
            $customer,
            $shareKey
        );

        if ($sharedProject === null) {
            throw $this->createNotFoundException('Project not found');
        }

        // Check access.
        if (!$viewService->hasAccess($sharedProject, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedProject->getCustomer(),
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
        $currency = $customer->getCurrency();

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();

        $statsPerMonth = $annualChartVisible ? $viewService->getAnnualStats($sharedProject, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $viewService->getMonthlyStats($sharedProject, $year, $month) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $customer->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $stats = $customerStatisticsService->getBudgetStatisticModel($customer, $date);
        $projects = $sharedProjectTimesheetRepository->getProjects($sharedProject);
        $projectStats = $projectStatisticService->getBudgetStatisticModelForProjects($projects, $date);

        return $this->render('@SharedProjectTimesheets/view/customer.html.twig', [
            'sharedProject' => $sharedProject,
            'customer' => $customer,
            'shareKey' => $shareKey,
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
            'projectStats' => $projectStats,
        ]);
    }

    #[Route(path: '/customer/{customer}/{shareKey}/project/{project}', name: 'view_shared_project_timesheets_project', methods: ['GET', 'POST'])]
    public function viewProjectAction(
        Customer $customer,
        string $shareKey,
        Project $project,
        Request $request,
        ProjectStatisticService $statisticsService,
        ViewService $viewService,
        SharedProjectTimesheetRepository $sharedProjectTimesheetRepository,
    ): Response
    {
        $givenPassword = $request->get('spt-password');
        $sharedProject = $sharedProjectTimesheetRepository->findByCustomerAndShareKey(
            $customer,
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

        return $this->renderProjectView(
            $sharedProject,
            $project,
            $request,
            $viewService,
            $statisticsService,
        );
    }

    protected function renderProjectView(
        SharedProjectTimesheet $sharedProject,
        Project $project,
        Request $request,
        ViewService $viewService,
        ProjectStatisticService $statisticsService,
    ): Response
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');
        $timeRecords = $viewService->getTimeRecords($sharedProject, $year, $month, $project);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Define currency.
        $currency = 'EUR';
        $customer = $project->getCustomer();

        if ($customer !== null) {
            $currency = $customer->getCurrency();
        }

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();
        $statsPerMonth = $annualChartVisible ? $viewService->getAnnualStats($sharedProject, $year, $project) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $viewService->getMonthlyStats($sharedProject, $year, $month, $project) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $project->getCustomer()->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));

        $stats = $statisticsService->getBudgetStatisticModel($project, $date);

        return $this->render('@SharedProjectTimesheets/view/project.html.twig', [
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
            'project' => $project,
        ]);
    }
}
