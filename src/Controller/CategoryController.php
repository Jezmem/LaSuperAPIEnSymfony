<?php
namespace App\Controller;

use App\Entity\Category;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/category')]
#[OA\Tag(name: "Category")]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'categories', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupère la liste paginée des catégories",
        description: "Permet de récupérer toutes les catégories avec pagination.",
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Numéro de la page", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "limit", in: "query", description: "Nombre d'éléments par page", required: false, schema: new OA\Schema(type: "integer", default: 5))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des catégories récupérée avec succès",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: new Model(type: Category::class, groups: ['category:read']))
                )
            )
        ]
    )]
    public function getCategories(
        CategoryRepository $categoryRepository, 
        Request $request, 
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 5));
        $cacheIdentifier = "getAllCategories-" . $page . "-" . $limit;

        $categories = $cachePool->get($cacheIdentifier, function (ItemInterface $item) use ($categoryRepository, $page, $limit) {
            $item->tag("categoryCache");
            return $categoryRepository->findAllWithPagination($page, $limit);
        });

        return $this->json($categories, Response::HTTP_OK, [], ['groups' => 'getCategory']);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupère une catégorie spécifique",
        description: "Permet d'obtenir les détails d'une catégorie via son ID.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "ID de la catégorie", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Détails de la catégorie", content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category:read']))),
            new OA\Response(response: 404, description: "Catégorie non trouvée")
        ]
    )]
    public function show(Category $category, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($category, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        summary: "Crée une nouvelle catégorie",
        description: "Permet aux administrateurs d'ajouter une nouvelle catégorie.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: "name", type: "string", example: "Action")
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: "Catégorie créée avec succès", content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category:read']))),
            new OA\Response(response: 400, description: "Données invalides")
        ]
    )]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $category = new Category();
        $category->setName($data['name'] ?? '');

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->persist($category);
        $em->flush();
        return new Response($serializer->serialize($category, 'json'), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Put(
        summary: "Met à jour une catégorie existante",
        description: "Permet aux administrateurs de modifier une catégorie.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "ID de la catégorie", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: "name", type: "string", example: "RPG")
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: "Catégorie mise à jour avec succès", content: new OA\JsonContent(ref: new Model(type: Category::class, groups: ['category:read']))),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 404, description: "Catégorie non trouvée")
        ]
    )]
    public function update(Request $request, Category $category, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $category->setName($data['name'] ?? $category->getName());

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->flush();
        return new Response($serializer->serialize($category, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        summary: "Supprime une catégorie",
        description: "Permet aux administrateurs de supprimer une catégorie.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "ID de la catégorie", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Catégorie supprimée avec succès"),
            new OA\Response(response: 404, description: "Catégorie non trouvée")
        ]
    )]
    public function delete(Category $category, EntityManagerInterface $em): Response
    {
        $em->remove($category);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
