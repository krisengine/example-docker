# Пример использования Docker

## О Docker

Docker — это открытая платформа для разработки, доставки и эксплуатации приложений. 
Docker позволяет создать изолированное переносимое окружение. Т.е. окружение не зависящее от того, на каком компьютере 
оно запускается и легко переносимой с одного компьютера на другой. Благодаря этой возможности используя докер можно 
создать оптимальное окружение для проекта (например php + web-сервер + ssl(c возможность шифрования алгоритмом GOST2012))
и легко распространить его между компьютерами разработчиков.

Подробную информацию о Docker и его установке можно прочитать на официальном сайте: 
[Docker overview](https://docs.docker.com/get-started/overview/)

## Хранилища образов (registry)

Основным местом для хранения образов докера является [docker hub](https://hub.docker.com/). Любой может 
зарегистрироваться на этом ресурсе и бесплатно размещать свои образы, чтобы получать доступ к ним с любого компа.

## Примеры использования

### Использование существующего контейнера

Чтобы воспользоваться уже существующим контейнером, нужно выполнить одну команду и, если образ не был ранее скачан, то
Docker его скачает и запустит.

Например можно запустить веб-сервер nginx выполнив в командной оболочке команду

```
docker run -p 8081:80 nginx:1.19
```

где:
 - docker - консольная утилита для управления докером
 - run - действие запуска контейнера (так же есть действия exec, build прочие)
 - -p 8081:80 - указывает, что необходимо связать порт 8081(можно указать любой не занятый порт) хостовой ОС с портом 
 80 внутри контейнера. 
 - nginx:1.19 - название и тег образа (в качестве тега часто указывается версия ПО или контейнера).
 
Чтобы остановить контейнер, нажмите Ctrl+C.
 
После скачивания образа и запуска контейнера можно перейти по адресу [http://localhost:8081/](http://localhost:8081/) и 
увидеть приветственную страницу nginx
 
Из документации nginx известно что путь к сайту по-умолчанию /usr/share/nginx/html. Можно смонтировать любую папку 
хостовой системы в папку /usr/share/nginx/html. в этом случае nginx будет брать сайт с вашего компа. 
- создадим папку и назовем её site
- запустим контейнер с монтированием папки site хостовой ОС в папку /usr/share/nginx/html контейнера
```
docker run -p 8081:80 -v "<путь_к_папке_site>:/usr/share/nginx/html" nginx:1.19
```
- теперь можно перейти по ссылке [http://localhost:8081/](http://localhost:8081/) и увидеть сайт.

Сайт должен быть статическим (т.е. не содержать никаких скриптов на PHP) и иметь файл index.html в корневой папке.

### Сборка кастомного контейнера 

Чтобы собрать свой контейнер с php и composer необходимо выполнить последовательность действий:
- выбрать ОС с которой будем работать, а точнее базовый образ. При использовании докера образы создаются на основе уже существующих образов (В примере будет очень легковесный Linux Alpine)
- установить в контейнер php с расширениями json, openssl, phar, iconv, mbstring
- установить composer
- назначить рабочую папку (с которой будет работать веб-сервер)
- задокументировать порт на котором будет доступен сайт
- сделать автозапуск [стандартного dev сервера php](https://www.php.net/manual/ru/features.commandline.webserver.php)

Чтобы не запускать все команды вручную, можно их записать в файл, понятный декеру в формате [Dokerfile](https://docs.docker.com/engine/reference/builder/)

Создадим папку php и добавим в неё 2 файла с именами Dockerfile и index.php

```dockerfile
FROM alpine:3.13
RUN apk add --no-cache php-cli php-json php-openssl php-phar gnu-libiconv php-iconv php-mbstring
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=bin --filename=composer && php -r "unlink('composer-setup.php');"
WORKDIR /app
EXPOSE 8000
ENTRYPOINT ["/usr/bin/php", "-S", "0.0.0.0:8000"]
```

```php
<?php phpinfo();
```

теперь можно перейти в папку php в командной строке и выполнить сборку образа:
```
docker build -t myphp .
```
где myphp - название нового образа.

После сборки можно запустить контейнер на основе созданного образа
```
docker run -v "<путь_к_папке_php>:/app" -p 8082:8000 myphp
```

Теперь открываем сайт [http://localhost:8082/](http://localhost:8082/) и видим phpinfo

### Запуск команды в контейнере

Попробуем сделать пример посложнее. создадим новую папку slim и создадим в ней 2 файла composer.json и index.php

```json
{
    "require": {
        "php": "^7.2",
        "ext-json": "*",
        "monolog/monolog": "^2.2",
        "php-di/php-di": "^6.3",
        "slim/psr7": "^1.3",
        "slim/slim": "^4.7"
    }
}
```

```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello world !!!");
    return $response;
});

$app->run();

```

теперь запустим этот код в нашем контейнере указав путь к папке slim
```
docker run -v "<путь_к_папке_slim>:/app" -p 8082:8000 myphp
```

Открываем сайт [http://localhost:8082/](http://localhost:8082/) и видим ошибку 500. При этом в командной строке мы 
видим ошибку ```require(/app/vendor/autoload.php): failed to open stream: No such file or directory```. Все верно,
композером мы ничего не устанавливали. при сборке контейнера, мы установили его в контейнер. осталось просто запустить 
контейнер с командой ```composer install```. но команда не выполнится, т.к. она дописывается к существующему entrypoint.
чтобы команда выполнилась, entrypoint нужно сделать пустым, что мы и сделаем при запуске контейнера:
```
docker run -v "<путь_к_папке_slim>:/app" -p 8082:8000 --entrypoint="" myphp composer install
```

после установки всех пакетов можно запускать контейнер
```
docker run -v "<путь_к_папке_slim>:/app" -p 8082:8000 myphp
```

Открываем сайт [http://localhost:8082/](http://localhost:8082/) и видим "Hello world !!!"

### Запуск приложения как сервис.

Для запуска контейнера мы использовали команду docker run, при этом в командную строку выводился лог программы, а, как 
только мы закрывали терминал или нажимали Ctrl+C контейнер прекращал работу. чтобы запустить контейнер, как фоновый 
процесс не связанный с командной строкой нужно дописать ключ -d. 

```
docker run -d -v "<путь_к_папке_slim>:/app" -p 8082:8000 myphp
```

После запуска докер напишет имя контейнера (не путать с именем образа) который он запустил. 
Например: e26679cbd7c6a99ed8817cb9c6a1c14276232f0836bb901f7f15824c2cf88888.

Чтобы контейнер остановить, нужно выполнить команду docker stop с названием контейнера

```
docker stop e26679cbd7c6a99ed8817cb9c6a1c14276232f0836bb901f7f15824c2cf88888
```

чтобы задавать контейнерам осмысленные имена, есть опция --name.

```
docker run -d -v "<путь_к_папке_slim>:/app" -p 8082:8000 --name="myphp_container" myphp
```

и остановка

```
docker stop myphp_container
```

В опирациооных системах на базе ядра GNU/Linux запущенные с опцией --restart=always контейнеры, запускаются вновь после 
перезагрузки ОС 

## Коллекция контейнеров

Для решения задачи запуска web-приложения требуется несколько программ 
- вебсервер (например apache)
- интерпретатор PHP 
- СУБД (например MySQL)

веб-сервер будет работать с php по протоколу fastcgi. 

Для работы создадим папку damp (docker apache mysql php) и будем работать в ней.

### Контейнер web-server

Для начала нам потребуется настроить веб-сервер (на базе nginx).
Сделаем заранее заготовленный конфиг виртуального хоста по-умолчанию

(файл 000-default.conf) 
```
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name _;
    root /app;

    location / {
         index index.php;
         try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param REQUEST_METHOD $request_method;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass php:9000;
    }

    location ~ /\.ht {
        deny all;
    }
}

```

Файл образа (Dockerfile-web-server)
```dockerfile
FROM nginx:1.19
COPY ./default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
WORKDIR /app

```

находясь в папке dump выполним сборку контейнера 
```
docker build -t damp-web-server -f Dockerfile-web-server .
```

### Контейнер php

В качестве интерпретатора php работающего по протоколу fastcgi будет использован php-fpm версии 7.3. 
для настройки модулей используем стандартную утилиту docker-php-ext-install из официальных образов php

Файл образа (Dockerfile-php)
```dockerfile
FROM php:7.3-fpm
RUN apt-get update && apt-get install -y zlib1g-dev libzip-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install -j$(nproc) iconv mbstring pdo pdo_mysql zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=bin --filename=composer && php -r "unlink('composer-setup.php');"
WORKDIR /app
CMD ["php-fpm"]

```

находясь в папке dump выполним сборку контейнера 
```
docker build -t damp-php -f Dockerfile-php .
```

### Контейнер db

В качестве базы данных будем использовать mysql версии 5.7. загрузим образ, чтобы не ждать его загрузки во время запуска

```
docker pull mysql:5.7
```

### Тестовый скрипт

Для тестирования напишем простой скрипт на php который выведет 
[ServerAPI](https://www.php.net/manual/ru/function.php-sapi-name.php) и подключится к БД

создадим папку app  и в ней файл index.php
```php
<?php

print "<p style='color:green'>" . php_sapi_name() . "</p>";
try {
    $dbh = new PDO(getenv('DB_DSN'), getenv('DB_USER'), getenv('DB_PASSWORD'));
    print "<p style='color:green'>Успешное подключение к базе данных</p>";
} catch (PDOException $e) {
    print "<p style='color:red'>'Подключение не удалось: " . $e->getMessage() . "</p>";
}

```

### Запуск всей коллекции

теперь нам нужно запустить СУБД, php и web-server. чтобы контейнеры были доступны по сети друг другу их нужно будет 
объединить в ощую сеть и дать удобные имена.

создадим новую сеть с названием damp-net
```
docker network create damp-net
```

Запустим в сети вебсервер с именем (и именем в сети) web-server на порту 8080 хостовой системы пробросом папки app в контейнер
```
docker run -p 8080:80 --name="web-server" --network="damp-net" -v "<путь_к_папке_damp>/app:/app" -d damp-web-server 
```

Запустим в сети СУБД с именем db и передав следующие параметры (помощью переменных окружения) для создания базы:
название(MYSQL_DATABASE): main 
логин(MYSQL_USER): user
пароль(MYSQL_PASSWORD): password

и пробросим в контейнер папку, в которую будут сохраняться файлы БД

```
docker run --name=db --network="damp-net" -v "<путь_к_папке_damp>/db:/var/lib/mysql" -e MYSQL_DATABASE=main -e MYSQL_USER=user -e MYSQL_PASSWORD=password -e MYSQL_ROOT_PASSWORD=root_password -d mysql:5.7
```

запустим сам php с именем php (именно это имя прописано в конфиге вебсервера fastcgi_pass php:9000;) и передадим 
переменные окружения, которые используются в тестовом скрипте (DB_DSN, DB_USER, DB_PASSWORD). и не забудем пробросить 
папку с тестовым скриптом. база данных в сети имеет имя db

```
docker run --name=php --network="damp-net" -v "<путь_к_папке_damp>/app:/app" -e DB_DSN="mysql:host=db;dbname=main" -e DB_USER=user -e DB_PASSWORD=password -d damp-php
```

теперь можно перейти по ссылке [localhost:8080](http://localhost:8080)

чтобы остановить это окружение нужно выполнить 7 команд
```
docker stop web-server
docker stop php
docker stop db
docker rm web-server
docker rm php
docker rm db
docker network remove damp-net 
```
