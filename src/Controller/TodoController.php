<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/todos/{jwt}", name="todo_index", methods={"GET"})
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
            return null;
        }
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $userId]);
        return $user;
    }
 
    /**
     * @Route("/todo/{jwt}", name="todo_new", methods={"POST"})
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
     * @Route("/todo/edit/{id}/{jwt}", name="todo_edit", methods={"POST"})
     */
    public function edit(Request $request, int $id, $jwt): Response
    {
        $user = $this->authenticate($jwt);
        if($jwt != null && $user != null){
            $entityManager = $this->doctrine->getManager();
            $todo = $entityManager->getRepository(Todo::class)->find($id);
            
            if (!$todo) {
                return $this->json('No todo found for id' . $id, 404);
            }
        
            $todo->setName($request->request->get('name'));
            $todo->setDescription($request->request->get('description'));
            $entityManager->flush();
        
            $data =  [
                'id' => $todo->getId(),
                'name' => $todo->getName(),
                'description' => $todo->getDescription(),
            ];
             
            return $this->json($data);
        }
        else{
            return $this->json("not authenticated");
        }
    }
 
    /**
     * @Route("/todo/delete/{id}/{jwt}", name="todo_delete", methods={"DELETE"})
     */
    public function delete(int $id, $jwt): Response
    {
        $user = $this->authenticate($jwt);
        if($jwt != null && $user != null){
            $entityManager = $this->doctrine->getManager();
            $todo = $entityManager->getRepository(Todo::class)->find($id);
            
            if (!$todo) {
                return $this->json('No todo found for id' . $id, 404);
            }
        
            $entityManager->remove($todo);
            $entityManager->flush();
        
            return $this->json('Deleted a todo successfully with id ' . $id);
        }
        else{
            return $this->json("not authenticated");
        }
    }

    
    /**
     * @Route("/manualAdd/{jwt}", name="manual_todo_add", methods={"POST"})
     */
    public function manualAddTodo(Request $request, $jwt): Response
    {
        $user = $this->authenticate($jwt);
        if($jwt != null && $user != null){
            $servername = "localhost";
            $username = "root";
            $dbname = "securytiapi";
            $conn = mysqli_connect($servername, $username, null, $dbname);

            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $creator = $user->getId();

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            $sql = "INSERT INTO todo (name, description, creator_id)
            VALUES ('".$name.", '".$description."', '".$creator."')";

            if (mysqli_query($conn, $sql)) {
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
            mysqli_close($conn);
            return $this->json('Created new todo successfully');
        }
        else{
            return $this->json("not authenticated");
        }
    }
}
