<?php

namespace App\Entity;

class Item
{
    /** @var string */
    // свойства protected, для этого нет причин, можно сделать их приватными
    protected $id;

    /** @var string */
    protected $orderId;

    /** @var string */
    protected $productId;

    /** @var string */
    protected $price;

    /** @var string */
    protected $quantity;

    /**
     * @param string $orderId
     * @param string $productId
     * @param string $price
     * @param string $quantity
     */
    public function __construct($orderId, $productId, $price, $quantity)
    {
        // вместо аннотаций использовать типизированные параметры
        // Также замечу, что в этом классе используются геттеры и сеттеры, а в Заказе - нет.
        // по типизации - в сеттерах это void, в геттерах - тип свойства
        // Ну и строгий режим (declare strict types)
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    /**
     * @param int $id
     */
    public function setId(int $id) // тип не указан // Метод не используется Он вообще нужен?
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId() // тип не указан // Метод не используется Он вообще нужен?
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOrderId() // тип не указан
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     */
    public function setOrderId(int $orderId) // тип не указан
    {
        $this->orderId = $orderId;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return Item
     */
    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        // лишний fluent interface. Это же не билдер + метод не используется + нигде больше нет такого
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice() // тип не указан
    {
        return $this->price;
    }

    /**
     * @param int $price
     */
    public function setPrice(int $price) // тип не указан
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getQuantity() // тип не указан
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity) // тип не указан
    {
        $this->quantity = $quantity;
    }
}