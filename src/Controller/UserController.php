<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use ReallySimpleJWT\Token;
use ReallySimpleJWT\Jwt;

/**
 * @Route("/api", name="api_")
 */
class UserController extends AbstractController
{

    public function __construct(ManagerRegistry $doctrine){
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/users", name="user_index", methods={"GET"})
     */
    public function index(): Response
    {
        $users = $this->doctrine
            ->getRepository(User::class)
            ->findAll();
 
        $data = [];
 
        foreach ($users as $user) {
           $data[] = [
               'id' => $user->getId(),
               'name' => $user->getLastame(),
               'description' => $user->getFirstName(),
           ];
        }
 
 
        return $this->json($data);
    }
 
    /**
     * @Route("/user", name="user_new", methods={"POST"})
     */
    public function new(Request $request): Response
    {
        $entityManager = $this->doctrine->getManager();
        $user = new User();
        $user->setLastname($request->request->get('lastname'));
        $user->setFirstname($request->request->get('firstname'));
        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));
        $user->setToken("");
        $entityManager->persist($user);
        $entityManager->flush();
        $token = Token::create($user->getId(), "sec!ReT423*&", time() + 999999, "testtokenissuer");
        $user->setToken($token);
        $entityManager->persist($user);
        $entityManager->flush();
 
        return $this->json('Created new user successfully with id ' . $user->getId());
    }
}
