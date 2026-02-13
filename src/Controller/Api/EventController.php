<?php

namespace App\Controller\Api;

use App\Entity\LiveEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Événement Public")
 */
#[Route('/api/event')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Obtenir les informations publiques du concert
     *
     * @OA\Response(
     *     response=200,
     *     description="Informations de l'événement retournées avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="title", type="string", example="Concert Live - Artiste Mystère"),
     *         @OA\Property(property="description", type="string", example="Un concert exceptionnel en direct"),
     *         @OA\Property(property="imageUrl", type="string", example="https://example.com/image.jpg"),
     *         @OA\Property(property="price", type="string", example="10.00"),
     *         @OA\Property(property="isActive", type="boolean", example=false),
     *         @OA\Property(property="liveDate", type="string", format="date-time", example="2026-02-15T20:00:00+00:00")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Aucun événement disponible",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="No live event available")
     *     )
     * )
     */
    #[Route('', name: 'api_event_get', methods: ['GET'])]
    public function getEvent(): JsonResponse
    {
        $event = $this->entityManager->getRepository(LiveEvent::class)->findOneBy([], ['id' => 'DESC']);

        if (!$event) {
            return $this->json([
                'error' => 'No live event available'
            ], 404);
        }

        return $this->json([
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'imageUrl' => $event->getImageUrl(),
            'price' => $event->getPrice(),
            'isActive' => $event->isActive(),
            'liveDate' => $event->getLiveDate()->format('c'),
        ]);
    }
}