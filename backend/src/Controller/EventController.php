<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    #[Route('', name: 'api_events_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): JsonResponse
    {
        $user = $this->getUser();
        $events = $eventRepository->findBy(['owner' => $user], ['occursAt' => 'ASC']);

        $data = array_map(static function (Event $event): array {
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'occursAt' => $event->getOccursAt()->format(\DateTimeInterface::ATOM),
                'description' => $event->getDescription(),
            ];
        }, $events);

        return $this->json($data);
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (!isset($payload['title'], $payload['occursAt'])) {
            return $this->json(['error' => 'title and occursAt are required'], Response::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $event->setTitle($payload['title']);
        $event->setOccursAt(new \DateTimeImmutable($payload['occursAt']));
        $event->setDescription($payload['description'] ?? null);
        $event->setOwner($this->getUser());

        $em->persist($event);
        $em->flush();

        return $this->json(['id' => $event->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_events_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, EventRepository $eventRepository, EntityManagerInterface $em): JsonResponse
    {
        $event = $eventRepository->find($id);
        if (!$event || $event->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (\array_key_exists('description', $payload)) {
            $event->setDescription($payload['description']);
        }

        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'api_events_delete', methods: ['DELETE'])]
    public function delete(int $id, EventRepository $eventRepository, EntityManagerInterface $em): JsonResponse
    {
        $event = $eventRepository->find($id);
        if (!$event || $event->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($event);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}

