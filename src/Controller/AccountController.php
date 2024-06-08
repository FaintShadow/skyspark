<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(name: 'account.')]
class AccountController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->redirectToRoute('account.login');
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirect('/login');
        }

        $response = new Response(null, $form->isSubmitted() ? 422 : 200);

        return $this->render('account/create.html.twig', [
            'form' => $form->createView()
        ], $response);
    }

    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('discussion.index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('account/login.html.twig', ['last_username' => $lastUsername ?? '', 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/update', name: 'update', methods: ['POST'])]
    #[isGranted('ROLE_ASTROBIT')]
    public function user_update(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if ($request->getMethod() == 'POST' && $this->getUser()) {
            if ($request->get('new_password') !== null && $request->get('old_password') !== null) {
                $isPasswordValid = $userPasswordHasher->isPasswordValid(
                    $this->getUser(),
                    $request->get('old_password')
                );
                if ($isPasswordValid) {
                    $new_password = $userPasswordHasher->hashPassword(
                        $this->getUser(),
                        $request->get('new_password')
                    );
                    $this->getUser()->setPassword($new_password);
                    $entityManager->persist($this->getUser());
                    $entityManager->flush();
                }
            }
            if (strtolower($request->get('email')) != strtolower($this->getUser()->getUserIdentifier())) {
                $this->getUser()->setEmail(strtolower($request->get('email')));
                $entityManager->persist($this->getUser());
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute('account.settings');
    }

    #[Route('/delete', name: 'delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if ($request->getMethod() == 'POST' && $this->getUser()) {
            if (strtolower($request->get('delete_confirmation')) == strtolower($this->getUser()->getUserIdentifier())) {

                $user = $this->getUser();
                $user->erasePersonalData();

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('account.login');
            }

            if ($request->get('new_password') !== null && $request->get('old_password') !== null) {
                $isPasswordValid = $userPasswordHasher->isPasswordValid(
                    $this->getUser(),
                    $request->get('old_password')
                );
                if ($isPasswordValid) {
                    $new_password = $userPasswordHasher->hashPassword(
                        $this->getUser(),
                        $request->get('new_password')
                    );
                    $this->getUser()->setPassword($new_password);
                    $entityManager->persist($this->getUser());
                    $entityManager->flush();
                }
            }
        }
        return $this->redirectToRoute($request->getRequestUri());
        // return $this->render('account/delete.html.twig', []);
    }

    #[Route('/settings', name: 'settings')]
    #[isGranted("ROLE_ASTROBIT")]
    public function settings(): Response
    {
        $user = $this->getUser();

        $discussions = [];
        if ($user->getDiscussions() !== null) {
            foreach ($user->getDiscussions() as $discussion) {
                $info = [
                    'name' => $discussion->getName(),
                    'id' => $discussion->getId()
                ];
                $discussions[] = $info;
            }
        }

        return $this->render('account/settings.html.twig', [
            'discussions' => $discussions,
            'user' => $this->getUser()
        ]);
    }
}
