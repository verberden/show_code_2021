<?php

namespace App\Controller\Api;

use App\Application\Service\ApiService;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\Type\CustomerType;
use App\Form\Type\OrderType;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\Sberbank;
use App\Service\TelegramService;
use App\Validation\OrderCreateApi;
use DateTime;
use DateTimeImmutable;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Order controller.
 */
final class OrderController extends AbstractFOSRestController
{

    private $serializer;
    private $validator;
    private $orderService;

    /** @var Sberbank $sberbank */
    private $sberbank;

    /** @var TelegramService $telegramService */
    private $telegramService;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, OrderService $orderService, Sberbank $sberbank, TelegramService $telegramService)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->orderService = $orderService;
        $this->sberbank = $sberbank;
        $this->telegramService = $telegramService;
    }

    private function getRepo(): OrderRepository
    {   
        return $this->getDoctrine()->getRepository(Order::class);
    }

    /**
     * Gets an Order resources
     * @Rest\Get("/orders/")
     * @return View
     */
    public function index(Request $request): View
    {
        $orders = [];
        $hashes = $request->query->get('hash');
        if ($hashes) {
            $orders = $this->getRepo()->findBy(['hash'=> $hashes]);
        }
        $serialized = json_decode($this->serializer->serialize($orders, 'json', ['groups' => ['order:list']]));
        return View::create($serialized, Response::HTTP_OK);
    }

    /**
     * Creates an Order resource
     * @Rest\Post("/orders")
     * @return View
     */
    public function create(Request $request): View
    {
        $data = json_decode($request->getContent(), true);
        $errors = $this->validator->validate(
            new OrderCreateApi($data)
        );

        if (isset($errors[0])) {
            return View::create('Данные не валидны.', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        if (!$data || !count($data['orderItems'])) 
            throw new NotFoundHttpException('У заказа не указаны позиции.');

        $house = $this->orderService->findUserHouse($data['customer']['house']['id']);
        if (!$house)
            throw new NotFoundHttpException('Не найден указанный домик.');
        $data['customer']['house'] = $house->getId();

        if (isset($data['payWithApple'])) {
            $processingSystem = Sberbank::APPLE_PROCESSING;
        } else if (isset($data['payWithGoogle'])) {
            $processingSystem = Sberbank::GOOGLE_PROCESSING;
        } else {
            $processingSystem = Sberbank::APP_PROCESSING;
        }

        // делаем номер телефона состоящим только из цифр.
        $data['customer']['phone'] = preg_replace('~{\D+() -}~', '', $data['customer']['phone']);
        $order = $this->orderService->createNewOrder();

        $orderCost = 0;
        $productIds=[];
        foreach ($data['orderItems'] as $item) {
            // проверяем чтобы quantity было числом.
            if (!is_numeric($item['quantity']))
                throw new NotFoundHttpException('У продукта c id '.$item['productId'].' не указано количество(quantity)');
            array_push($productIds, $item['productId']);
        }

        $products = $this->orderService->processOrderItems($productIds);
        foreach ($data['orderItems'] as $item) {
            $product = $products[$item['productId']];
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setName($product->getName());
            $orderItem->setPrice($product->getPrice());
            $orderItem->setQuantity(intval($item['quantity']));
            $orderItem->setCost(intval($item['quantity']) * $product->getPrice());
            $orderCost += $orderItem->getCost();
            $order->addOrderItem($orderItem);
        }

        // ищем кастомера по номеру телефона
        $customer = $this->orderService->findUserByPhone($data['customer']['phone']);
        if (!$customer) {
            $customer = new Customer();
            $customer->setName($data['customer']['name']);
            $customer->setPhone($data['customer']['phone']);

            $customer->setHouse($house);

            $formCustomer = $this->createForm(CustomerType::class, $customer, array('csrf_protection' => false));

            $formCustomer->submit($data['customer']);

            if ($formCustomer->isSubmitted() && $formCustomer->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($customer);
        
                $em->flush();
            } else {
                return View::create($formCustomer->getErrors(true), Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
            }
        } else {
            // так сделать сказал Дима!
            $customer->setName($data['customer']['name']);
            // на всякий случай привязываем домик, вдруг новый.
            $customer->setHouse($house);
            $em = $this->getDoctrine()->getManager();
    
            $em->flush();
        }

        $order->setCost($orderCost);
        $order->setStatus(Order::NEW_STATUS);
        $order->setHouseText($house->getTypeText() .' - '.$house->getName());
        $order->setDeliveryTime(new DateTime($data['deliveryTime']));
        $order->setNumberOfPersons($data['numberOfPersons']);
        isset($data['notifyToken']) && $order->setNotifyToken($data['notifyToken']);
        $order->setCustomerName($customer->getName());
        $order->setCustomerPhone($customer->getPhone());
        $order->setCustomer($customer);
        $order->setPaymentSum($orderCost);

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
    
        $em->flush();
        $url = $this->sberbank->init($order, $order->getCost(), 'Заказ №'.$order->getId(), $processingSystem);
        $serialized = json_decode($this->serializer->serialize(['order' => $order, 'url' => $url], 'json', ['groups' => ['order:item']]));

        $this->telegramService->sendToAllAdmins('Поступил новый заказ: '.$this->generateUrl('admin_order_show', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
        return View::create($serialized, Response::HTTP_CREATED);
    }

    /**
     * Gets a Categories resources
     * @Rest\Get("/order/{hash}")
     * @return View
     */
    public function one(string $hash): View
    {   
        $order = $this->getRepo()->findOneBy(['hash'=> $hash]);
        if (!$order)
          throw new NotFoundHttpException('Заказ не найден.');
        $statusText = $order->getStatusText();
        $serialized = json_decode($this->serializer->serialize($order, 'json', ['groups' => ['order:item']]));
        $serialized->statusText = $statusText;
        return View::create($serialized, Response::HTTP_OK);
    }

    // /**
    //  * Creates an Order resource
    //  * @Rest\Post("/orders/payed")
    //  * @return View
    //  */
    // public function updateStatus(Request $request): View
    // {
    //     $data = json_decode($request->getContent(), true);
    //     if (!$data || !$data['id']) 
    //         throw new NotFoundHttpException('Нет id заказа.');
        
    //     $this->orderService->checkProducts($productIds);


    //     return View::create($serialized, Response::HTTP_CREATED);
    // }
}