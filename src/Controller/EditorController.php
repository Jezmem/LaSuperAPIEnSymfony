<?php
namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
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

#[Route('/editor')]
class EditorController extends AbstractController
{
    #[Route('/', name: 'editors', methods: ['GET'])]
    public function getEditors(
        EditorRepository $editorRepository, 
        Request $request, 
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 5));
        $cacheIdentifier = "getAllEditors-" . $page . "-" . $limit;

        $editors = $cachePool->get($cacheIdentifier, function (ItemInterface $item) use ($editorRepository, $page, $limit) {
            $item->tag("editorCache");
            return $editorRepository->findAllWithPagination($page, $limit);
        });

        return $this->json($editors, Response::HTTP_OK, [], ['groups' => 'getEditor']);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Editor $editor, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($editor, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $editor = new Editor();
        $editor->setName($data['name'] ?? '');
        $editor->setCountry($data['country'] ?? '');

        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->persist($editor);
        $em->flush();
        return new Response($serializer->serialize($editor, 'json'), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Editor $editor, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $editor->setName($data['name'] ?? $editor->getName());
        $editor->setCountry($data['country'] ?? $editor->getCountry());

        $errors = $validator->validate($editor);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->flush();
        return new Response($serializer->serialize($editor, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Editor $editor, EntityManagerInterface $em): Response
    {
        $em->remove($editor);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
