<?php
// src/Controller/ContactController.php
namespace App\Controller;

use App\Entity\ContactMessage;
use App\Service\SystemLoggerService; // ✅ Ajout
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(): Response
    {
        return $this->render('contact/contact.html.twig');
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        SystemLoggerService $logger // ✅ Injection du service
    ): Response {
        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $email = $request->request->get('email');
        $telephone = $request->request->get('telephone');
        $message = $request->request->get('message');

        $contact = new ContactMessage();
        $contact->setNom($nom);
        $contact->setPrenom($prenom);
        $contact->setEmail($email);
        $contact->setTelephone($telephone);
        $contact->setMessage($message);

        $em->persist($contact);
        $em->flush();

        // ✅ Enregistrement dans les logs système
        $logger->add(
            'Message de contact',
            sprintf(
                'Nouveau message reçu de %s %s (%s). Téléphone : %s',
                $prenom,
                $nom,
                $email,
                $telephone ?: 'non renseigné'
            )
        );

        $this->addFlash('success', 'Votre message a bien été envoyé !');

        return $this->redirectToRoute('contact');
    }
}
