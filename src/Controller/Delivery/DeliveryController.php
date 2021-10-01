<?php

namespace App\Controller\Delivery;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

/**
 * Order controller.
 */
final class DeliveryController extends AbstractFOSRestController
{
  /** @var Security */
  private $security;

  public function __construct(Security $security)
  {
    $this->security = $security;
  }

  private function getRepo(): OrderRepository
  {
    return $this->getDoctrine()->getRepository(Order::class);
  }


  public function index()
  {
    /** @var User $user */
    $user = $this->security->getUser();
    if (in_array(User::ROLE_ADMIN, $user->getRoles()) || in_array(User::ROLE_SUPER_ADMIN, $user->getRoles()) || in_array(User::ROLE_ADMIN_CAFE, $user->getRoles())) {
      return $this->redirectToRoute('admin_orders');
    }
    $orders = $this->getRepo()->findByDeliveryMan($user);
    return $this->render('admin/order/index.html.twig', [
        'orders' => $orders,
        'statuses' => Order::STATUSES,
        'deliveryMen' => [],
        'buttonStatuses' => Order::STATUSES_FOR_BBUTTON,
        'btnColors' => Order::STATUS_BTN_COLOR,
        'rowColor' => Order::STATUS_COLOR,
    ]);
  }

}