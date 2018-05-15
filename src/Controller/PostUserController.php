<?php
/**
 * Created by PhpStorm.
 * User: oriol
 * Date: 12/4/2018
 * Time: 21:01
 */

namespace PwBox\Controller;


use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PwBox\Model\User;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use PwBox\Model\UserRepository;
use PwBox\Model\FileRepository;
class PostUserController
{
    /** @var ContainerInterface */
    private $container;

    /**
     * PostUserController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(Request $request, Response $response){

        $name = $_POST['name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $date = $_POST['birth'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        $errors = [];

        if($this->container->get('user_repository')->checkIfUserExists($username, $email)){
            $errors['login'] = 'the username or the mail already exist';
        }

        if (empty($name)){
            $errors['name'] = 'invalid user';
        }

        if (empty($username)||strlen($username)>20||!ctype_alnum($username)){
            $errors['username'] = 'invalid user';
        }

        if (empty($password)||strlen($password)>12||strlen($password)<6||!preg_match('/^[A-Za-z0-9]*([A-Z][A-Za-z0-9]*\d|\d[A-Za-z0-9]*[A-Z])[A-Za-z0-9]*$/',$password)){
            $errors['password'] = 'invalid password';
        }

        if(strcmp($confirmPassword, $password) != 0){
            echo($password);
            echo($confirmPassword);
            echo(" ".strcmp($confirmPassword, $password));
            $errors['confirmPassword'] = 'Confirm password field does not match up';

        }

        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
            $errors['birth'] = 'wrong birth';
        }


        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors['email'] = 'invalid email';
        }

        if (!empty($errors)) {
            return $this->container->get('view')
                ->render($response,'home.twig',['errors'=> $errors]);
        }

        $target_dir = "assets/resources/perfils";

        if( !empty($_FILES['image'])){

            $errors =[];

            $allowed_types =array('jpg','png' );
            $name = $_FILES['image']['name'];
            $error = null;

            // Get the file extensions
            $extension = pathinfo($name, PATHINFO_EXTENSION);

            // Search the array for the allowed file type
            if (in_array($extension, $allowed_types, false) != true) {
                $errors['extension'] = "Extension not allowed";
            }

            if($_FILES["image"]["size"]>500000){
               $errors['image'] = 'image too big';

           }else{

               mkdir($target_dir."/".$username, 0777, TRUE);
               mkdir($target_dir."/".$username."/root", 0777, TRUE);
               $target_file = $target_dir."/".$username."/"."profile.png";

               if (move_uploaded_file( $_FILES["image"]["tmp_name"], $target_file)) {
                   echo "The file ". basename( $name). " has been uploaded. ";
               }

           }

       }else{
            $target_file = $target_dir."/".$username."/"."profile.png";

            move_uploaded_file( "assets/resources/user.png", $target_file);
        }




        $user = new User
        (null,
            $_POST['name'],
            $_POST['surname'],
            $username,$email,
            $_POST['password'],
            $_POST['birth'],
            null
        );
        try {
            /** @var UserRepository $userRepo */
            $this->container->get('user_repository')->save($user);
            $_SESSION['id'] = $this->container->get('user_repository')->getId($username);

            $id = $_SESSION['id'];

            $id_motherfolder = $this->container->get('file_repository')->iniciaFolder();


            $this->container->get('user_repository')->setMotherFolder($id_motherfolder);


            $messages = $this->container->get('flash')->getMessages();
            $registerMessages = isset($messages['register'])?$messages['register']:[];

            $_SESSION['currentFolder'] = $id_motherfolder;
            $_SESSION['motherFolder'] = $id_motherfolder;



            //Si tot està bé, enviem missatge
            $transport = (new Swift_SmtpTransport('smtp.live.com', 587, 'tls'))
                ->setUsername('projectesweb2@hotmail.com')
                ->setPassword('MiqPanCar96')
            ;

            // Create the Mailer using your created Transport
            $mailer = new Swift_Mailer($transport);



            // Create a message
            $message = (new Swift_Message('Activation mail'))
                ->setFrom(['projectesweb2@hotmail.com' => 'Pwbox Awesome Team'])
                ->setTo([$email, $email => $name])
                ->setBody(
                    '<html>' .
                    ' <head></head>' .
                    ' <body>' .
                    '<a href="pwbox.test/activate?id=' . $id.'">Sign in</a>'.
                    ' </body>' .
                    '</html>',
                    'text/html' // Mark the content-type as HTML
                );

            // Send the message
            $result = $mailer->send($message);




           // return $this->container->get('view')->render($response,'dashboard.twig',['messages'=> $registerMessages]);
            return $response->withStatus(302)->withHeader('Location','/dashboard');

        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function loginCheck(Request $request, Response $response)
    {

        $username = $_POST['title'];
        $password = $_POST['passwordLogin'];

        $erroUs = 0;
        $erroMail = 0;

        if (empty($username)||strlen($username)>20||!ctype_alnum($username)){
            $erroUs = 1;
        }

        if (empty($password)||strlen($password)>12||strlen($password)<6||!preg_match('/^[A-Za-z0-9]*([A-Z][A-Za-z0-9]*\d|\d[A-Za-z0-9]*[A-Z])[A-Za-z0-9]*$/',$password)){
            $errors['password'] = 'invalid password';
        }

        if(!filter_var($username, FILTER_VALIDATE_EMAIL)){
            $erroMail = 1;
        }

        if ($erroMail == 1 & $erroUs == 1){
            $errors['user&mail'] = 'invalid username or mail';
        }
        if (!empty($errors)) {
            return $this->container->get('view')
                ->render($response,'home.twig',['errors'=> $errors]);
        }

        $errors = [];

        $id = [];
        $id = $this->container->get('user_repository')->tryLogin($_POST['title'], $_POST['passwordLogin']);

        if ($id[0] == -1){
            //Username o email no existeix a bbdd
            $errors['notexistent'] = 'The username or the email do not exist';
            return $this->container->get('view')->render($response,'home.twig',['errors'=> $errors]);
        }else{
            if ($id[0] == -2){
                //Contrasenya incorrecta

                $errors['password'] = 'Incorrect password';
                return $this->container->get('view')->render($response,'home.twig',['errors'=> $errors]);

            }else{

                /** @var FileRepository $fileRepo **/

                $_SESSION['currentFolder'] = $id[1];

                $_SESSION['id'] = $id[0];

                $_SESSION['motherFolder'] = $id[1];

                return $response->withStatus(302)->withHeader('Location','/dashboard');
            }
        }
    }

    public function logOut(Request $request, Response $response)
    {

        session_destroy();
        return $response->withStatus(302)->withHeader('Location','/');
    }


    public function activate(Request $request, Response $response)
    {

        return $response->withStatus(302)->withHeader('Location','/');
    }

}