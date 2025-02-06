<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $repository, SerializerInterface $serializer): Response
    {
        $users = $repository->findAll();
        return new Response($serializer->serialize($users, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user, SerializerInterface $serializer): Response
    {
        return new Response($serializer->serialize($user, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password'] ?? ''));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->persist($user);
        $em->flush();
        return new Response($serializer->serialize($user, 'json'), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true);
        $user->setEmail($data['email'] ?? $user->getEmail());
        $user->setRoles($data['roles'] ?? $user->getRoles());

        if (!empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $em->flush();
        return new Response($serializer->serialize($user, 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
