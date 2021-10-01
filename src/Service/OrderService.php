<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\House;
use App\Entity\Order;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\BathPaymentRepository;
use App\Repository\BathReservationRepository;
use App\Repository\CustomerRepository;
use App\Repository\HouseRepository;
use App\Repository\OrderRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProductRepository;
use Liip\ImagineBundle\Exception\Config\Filter\NotFoundException;

class OrderService {
    /** @var ProductRepository $productRepository */
    private $productRepository;

    /** @var CustomerRepository $customerRepository */
    private $customerRepository;

    /** @var OrderRepository $orderRepository */
    private $orderRepository;

    /** @var HouseRepository $houseRepository */
    private $houseRepository;

    /** @var PaymentRepository $paymentRepository */
    private $paymentRepository;

    public function __construct(
        ProductRepository $productRepository, 
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        HouseRepository $houseRepository,
        PaymentRepository $paymentRepository)
    {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->houseRepository = $houseRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function checkProducts(array $productIds): bool
    {
        foreach ($productIds as $productId) {
            $result = $this->productRepository->findEnabledById($productId);
            if (!$result) {
                throw new NotFoundException('Товар с id '.$productId.' не найден.');
            }
        }
        return true;
    }

    public function processOrderItems(array $productIds): array
    {
        $results = [];
        foreach ($productIds as $productId) {
            // возможно лучше всё получить одним запросом
            $result = $this->productRepository->findEnabledById($productId);
            if (!$result) {
                throw new NotFoundException('Товар с id '.$productId.' не найден.');
            }
            $results[$productId] = $result;
        }
        return $results;
    }

    public function findUserByPhone(string $phone): ?Customer
    {

        return $this->customerRepository->findUserByPhone($phone);

    }

    public function findUserHouse(int $id): ?House
    {

        return $this->houseRepository->findOneBy(['id' => $id]);

    }

    public function findByPaymentOrderId(string $id)
    {
        return $this->orderRepository->findByPaymentOrderId($id);
    }

    public function findPaymentByOrderId(string $id)
    {

        return $this->paymentRepository->findOneBy(['systemId' => $id]);

    }

    public function createNewOrder(): Order
    {

        $order = new Order();

        $existingOrder = $this->orderRepository->findOneBy(['hash' => $order->getHash()]);

        if ($existingOrder) return $this->createNewOrder();

        return $order;
    }

    public function sendNotificationToDeliveryMan(): Order
    {

        $order = new Order();

        $existingOrder = $this->orderRepository->findOneBy(['hash' => $order->getHash()]);

        if ($existingOrder) return $this->createNewOrder();

        return $order;
    }

    public function getOrdersWaitingPayment(string $hash, string $processingSystem): ?Payment
    {
        $order = $this->orderRepository->findOneBy(['hash' => $hash]);
        $returningPayment = null;
        if ($order) {
            /** @var Payment[] $payments */
            $payments = $order->getPayments();
            foreach ($payments as $payment) {
                if ($payment->getStatus() === Payment::WAIT && $payment->getProcessingSystem() === $processingSystem) {
                    $returningPayment = $payment;
                }
            }
        }

        return $returningPayment;
    }
    public function findOneByHash(string $hash): ?Order
    {
        return $this->orderRepository->findOneBy(['hash' => $hash]);
    }
}