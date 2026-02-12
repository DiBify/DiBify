# DiBify ORM

DiBify — PHP ORM-библиотека, построенная на паттернах **Repository**, **Identity Map**, **Unit of Work** и **Data Mapper**.

## Философия и ключевая идея

Главная задача DiBify — обеспечить **прозрачную работу приложения сразу с несколькими разными СУБД** одновременно. Бизнес-логика работает с моделями и репозиториями, не зная и не заботясь о том, в каких конкретно базах данных хранятся данные — MongoDB, Redis, Elasticsearch, Clickhouse или любых других.

Благодаря слою **Storage**, который является единственной точкой взаимодействия с конкретной СУБД, можно в любой момент:

- **добавить новую СУБД** — например, начать реплицировать данные в Elasticsearch для полнотекстового поиска, просто написав новый Storage и подключив его как secondary в Replicator;
- **заменить одну СУБД на другую** — например, мигрировать с MongoDB на PostgreSQL, переписав только Storage-слой, без изменений в моделях, сервисах и бизнес-логике;
- **использовать разные СУБД для разных задач** — MongoDB как основное хранилище, Redis для быстрого кеша, Elasticsearch для поиска, Clickhouse для аналитики — и всё это прозрачно для остального кода.

DiBify спроектирована с прицелом на **SaaS-приложения**: встроенная поддержка мультитенантности через механизм **Scope** позволяет автоматически изолировать данные разных клиентов (компаний, аккаунтов) на уровне хранилища, не загромождая бизнес-логику фильтрами и проверками.

---

## Содержание

