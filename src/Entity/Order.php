<?php

namespace App\Entity;

use App\Service\BillGenerator;
use App\Service\BillMicroserviceClient;

// Константы можно положить прямо в Order + я бы предложил заменить их на текстовые обозначения, так в бд например будет понятно, что они значат
// или можно сгруппировать их, положив в какой-нибудь абстрактный класс.
const CONTRACTOR_TYPE_PERSON = 1;
const CONTRACTOR_TYPE_LEGAL = 2;

class Order
{
    /** @var string
     * // в Item свойства закрыты и доступ к ним предоставляется через методы
    public $id;

    /** @var int */
    // с 7.4 можно использовать типизированные свойства.
    public $sum;

    /** @var Item[] */
    public $items = [];

    /** @var int */
    // Так Так как количество значений у нас ограничено, то можно использовать enum на уровне бд или на уровне кода, через сеттер метод например.
    public $contractorType;

    /** @var bool */
    public $isPaid;

    /** @var BillGenerator */
    // Лишние зависимости, сущность должна оставаться чистой и не содержать зависимостей
    // + там есть геттеры и сеттеры так что публиность свойств сомнительна
    public $billGenerator;

    /** @var BillMicroserviceClient */
    public $billMicroserviceClient;

    /**
     * @param string $id
     */
    // Для чего ID передавать в конструкторе, а остальные параметры сетить? Кроме того id публичный
    public function __construct($id) // нет типизации
    {
        $this->id = $id;
    }

    public function getPayUrl() // нет типизации
    {
        // Не похоже, что это должно быть в заказе. Как минимум потому, что заказ за оплату не отвечает
        // Да и платежный интерфейс может измениться + его лучше хранить где-то в конфигурации сервиса (в переменных окружения)
        // или в константе. В общем надо это отсюда убирать.
        return "http://some-pay-agregator.com/pay/" . $this->id;

    }

    // Лишний метод для лишней зависимости
    public function setBillGenerator($billGenerator)
    {
        $this->billGenerator = $billGenerator;
    }

    // Лишняя зависимость
    // Этот метод и setBillClient больше относится к платежной инфе,
    // т.е. стоит вынести из сущности в какой-нибудь сервис (класс) по генерации платежных данных
    // Обращаем запрос и делаем вызов типа $billGenerator->generate($order)
    public function getBillUrl() // нет типизации
    {
        return $this->billGenerator->generate($this);
    }

    public function setBillClient(BillMicroserviceClient $cl) // $cl - лишнее сокращение кода + нет типизации (void)
    {
        $this->billMicroserviceClient = $cl;
    }

    // Вызывается внешний сервис из сущности для проверки статуса заказа
    // В сущности вообще не должно быть зависимостей
    // Поэтому проверку стоит инвертировать - вызывать сервис, передавать туда заказ или id, обновлять заказ
    // Т.е в заказе иметь только актуальную инфу
    public function isPaid() // нет типизации (bool)
    {
        if ($this->contractorType == CONTRACTOR_TYPE_PERSON) {
            return $this->isPaid;
        }
        if ($this->contractorType == CONTRACTOR_TYPE_LEGAL) {
            return $this->billMicroserviceClient->IsPaid($this->id);  // имя метода с большой буквы (PSR)
        }
    }
}