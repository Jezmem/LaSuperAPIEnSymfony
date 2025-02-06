<?php
namespace App\Controller;

use App\Entity\Category;
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

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'categories', methods: ['GET'])]
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
    public function show(Category $category, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($category, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
    public function delete(Category $category, EntityManagerInterface $em): Response
    {
        $em->remove($category);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
