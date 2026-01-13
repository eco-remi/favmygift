<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_index')]
    public function index(UserRepository $userRepository): Response
    {
        // Fetching with basic gift stats
        $usersData = $userRepository->findAllWithStats();

        // Prepare data for view (calculating friends count in PHP for simplicity/accuracy without complex SQL)
        $users = [];
        foreach ($usersData as $row) {
            /** @var User $user */
            $user = $row['user'];

            // Calculate accepted friends count
            $friendsCount = 0;
            foreach ($user->getSentFriendRequests() as $req) {
                if ($req->getStatus() === 'ACCEPTED')
                    $friendsCount++;
            }
            foreach ($user->getReceivedFriendRequests() as $req) {
                if ($req->getStatus() === 'ACCEPTED')
                    $friendsCount++;
            }

            $users[] = [
                'entity' => $user,
                'giftCount' => $row['giftCount'],
                'friendCount' => $friendsCount
            ];
        }

        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Cannot delete yourself
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'admin.error.self_delete');
                return $this->redirectToRoute('app_admin_index');
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'admin.user_deleted');
        }

        return $this->redirectToRoute('app_admin_index');
    }

    #[Route('/user/{id}/gifts', name: 'app_admin_user_gifts')]
    public function viewGifts(User $user): Response
    {
        return $this->render('admin/gifts.html.twig', [
            'user' => $user,
            'gifts' => $user->getGifts(),
        ]);
    }

    #[Route('/gift/{id}/delete', name: 'app_admin_gift_delete', methods: ['POST'])]
    public function deleteGift(Request $request, Gift $gift, EntityManagerInterface $entityManager): Response
    {
        $ownerId = $gift->getOwner()->getId();
        if ($this->isCsrfTokenValid('delete' . $gift->getId(), $request->request->get('_token'))) {
            $entityManager->remove($gift);
            $entityManager->flush();
            $this->addFlash('success', 'admin.gift_deleted');
        }

        return $this->redirectToRoute('app_admin_user_gifts', ['id' => $ownerId]);
    }
}