1. [Архитектура и основные концепции](#1-архитектура-и-основные-концепции)
2. [Model — модель данных](#2-model--модель-данных)
3. [Id — идентификатор модели](#3-id--идентификатор-модели)
4. [IdGenerator — генерация идентификаторов](#4-idgenerator--генерация-идентификаторов)
5. [Reference — ссылки между моделями](#5-reference--ссылки-между-моделями)
6. [Mapper — сериализация и десериализация](#6-mapper--сериализация-и-десериализация)
7. [StorageData — объект данных хранилища](#7-storagedata--объект-данных-хранилища)
8. [Storage — хранилище данных](#8-storage--хранилище-данных)
9. [Replicator — репликация данных](#9-replicator--репликация-данных)
10. [Repository — репозиторий](#10-repository--репозиторий)
11. [Pool — накопительные операции](#11-pool--накопительные-операции)
12. [Locker — блокировки моделей](#12-locker--блокировки-моделей)
13. [Transaction — транзакции](#13-transaction--транзакции)
14. [ConfigManager — реестр конфигурации](#14-configmanager--реестр-конфигурации)
15. [ModelManager — центральный менеджер](#15-modelmanager--центральный-менеджер)
16. [Жизненный цикл сохранения модели](#16-жизненный-цикл-сохранения-модели)
17. [Scope — мультитенантность](#17-scope--мультитенантность)
18. [Дополнительные пакеты](#18-дополнительные-пакеты)
19. [Полный пример приложения](#19-полный-пример-приложения)

---

## 1. Архитектура и основные концепции

DiBify разделяет ответственность между несколькими слоями:

```
┌─────────────────────────────────────────────────────────┐
│                     ModelManager                        │  Координирует всё
│  (commit, refresh, findByReference, scope, locker)      │
├─────────────────────────────────────────────────────────┤
│                     ConfigManager                       │  Связывает модели,
│  (модель → репозиторий, модель → генератор ID)          │  репозитории и генераторы
├─────────────────────────────────────────────────────────┤
│                     Transaction                         │  Группирует изменения
│  (persisted, deleted, metadata, events)                 │  для атомарного коммита
├──────────────┬──────────────────────────────────────────┤
│  Repository  │  Mapper         │  Locker               │
│  (Identity   │  (serialize/    │  (lock/unlock/         │
│   Map,       │   deserialize)  │   passLock/            │
│   CRUD)      │                 │   waitForLock)         │
├──────────────┴─────────────────┴───────────────────────┤
│                     Replicator                          │  Координирует запись
│  (primary + secondaries)                                │  в несколько хранилищ
├─────────────────────────────────────────────────────────┤
│                     Storage                             │  Непосредственная
│  (MongoDB, Redis, Elasticsearch, Clickhouse, ...)       │  работа с БД
└─────────────────────────────────────────────────────────┘
```

**Поток данных при сохранении:**

```
Model → Mapper.serialize() → StorageData → Replicator → Storage(s) → БД
```

**Поток данных при чтении:**

```
БД → Storage → StorageData → Mapper.deserialize() → Model
```

---

## 2. Model — модель данных

### Интерфейс ModelInterface

Каждая модель (сущность) должна реализовывать `ModelInterface`:

```php
namespace DiBify\DiBify\Model;

interface ModelInterface
{
    public function id(): Id;
    public static function getModelAlias(): string;
}
```

- `id()` — возвращает объект `Id` (уникальный идентификатор модели)
- `getModelAlias()` — возвращает строковый псевдоним модели (например, `'user'`, `'role'`). Используется при хранении ссылок (`Reference`) и внутренней маршрутизации

### Создание модели

```php
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;

class User implements ModelInterface
{
    protected Id $id;
    protected string $name;
    protected string $email;
    protected DateTimeImmutable $createdAt;

    public function __construct(string $name, string $email)
    {
        $this->id = new Id();  // ID ещё не присвоен
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): Id
    {
        return $this->id;
    }

    public static function getModelAlias(): string
    {
        return 'user';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

> **Важно:** при создании модели `Id` создаётся без значения (`new Id()`). Реальный идентификатор будет присвоен автоматически при коммите через `IdGenerator`.

### Хуки жизненного цикла

Модели могут реализовывать дополнительные интерфейсы для подключения к жизненному циклу коммита:

```php
use DiBify\DiBify\Model\ModelBeforeCommitEventInterface;
use DiBify\DiBify\Model\ModelAfterCommitEventInterface;

class User implements ModelInterface, ModelBeforeCommitEventInterface, ModelAfterCommitEventInterface
{
    // ... поля и методы ...

    public function onBeforeCommit(): void
    {
        // Вызывается перед сохранением в хранилище.
        // Используется для валидации, нормализации данных и т.д.
        $this->name = trim($this->name);
    }

    public function onAfterCommit(): void
    {
        // Вызывается после успешного сохранения.
        // Используется для отправки уведомлений, генерации событий и т.д.
    }
}
```

---

## 3. Id — идентификатор модели

`Id` — это value-объект, представляющий уникальный идентификатор модели.

```php
namespace DiBify\DiBify\Id;

class Id implements JsonSerializable
{
    public function __construct(string|int $id = null);
    public function get(): ?string;           // Получить значение
    public function isAssigned(): bool;       // Присвоен ли ID
    public function assign(string $id): bool; // Присвоить (только один раз)
    public function isEqual(ModelInterface|Reference|Id|string|int $id): bool;
    public function __toString(): string;
}
```

### Состояния Id

```php
// Неприсвоенный — только что создана модель
$id = new Id();
$id->isAssigned(); // false
$id->get();        // null

// Присвоенный — после прохождения через IdGenerator или при загрузке из БД
$id = new Id('42');
$id->isAssigned(); // true
$id->get();        // '42'
(string) $id;      // '42'
```

> **Важно:** Метод `assign()` можно вызвать только один раз. Повторное присвоение вернёт `false`. Это предотвращает случайную смену идентификатора.

---

## 4. IdGenerator — генерация идентификаторов

`IdGeneratorInterface` определяет контракт для генерации уникальных идентификаторов:

```php
namespace DiBify\DiBify\Id;

interface IdGeneratorInterface
{
    public function __invoke(ModelInterface $model): Id;
}
```

Генератор вызывается как функция — он проверяет, нужно ли модели присвоить ID, и если нужно, генерирует и присваивает его.

### Встроенные генераторы

#### UuidGenerator

Генерирует UUID v4:

```php
use DiBify\DiBify\Id\UuidGenerator;

$uuid = UuidGenerator::generate();
// Пример: "550e8400-e29b-41d4-a716-446655440000"
```

#### SortableUniqueIdGenerator

Генерирует сортируемые уникальные идентификаторы на основе временной метки:

```php
use DiBify\DiBify\Id\SortableUniqueIdGenerator;

$id = SortableUniqueIdGenerator::generate();
// Пример: "k5f3h7r9m2x1p4w6" — сортируется по времени создания
```

Полезен для моделей, где важен порядок создания (логи, события).

### Redis IdGenerator (пакет `dibify/id-generator-redis`)

Генерирует последовательные числовые идентификаторы с помощью Redis:

```php
use DiBify\IdGenerator\Redis\IdGenerator;

$generator = new IdGenerator($redis, 'RedisIdGenerator');
// User получит ID: 1, 2, 3, ...
// Role получит ID: 1, 2, 3, ... (независимая последовательность)
```

Каждый тип модели (определяемый по `getModelAlias()`) получает отдельный счётчик. Инкремент атомарный благодаря Redis `HINCRBY`.

---

## 5. Reference — ссылки между моделями

`Reference` — это ленивая ссылка на модель. Позволяет хранить связь между моделями без загрузки связанной модели до момента обращения.

### Создание ссылок

```php
use DiBify\DiBify\Model\Reference;

// Создать ссылку из модели (модель уже загружена)
$userRef = Reference::to($user);

// Создать ссылку по алиасу и ID (модель НЕ загружена)
$userRef = Reference::create('user', '42');
$userRef = Reference::create(User::class, '42'); // или по классу
```

### Использование ссылок

```php
// Получить алиас и ID
$userRef->getModelAlias(); // 'user'
$userRef->id();            // Id('42')

// Получить саму модель (ленивая загрузка)
$user = $userRef->getModel(); // Загрузит из БД при первом обращении

// Проверить, ссылается ли на конкретную модель
$userRef->isFor($user); // true/false
```

### Ссылки в модели

```php
class Shop implements ModelInterface
{
    protected Id $id;
    protected string $name;
    protected Reference $owner;      // Обязательная ссылка на User
    protected ?Reference $manager;   // Необязательная ссылка

    public function __construct(string $name, User $owner, ?User $manager = null)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->owner = Reference::to($owner);
        $this->manager = $manager ? Reference::to($manager) : null;
    }

    public function getOwner(): User
    {
        return $this->owner->getModel();
    }

    public function getOwnerReference(): Reference
    {
        return $this->owner;
    }

    // ...
}
```

### Предзагрузка (preload)

Чтобы избежать проблемы N+1, можно предзагрузить несколько ссылок одним запросом:

```php
// Собираем ссылки
Reference::preload($shop1->getOwnerReference(), $shop2->getOwnerReference(), $shop3->getOwnerReference());

// При первом вызове getModel() на любой из них — загрузятся все сразу
$owner1 = $shop1->getOwner(); // Один запрос загрузит всех трёх владельцев
$owner2 = $shop2->getOwner(); // Уже в кеше
$owner3 = $shop3->getOwner(); // Уже в кеше
```

### Кеширование

`Reference` внутренне кеширует все созданные ссылки. Если вы дважды создадите ссылку на один и тот же алиас + ID, вернётся один и тот же объект:

```php
$ref1 = Reference::create('user', '42');
$ref2 = Reference::create('user', '42');
$ref1 === $ref2; // true — один и тот же объект
```

### Сериализация

```php
$ref = Reference::to($user);

// В JSON
$json = json_encode($ref);
// {"alias":"user","id":"42"}

// Из массива
$ref = Reference::fromArray(['alias' => 'user', 'id' => '42']);

// Из JSON
$ref = Reference::fromJson('{"alias":"user","id":"42"}');
```

---

## 6. Mapper — сериализация и десериализация

Mapper отвечает за преобразование объектов в данные для хранения и обратно. Это ключевое звено между бизнес-объектами и хранилищем.

### MapperInterface

```php
namespace DiBify\DiBify\Mappers;

interface MapperInterface
{
    /**
     * Преобразование сложных данных (объект) в простые (скаляр, null, массив)
     */
    public function serialize($complex);

    /**
     * Преобразование простых данных (скаляр, null, массив) в сложные (объект)
     */
    public function deserialize($data);
}
```

### Базовые мапперы

| Маппер | Описание | Пример |
|--------|----------|--------|
| `StringMapper` | Строки | `"hello"` ↔ `"hello"` |
| `IntMapper` | Целые числа | `42` ↔ `42` |
| `FloatMapper` | Числа с плавающей точкой | `3.14` ↔ `3.14` |
| `BoolMapper` | Логические значения | `true` ↔ `true` |
| `DateTimeMapper` | Даты (мутабельные/иммутабельные) | `DateTime` ↔ `1707820800` (timestamp) |
| `IdMapper` | Идентификаторы | `Id('42')` ↔ `'42'` |
| `EnumMapper` | PHP Enum (BackedEnum) | `Status::Active` ↔ `'active'` |

Базовые мапперы используют паттерн Singleton:

```php
$mapper = StringMapper::getInstance();
$mapper = IntMapper::getInstance();
$mapper = DateTimeMapper::getInstanceImmutable(); // для DateTimeImmutable
$mapper = DateTimeMapper::getInstanceMutable();   // для DateTime
```

### Составные мапперы

#### ObjectMapper — маппинг вложенных объектов

```php
use DiBify\DiBify\Mappers\ObjectMapper;

// Для value-объекта Address
$addressMapper = new ObjectMapper(Address::class, [
    'city' => StringMapper::getInstance(),
    'street' => StringMapper::getInstance(),
    'zip' => StringMapper::getInstance(),
]);

// Сериализация:
// Address { city: "Москва", street: "Тверская", zip: "101000" }
// → ['city' => 'Москва', 'street' => 'Тверская', 'zip' => '101000']
```

#### ModelMapper — маппинг моделей

Расширяет `ObjectMapper` для моделей — автоматически извлекает `id` в отдельное поле `StorageData`:

```php
use DiBify\DiBify\Mappers\ModelMapper;

class UserMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(User::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'email' => StringMapper::getInstance(),
            'createdAt' => DateTimeMapper::getInstanceImmutable(),
        ]);
    }
}
```

При `serialize()` возвращает `StorageData` с отдельным `id` и `body`:
```php
$data = $mapper->serialize($user);
// StorageData {
//   id: '42',
//   body: ['name' => 'Иван', 'email' => 'ivan@example.com', 'createdAt' => 1707820800]
// }
```

При `deserialize()` принимает `StorageData` и восстанавливает модель:
```php
$user = $mapper->deserialize($storageData); // → User
```

#### ReferenceMapper — маппинг ссылок

```php
use DiBify\DiBify\Mappers\ReferenceMapper;

// Ленивая загрузка (по умолчанию) — модель загружается при обращении
$lazyMapper = ReferenceMapper::getInstanceLazy();

// Жадная загрузка — модель загружается сразу при десериализации
$eagerMapper = ReferenceMapper::getInstanceEager();
```

Хранимый формат: `['alias' => 'user', 'id' => '42']` ↔ `Reference`

#### NullOrMapper — обёртка для nullable-полей

```php
use DiBify\DiBify\Mappers\NullOrMapper;

// Поле может быть null или Reference
$mapper = new NullOrMapper(ReferenceMapper::getInstanceLazy());

$mapper->serialize(null);  // null
$mapper->serialize($ref);  // ['alias' => 'user', 'id' => '42']
```

#### ArrayMapper — маппинг массивов

```php
use DiBify\DiBify\Mappers\ArrayMapper;

// Массив строк
$tagsMapper = new ArrayMapper(StringMapper::getInstance());

// Массив объектов Address
$addressesMapper = new ArrayMapper($addressMapper);
```

#### ValueObjectMapper — маппинг value-объектов с одним свойством

```php
use DiBify\DiBify\Mappers\ValueObjectMapper;

// Email как value-объект
$emailMapper = new ValueObjectMapper(Email::class, 'value', StringMapper::getInstance());
// Email { value: "user@example.com" } ↔ "user@example.com"
```

#### CallableMapper — произвольная логика

```php
use DiBify\DiBify\Mappers\CallableMapper;

$statusMapper = new CallableMapper(
    serialize: fn($status) => $status->value,
    deserialize: fn($data) => Status::from($data),
);
```

#### UnionMapper — полиморфные типы

Когда одно поле может содержать объекты разных типов:

```php
use DiBify\DiBify\Mappers\UnionMapper;
use DiBify\DiBify\Mappers\Components\UnionRule;

$paymentMapper = new UnionMapper([
    new UnionRule(
        mapper: $cardPaymentMapper,
        serialize: fn($value) => $value instanceof CardPayment,
        deserialize: fn($data) => isset($data['cardNumber']),
    ),
    new UnionRule(
        mapper: $bankTransferMapper,
        serialize: fn($value) => $value instanceof BankTransfer,
        deserialize: fn($data) => isset($data['bankAccount']),
    ),
]);
```

#### PoolMapper — маппинг пулов

```php
use DiBify\DiBify\Mappers\PoolMapper;

$balanceMapper = new PoolMapper(IntPool::class, IntMapper::getInstance());
// IntPool { current: 100, pool: 50 } ↔ ['current' => 100, 'pool' => 50]
```

### Полный пример маппера модели

```php
class ShopMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(Shop::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'owner' => ReferenceMapper::getInstanceLazy(),
            'manager' => new NullOrMapper(ReferenceMapper::getInstanceLazy()),
            'address' => new NullOrMapper(new ObjectMapper(Address::class, [
                'city' => StringMapper::getInstance(),
                'street' => StringMapper::getInstance(),
                'zip' => StringMapper::getInstance(),
            ])),
            'tags' => new ArrayMapper(StringMapper::getInstance()),
            'rating' => FloatMapper::getInstance(),
            'isActive' => BoolMapper::getInstance(),
            'createdAt' => DateTimeMapper::getInstanceImmutable(),
        ]);
    }
}
```

### Миграция данных через Mapper

Переопределяя `deserialize()`, можно обрабатывать изменения схемы:

```php
class ShopMapper extends ModelMapper
{
    public function __construct() { /* ... */ }

    public function deserialize($data)
    {
        // Раньше поле называлось 'title', теперь 'name'
        if (isset($data->body['title']) && !isset($data->body['name'])) {
            $data->body['name'] = $data->body['title'];
            unset($data->body['title']);
        }

        return parent::deserialize($data);
    }
}
```

---

## 7. StorageData — объект данных хранилища

`StorageData` — транспортный объект между Mapper, Repository и Storage. Он несёт сериализованные данные модели.

```php
namespace DiBify\DiBify\Repository\Storage;

class StorageData implements JsonSerializable
{
    public ?string $scope = null;   // Область видимости (мультитенантность)
    public string $id;              // Идентификатор модели
    public array $body;             // Сериализованное тело модели
    public array $metadata = [];    // Дополнительные метаданные
}
```

### Создание

```php
// Автоматически подставляет текущий scope из ModelManager
$data = new StorageData('42', ['name' => 'Иван', 'email' => 'ivan@example.com']);

// С явным scope
$data = new StorageData('42', ['name' => 'Иван'], 'company_1');

// Из массива/JSON
$data = StorageData::fromArray(['id' => '42', 'body' => [...], 'scope' => 'company_1']);
$data = StorageData::fromJson('{"id":"42","body":{...}}');
```

`StorageData` создаётся маппером при `serialize()` и передаётся мапперу при `deserialize()`.

---

## 8. Storage — хранилище данных

`StorageInterface` определяет контракт для работы с конкретным бэкендом хранения:

```php
namespace DiBify\DiBify\Repository\Storage;

interface StorageInterface extends FreeUpMemoryInterface
{
    public function findById(string $id): ?StorageData;
    public function findByIds($ids): array;            // индексирован по id
    public function insert(StorageData $data, array $options = []): void;
    public function update(StorageData $data, array $options = []): void;
    public function delete(string $id, array $options = []): void;
    public function scope(): ?string;
}
```

### Реализации хранилищ

DiBify предоставляет абстрактные хранилища для разных бэкендов:

#### MongoDB (пакет `dibify/storage-mongodb`)

```php
use DiBify\Storage\MongoDB\MongoStorage;

abstract class MongoStorage implements StorageInterface
{
    abstract protected function scope(): ?string;           // ID тенанта
    abstract protected function scopeKey(): string;         // Поле для скоупа (напр. "company")
    abstract protected function getCollection(): Collection; // MongoDB-коллекция

    // Переопределяемые методы для настройки типов полей:
    protected function defaults(): array;    // Значения по умолчанию
    protected function dates(): array;       // Поля-даты (timestamp ↔ UTCDateTime)
    protected function uuids(): array;       // UUID-поля (string ↔ Binary)
    protected function pools(): array;       // Pool-поля
    protected function references(): array;  // Reference-поля
    protected function ignore(): array;      // Игнорируемые поля
}
```

Пример реализации:

```php
class UserMongoStorage extends MongoStorage
{
    public function __construct(Client $client, Database $database)
    {
        parent::__construct($client, $database);
    }

    protected function scope(): ?string
    {
        return ModelManager::getScope();
    }

    protected function scopeKey(): string
    {
        return 'company';
    }

    protected function getCollection(): Collection
    {
        return $this->database->selectCollection('users');
    }

    protected function dates(): array
    {
        return ['createdAt', 'updatedAt'];
    }

    protected function references(): array
    {
        return ['role' => Role::class];
    }

    // Кастомные запросы для этой модели
    public function findByEmail(string $email): ?StorageData
    {
        return $this->findOneByFilter(['email' => $email]);
    }
}
```

MongoDB-хранилище автоматически:
- Формирует составной `_id` из `scope` + `id`
- Преобразует даты (timestamp ↔ `UTCDateTime`)
- Преобразует UUID (строка ↔ `Binary`)
- Фильтрует все запросы по текущему `scope`

#### Redis (пакет `dibify/storage-redis`)

Два варианта:

**RedisStorage** — каждый документ в отдельном ключе:
```php
use DiBify\Storage\Redis\RedisStorage;

abstract class RedisStorage implements StorageInterface
{
    abstract protected function keyPrefix(): string;  // Префикс ключа
    abstract protected function scope(): ?string;     // Скоуп
    protected function ttl(): ?int;                   // TTL в секундах (null = бессрочно)
}
// Ключ: "keyPrefix:scope:id" → JSON-данные
```

**RedisHashStorage** — все документы в одном хеше:
```php
use DiBify\Storage\Redis\RedisHashStorage;

abstract class RedisHashStorage implements StorageInterface
{
    abstract protected function keyPrefix(): string;
    abstract protected function scope(): ?string;
}
// Ключ: "keyPrefix:scope" → Hash { id1: JSON, id2: JSON, ... }
```

### Собственные хранилища

Можно реализовать `StorageInterface` для любого бэкенда (Elasticsearch, Clickhouse, файлы и т.д.):

```php
class UserElasticStorage implements StorageInterface
{
    public function findById(string $id): ?StorageData { /* ... */ }
    public function findByIds($ids): array { /* ... */ }
    public function insert(StorageData $data, array $options = []): void { /* ... */ }
    public function update(StorageData $data, array $options = []): void { /* ... */ }
    public function delete(string $id, array $options = []): void { /* ... */ }
    public function scope(): ?string { /* ... */ }
    public function freeUpMemory(): void { /* ... */ }

    // Кастомные методы
    public function searchByFullText(string $query): array { /* ... */ }
}
```

---

## 9. Replicator — репликация данных

Replicator координирует запись данных в несколько хранилищ одновременно.

### ReplicatorInterface

```php
namespace DiBify\DiBify\Replicator;

interface ReplicatorInterface extends FreeUpMemoryInterface
{
    public function getPrimary(): StorageInterface;
    public function getSecondaryByName(string $name): StorageInterface;
    public function getSecondaries(): array;

    public function insert(StorageData $data, Transaction $transaction): void;
    public function update(StorageData $data, Transaction $transaction): void;
    public function delete(string $id, Transaction $transaction): void;

    public function onBeforeCommit(Transaction $transaction): void;
    public function onAfterCommit(Transaction $transaction): void;
}
```

### DirectReplicator

Встроенная реализация — синхронно записывает во все хранилища:

```php
use DiBify\DiBify\Replicator\DirectReplicator;

$replicator = new DirectReplicator(
    primary: $mongoStorage,
    secondaries: [
        'redis' => $redisStorage,
        'elastic' => $elasticStorage,
    ]
);
```

При вызове `insert()`, `update()` или `delete()`:
1. Операция выполняется на **primary**
2. Затем на каждом **secondary** по очереди

### Доступ к хранилищам через Replicator

```php
// Primary — основное хранилище (для чтения и записи)
$primary = $replicator->getPrimary();

// Secondary — дополнительные хранилища (для специализированных запросов)
$elastic = $replicator->getSecondaryByName('elastic');
$redis = $replicator->getSecondaryByName('redis');
```

### Когда нужны несколько хранилищ

| Primary (MongoDB) | Secondary (Redis) | Secondary (Elasticsearch) |
|---|---|---|
| Основной источник истины | Быстрый кеш для часто запрашиваемых данных | Полнотекстовый поиск, сложная фильтрация |
| CRUD-операции | Lookup по ID | Аналитические запросы |

---

## 10. Repository — репозиторий

Repository — это уровень доступа к данным для конкретного типа модели. Реализует паттерн **Identity Map**: каждая модель загружается только один раз и переиспользуется.

### Базовый класс

```php
namespace DiBify\DiBify\Repository;

abstract class Repository
{
    protected array $registered = [];  // Identity Map
    protected ReplicatorInterface $replicator;

    public function __construct(ReplicatorInterface $replicator);

    // Поиск
    public function findById($id): ?ModelInterface;
    public function findByIds($ids): array;

    // Обновление из БД
    public function refresh(ModelInterface ...$models): SplObjectStorage;

    // Сохранение/удаление
    public function commit(Transaction $transaction): void;

    // Очистка кеша
    public function freeUpMemory(): void;

    // Абстрактные — реализуются для каждой модели
    abstract protected function getMapper(): MapperInterface;
    abstract public function classes(): array;
}
```

### Создание репозитория

```php
class UserRepo extends Repository
{
    protected ?UserMapper $mapper = null;

    protected function getMapper(): MapperInterface
    {
        if ($this->mapper === null) {
            $this->mapper = new UserMapper();
        }
        return $this->mapper;
    }

    public function classes(): array
    {
        return [User::class];
    }

    // Кастомные методы поиска
    public function findByEmail(string $email): ?User
    {
        /** @var UserMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        $data = $storage->findByEmail($email);

        if ($data === null) {
            return null;
        }

        return $this->populateOne($data);
    }

    public function findAll(): array
    {
        /** @var UserMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        $dataArray = $storage->findAll();
        return $this->populateMany($dataArray);
    }
}
```

### Один репозиторий — несколько моделей

Если несколько моделей хранятся в одной коллекции:

```php
class RoleRepo extends Repository
{
    public function classes(): array
    {
        return [Role::class, AdminRole::class]; // Оба типа обслуживаются этим репозиторием
    }

    protected function getMapper(): MapperInterface
    {
        // UnionMapper различает типы при десериализации
        return new UnionMapper([
            new UnionRule($adminRoleMapper, /* ... */),
            new UnionRule($roleMapper, /* ... */),
        ]);
    }
}
```

### Identity Map

Repository автоматически кеширует загруженные модели:

```php
$user1 = $repo->findById('42');
$user2 = $repo->findById('42');
$user1 === $user2; // true — один и тот же объект из кеша
```

Это гарантирует, что изменения модели видны во всех местах, где на неё ссылаются.

### Методы populateOne / populateMany

Защищённые методы для преобразования `StorageData` в модели с автоматической регистрацией в Identity Map:

```php
// В кастомном методе репозитория
public function findActive(): array
{
    $storage = $this->replicator->getPrimary();
    $dataArray = $storage->findByFilter(['isActive' => true]);
    return $this->populateMany($dataArray); // Десериализует и регистрирует
}
```

### Обновление модели из БД

```php
// Перезагрузить одну модель
$freshUser = $modelManager->refreshOne($user);

// Перезагрузить несколько моделей
$changes = $modelManager->refreshMany($user1, $user2);
// $changes[$user1] — обновлённая версия $user1 (или null, если удалена)
```

---

## 11. Pool — накопительные операции

Pool — это паттерн для безопасного изменения числовых значений в условиях конкурентного доступа. Вместо перезаписи текущего значения, изменения накапливаются в «пуле» и применяются атомарно при коммите.

### Зачем нужен Pool

Проблема без Pool:
```
Процесс A: читает баланс = 100
Процесс B: читает баланс = 100
Процесс A: записывает баланс = 100 + 50 = 150
Процесс B: записывает баланс = 100 - 30 = 70  ← ПОТЕРЯ данных процесса A!
```

С Pool:
```
Процесс A: добавляет в pool +50, записывает: current=100, pool=+50
Процесс B: добавляет в pool -30, записывает: current=100, pool=-30
Хранилище применяет: 100 + 50 = 150, затем 150 - 30 = 120  ← КОРРЕКТНО
```

### PoolInterface

```php
namespace DiBify\DiBify\Pool;

interface PoolInterface
{
    public function getCurrent();   // Текущее (зафиксированное) значение
    public function getPool();      // Накопленные изменения
    public function getResult();    // current + pool
    public function add($value): void;
    public function subtract($value): void;
    public function merge(): void;  // Применить pool к current
}
```

### Реализации

```php
use DiBify\DiBify\Pool\IntPool;
use DiBify\DiBify\Pool\FloatPool;

// Целочисленный пул (баланс, количество на складе и т.д.)
$balance = new IntPool(1000); // current=1000, pool=0
$balance->add(500);           // current=1000, pool=500
$balance->subtract(200);      // current=1000, pool=300
$balance->getResult();        // 1300
$balance->merge();            // current=1300, pool=0

// Дробный пул (рейтинг, вес и т.д.)
$rating = new FloatPool(4.5);
$rating->add(0.3);
$rating->getResult(); // 4.8
```

### Pool в модели

```php
class Wallet implements ModelInterface
{
    protected Id $id;
    protected IntPool $balance;

    public function __construct(int $initialBalance)
    {
        $this->id = new Id();
        $this->balance = new IntPool($initialBalance);
    }

    public function deposit(int $amount): void
    {
        $this->balance->add($amount);
    }

    public function withdraw(int $amount): void
    {
        if ($this->balance->getResult() < $amount) {
            throw new InsufficientFundsException();
        }
        $this->balance->subtract($amount);
    }

    public function getBalance(): int
    {
        return $this->balance->getResult();
    }

    // ...
}
```

### Pool в маппере

```php
class WalletMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(Wallet::class, [
            'id' => IdMapper::getInstance(),
            'balance' => new PoolMapper(IntPool::class, IntMapper::getInstance()),
        ]);
    }
}
```

`PoolMapper` сериализует в формат `['current' => 1000, 'pool' => 300]`.

> **Важно:** `PoolMapper::merge()` вызывается автоматически при успешном коммите (`ModelManager::commit()`), объединяя `pool` с `current` для всех пулов, задействованных в транзакции.

---

## 12. Locker — блокировки моделей

Locker обеспечивает контроль конкурентного доступа к моделям. Предотвращает одновременное изменение одной модели несколькими процессами.

### LockerInterface

```php
namespace DiBify\DiBify\Locker;

interface LockerInterface
{
    public function lock(ModelInterface $model, Lock $lock, ?Throwable $throwable = null): bool;
    public function unlock(ModelInterface $model, Lock $lock, ?Throwable $throwable = null): bool;
    public function passLock(ModelInterface $model, Lock $currentLock, Lock $lock, ?Throwable $throwable = null): bool;
    public function waitForLock(array $models, int $waitTimeout, Lock $lock, ?Throwable $throwable = null): bool;
    public function isLockedFor(ModelInterface|Reference $model, Lock $lock): bool;
    public function getLock(ModelInterface|Reference $model): ?Lock;
    public function getDefaultTimeout(): int;
}
```

### Lock — объект блокировки

```php
use DiBify\DiBify\Locker\Lock\Lock;

$lock = new Lock(
    locker: $currentUser,      // Кто блокирует (ModelInterface или Reference)
    identity: 'edit-profile',  // Уникальный идентификатор операции (необязательно)
    timeout: 30                // Время жизни блокировки в секундах (необязательно)
);
```

### ServiceLock — системная блокировка

`ServiceLock` — специальный подтип `Lock`, который автоматически снимается после коммита:

```php
use DiBify\DiBify\Locker\Lock\ServiceLock;

$lock = new ServiceLock($currentUser, 'order-processing', 10);

// После ModelManager::commit($transaction, $lock) —
// блокировка автоматически снимется
```

### Redis-реализация (пакет `dibify/locker-redis`)

```php
use DiBify\Locker\Redis\Locker;

$locker = new Locker(
    redis: $redis,
    keyPrefix: 'Locker:',
    defaultTimeout: 5,   // Секунды по умолчанию
    maxTimeout: 60        // Максимальный таймаут
);
```

### Примеры использования

#### Блокировка модели

```php
$lock = new Lock($currentUser, 'editing', 30);

if ($locker->lock($shop, $lock)) {
    try {
        $shop->setName('Новое название');
        $modelManager->commit(new Transaction([$shop]));
    } finally {
        $locker->unlock($shop, $lock);
    }
} else {
    echo "Магазин редактируется другим пользователем";
}
```

#### Блокировка с исключением

```php
// Если не удалось заблокировать — выбросится исключение
$locker->lock($shop, $lock, new LockedModelException('Магазин занят'));
```

#### Ожидание блокировки

```php
// Ждать до 10 секунд, пока модель освободится
$locked = $locker->waitForLock(
    models: [$shop],
    waitTimeout: 10,
    lock: $lock,
    throwable: new LockedModelException('Не удалось заблокировать')
);
// Внутри — опрос каждую секунду
```

#### Передача блокировки

```php
// Передать блокировку от одного владельца другому
$currentLock = new Lock($user1, 'processing', 30);
$newLock = new Lock($user2, 'processing', 30);

$locker->passLock($shop, $currentLock, $newLock);
```

#### Автоматическая блокировка при коммите

При передаче `Lock` в `ModelManager::commit()` все модели транзакции автоматически блокируются перед записью:

```php
$lock = new ServiceLock($currentUser, 'save-order', 10);

$transaction = new Transaction([$shop, $shopOwner]);
$modelManager->commit($transaction, $lock);
// 1. Все модели блокируются
// 2. Данные сохраняются
// 3. ServiceLock автоматически снимается
```

---

## 13. Transaction — транзакции

`Transaction` группирует изменения моделей для атомарного сохранения. Это реализация паттерна **Unit of Work**.

### Создание транзакции

```php
use DiBify\DiBify\Manager\Transaction;

// Модели для сохранения
$transaction = new Transaction([$user, $shop]);

// Модели для удаления
$transaction = new Transaction(deleted: [$oldShop]);

// Смешанная транзакция
$transaction = new Transaction(
    persisted: [$user, $newShop],
    deleted: [$oldShop]
);
```

### Добавление моделей после создания

```php
$transaction = new Transaction();

$transaction->persists($user);           // Добавить на сохранение
$transaction->persists($shop1, $shop2);  // Можно несколько сразу

$transaction->delete($oldShop);          // Добавить на удаление
```

> **Примечание:** Если модель добавляется на сохранение, она автоматически удаляется из списка на удаление, и наоборот.

### Получение моделей

```php
// Все модели (и сохраняемые, и удаляемые)
$all = $transaction->getModels();

// Только сохраняемые
$persisted = $transaction->getPersisted();

// Сохраняемые определённого типа
$users = $transaction->getPersisted(User::class);

// Одна модель определённого типа
$user = $transaction->getPersistedOne(User::class);

// Только удаляемые
$deleted = $transaction->getDeleted();
$deletedShop = $transaction->getDeletedOne(Shop::class);
```

### Метаданные транзакции

Метаданные позволяют передавать дополнительную информацию через цепочку обработки:

```php
$transaction->setMetadata('disable_logs', true);
$transaction->setMetadata('initiator', 'cron-job');

$value = $transaction->getMetadata('disable_logs'); // true
```

### Обработчики событий

```php
use DiBify\DiBify\Manager\TransactionEvent;

$transaction->addEventHandler(TransactionEvent::BEFORE_COMMIT, function (Transaction $t) {
    // Вызывается перед записью в хранилище
    echo "Сохраняем " . count($t->getPersisted()) . " моделей";
});

$transaction->addEventHandler(TransactionEvent::AFTER_COMMIT, function (Transaction $t) {
    // Вызывается после успешного сохранения
    $user = $t->getPersistedOne(User::class);
    sendWelcomeEmail($user);
});

$transaction->addEventHandler(TransactionEvent::COMMIT_EXCEPTION, function (Transaction $t) {
    // Вызывается при ошибке
    logError("Ошибка сохранения транзакции " . $t->id());
});
```

> **Важно:** Обработчики очищаются после срабатывания, поэтому они выполнятся только один раз.

### Связи между моделями

Статические методы `Transaction` позволяют декларативно связать жизненный цикл моделей:

#### persistsWith — сохранять вместе

```php
// Когда $shop добавляется на сохранение — $shopSettings тоже автоматически добавится
Transaction::persistsWith($shop, $shopSettings);

$transaction = new Transaction();
$transaction->persists($shop); // $shopSettings тоже добавится
```

#### deleteWith — удалять вместе

```php
// Когда $shop удаляется — $shopSettings тоже удалится
Transaction::deleteWith($shop, $shopSettings);

$transaction = new Transaction();
$transaction->delete($shop); // $shopSettings тоже удалится
```

#### withDeletePersists — при удалении одной, сохранить другую

```php
// Когда $membership удаляется — $user (с обновлёнными данными) сохраняется
Transaction::withDeletePersists($membership, $user);
```

#### withPersistsDelete — при сохранении одной, удалить другую

```php
// Когда $newMembership сохраняется — $oldMembership удаляется
Transaction::withPersistsDelete($newMembership, $oldMembership);
```

### RetryPolicy — политика повторных попыток

```php
use DiBify\DiBify\Manager\RetryPolicy;

$retryPolicy = new RetryPolicy(
    isRetryRequiredCallable: fn(Transaction $t, Throwable $e, int $attempt) => $attempt <= 3,
    beforeRetryCallable: fn(Transaction $t, Throwable $e, int $attempt, ModelManager $mm) => true,
    retries: 3,
    delay: 500000  // 0.5 секунды между попытками
);

// Для конкретной транзакции
$transaction = new Transaction([$user], retryPolicy: $retryPolicy);

// Или глобально для ModelManager
$modelManager->setRetryPolicy($retryPolicy);
```

---

## 14. ConfigManager — реестр конфигурации

`ConfigManager` — центральный реестр, связывающий классы моделей с их репозиториями и генераторами ID.

```php
use DiBify\DiBify\Manager\ConfigManager;

$config = new ConfigManager();

// Связать модель → репозиторий + генератор ID
$config->add(
    $userRepo,                          // Repository или callable
    [User::class],                      // Классы моделей
    $redisIdGenerator                   // Генератор ID
);

// Несколько моделей в одном репозитории
$config->add(
    $roleRepo,
    [Role::class, AdminRole::class],
    $redisIdGenerator
);

// Ленивая инициализация репозитория (через callable)
$config->add(
    fn() => $container->get(ShopRepo::class),
    [Shop::class],
    $redisIdGenerator
);
```

### Получение из ConfigManager

```php
// По модели, Reference или алиасу/классу
$repo = $config->getRepository($user);              // по модели
$repo = $config->getRepository($userReference);      // по Reference
$repo = $config->getRepository(User::class);         // по классу
$repo = $config->getRepository('user');              // по алиасу

$generator = $config->getIdGenerator(User::class);

// Все зарегистрированные классы моделей
$classes = $config->getModelClasses();
```

> **Примечание:** Если репозиторий зарегистрирован как callable, он будет инициализирован при первом обращении (ленивая загрузка).

---

## 15. ModelManager — центральный менеджер

`ModelManager` — точка входа для всех операций ORM. Это синглтон, координирующий работу всех компонентов.

### Создание

```php
use DiBify\DiBify\Manager\ModelManager;

$modelManager = ModelManager::construct(
    configManager: $configManager,
    locker: $locker,
    onBeforeCommit: function (Transaction $transaction) {
        // Глобальный хук перед коммитом (например, начать DB-транзакцию)
    },
    onAfterCommit: function (Transaction $transaction) {
        // Глобальный хук после коммита (например, закоммитить DB-транзакцию)
    },
    onCommitException: function (Transaction $transaction, Throwable $e) {
        // Обработка ошибки (например, откатить DB-транзакцию)
    },
    retryPolicy: $retryPolicy,  // Необязательно
);
```

### Основные операции

#### Сохранение моделей

```php
$user = new User('Иван', 'ivan@example.com');
$shop = new Shop('Магазин', $user);

$transaction = new Transaction([$user, $shop]);
$modelManager->commit($transaction);

// После коммита: $user->id()->isAssigned() === true
```

#### Сохранение с блокировкой

```php
$lock = new ServiceLock($currentUser, 'update', 10);
$modelManager->commit(new Transaction([$shop]), $lock);
```

#### Удаление моделей

```php
$modelManager->commit(new Transaction(deleted: [$shop]));
```

#### Поиск моделей

```php
// Через репозиторий
$userRepo = $modelManager->getRepository(User::class);
$user = $userRepo->findById('42');
$users = $userRepo->findByIds(['1', '2', '3']);

// Через Reference (статический метод)
$user = ModelManager::findByReference($userRef);

// Несколько ссылок за раз
$models = ModelManager::findByReferences($ref1, $ref2, $ref3);

// Универсальный поиск по любому типу идентификатора
$user = $modelManager->findByAnyTypeId('42', User::class);
$user = $modelManager->findByAnyTypeId($reference);
$user = $modelManager->findByAnyTypeId($userObject); // вернёт сам объект
```

#### Обновление из БД

```php
// Перезагрузить одну модель
$freshUser = $modelManager->refreshOne($user);

// Перезагрузить несколько
$changes = $modelManager->refreshMany($user1, $user2, $shop);
$freshUser1 = $changes[$user1]; // обновлённая версия (или null если удалена)
```

#### Управление памятью

```php
// Очистить все кеши (Identity Map, References, Pools)
$modelManager->freeUpMemory();
```

#### Доступ к Locker

```php
$locker = $modelManager->getLocker();
$locker->lock($shop, $lock);
```

---

## 16. Жизненный цикл сохранения модели

При вызове `ModelManager::commit($transaction, $lock)` происходит следующее:

```
1. Присвоение ID
   Для каждой сохраняемой модели вызывается IdGenerator.
   Новые модели получают свой уникальный идентификатор.

2. Model::onBeforeCommit()
   Для моделей, реализующих ModelBeforeCommitEventInterface,
   вызывается onBeforeCommit() — валидация, нормализация данных.

3. Replicator::onBeforeCommit()
   Хуки репликаторов для подготовки хранилищ.

4. Transaction::triggerEvent(BEFORE_COMMIT)
   Срабатывают пользовательские обработчики BEFORE_COMMIT.

5. onBeforeCommit callback (глобальный)
   Вызывается глобальный хук ModelManager.
   Типичное использование: начало DB-транзакции.

6. ──── Начало коммита (commitInternal) ────

   6a. Блокировка моделей
       Все модели транзакции блокируются через Locker.

   6b. Repository::commit()
       Для каждого типа модели:
       - Сохраняемые: Mapper.serialize() → Replicator.insert/update()
       - Удаляемые: Replicator.delete()

   6c. onAfterCommit callback (глобальный)
       Типичное использование: коммит DB-транзакции.

   6d. Replicator::onAfterCommit()
       Хуки репликаторов после записи.

   6e. Model::onAfterCommit()
       Для моделей, реализующих ModelAfterCommitEventInterface.

   6f. Transaction::triggerEvent(AFTER_COMMIT)
       Пользовательские обработчики AFTER_COMMIT.

   6g. PoolMapper::merge()
       Все пулы, участвовавшие в транзакции, объединяют pool с current.

   6h. Снятие ServiceLock
       Если передан ServiceLock — блокировки снимаются.

7. ──── Если ошибка ────

   7a. onCommitException callback (глобальный)
       Типичное использование: откат DB-транзакции.

   7b. Transaction::triggerEvent(COMMIT_EXCEPTION)

   7c. RetryPolicy (если настроен)
       Повторная попытка коммита по заданной стратегии.

   7d. Снятие ServiceLock при исчерпании попыток.
```

---

## 17. Scope — мультитенантность

DiBify поддерживает мультитенантность через механизм `scope`. Scope — это идентификатор «области видимости» (например, ID компании), который автоматически добавляется ко всем запросам.

### Установка scope

```php
// Установить текущий scope (обычно при обработке запроса)
ModelManager::setScope('company_42');

// Получить текущий scope
$scope = ModelManager::getScope(); // 'company_42'

// Сбросить scope
ModelManager::setScope(null);
```

### Как scope работает

1. При создании `StorageData` scope подставляется автоматически:
   ```php
   $data = new StorageData('1', ['name' => 'Иван']);
   $data->scope; // 'company_42' — взят из ModelManager::getScope()
   ```

2. Хранилища (Storage) используют scope для изоляции данных:
   - В MongoDB: составной `_id = {company: 'company_42', id: '1'}`
   - В Redis: ключ `prefix:company_42:1`

3. Все запросы автоматически фильтруются по текущему scope.

> **Важно:** При смене scope автоматически очищается весь кеш (`freeUpMemory()`), т.к. данные из старого scope более не актуальны.

---

## 18. Дополнительные пакеты

### dibify/id-generator-redis

Последовательная генерация числовых ID через Redis.

```php
use DiBify\IdGenerator\Redis\IdGenerator;

$generator = new IdGenerator($redis, 'IdCounter');
// Для User: 1, 2, 3, ...
// Для Shop: 1, 2, 3, ... (независимые счётчики)
```

Метод `setCounterValue()` позволяет восстановить счётчик (например, после конфликта дубликатов ключей).

### dibify/locker-redis

Распределённые блокировки через Redis.

```php
use DiBify\Locker\Redis\Locker;

$locker = new Locker($redis, 'Lock:', defaultTimeout: 5, maxTimeout: 60);
```

Использует Redis `SET NX` для атомарного захвата блокировки с TTL.

### dibify/storage-mongodb

MongoDB-хранилище с поддержкой scope, автоматическим преобразованием дат и UUID.

### dibify/storage-redis

Redis-хранилище в двух вариантах: строковые ключи (с TTL) и хеши.

### dibify/migrations

Система миграций с Symfony Console:

```bash
# Создать миграцию
php console migration:new add_users_index

# Запустить миграции
php console migration:run
```

Миграции хранятся в `migrations/`, прогресс отслеживается в `_applied.json`.

---

## 19. Полный пример приложения

Рассмотрим полный пример с моделями `User`, `Role` и `Shop`.

### Модели

```php
// Role
class Role implements ModelInterface
{
    protected Id $id;
    protected string $name;
    protected array $permissions;

    public function __construct(string $name, array $permissions)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->permissions = $permissions;
    }

    public function id(): Id { return $this->id; }
    public static function getModelAlias(): string { return 'role'; }

    public function getName(): string { return $this->name; }
    public function getPermissions(): array { return $this->permissions; }
}

// User
class User implements ModelInterface, ModelBeforeCommitEventInterface
{
    protected Id $id;
    protected string $name;
    protected string $email;
    protected Reference $role;
    protected IntPool $balance;
    protected DateTimeImmutable $createdAt;

    public function __construct(string $name, string $email, Role $role)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->email = $email;
        $this->role = Reference::to($role);
        $this->balance = new IntPool(0);
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): Id { return $this->id; }
    public static function getModelAlias(): string { return 'user'; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): Role { return $this->role->getModel(); }
    public function getRoleReference(): Reference { return $this->role; }

    public function deposit(int $amount): void { $this->balance->add($amount); }
    public function getBalance(): int { return $this->balance->getResult(); }

    public function onBeforeCommit(): void
    {
        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));
    }
}

// Shop
class Shop implements ModelInterface
{
    protected Id $id;
    protected string $name;
    protected Reference $owner;
    protected ?string $description;
    protected bool $isActive;
    protected DateTimeImmutable $createdAt;

    public function __construct(string $name, User $owner, ?string $description = null)
    {
        $this->id = new Id();
        $this->name = $name;
        $this->owner = Reference::to($owner);
        $this->description = $description;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): Id { return $this->id; }
    public static function getModelAlias(): string { return 'shop'; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getOwner(): User { return $this->owner->getModel(); }
    public function isActive(): bool { return $this->isActive; }
    public function deactivate(): void { $this->isActive = false; }
}
```

### Мапперы

```php
class RoleMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(Role::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'permissions' => new ArrayMapper(StringMapper::getInstance()),
        ]);
    }
}

class UserMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(User::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'email' => StringMapper::getInstance(),
            'role' => ReferenceMapper::getInstanceLazy(),
            'balance' => new PoolMapper(IntPool::class, IntMapper::getInstance()),
            'createdAt' => DateTimeMapper::getInstanceImmutable(),
        ]);
    }
}

