<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'app_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Validate locale
        $supportedLocales = ['en', 'de'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'en'; // fallback to English
        }

        // Store locale in session
        $request->getSession()->set('_locale', $locale);

        // Redirect back to referer or home page
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}
