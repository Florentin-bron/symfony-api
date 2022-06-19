<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Project;
use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use ReallySimpleJWT\Token;
use ReallySimpleJWT\Parse;
use ReallySimpleJWT\Jwt;
use ReallySimpleJWT\Decode;

/**
 * @Route("/api", name="api_todo")
 */
class TodoController extends AbstractController
{

    public function __construct(ManagerRegistry $doctrine){
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/todos/{jwt}", name="project_index", methods={"GET"})
     */
    public function index(Request $request, $jwt): Response
    {
        $data = [];
        if($jwt != null && $this->authenticate($jwt) != null){
            $todos = $this->doctrine
                ->getRepository(Todo::class)
                ->findAll();            
    
            foreach ($todos as $todo) {
                $data[] = [
                    'id' => $todo->getId(),
                    'name' => $todo->getName(),
                    'description' => $todo->getDescription(),
                ];
            }
        }
        
 
        return $this->json($data);
    }

    public function authenticate($jwt){
        $jwt = new Jwt($jwt);

        $parse = new Parse($jwt, new Decode());
        
        $parsed = $parse->parse();
        
        // Return the token header claims as an associative array.
        $parsed->getHeader();
        
        // Return the token payload claims as an associative array.
        $parsed->getPayload();
        $userId = $parsed->getPayload();
        if (isset($userId['user_id'])){
            $userId = $userId['user_id'];
        } else {
            return "not authenticated";
        }
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $userId]);
        return $user;
    }
 
    /**
     * @Route("/todo", name="project_new", methods={"POST"})
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
 
        return $this->json('Created new user successfully with id ' . $user->getId() . 'with jwt:   ' . $token);
    }
 
    /**
     * @Route("/project/{id}", name="project_show", methods={"GET"})
     */
    public function show(int $id): Response
    {
        $project = $this->doctrine
            ->getRepository(Project::class)
            ->find($id);
 
        if (!$project) {
 
            return $this->json('No project found for id' . $id, 404);
        }
 
        $data =  [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
        ];
         
        return $this->json($data);
    }
 
    /**
     * @Route("/project/{id}", name="project_edit", methods={"PUT"})
     */
    public function edit(Request $request, int $id): Response
    {
        $entityManager = $this->doctrine->getManager();
        $project = $entityManager->getRepository(Project::class)->find($id);
 
        if (!$project) {
            return $this->json('No project found for id' . $id, 404);
        }
 
        $project->setName($request->request->get('name'));
        $project->setDescription($request->request->get('description'));
        $entityManager->flush();
 
        $data =  [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
        ];
         
        return $this->json($data);
    }
 
    /**
     * @Route("/project/{id}", name="project_delete", methods={"DELETE"})
     */
    public function delete(int $id): Response
    {
        $entityManager = $this->doctrine->getManager();
        $project = $entityManager->getRepository(Project::class)->find($id);
 
        if (!$project) {
            return $this->json('No project found for id' . $id, 404);
        }
 
        $entityManager->remove($project);
        $entityManager->flush();
 
        return $this->json('Deleted a project successfully with id ' . $id);
    }

}
