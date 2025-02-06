<?php
namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\VideoGameRepository;
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

#[Route('/videogame')]
class VideoGameController extends AbstractController
{
    #[Route('/', name: 'videogames', methods: ['GET'])]
    public function getVideoGames(
        VideoGameRepository $videoGameRepository, 
        Request $request, 
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 5));
        $cacheIdentifier = "getAllVideoGames-" . $page . "-" . $limit;

        $videoGames = $cachePool->get($cacheIdentifier, function (ItemInterface $item) use ($videoGameRepository, $page, $limit) {
            $item->tag("videogameCache");
            return $videoGameRepository->findAllWithPagination($page, $limit);
        });

        return $this->json($videoGames, Response::HTTP_OK, [], ['groups' => 'getVideoGame']);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(VideoGame $game, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($game, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $game = new VideoGame();
        $game->setTitle($data['title']);
        $game->setReleaseDate(new \DateTime($data['releaseDate']));
        $game->setDescription($data['description']);
        
        $errors = $validator->validate($game);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->persist($game);
        $em->flush();
        return new Response($serializer->serialize($game, 'json'), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, VideoGame $game, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $data = json_decode($request->getContent(), true);
        $game->setTitle($data['title'] ?? $game->getTitle());
        $game->setReleaseDate(isset($data['releaseDate']) ? new \DateTime($data['releaseDate']) : $game->getReleaseDate());
        $game->setDescription($data['description'] ?? $game->getDescription());
        
        $errors = $validator->validate($game);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }
        
        $em->flush();
        return new Response($serializer->serialize($game, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(VideoGame $game, EntityManagerInterface $em): Response
    {
        $em->remove($game);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