class ShopMapper extends ModelMapper
{
    public function __construct()
    {
        parent::__construct(Shop::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'owner' => ReferenceMapper::getInstanceLazy(),
            'description' => new NullOrMapper(StringMapper::getInstance()),
            'isActive' => BoolMapper::getInstance(),
            'createdAt' => DateTimeMapper::getInstanceImmutable(),
        ]);
    }
}
```

### Репозитории

```php
class RoleRepo extends Repository
{
    protected function getMapper(): MapperInterface
    {
        return new RoleMapper();
    }

    public function classes(): array
    {
        return [Role::class];
    }
}

class UserRepo extends Repository
{
    protected function getMapper(): MapperInterface
    {
        return new UserMapper();
    }

    public function classes(): array
    {
        return [User::class];
    }

    public function findByEmail(string $email): ?User
    {
        /** @var UserMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        $data = $storage->findByEmail($email);
        return $data ? $this->populateOne($data) : null;
    }
}

class ShopRepo extends Repository
{
    protected function getMapper(): MapperInterface
    {
        return new ShopMapper();
    }

    public function classes(): array
    {
        return [Shop::class];
    }

    public function findByOwner(User $owner): array
    {
        /** @var ShopMongoStorage $storage */
        $storage = $this->replicator->getPrimary();
        $dataArray = $storage->findByOwner((string) $owner->id());
        return $this->populateMany($dataArray);
    }
}
```

### Конфигурация

```php
use DiBify\DiBify\Manager\ConfigManager;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Replicator\DirectReplicator;
use DiBify\IdGenerator\Redis\IdGenerator;
use DiBify\Locker\Redis\Locker;

