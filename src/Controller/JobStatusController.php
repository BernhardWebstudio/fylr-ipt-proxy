<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\ImportDataMessage;
use App\Service\JobStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
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

    #[Route('/job/{jobId}/cancel', name: 'app_job_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelJob(string $jobId): Response
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_jobs_list');
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_jobs_list');
        }

        if (!$jobStatus->canBeCancelled()) {
            $this->addFlash('error', 'This job cannot be cancelled.');
            return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
        }

        if ($this->jobStatusService->cancelJob($jobId)) {
            $this->addFlash('success', 'Job cancelled successfully.');
        } else {
            $this->addFlash('error', 'Failed to cancel job.');
        }

        return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
    }

    #[Route('/job/{jobId}/reset', name: 'app_job_reset', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resetJob(string $jobId, MessageBusInterface $messageBus): Response
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_jobs_list');
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_jobs_list');
        }

        if (!$jobStatus->canBeReset()) {
            $this->addFlash('error', 'Only failed jobs can be reset.');
            return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
        }

        if ($jobStatus->getType() !== 'import') {
            $this->addFlash('error', 'Reset is currently only supported for import jobs.');
            return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
        }

        if ($this->jobStatusService->resetJob($jobId)) {
            // Re-dispatch the import message with the same parameters
            // We need to reconstruct the message from the job status
            $criteria = $jobStatus->getCriteria() ?? [];
            $type = $criteria['objectType'] ?? $jobStatus->getType();

            // For now, we'll assume the session credentials are still valid
            // We might want to store credentials separately or require re-authentication
            $request = $this->container->get('request_stack')->getCurrentRequest();
            $session = $request->getSession();
            $easydbToken = $session->get('easydb_token');
            $easydbSessionContent = $session->get('easydb_session_content');
            $isFylr = $session->get('easydb_is_fylr', false);

            if (!$easydbToken) {
                $this->addFlash('error', 'EasyDB session expired. Please log in again to retry the job.');
                return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
            }

            $importMessage = new ImportDataMessage(
                $jobId,
                $type,
                $criteria,
                $user->getId(),
                $easydbToken,
                $easydbSessionContent,
                $isFylr
            );
            $messageBus->dispatch($importMessage);

            $this->addFlash('success', 'Job reset and re-started successfully.');
        } else {
            $this->addFlash('error', 'Failed to reset job.');
        }

        return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
    }

    #[Route('/job/{jobId}/delete', name: 'app_job_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteJob(string $jobId): Response
    {
        $jobStatus = $this->jobStatusService->getJobStatus($jobId);

        if (!$jobStatus) {
            $this->addFlash('error', 'Job not found.');
            return $this->redirectToRoute('app_jobs_list');
        }

        // Check if user has access to this job
        /** @var User $user */
        $user = $this->getUser();
        if ($jobStatus->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_jobs_list');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        if ($this->jobStatusService->deleteJob($jobId, $projectDir)) {
            $this->addFlash('success', 'Job deleted successfully.');
        } else {
            $this->addFlash('error', 'Failed to delete job.');
        }

        return $this->redirectToRoute('app_jobs_list');
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
