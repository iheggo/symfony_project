<?php

namespace Tahrir\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use Tahrir\BlogBundle\Entity\User;
use Tahrir\BlogBundle\Entity\Post;
use Tahrir\BlogBundle\Modals\Login;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
		//$session = $this->getRequest()->getSession();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TahrirBlogBundle:User');
		$postRepository = $em->getRepository('TahrirBlogBundle:Post');
		$posts = $postRepository->findAll();
		$session = $this->get('session');		// get the session
		
        if ($request->getMethod() == 'POST') {		// if login form is submitted
            //$session->clear();
            $username = $request->request->get('username');
            $password = sha1($request->request->get('password'));
            $remember = $request->request->get('remember');
            $user = $repository->findOneBy(array('username' => $username, 'password' => $password));
			
		
            if ($user) {
				$session = new Session();
				$session->start();
				
				$session->set('id',  $user->getId());		// set user id into session
				$session->set('username', $user->getUsername()); // set user username into session
				
				$greeting='Hello '.$user->getUsername().',<br> You logged successfully';
				$return=array("code"=>200,  "greeting"=>$greeting);				
                //return $this->render('TahrirBlogBundle:Default:welcome.html.twig', array('name' => $user->getUsername()));
            } else {
                $return=array("code"=>400, "greeting"=>"You have to write your name!");
				//return $this->render('TahrirBlogBundle:Default:login.html.twig', array('name' => 'Login Error'));
            }
			$return=json_encode($return);//jscon encode the array
			return new Response($return,200,array('Content-Type'=>'application/json'));			
        }
		
		$logged = false;
		if($session->has('id'))		// check if the user is logged or not
			$logged = true;
			
		return $this->render('TahrirBlogBundle:Default:login.html.twig',array('posts'=>$posts, 'logged' => $logged));
		
    }
	
	/****************************************	Signup	****************************************/
    public function signupAction(Request $request)		// signup controller
    {
		$em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TahrirBlogBundle:Post');	
        
		if ($request->getMethod() == 'POST') {
			$username = $request->get('username');
            $password = sha1($request->get('password'));
            $email = $request->get('email');
			
			$user = new User();
			$user->setUsername($username);
			$user->setPassword($password);
			$user->setEmail($email);
			$em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
			
			return $this->render('TahrirBlogBundle:Default:signup.html.twig',array('message'=> 'Successfully Signed-up'));
		}
		
		return $this->render('TahrirBlogBundle:Default:signup.html.twig');
		
    }
	
	/****************************************	Create New Post	****************************************/
    public function createPostAction(Request $request)		// create new post controller
    {
		$session = $this->get('session');		// get the session
		if($session->has('id'))		// check if the user is logged or not
		{
			if ($request->getMethod() == 'POST') {
				$title = $request->get('title');
				$content = $request->get('content');
				$tags = $request->get('tags');
				$date = date('Y-m-d H:i:s');;
			
				
				$post = new Post();
				$post->setTitle($title);
				$post->setContent($content);
				$post->setTags($tags);
				$post->setPostedBy($session->get('id'));
				$post->setImageUrl('');
				$post->setCreationDate(new \DateTime('now'));
				$em = $this->getDoctrine()->getManager();
				$em->persist($post);
				$em->flush();
				
			return $this->render('TahrirBlogBundle:Default:newpost.html.twig',array('message' => 'Successfully Created'));	
			}
			
		return $this->render('TahrirBlogBundle:Default:newpost.html.twig');	
		}
		else{ 
			return $this->render('TahrirBlogBundle:Default:login.html.twig');
		}
    }
	
	/****************************************	Get User Posts	****************************************/
	public function myPostsAction(Request $request)		// view user posts controller
    {	
		$session = $this->get('session');		// get the session
		$em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TahrirBlogBundle:Post');	
        
		$current_user = $session->get('id');		// retrieve current user id from session
		$posts = $repository->findBy(array('postedBy' => $current_user ));	// get all posts created by current user
		
		$post_arr;
		$i=0;
		foreach ($posts as $post) {
			$post_arr[$i]['id']= $post->getId();
			$post_arr[$i++]['title']= $post->getTitle();
		}
		if ($posts) {	
			$return = array("code"=>200, "posts"=>$post_arr);				
			
		} else {
			$return=array("code"=>400, "posts"=>"Nothing to view!");
			//return $this->render('TahrirBlogBundle:Default:login.html.twig', array('name' => 'Login Error'));
		}
		$return=json_encode($return);//jscon encode the array
		return new Response($return,200,array('Content-Type'=>'application/json'));			
		
    }
	
	/****************************************	View Post	****************************************/
    public function viewPostAction($id)		// view post controller
    {
		$em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TahrirBlogBundle:Post');		
		$post = $repository->findOneBy(array('id' => $id));
		return $this->render('TahrirBlogBundle:Default:post.html.twig',array('id'=> $id, 'title' => $post->getTitle() ,'content' => $post->getContent(),
		'tags' => $post->getTags(), 'creationDate' => $post->getCreationDate() ));
		
    }	

	/****************************************	Delete Post	****************************************/
    public function deletePostAction(Request $request)		// delete post controller
    {
		$session = $this->get('session');		// get the session
		if($session->has('id'))		// check if the user is logged or not
		{
			$em = $this->getDoctrine()->getManager();
			$repository = $em->getRepository('TahrirBlogBundle:Post');
			$id = $request->request->get('id');			
			$post = $repository->find($id);
			
			if (!$post) 		// no such post found
				$return=array("code"=>400);
			else{
				$em->remove($post);
				$em->flush();
				$return=array("code"=>200);
			}
		}
		else{
			$return=array("code"=>400);
		}	
		
		$return=json_encode($return);//jscon encode the array
		return new Response($return,200,array('Content-Type'=>'application/json'));	
		
    }
		
	/****************************************	Edit Post	****************************************/
    public function editPostAction(Request $request, $id)		// edit post controller
    {
		$session = $this->get('session');		// get the session
		if($session->has('id'))					// check if the user is logged or not
		{	
			$em = $this->getDoctrine()->getManager();
			$repository = $em->getRepository('TahrirBlogBundle:Post');		
			$post = $repository->find($id);		// find post with the required id
			$current_user = $session->get('id');		// retrieve current user id from session
			// find if the post belongs to this user
			$user_post = $repository->findOneBy(array('id' => $id, 'postedBy' => $current_user)); 
			
			if ($user_post)
			{
				if ($request->getMethod() == 'POST') {
					$post->setTitle($request->get('title'));
					$post->setContent($request->get('content'));
					$post->setTags($request->get('tags'));
					$em->flush();
				
					return $this->render('TahrirBlogBundle:Default:editpost.html.twig',array('message'=> 'Successfully Saved', 'id'=> $id, 'title' => $post->getTitle() ,'content' => $post->getContent(),
					'tags' => $post->getTags(), 'creationDate' => $post->getCreationDate() ));
				}
				
				
				return $this->render('TahrirBlogBundle:Default:editpost.html.twig',array('id'=> $id, 'title' => $post->getTitle() ,'content' => $post->getContent(),
				'tags' => $post->getTags(), 'creationDate' => $post->getCreationDate() ));
			}
	
		}
		
		return $this->indexAction(new Request);
			
    }
	
	/****************************************	Logout	****************************************/
    public function logoutAction(Request $request)		// logout controller
    {
		$session = $this->getRequest()->getSession();
        $session->clear();
		$return=array("code"=>200);
		$return=json_encode($return);//jscon encode the array
		return new Response($return,200,array('Content-Type'=>'application/json'));	
        //return $this->render('TahrirBlogBundle:Default:login.html.twig');		
		
    }	
}
