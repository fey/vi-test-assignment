<?php

namespace App\Controller;

use App\Factory\OrderFactory;
use App\Repository\OrderRepository;
use App\Service\BillGenerator;
use App\Service\BillMicroserviceClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Request; // лишний начальный символ \
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use const App\Entity\CONTRACTOR_TYPE_LEGAL;
use const App\Entity\CONTRACTOR_TYPE_PERSON;

class OrderController
{
    /** @var OrderFactory */
    protected $order_factory;

    /** @var OrderRepository */
    // если используем 7.4 версию, то можем использовать типизированные свойства
    // имя свойств должно быть в camelCase (стандарты PSR)
    protected $order_repository;

    // имя переменных и параметров должно быть в camelCase (стандарты PSR)
    public function __construct(OrderFactory $order_factory, OrderRepository $order_repository)
    {
        $this->order_factory = $order_factory;
        $this->order_repository = $order_repository;
    }

    // В путь запроса стоит указывать что за сущность мы создаем или меняем т.е если это заказ то /orders
    // + по REST запрос должен идти на маршрут ресурса - т.е. POST /orders Добавлять глагол в путь - антипаттерн
    // https://www.restapitutorial.com/lessons/restfulresourcenaming.html
    /**
     * @Route("/create", methods={"POST"})
     */
    public function create(Request $request)// Нет типизации возвращаемого значения
    {
        // Не увидел валидации входящих параметров (хотя бы валидацию JSON схемой)
        $orderData = json_decode($request->getContent(), true);
        $orderId = $this->order_factory->generateOrderId();

        try {
            $order = $this->order_factory->createOrder($orderData, $orderId);

            // Можно сделать через switch
            if ($order->contractorType === CONTRACTOR_TYPE_PERSON) {
                $this->order_repository->save($order);
                return new RedirectResponse($order->getPayUrl());

            }
            if ($order->contractorType === CONTRACTOR_TYPE_LEGAL) {
                // Там в getBillUrl я уже отписался. Выглядит лишней зависимость.
                // Гораздо лучше передавать BillGenerator как зависимость или использовать внутри какого-нибудь сервиса
                // и ничего не прокидывать в Заказ, а оставить его чистой сущности.
                // В getBillUrl() прокидывать заказ типа ->getBillUrl($order) и получаем ссылку
                $order->setBillGenerator(new BillGenerator());
                $this->order_repository->save($order);
                // Не знаю какая архитектура у сервиса и вообще приложения, но выглядит странно общаться по HTTP и делать редиректы.
                // Т.е. у нас большое приложение общается с миром, а сервис заказов не знает особо про внешний мир - то лучше отдавтаь JSON ответ со ссылкой для редиректа,
                // А главное приложение пусть само редиректит, если необходимо.
                return new RedirectResponse($order->getBillUrl());
            }
        } catch (\Exception $exception) {
            return new Response("Something went wrong"); // если мы принимаем json, отдаем в других методах json, то почему на ошибку отдаем просто строчку?
            // Плюс я не вижу http кода о том, что произошла ошибка (т.е. придется 200 ответ и "Что-то пошло не так)
        }
    }

    // Аналогичный комментарий finish не говорит о том, что мы завершаем заказ. Мы проверяем статус
    // Т.е. с ресурсным неймингом будет путь /orders/{orderId}/status
    /**
     * @Route("/finish/{orderId}", methods={"GET"})
     *
     */
    public function finish($orderId) // так как это проверка статуса, а не реальное завершение заказа, можно назвать метод getStatus + типизации нет
    {
        $order = $this->order_repository->get($orderId);
        if ($order->contractorType == CONTRACTOR_TYPE_LEGAL) {
            // Внутри isPaid отписался, что BillMicroserviceClient внутри Заказа не нужен.
            // Лучше использовать класс сервис, который работает с заказом и проверяет статус платежа заказа
            // Order следует оставить чистой сущностью
            $order->setBillClient(new BillMicroserviceClient());
        }
        if ($order->isPaid()) {
            // Опять же к вопросу об архитектуре приложения. Выглядит странно, что часть методов у нас отдает ответы JSON, а часть - страницу с текстом.
            return new Response("Thank you");
        } else { // else лишний
            return new Response("You haven't paid bill yet");
        }
    }

    // Если используем ресурсный роутинг, то будет следующий урл запроса - /orders/last
    /**
     * @Route("/last", methods={"GET"})
     */
    public function last(Request $request) // имя функции - глагол т.е. getLatestOrders + типизации нет
    {
        $limit = $request->get("limit");
        $orders = $this->order_repository->last($limit);
        // Заказы никаким образом не форматируются для ответа в соответствии со спецификацией
        return new JsonResponse($orders);
    }
}