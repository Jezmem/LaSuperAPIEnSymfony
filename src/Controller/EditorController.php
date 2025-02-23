<?php
namespace App\Controller;

use App\Entity\Editor;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
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

#[Route('/api/editor')]
#[OA\Tag(name: 'Editor')]
class EditorController extends AbstractController
{
    #[Route('/', name: 'editors', methods: ['GET'])]
    #[OA\Get(
        summary: 'Retrieve a paginated list of editors',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Editor::class, groups: ['editor:read']))
                )
            )
        ]
    )]
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
    #[OA\Get(
        summary: 'Retrieve an editor by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(ref: new Model(type: Editor::class, groups: ['editor:read']))
            )
        ]
    )]
    public function show(Editor $editor, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($editor, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        summary: 'Create a new editor',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'country', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Editor created')
        ]
    )]
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
    #[OA\Put(
        summary: 'Update an existing editor',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'country', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Editor updated')
        ]
    )]
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
    #[OA\Delete(
        summary: 'Delete an editor',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Editor deleted')
        ]
    )]
    public function delete(Editor $editor, EntityManagerInterface $em): Response
    {
        $em->remove($editor);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
