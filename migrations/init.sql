CREATE TABLE orders
(
    id              VARCHAR(20) NOT NULL PRIMARY KEY, -- ограничение в 20 символов - это очень мало, у нас и так часть id занята датой и разделителями 2020-09- - уже почти половина занята
    sum             INT       DEFAULT 0, -- Предложил бы дефолтную сумму не делать 0 (см ниже - явное лучше неявного)
    contractor_type SMALLINT,
    is_paid         SMALLINT  DEFAULT 0, -- вместо флага я бы предложил использовать статус - paid, new Если у нас появятся новые статусы  - например отмена заказа, то мы сможем с легкостью с этим работать (а я уверен, что такое появится)
    -- Плюс на состояние мы можем добавить например конечный автомат
    createdAt       TIMESTAMP DEFAULT NOW() -- часть полей в snake_case, часть в camelCase. необходимо единообразие
) CHARACTER SET utf8 COLLATE utf8_general_ci engine MyISAM;

CREATE TABLE order_products
(
    id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id   VARCHAR(20),
    product_id INT, -- внешний ключ на товар? Возможно не нужен, если сервис работает с отдельной бд
    price      INT DEFAULT 0, -- Я бы предложил убрать дефолтную цену и количество, чтобы избежать ошибок. Явное лучше неявного. Поэтому если нам не передали количество или цену, то пусть будет ошибка.
    quantity   INT DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
) CHARACTER SET utf8 COLLATE utf8_general_ci  engine MyISAM;