
<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Order;

class OrderRepository
{
    /** @var \PDO */
    // свойство protected, но класса нет потомков, лучше использовать private
    protected $pdo;

    /**
     * @param \PDO $pdo
     */
    // вместо использования глобального неймспейса везде (здесь и в аннотациях к свойству) можно использовать явно use \PDO;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Order $order) // нет типизации
    {
        // ниже идет prepare, но по факту запрос не подготавливается
        // Параметры не биндятся
        // Учитывая, что запрос берется "как есть" и ничего не валидируется и не проверяется, то может это привести к беде
        // Лучше обернуть все это дело (создание заказа и создание товаров заказа) в транзакцию, иначе у нас может сохраниться заказ без товаров
        $sql = "INSERT INTO orders (id, sum, contractor_type) VALUES ({$order->id}, {$order->sum}, {$order->contractorType})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        foreach ($order->items as $item) {
            // а еще пробелы между запятыми потеряны
            $sql = "INSERT 
                INTO order_products (order_id,product_id,price,quantity) 
                VALUES ({$order->id},{$item->getProductId()},{$item->getPrice()}, {$item->getQuantity()})";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
    }

    /** @return Order */
    public function get($orderId) // нет типизации
    {
        // такое же замечание как и выше - нет настоящей подготовки запроса, данные пихаются как есть.
        $sql = "SELECT * FROM orders WHERE id={$orderId} LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        // не обрабатывается кейс, когда у нас не найден заказ или возможно вообще что-то пошло не так с базой (в ошибке можно словить креды от базы)
        // $data слишком общее название для переменной, можно подобрать более подходящее название (например rawOrders)
        $data = $stmt->fetch();

        $order = new Order($data['id']);
        $order->contractorType = $data['contractor_type'];
        $order->isPaid = $data['is_paid'];
        $order->sum = $data['sum'];
        $order->items = $this->getOrderItems($data['id']);

        return $order;
    }

    /** @return Order[] */
    public function last($limit = 10) // нет типизации + имя функции - глагол т.е. getLatest
    {
        // такое же замечание как выше - limit передается снаружи, а значит надо его обрабатывать
        $sql = "SELECT * FROM orders ORDER BY createdAt DESC LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $orders = []; // можно использовать array_map
        foreach ($data as $item) {
            $order = new Order($item['id']);
            $order->contractorType = $item['contractor_type'];
            $order->isPaid = $item['is_paid'];
            $order->sum = $item['sum'];
            $order->items = $this->getOrderItems($item['id']);
            $orders[] = $order;
        }
        return $orders;
    }

    public function getOrderItems($orderId) // нет типизации
    {// такое же замечание как выше - $orderId передается снаружи, а значит надо его обрабатывать
        $sql = "SELECT * FROM order_products WHERE order_id={$orderId}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(); // $data слишком общее название для переменной, можно подобрать более подходящее название (например rawItems)

        $items = [];
        foreach ($data as $item) {// можно использовать array_map
            $items[] = new Item($item['order_id'], $item['product_id'], $item['price'], $item['quantity']);
        }
        return $items;
    }
}

