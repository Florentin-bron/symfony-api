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
        $user = $this->authenticate($jwt);
        if($jwt != null && $user != null){
            $todos = $this->doctrine
                ->getRepository(Todo::class)
                ->findBy(['creator' => $user->getId()]);            
    
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
     * @Route("/todo/{jwt}", name="project_new", methods={"POST"})
     */
    public function new(Request $request, $jwt): Response
    {
        $user = $this->authenticate($jwt);
        if($jwt != null && $user != null){
            $entityManager = $this->doctrine->getManager();
            $todo = new Todo();
            $todo->setName($request->request->get('name'));
            $todo->setDescription($request->request->get('description'));
            $todo->setCreator($user);
            $entityManager->persist($todo);
            $entityManager->flush();
            
            return $this->json('Created new todo successfully with id ' . $todo->getId());
        }
        else{
            return $this->json("not authenticated");
        }
    }
 
    /**
     * @Route("/todo/view/{id}", name="todo_show", methods={"GET"})
     */
    public function show(int $id): Response
    {
        $todo = $this->doctrine
            ->getRepository(Todo::class)
            ->find($id);
 
        if (!$todo) {
 
            return $this->json('No todo found for id' . $id, 404);
        }
 
        $data[] = [
            'id' => $todo->getId(),
            'name' => $todo->getName(),
            'description' => $todo->getDescription(),
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