// Генератор ID
$idGenerator = new IdGenerator($redis);

// Хранилища
$roleMongoStorage = new RoleMongoStorage($mongoClient, $mongoDb);
$userMongoStorage = new UserMongoStorage($mongoClient, $mongoDb);
$shopMongoStorage = new ShopMongoStorage($mongoClient, $mongoDb);
$userRedisStorage = new UserRedisStorage($redis);

// Репозитории с репликаторами
$roleRepo = new RoleRepo(new DirectReplicator($roleMongoStorage));
$userRepo = new UserRepo(new DirectReplicator($userMongoStorage, ['redis' => $userRedisStorage]));
$shopRepo = new ShopRepo(new DirectReplicator($shopMongoStorage));

// ConfigManager — связываем модели с репозиториями
$configManager = new ConfigManager();
$configManager->add($roleRepo, [Role::class], $idGenerator);
$configManager->add($userRepo, [User::class], $idGenerator);
$configManager->add($shopRepo, [Shop::class], $idGenerator);

// Locker
$locker = new Locker($redis, 'Lock:', defaultTimeout: 5, maxTimeout: 60);

// ModelManager
$modelManager = ModelManager::construct(
    configManager: $configManager,
    locker: $locker,
    onBeforeCommit: function (Transaction $transaction) use ($mongoClient) {
        $session = $mongoClient->startSession();
        $session->startTransaction();
        $transaction->setMetadata('session', $session);
    },
    onAfterCommit: function (Transaction $transaction) {
        $session = $transaction->getMetadata('session');
        $session?->commitTransaction();
    },
    onCommitException: function (Transaction $transaction, Throwable $e) {
        $session = $transaction->getMetadata('session');
        $session?->abortTransaction();
    },
);
```

### Использование

```php
// Установить scope (компания)
ModelManager::setScope('company_1');

