<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JobStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class JobStatusController extends AbstractController
{
    public function __construct(
        private JobStatusService $jobStatusService
    ) {}

    #[Route('/job/{jobId}', name: 'app_job_status')]
    #[IsGranted('ROLE_USER')]
    public function jobStatus(string $jobId): Response
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_home');
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('job_status/status.html.twig', [
            'job' => $jobStatus,
        ]);
    }

    #[Route('/job/{jobId}/api', name: 'app_job_status_api', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function jobStatusApi(string $jobId): JsonResponse
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            return new JsonResponse(['error' => 'Job not found'], 404);
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse([
            'jobId' => $jobStatus->getJobId(),
            'type' => $jobStatus->getType(),
            'status' => $jobStatus->getStatus(),
            'progress' => $jobStatus->getProgress(),
            'totalItems' => $jobStatus->getTotalItems(),
            'progressPercentage' => $jobStatus->getProgressPercentage(),
            'errorMessage' => $jobStatus->getErrorMessage(),
            'resultFilePath' => $jobStatus->getResultFilePath(),
            'createdAt' => $jobStatus->getCreatedAt()?->format('Y-m-d H:i:s'),
            'completedAt' => $jobStatus->getCompletedAt()?->format('Y-m-d H:i:s'),
            'format' => $jobStatus->getFormat(),
        ]);
    }

    #[Route('/job/{jobId}/download', name: 'app_job_download')]
    #[IsGranted('ROLE_USER')]
    public function downloadJobResult(string $jobId): Response
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_home');
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_home');
        }

        if (!$jobStatus->isCompleted() || !$jobStatus->getResultFilePath()) {
            $this->addFlash('error', 'Job is not completed or has no result file.');
            return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/' . $jobStatus->getResultFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Result file not found.');
            return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($filePath) {
            readfile($filePath);
        });

        $filename = basename($filePath);
        $response->headers->set('Content-Type', $this->getContentTypeForFile($filename));
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Content-Length', (string) filesize($filePath));

        return $response;
    }

    #[Route('/jobs', name: 'app_jobs_list')]
    #[IsGranted('ROLE_USER')]
    public function jobsList(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $jobs = $this->jobStatusService->getUserJobs($user, 50);

        return $this->render('job_status/list.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    private function getContentTypeForFile(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => 'application/octet-stream',
        };
    }
}
