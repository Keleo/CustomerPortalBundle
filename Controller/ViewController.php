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

class ViewController extends AbstractController
{
    public function __construct(
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly ViewService $viewService,
        private readonly SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
    ) {
    }

    #[Route(path: '/auth/shared-project-timesheets/{project}/{shareKey}', name: 'customer_portal_deprecated_project', methods: ['GET', 'POST'])]
    #[Route(path: '/auth/customer-portal/p/{project}/{shareKey}', name: 'view_shared_project_timesheets', methods: ['GET', 'POST'])]
    public function indexAction(Project $project, string $shareKey, Request $request): Response
    {
        $givenPassword = $request->get('spt-password');

        $sharedPortal = $this->sharedProjectTimesheetRepository->findByProjectAndShareKey(
            $project->getId(),
            $shareKey
        );

        if ($sharedPortal === null) {
            throw $this->createNotFoundException('Project not found');
        }

        // Check access.
        if (!$this->viewService->hasAccess($sharedPortal, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedPortal->getProject(),
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        return $this->renderProjectView($sharedPortal, $sharedPortal->getProject(), $request);
    }

    #[Route(path: '/auth/shared-project-timesheets/customer/{customer}/{shareKey}', name: 'customer_portal_deprecated_customer', methods: ['GET', 'POST'])]
    #[Route(path: '/auth/customer-portal/c/{customer}/{shareKey}', name: 'view_shared_project_timesheets_customer', methods: ['GET', 'POST'])]
    public function viewCustomerAction(Customer $customer, string $shareKey, Request $request, CustomerStatisticService $customerStatisticsService): Response
    {
        $givenPassword = $request->get('spt-password');
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');
        $sharedPortal = $this->sharedProjectTimesheetRepository->findByCustomerAndShareKey(
            $customer,
            $shareKey
        );

        if ($sharedPortal === null) {
            throw $this->createNotFoundException('Project not found');
        }

        // Check access.
        if (!$this->viewService->hasAccess($sharedPortal, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedPortal->getCustomer(),
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        // Get time records.
        $timeRecords = $this->viewService->getTimeRecords($sharedPortal, $year, $month);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Prepare stats for charts.
        $annualChartVisible = $sharedPortal->isAnnualChartVisible();
        $monthlyChartVisible = $sharedPortal->isMonthlyChartVisible();

        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedPortal, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedPortal, $year, $month) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $customer->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $stats = $customerStatisticsService->getBudgetStatisticModel($customer, $date);
        $projects = $this->sharedProjectTimesheetRepository->getProjects($sharedPortal);
        $projectStats = $this->projectStatisticService->getBudgetStatisticModelForProjects($projects, $date);

        return $this->render('@SharedProjectTimesheets/view/customer.html.twig', [
            'sharedProject' => $sharedPortal,
            'customer' => $customer,
            'shareKey' => $shareKey,
            'timeRecords' => $timeRecords,
            'rateSum' => $rateSum,
            'durationSum' => $durationSum,
            'year' => $year,
            'month' => $month,
            'currency' => $customer->getCurrency(),
            'statsPerMonth' => $statsPerMonth,
            'monthlyChartVisible' => $monthlyChartVisible,
            'statsPerDay' => $statsPerDay,
            'detailsMode' => $detailsMode,
            'stats' => $stats,
            'projectStats' => $projectStats,
        ]);
    }

    #[Route(path: '/auth/shared-project-timesheets/customer/{customer}/{shareKey}/project/{project}', name: 'customer_portal_deprecated_customer_project', methods: ['GET', 'POST'])]
    #[Route(path: '/auth/customer-portal/customer/{customer}/{shareKey}/project/{project}', name: 'view_shared_project_timesheets_project', methods: ['GET', 'POST'])]
    public function viewCustomerProjectAction(Customer $customer, string $shareKey, Project $project, Request $request): Response
    {
        $givenPassword = $request->get('spt-password');
        $sharedPortal = $this->sharedProjectTimesheetRepository->findByCustomerAndShareKey(
            $customer,
            $shareKey
        );

        if ($sharedPortal === null) {
            throw $this->createNotFoundException('Project not found');
        }

        if ($project->getCustomer() !== $customer) {
            throw $this->createAccessDeniedException('Requested project does not match customer');
        }

        // Check access.
        if (!$this->viewService->hasAccess($sharedPortal, $givenPassword)) {
            return $this->render('@SharedProjectTimesheets/view/auth.html.twig', [
                'project' => $sharedPortal->getProject(),
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        return $this->renderProjectView($sharedPortal, $project, $request);
    }

    private function renderProjectView(SharedProjectTimesheet $sharedProject, Project $project, Request $request): Response
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');
        $timeRecords = $this->viewService->getTimeRecords($sharedProject, $year, $month, $project);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Define currency.
        $currency = $project->getCustomer()?->getCurrency() ?? Customer::DEFAULT_CURRENCY;

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();
        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedProject, $year, $project) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedProject, $year, $month, $project) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $project->getCustomer()->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));

        $stats = $this->projectStatisticService->getBudgetStatisticModel($project, $date);

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
