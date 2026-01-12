<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/friends')]
class FriendController extends AbstractController
{
    #[Route('/', name: 'app_friend_index', methods: ['GET'])]
    public function index(FriendRequestRepository $friendRequestRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        // Get accepted friends
        $sentAccepted = $friendRequestRepository->findBy(['requester' => $user, 'status' => FriendRequest::STATUS_ACCEPTED]);
        $receivedAccepted = $friendRequestRepository->findBy(['receiver' => $user, 'status' => FriendRequest::STATUS_ACCEPTED]);

        $friends = [];
        foreach ($sentAccepted as $req)
            $friends[] = $req->getReceiver();
        foreach ($receivedAccepted as $req)
            $friends[] = $req->getRequester();

        // Get pending updates
        $pendingReceived = $friendRequestRepository->findBy(['receiver' => $user, 'status' => FriendRequest::STATUS_PENDING]);

        return $this->render('friend/index.html.twig', [
            'friends' => $friends,
            'pending_requests' => $pendingReceived,
        ]);
    }

    #[Route('/search', name: 'app_friend_search', methods: ['GET', 'POST'])]
    public function search(Request $request, UserRepository $userRepository, FriendRequestRepository $frRepository, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $targetUser = $userRepository->findOneBy(['email' => $email]);
            $currentUser = $this->getUser();

            if ($targetUser && $targetUser !== $currentUser) {
                // Check if request already exists
                $existing = $frRepository->createQueryBuilder('fr')
                    ->where('fr.requester = :me AND fr.receiver = :them')
                    ->orWhere('fr.requester = :them AND fr.receiver = :me')
                    ->setParameter('me', $currentUser)
                    ->setParameter('them', $targetUser)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (!$existing) {
                    $req = new FriendRequest();
                    $req->setRequester($currentUser);
                    $req->setReceiver($targetUser);
                    $em->persist($req);
                    $em->flush();
                    $this->addFlash('success', 'Friend request sent!');
                } else {
                    $this->addFlash('warning', 'Request already exists or you are already friends.');
                }
            } else {
                $this->addFlash('error', 'User not found or invalid.');
            }
            return $this->redirectToRoute('app_friend_index');
        }

        return $this->redirectToRoute('app_friend_index');
    }

    #[Route('/request/{id}/{action}', name: 'app_friend_request_action', methods: ['POST'])]
    public function requestAction(FriendRequest $friendRequest, string $action, EntityManagerInterface $em): Response
    {
        if ($friendRequest->getReceiver() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($action === 'accept') {
            $friendRequest->setStatus(FriendRequest::STATUS_ACCEPTED);
        } elseif ($action === 'reject') {
            $friendRequest->setStatus(FriendRequest::STATUS_REJECTED);
        }

        $em->flush();
        return $this->redirectToRoute('app_friend_index');
    }

    #[Route('/{id}', name: 'app_friend_show', methods: ['GET'])]
    public function show(User $friend, FriendRequestRepository $frRepository): Response
    {
        // Verify friendship
        $currentUser = $this->getUser();
        $isFriend = $frRepository->createQueryBuilder('fr')
            ->where('fr.status = :status')
            ->andWhere('(fr.requester = :me AND fr.receiver = :them) OR (fr.requester = :them AND fr.receiver = :me)')
            ->setParameter('status', FriendRequest::STATUS_ACCEPTED)
            ->setParameter('me', $currentUser)
            ->setParameter('them', $friend)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$isFriend) {
            throw $this->createAccessDeniedException('You are not friends with this user.');
        }

        return $this->render('friend/show.html.twig', [
            'friend' => $friend,
            'gifts' => $friend->getGifts(),
        ]);
    }
}