// Создание
$role = new Role('Администратор', ['users.manage', 'shops.manage']);
$user = new User('Иван Петров', 'ivan@example.com', $role);
$shop = new Shop('Книжный магазин', $user, 'Лучшие книги города');

// Связать: при сохранении магазина — сохранить и пользователя
Transaction::persistsWith($shop, $user);

// Сохранить
$transaction = new Transaction([$role, $shop]);
// $user тоже сохранится благодаря persistsWith
$modelManager->commit($transaction);

echo $role->id()->get(); // '1'
echo $user->id()->get(); // '1'
echo $shop->id()->get(); // '1'

// Поиск
$userRepo = $modelManager->getRepository(User::class);
$foundUser = $userRepo->findById('1');
$foundUser === $user; // true — Identity Map

// Обновление
$lock = new ServiceLock($user, 'update-shop', 10);
$shop->setName('Новое название');
$modelManager->commit(new Transaction([$shop]), $lock);

// Начисление баланса (Pool)
$user->deposit(1000);
$modelManager->commit(new Transaction([$user]));
echo $user->getBalance(); // 1000

// Удаление
$modelManager->commit(new Transaction(deleted: [$shop]));

// Предзагрузка ссылок
$shops = $shopRepo->findByOwner($user);
Reference::preload(...array_map(fn($s) => $s->getOwnerReference(), $shops));
foreach ($shops as $shop) {
    echo $shop->getOwner()->getName(); // Без N+1
}

// Очистка памяти (для долгоживущих процессов)
$modelManager->freeUpMemory();
```
