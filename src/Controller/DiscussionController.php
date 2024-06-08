<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\DiscussionMessages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function Symfony\Component\Clock\now;

#[Route('/discussion', name: 'discussion.')]
#[isGranted("ROLE_ASTROBIT")]
class DiscussionController extends AbstractController
{

    private string $API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=";

    #[Route('', name: 'index')]
    public function index(): Response
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

        return $this->render('discussion/index.html.twig', [
            'controller_name' => 'DiscussionController',
            'user' => $user,
            'discussions' => $discussions,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('token');

        // Not working: $this->isCsrfTokenValid('create', $submittedToken)

        if (true) {

            $discussion = new Discussion();
            $discussion->setUser($this->getUser());

            $entityManager->persist($discussion);
            $entityManager->flush();

            $this->initDiscussion($discussion, $entityManager);

            return $this->redirectToRoute('discussion.view', array('discussion' => $discussion->getId()));
        }

        return $this->redirectToRoute('discussion.index');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \DateMalformedStringException
     */
    #[Route('/response/{discussion}', name: 'response', methods: ['POST'])]
    public function getResponse(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($this->getUser() !== $discussion->getUser()) {
            return $this->json([
                'Error' => 'Access Prohibited, This discussion does not belong to you'
            ]);
        }


        $httpClient = HttpClient::create();

        $data = [
            "contents" => []
        ];

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $messages = $discussion->getDiscussionMessages();

        if (sizeof($messages) > 0) {
            foreach ($messages as $message) {
                $message_content = $message->getMessage();
                if (str_contains($message->getMessage(), "[ignore_message:system]")) {
                    $message_content = str_replace("[ignore_message:system]", "", $message_content);
                }

                $json_message = [
                    "role" => $message->getUser() ? "user" : "model",
                    "parts" => [
                        [
                            "text" => $message_content
                        ]
                    ]
                ];

                $data['contents'][] = $json_message;
            }
        }

        $user_prompt = $request->get('prompt');

        if (str_contains($request->get('prompt'), "[ignore_message:system]")) {
            $user_prompt = str_replace("[ignore_message:system]", "", $request->get('prompt'));
        }

        $data['contents'][] = [
            "role" => "user",
            "parts" => [
                [
                    "text" => $user_prompt
                ]
            ]
        ];

        $user_message = new DiscussionMessages();
        $user_message->setUser($this->getUser());
        $user_message->setDiscussion($discussion);
        $user_message->setMessage($user_prompt);

        $response = $httpClient->request('POST', $this->API_URL . $this->getParameter('app.gemini_api_key'), [
            'body' => json_encode($data),
            'headers' => $headers
        ]);

        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $final_response = $response->toArray()["candidates"][0]["content"]["parts"][0]['text'];

            // Save User message to DB:
            $discussion->addDiscussionMessage($user_message);
            $entityManager->flush();

            // Save Response message to DB:
            $response_message = new DiscussionMessages();
            $response_message->setUser(null);
            $response_message->setDiscussion($discussion);
            $response_message->setMessage($final_response);

            $discussion->addDiscussionMessage($response_message);
            $entityManager->flush();

            // Send Response back as JSON:
            return $this->json([
                "response" => $final_response
            ]);
        }

        return $this->json(['error' => 'HTTP error code: ' . $response->getStatusCode()]);
    }


    public function initDiscussion(Discussion $discussion, EntityManagerInterface $entityManager)
    {
        // Add system instruct- to DB:
        $filePath = dirname(__DIR__) . '/Prompts/system.prompt';
        $systemPrompt = file_get_contents($filePath);

        $httpClient = HttpClient::create();

        $system_message = new DiscussionMessages();
        $system_message->setUser($this->getUser());
        $system_message->setDiscussion($discussion);
        $system_message->setMessage($systemPrompt);
        $system_message->setSentAt(now());

        $discussion->addDiscussionMessage($system_message);
        $entityManager->flush();

        // Remove system prompt tag for AI request:
        $systemPrompt = str_replace("[ignore_message:system]", "", $systemPrompt);

        // Add system instruction as first request message:
        $data = [
            "contents" => []
        ];

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $json_message = [
            "role" => "user",
            "parts" => [
                [
                    "text" => $systemPrompt
                ]
            ]
        ];

        $data['contents'][] = $json_message;

        // Send System Prompt to the API
        $response = $httpClient->request('POST', $this->API_URL . $this->getParameter('app.gemini_api_key'), [
            'body' => json_encode($data),
            'headers' => $headers
        ]);


        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            // Save Response message to DB:
            $text_response = "[ignore_message:system]" . $response->toArray()["candidates"][0]["content"]["parts"][0]['text'];

            $system_message_response = new DiscussionMessages();
            $system_message_response->setUser(null);
            $system_message_response->setDiscussion($discussion);
            $system_message_response->setMessage($text_response);

            $discussion->addDiscussionMessage($system_message_response);
            $entityManager->flush();
        }
    }

    #[Route('/{discussion}/updatename', name: 'update_name', methods: ['POST'])]
    public function updateName(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
    {
        $discussion->setName($request->get('name')->value);

        $entityManager->persist($discussion);
        $entityManager->flush();

        return $this->redirectToRoute("discussion.view", array("discussion", $discussion->getId()));
    }

    #[Route('/{discussion}', name: 'view')]
    public function view(?Discussion $discussion): Response
    {
        if ($discussion == null) {
            return $this->redirectToRoute("discussion.index");
        }

        // Current Discussion:
        $current = [
            'name' => $discussion->getName(),
        ];

        // Current Discussion Messages:
        $messages = $discussion->getDiscussionMessages();

        $array_messages = [];
        foreach ($messages as $message) {
            if (!str_contains($message->getMessage(), '[ignore_message:system]')) {
                $array_message = [
                    'sentBy' => $message->getUser() ? 'You' : 'Oorbot',
                    'text' => $message->getMessage(),
                ];
                $array_messages[] = $array_message;
            }
        }

        // User Discussion List:
        $user = $this->getUser();
        $user_discussions = $user->getDiscussions();
        $discussions = [];
        if ($user_discussions !== null){
            foreach ($user->getDiscussions() as $discussion) {
                $info = [
                    'name' => $discussion->getName(),
                    'id' => $discussion->getId()
                ];
                $discussions[] = $info;
            }
        }

        return $this->render('discussion/view.html.twig', [
            'current' => $current,
            'messages' => $array_messages,
            'discussions' => $discussions,
            'user' => $this->getUser()
        ]);
    }
}
