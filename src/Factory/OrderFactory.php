<?php
// я бы здесь и везде предложил использовать строгие типы, чтобы избежать случайно привидения типов
namespace App\Factory;

use App\Entity\Item;
use App\Entity\Order;

class OrderFactory
{
    /** @var \PDO */
    protected $pdo;

    /**
     * OrderFactory constructor. // лишний комментарий, мы и так понимаем, что это конструктор
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo; // так как PDO используется для генерации ID (и неправильнйо), то его можно будет отсюда унести в соотв. с комментом ниже (где про класс-генератор)
    }

    // Не похоже, фабрика должна генерировать ID. Стоит вынести в отдельный класс типа OrderIdGenerator
    public function generateOrderId()
    {
        $sql = "SELECT id FROM orders ORDER BY createdAt DESC LIMIT 1";
        $result = $this->pdo->query($sql)->fetch();
        return (new \DateTime())->format("Y-m") . "-" . $result['id'] + 1;
        // Стоит DateTime передавать снаружи как провайдер времени.
        // Может потребоваться для тестирования. (иначе если мы будем в тестах генерировать текущую, то ощутим боль)
        // Уверен, что код не заработает. У нас ID заказа это строка вида 2020-09-12345
        // и мы будем получать ид последенго заказа и будет конкатенировать с сгенерированным ID получая строка типа 2020-09-12345-2021-02-12345
        // а к ней еще плюсовать 1 т.е. код просто не рабочий. non-numeric value encountered in
    }

    public function createOrder($data, $id) // нет типизации параметров и возвращаемого значения
    {
        $order = new Order($id);
        // Вместо прохода по массиву с данными использовать явное наполнение сущности, таким образом не будет отдельного ифа.
        // Также вместо массива с данных можно использовать DTO, чтобы мы 100% были уверены какие данные приходят
        foreach ($data as $key => $value) // здесь и ниже код конструкций {} оформлен неправильно. Необходимо код проверить линтерам на стандарт PSR
        {
            if ($key == 'items')
            {
                // можно использовать array_map вместо  foreach
                foreach ($value as $itemValue) {
                    $order->items[] = new Item($id, $itemValue['productId'], $itemValue['price'], $itemValue['quantity']);
                }
                continue;
            }
            $order->{$key} = $value;
        }
        return $order;
    }
}