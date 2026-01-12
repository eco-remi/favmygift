<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Repository\GiftRepository;
use App\Service\MetadataScraper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/gifts')]
class GiftController extends AbstractController
{
    #[Route('/', name: 'app_gift_index', methods: ['GET'])]
    public function index(GiftRepository $giftRepository): Response
    {
        return $this->render('gift/index.html.twig', [
            'gifts' => $giftRepository->findBy(['owner' => $this->getUser()], ['priority' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_gift_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MetadataScraper $scraper): Response
    {
        $gift = new Gift();

        if ($request->isMethod('POST')) {
            // Handle scraping if URL provided and scrape requested
            $url = $request->request->get('url');
            $scrape = $request->request->get('scrape');

            if ($url && $scrape) {
                $metadata = $scraper->scrape($url);
                return $this->json($metadata);
            }

            // Normal form submission
            $gift->setName($request->request->get('name'));
            $gift->setDescription($request->request->get('description'));
            $gift->setUrl($request->request->get('url'));
            $gift->setImageUrl($request->request->get('imageUrl'));
            $gift->setPriority((int) $request->request->get('priority', 0));
            $gift->setOwner($this->getUser());

            $entityManager->persist($gift);
            $entityManager->flush();

            return $this->redirectToRoute('app_gift_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('gift/new.html.twig', [
            'gift' => $gift,
        ]);
    }

    #[Route('/{id}', name: 'app_gift_delete', methods: ['POST'])]
    public function delete(Request $request, Gift $gift, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $gift->getId(), $request->request->get('_token'))) {
            // Ensure owner
            if ($gift->getOwner() !== $this->getUser()) {
                throw $this->createAccessDeniedException();
            }

            $entityManager->remove($gift);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_gift_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/favorite', name: 'app_gift_favorite', methods: ['POST'])]
    public function toggleFavorite(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($gift->getFavoritedBy()->contains($user)) {
            $gift->removeFavoritedBy($user);
        } else {
            $gift->addFavoritedBy($user);
        }

        $entityManager->flush();

        // Redirect back to where we came from (likely friend's page)
        return $this->redirect($this->generateUrl('app_friend_show', ['id' => $gift->getOwner()->getId()]));
    }
}
