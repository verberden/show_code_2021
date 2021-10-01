<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class OrderCreateApi {

  public function __construct($data)
  {
      $this->deliveryTime = $data['deliveryTime'];
      $this->numberOfPersons = $data['numberOfPersons'];
      $this->orderItems = $data['orderItems'];
      $this->customer = $data['customer'];
  }

  // No getters/setters for brevity
  /**
   * @Assert\NotBlank
   * @Assert\DateTime(format="Y-m-d H:i:s T")
   */
  public $deliveryTime;

  /**
   * @Assert\Length(min=1, max=255)
   */
  public $notificationToken;

  /**
   * @Assert\NotBlank
   * @Assert\Positive
   */
  public $numberOfPersons;

    /**
     * @Assert\NotBlank
     * @Assert\All(@Assert\Collection(
     *     fields = {
     *         "productId" = @Assert\Required({ @Assert\NotBlank, @Assert\Positive }),
     *         "quantity" = @Assert\Required({ @Assert\NotBlank, @Assert\Positive })
     *     },
     *     allowExtraFields = true
     * ))
     */
  public $orderItems;

  /**
   * @Assert\NotBlank
   * @Assert\Collection(
   *     fields = {
   *         "name" = @Assert\Required({ @Assert\NotBlank }),
   *         "phone" = @Assert\Required({ @Assert\NotBlank }),
   *         "house" =@Assert\Required({ @Assert\Collection(
   *                          fields = {
   *                                 "id" = @Assert\Required({ @Assert\NotBlank, @Assert\Positive })
   *                           }
   *          ) })
   *     },
   *     allowExtraFields = true)
   * )
   */
  public $customer;

  // ["orderItems"]=>
  // array(2) {
  //   [0]=>
  //   array(2) {
  //     ["productId"]=>
  //     int(1)
  //     ["quantity"]=>
  //     int(3)
  //   }
  //   [1]=>
  //   array(2) {
  //     ["productId"]=>
  //     int(2)
  //     ["quantity"]=>
  //     int(2)
  //   }
  // }
}