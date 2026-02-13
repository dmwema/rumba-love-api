<?php

namespace App\Controller\Api;

use App\DTO\CodeValidationRequest;
use App\DTO\CodeValidationResponse;
use App\Service\AccessCodeService;
use App\Service\LiveAccessTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Validation de Code")
 */
#[Route('/api/code')]
class CodeController extends AbstractController
{
    public function __construct(
        private AccessCodeService $accessCodeService,
        private LiveAccessTokenService $liveAccessTokenService,
        private ValidatorInterface $validator
    ) {}

    /**
     * Valider un code d'accès et obtenir un token live temporaire
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         type="object",
     *         required={"code"},
     *         @OA\Property(property="code", type="string", example="CINE-A1B2C3D4", description="Code d'accès au format CINE-XXXXXXXX")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Code validé avec succès",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *         @OA\Property(property="expiresIn", type="integer", example=300),
     *         @OA\Property(property="message", type="string", example="Access code validated successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Code invalide ou expiré",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", example="Invalid or expired access code")
     *     )
     * )
     */
    #[Route('/validate', name: 'api_code_validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $dto = new CodeValidationRequest($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $accessCode = $this->accessCodeService->validateCode($dto->code);

        if (!$accessCode) {
            return $this->json(['error' => 'Invalid or expired access code'], 400);
        }

        // Marquer le code comme utilisé
        $this->accessCodeService->markCodeAsUsed($accessCode);

        // Générer le token d'accès live temporaire
        $token = $this->liveAccessTokenService->generateLiveAccessToken(
            $accessCode->getUser()->getId(),
            $accessCode->getCode()
        );

        $response = new CodeValidationResponse($token);

        return $this->json([
            'token' => $response->token,
            'expiresIn' => $response->expiresIn,
            'message' => 'Access code validated successfully'
        ]);
    }
}