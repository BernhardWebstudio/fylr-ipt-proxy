<?php

namespace App\Controller;

use App\Service\EasydbApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EasydbController extends AbstractController
{
    public function __construct(
        private EasydbApiService $easydbApiService
    ) {}

    #[Route('/easydb/status', name: 'easydb_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function status(): JsonResponse
    {
        try {
            if (!$this->easydbApiService->hasValidSession()) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No valid EasyDB session'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $status = $this->easydbApiService->getServerStatus();

            return new JsonResponse([
                'status' => 'success',
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/easydb/search', name: 'easydb_search', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function search(): JsonResponse
    {
        try {
            $query = []; // You would parse this from the request body

            $results = $this->easydbApiService->search($query);

            return new JsonResponse([
                'status' => 'success',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
