<?php

namespace App\Controller;

use App\Service\EasydbApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to home page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/sync-roles', name: 'app_sync_roles')]
    #[IsGranted('ROLE_USER')]
    public function syncRoles(
        EntityManagerInterface $entityManager,
        EasydbApiService $easydbApiService,
    ): Response {
        $user = $this->getUser();

        // load object types accessible to the user from EasyDB, and set them in the User entity
        assert($user instanceof \App\Entity\User);
        $objectTypes = $easydbApiService->fetchObjectTypes();

        foreach ($objectTypes as $objType) {
            $objTypeId = $objType['_id'];
            $objTypeName = $objType['name'];

            // load from DB if exists, else create new
            $easydbObjectType = $entityManager->getRepository(\App\Entity\EasydbObjectType::class)
                ->findOneBy(['name' => $objTypeName]);
            if (!$easydbObjectType) {
                $easydbObjectType = new \App\Entity\EasydbObjectType();
                // $easydbObjectType->setEasydbId($objTypeId);
                $easydbObjectType->setName($objTypeName);
                $entityManager->persist($easydbObjectType);
            }

            // set on the user if not already set
            $user->addAccessibleObjectType($easydbObjectType);
        }

        // Save changes
        $entityManager->persist($user);
        $entityManager->flush();

        // redirect to previous page or home if not available
        $referer = $this->container->get('request_stack')->getCurrentRequest()->headers->get('referer');
        $redirectUrl = $referer ?: $this->generateUrl('app_home');
        $this->addFlash('info', 'User roles synchronized from EasyDB.');
        return $this->redirect($redirectUrl);
    }
}
