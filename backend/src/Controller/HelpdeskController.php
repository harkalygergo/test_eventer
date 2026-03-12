<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HelpdeskController extends AbstractController
{
    #[Route('/api/helpdesk/messages', name: 'api_helpdesk_message', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function sendMessage(Request $request, ConversationRepository $conversationRepository, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $text = (string)($payload['text'] ?? '');

        if ($text === '') {
            return $this->json(['error' => 'text is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        $conversation = $conversationRepository->findOneBy(['user' => $user, 'status' => 'open']);
        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser($user);
            $em->persist($conversation);
        }

        $userMessage = new Message();
        $userMessage->setSender('user');
        $userMessage->setContent($text);
        $conversation->addMessage($userMessage);

        if (str_contains(mb_strtolower($text), 'agent')) {
            $conversation->setStatus('waiting_agent');
            $botReplyText = 'I have forwarded your conversation to a human helpdesk agent.';
        } else {
            $botReplyText = $this->generateBotReply($text);
        }

        $botMessage = new Message();
        $botMessage->setSender('bot');
        $botMessage->setContent($botReplyText);
        $conversation->addMessage($botMessage);

        $em->flush();

        return $this->json($this->serializeConversation($conversation), Response::HTTP_CREATED);
    }

    #[Route('/api/helpdesk/conversations', name: 'api_helpdesk_conversations', methods: ['GET'])]
    #[IsGranted('ROLE_HELPDESK')]
    public function listConversations(ConversationRepository $conversationRepository): JsonResponse
    {
        $conversations = $conversationRepository->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(fn (Conversation $c) => $this->serializeConversation($c), $conversations);

        return $this->json($data);
    }

    #[Route('/api/helpdesk/conversations/{id}/reply', name: 'api_helpdesk_reply', methods: ['POST'])]
    #[IsGranted('ROLE_HELPDESK')]
    public function reply(int $id, Request $request, ConversationRepository $conversationRepository, EntityManagerInterface $em): JsonResponse
    {
        $conversation = $conversationRepository->find($id);
        if (!$conversation) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $text = (string)($payload['text'] ?? '');

        if ($text === '') {
            return $this->json(['error' => 'text is required'], Response::HTTP_BAD_REQUEST);
        }

        $message = new Message();
        $message->setSender('agent');
        $message->setContent($text);
        $conversation->addMessage($message);
        $conversation->setStatus('open');

        $em->flush();

        return $this->json($this->serializeConversation($conversation));
    }

    private function serializeConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->getId(),
            'user' => $conversation->getUser()->getUserIdentifier(),
            'status' => $conversation->getStatus(),
            'createdAt' => $conversation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'messages' => array_map(static function (Message $m): array {
                return [
                    'id' => $m->getId(),
                    'sender' => $m->getSender(),
                    'content' => $m->getContent(),
                    'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ];
            }, $conversation->getMessages()->toArray()),
        ];
    }

    private function generateBotReply(string $text): string
    {
        $lower = mb_strtolower($text);

        if (str_contains($lower, 'reset') && str_contains($lower, 'password')) {
            return 'To reset your password, use the password reset option on the login screen. If you still have trouble, ask for a human agent by mentioning "agent".';
        }

        if (str_contains($lower, 'event')) {
            return 'You can create, list, update and delete your events from the Events page.';
        }

        return 'I\'m a simple virtual assistant. Please clarify your question or mention "agent" to talk to a human helpdesk agent.';
    }
}

