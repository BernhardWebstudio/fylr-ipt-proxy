<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\OccurrenceImportRepository;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    #[Route('/home', name: 'app_home')]
    public function index(OccurrenceImportRepository $occurrenceImportRepository): Response
    {
        $totalCount = $occurrenceImportRepository->getTotalCount();
        $countsByType = $occurrenceImportRepository->getCountsByObjectType();
        $countsByTag = $occurrenceImportRepository->getCountsByTagId();

        return $this->render('home/index.html.twig', [
            'stats' => [
                'total' => $totalCount,
                'byType' => $countsByType,
                'byTag' => $countsByTag,
            ],
        ]);
    }
}
