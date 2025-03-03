<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\User;
use App\Entity\VideoGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des utilisateurs
        $users = [];
        $userData = [
            [
                'email' => 'T-800@terminator.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'LeMot2PasseTrèsSécurisé',
                'subcription_to_newsletter' => '1',
            ],
            [
                'email' => 'l\'utilisateurMasquer@secret.com',
                'roles' => ['ROLE_USER'],
                'password' => 'LeMotDePasse',
                'subcription_to_newsletter' => '0',
            ],
            [
                'email' => 'JaiPasMisDeRefs@films.com',
                'roles' => ['ROLE_USER'],
                'password' => 'LeSecondMotDePasse',
                'subcription_to_newsletter' => '0',
            ],
        ];

        foreach ($userData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles($data['roles']);
            $user->setSubcriptionToNewsletter($data['subcription_to_newsletter']);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $users[] = $user;
        }

        $categories = [];
        $categoryNames = ['Action', 'RPG', 'Simulation', 'Strategy', 'Adventure'];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        $editors = [];
        $editorData = [
            ['name' => 'Nintendo', 'country' => 'Japan'],
            ['name' => 'Ubisoft', 'country' => 'France'],
            ['name' => 'Electronic Arts', 'country' => 'USA'],
            ['name' => 'Capcom', 'country' => 'Japan'],
            ['name' => 'CD Projekt', 'country' => 'Poland'],
        ];

        foreach ($editorData as $data) {
            $editor = new Editor();
            $editor->setName($data['name']);
            $editor->setCountry($data['country']);
            $manager->persist($editor);
            $editors[] = $editor;
        }

        $videoGamesData = [
            [
                'title' => 'The Legend of Zelda: Breath of the Wild',
                'releaseDate' => new \DateTime('2017-03-03'),
                'description' => 'An open-world action-adventure game set in the kingdom of Hyrule.',
                'editor' => $editors[0],
                'category' => $categories[1], // RPG
            ],
            [
                'title' => 'Assassin\'s Creed Valhalla',
                'releaseDate' => new \DateTime('2020-11-10'),
                'description' => 'An action RPG exploring the Viking era.',
                'editor' => $editors[1],
                'category' => $categories[1], // RPG
            ],
            [
                'title' => 'FIFA 23',
                'releaseDate' => new \DateTime('2022-09-30'),
                'description' => 'A football simulation game with realistic mechanics.',
                'editor' => $editors[2],
                'category' => $categories[2], // Simulation
            ],
            [
                'title' => 'Resident Evil Village',
                'releaseDate' => new \DateTime('2021-05-07'),
                'description' => 'A survival horror game with stunning graphics and thrilling gameplay.',
                'editor' => $editors[3],
                'category' => $categories[0], // Action
            ],
            [
                'title' => 'Cyberpunk 2077',
                'releaseDate' => new \DateTime('2020-12-10'),
                'description' => 'A futuristic open-world RPG with a deep narrative.',
                'editor' => $editors[4],
                'category' => $categories[1], // RPG
            ],
        ];

        foreach ($videoGamesData as $data) {
            $videoGame = new VideoGame();
            $videoGame->setTitle($data['title']);
            $videoGame->setReleaseDate($data['releaseDate']);
            $videoGame->setDescription($data['description']);
            $videoGame->setEditor($data['editor']);
            $videoGame->setCategory($data['category']);
            $manager->persist($videoGame);
        }

        $manager->flush();
    }
}
