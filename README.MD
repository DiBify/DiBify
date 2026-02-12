# DiBify ORM

DiBify is a PHP ORM library built on the **Repository**, **Identity Map**, **Unit of Work**, and **Data Mapper** patterns.

## Philosophy and Core Idea

The primary goal of DiBify is to enable **transparent application operation with multiple different databases** simultaneously. Business logic works with models and repositories without knowing or caring which specific databases store the data — MongoDB, Redis, Elasticsearch, Clickhouse, or any others.

Thanks to the **Storage** layer, which is the sole point of interaction with a specific database engine, you can at any time:

- **Add a new database** — for example, start replicating data to Elasticsearch for full-text search by simply writing a new Storage and plugging it in as a secondary in the Replicator;
- **Replace one database with another** — for example, migrate from MongoDB to PostgreSQL by rewriting only the Storage layer, with zero changes to models, services, or business logic;
- **Use different databases for different tasks** — MongoDB as the primary store, Redis for fast caching, Elasticsearch for search, Clickhouse for analytics — all transparent to the rest of the code.

DiBify is designed with **SaaS applications** in mind: built-in multi-tenancy support via the **Scope** mechanism automatically isolates data of different clients (companies, accounts) at the storage level, without cluttering business logic with filters and checks.

---

## Table of Contents

1. [Architecture and Core Concepts](#1-architecture-and-core-concepts)
2. [Model — Data Model](#2-model--data-model)
3. [Id — Model Identifier](#3-id--model-identifier)
4. [IdGenerator — Identifier Generation](#4-idgenerator--identifier-generation)
5. [Reference — Cross-Model References](#5-reference--cross-model-references)
6. [Mapper — Serialization and Deserialization](#6-mapper--serialization-and-deserialization)
7. [StorageData — Storage Data Object](#7-storagedata--storage-data-object)
8. [Storage — Data Storage](#8-storage--data-storage)
9. [Replicator — Data Replication](#9-replicator--data-replication)
10. [Repository](#10-repository)
11. [Pool — Accumulative Operations](#11-pool--accumulative-operations)
12. [Locker — Model Locking](#12-locker--model-locking)
13. [Transaction](#13-transaction)
14. [ConfigManager — Configuration Registry](#14-configmanager--configuration-registry)
15. [ModelManager — Central Manager](#15-modelmanager--central-manager)
16. [Model Persistence Lifecycle](#16-model-persistence-lifecycle)
17. [Scope — Multi-Tenancy](#17-scope--multi-tenancy)
18. [Additional Packages](#18-additional-packages)
19. [Complete Application Example](#19-complete-application-example)

---

## 1. Architecture and Core Concepts

DiBify separates concerns across multiple layers:

```
┌─────────────────────────────────────────────────────────┐
│                     ModelManager                        │  Coordinates everything
│  (commit, refresh, findByReference, scope, locker)      │
├─────────────────────────────────────────────────────────┤
│                     ConfigManager                       │  Maps models to
│  (model → repository, model → ID generator)             │  repositories & generators
├─────────────────────────────────────────────────────────┤
│                     Transaction                         │  Groups changes for
│  (persisted, deleted, metadata, events)                 │  atomic commit
├──────────────┬──────────────────────────────────────────┤
│  Repository  │  Mapper         │  Locker               │
│  (Identity   │  (serialize/    │  (lock/unlock/         │
│   Map,       │   deserialize)  │   passLock/            │
│   CRUD)      │                 │   waitForLock)         │
├──────────────┴─────────────────┴───────────────────────┤
│                     Replicator                          │  Coordinates writes
│  (primary + secondaries)                                │  to multiple storages
├─────────────────────────────────────────────────────────┤
│                     Storage                             │  Direct database
│  (MongoDB, Redis, Elasticsearch, Clickhouse, ...)       │  interaction
└─────────────────────────────────────────────────────────┘
```

**Data flow on write:**

```
Model → Mapper.serialize() → StorageData → Replicator → Storage(s) → DB
```

**Data flow on read:**

```
DB → Storage → StorageData → Mapper.deserialize() → Model
```

---

## 2. Model — Data Model

### ModelInterface

Every model (entity) must implement `ModelInterface`:

```php
namespace DiBify\DiBify\Model;

interface ModelInterface
{
    public function id(): Id;
    public static function getModelAlias(): string;
}
```

- `id()` — returns the `Id` object (unique model identifier)
- `getModelAlias()` — returns a string alias for the model (e.g., `'user'`, `'role'`). Used for storing references (`Reference`) and internal routing

### Creating a Model

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
        $this->id = new Id();  // ID not yet assigned
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

> **Important:** When creating a model, `Id` is created without a value (`new Id()`). The actual identifier will be automatically assigned during commit via `IdGenerator`.

### Lifecycle Hooks

Models can implement additional interfaces to hook into the commit lifecycle:

```php
use DiBify\DiBify\Model\ModelBeforeCommitEventInterface;
use DiBify\DiBify\Model\ModelAfterCommitEventInterface;

class User implements ModelInterface, ModelBeforeCommitEventInterface, ModelAfterCommitEventInterface
{
    // ... fields and methods ...

    public function onBeforeCommit(): void
    {
        // Called before persisting to storage.
        // Use for validation, data normalization, etc.
        $this->name = trim($this->name);
    }

    public function onAfterCommit(): void
    {
        // Called after successful persistence.
        // Use for sending notifications, firing events, etc.
    }
}
```

---

## 3. Id — Model Identifier

`Id` is a value object representing the unique identifier of a model.

```php
namespace DiBify\DiBify\Id;

class Id implements JsonSerializable
{
    public function __construct(string|int $id = null);
    public function get(): ?string;           // Get the value
    public function isAssigned(): bool;       // Whether the ID is assigned
    public function assign(string $id): bool; // Assign (only once)
    public function isEqual(ModelInterface|Reference|Id|string|int $id): bool;
    public function __toString(): string;
}
```

### Id States

```php
// Unassigned — model just created
$id = new Id();
$id->isAssigned(); // false
$id->get();        // null

// Assigned — after passing through IdGenerator or when loaded from DB
$id = new Id('42');
$id->isAssigned(); // true
$id->get();        // '42'
(string) $id;      // '42'
```

> **Important:** The `assign()` method can only be called once. A second call returns `false`. This prevents accidental identifier changes.

---

## 4. IdGenerator — Identifier Generation

`IdGeneratorInterface` defines the contract for unique identifier generation:

```php
namespace DiBify\DiBify\Id;

interface IdGeneratorInterface
{
    public function __invoke(ModelInterface $model): Id;
}
```

The generator is invoked as a callable — it checks whether the model needs an ID, and if so, generates and assigns one.

### Built-in Generators

#### UuidGenerator

Generates UUID v4:

```php
use DiBify\DiBify\Id\UuidGenerator;

$uuid = UuidGenerator::generate();
// Example: "550e8400-e29b-41d4-a716-446655440000"
```

#### SortableUniqueIdGenerator

Generates sortable unique identifiers based on timestamps:

```php
use DiBify\DiBify\Id\SortableUniqueIdGenerator;

$id = SortableUniqueIdGenerator::generate();
// Example: "k5f3h7r9m2x1p4w6" — sortable by creation time
```

Useful for models where creation order matters (logs, events).

### Redis IdGenerator (package `dibify/id-generator-redis`)

Generates sequential numeric identifiers using Redis:

```php
use DiBify\IdGenerator\Redis\IdGenerator;

$generator = new IdGenerator($redis, 'RedisIdGenerator');
// User gets IDs: 1, 2, 3, ...
// Role gets IDs: 1, 2, 3, ... (independent sequence)
```

Each model type (determined by `getModelAlias()`) gets a separate counter. Increment is atomic thanks to Redis `HINCRBY`.

---

## 5. Reference — Cross-Model References

`Reference` is a lazy reference to a model. It allows storing relationships between models without loading the related model until access time.

### Creating References

```php
use DiBify\DiBify\Model\Reference;

// Create a reference from a model (model already loaded)
$userRef = Reference::to($user);

// Create a reference by alias and ID (model NOT loaded)
$userRef = Reference::create('user', '42');
$userRef = Reference::create(User::class, '42'); // or by class
```

### Using References

```php
// Get alias and ID
$userRef->getModelAlias(); // 'user'
$userRef->id();            // Id('42')

// Get the model itself (lazy loading)
$user = $userRef->getModel(); // Loads from DB on first access

// Check if reference points to a specific model
$userRef->isFor($user); // true/false
```

### References in Models

```php
class Shop implements ModelInterface
{
    protected Id $id;
    protected string $name;
    protected Reference $owner;      // Required reference to User
    protected ?Reference $manager;   // Optional reference

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

### Preloading

To avoid the N+1 problem, you can preload multiple references in a single query:

```php
// Collect references
Reference::preload($shop1->getOwnerReference(), $shop2->getOwnerReference(), $shop3->getOwnerReference());

// On first getModel() call on any of them — all are loaded at once
$owner1 = $shop1->getOwner(); // Single query loads all three owners
$owner2 = $shop2->getOwner(); // Already cached
$owner3 = $shop3->getOwner(); // Already cached
```

### Caching

`Reference` internally caches all created references. If you create a reference to the same alias + ID twice, the same object is returned:

```php
$ref1 = Reference::create('user', '42');
$ref2 = Reference::create('user', '42');
$ref1 === $ref2; // true — same object
```

### Serialization

```php
$ref = Reference::to($user);

// To JSON
$json = json_encode($ref);
// {"alias":"user","id":"42"}

// From array
$ref = Reference::fromArray(['alias' => 'user', 'id' => '42']);

// From JSON
$ref = Reference::fromJson('{"alias":"user","id":"42"}');
```

---

## 6. Mapper — Serialization and Deserialization

Mapper is responsible for converting objects to storage data and back. It is the key link between business objects and storage.

### MapperInterface

```php
namespace DiBify\DiBify\Mappers;

interface MapperInterface
{
    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     */
    public function serialize($complex);

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     */
    public function deserialize($data);
}
```

### Basic Mappers

| Mapper | Description | Example |
|--------|-------------|---------|
| `StringMapper` | Strings | `"hello"` ↔ `"hello"` |
| `IntMapper` | Integers | `42` ↔ `42` |
| `FloatMapper` | Floating point numbers | `3.14` ↔ `3.14` |
| `BoolMapper` | Booleans | `true` ↔ `true` |
| `DateTimeMapper` | Dates (mutable/immutable) | `DateTime` ↔ `1707820800` (timestamp) |
| `IdMapper` | Identifiers | `Id('42')` ↔ `'42'` |
| `EnumMapper` | PHP Enum (BackedEnum) | `Status::Active` ↔ `'active'` |

Basic mappers use the Singleton pattern:

```php
$mapper = StringMapper::getInstance();
$mapper = IntMapper::getInstance();
$mapper = DateTimeMapper::getInstanceImmutable(); // for DateTimeImmutable
$mapper = DateTimeMapper::getInstanceMutable();   // for DateTime
```

### Composite Mappers

#### ObjectMapper — Nested Object Mapping

```php
use DiBify\DiBify\Mappers\ObjectMapper;

// For an Address value object
$addressMapper = new ObjectMapper(Address::class, [
    'city' => StringMapper::getInstance(),
    'street' => StringMapper::getInstance(),
    'zip' => StringMapper::getInstance(),
]);

// Serialization:
// Address { city: "London", street: "Baker Street", zip: "NW1 6XE" }
// → ['city' => 'London', 'street' => 'Baker Street', 'zip' => 'NW1 6XE']
```

#### ModelMapper — Model Mapping

Extends `ObjectMapper` for models — automatically extracts `id` into a separate `StorageData` field:

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

On `serialize()`, returns `StorageData` with separate `id` and `body`:
```php
$data = $mapper->serialize($user);
// StorageData {
//   id: '42',
//   body: ['name' => 'John', 'email' => 'john@example.com', 'createdAt' => 1707820800]
// }
```

On `deserialize()`, accepts `StorageData` and reconstructs the model:
```php
$user = $mapper->deserialize($storageData); // → User
```

#### ReferenceMapper — Reference Mapping

```php
use DiBify\DiBify\Mappers\ReferenceMapper;

// Lazy loading (default) — model is loaded on access
$lazyMapper = ReferenceMapper::getInstanceLazy();

// Eager loading — model is loaded immediately during deserialization
$eagerMapper = ReferenceMapper::getInstanceEager();
```

Stored format: `['alias' => 'user', 'id' => '42']` ↔ `Reference`

#### NullOrMapper — Nullable Field Wrapper

```php
use DiBify\DiBify\Mappers\NullOrMapper;

// Field can be null or Reference
$mapper = new NullOrMapper(ReferenceMapper::getInstanceLazy());

$mapper->serialize(null);  // null
$mapper->serialize($ref);  // ['alias' => 'user', 'id' => '42']
```

#### ArrayMapper — Array Mapping

```php
use DiBify\DiBify\Mappers\ArrayMapper;

// Array of strings
$tagsMapper = new ArrayMapper(StringMapper::getInstance());

// Array of Address objects
$addressesMapper = new ArrayMapper($addressMapper);
```

#### ValueObjectMapper — Single-Property Value Object Mapping

```php
use DiBify\DiBify\Mappers\ValueObjectMapper;

// Email as a value object
$emailMapper = new ValueObjectMapper(Email::class, 'value', StringMapper::getInstance());
// Email { value: "user@example.com" } ↔ "user@example.com"
```

#### CallableMapper — Custom Logic

```php
use DiBify\DiBify\Mappers\CallableMapper;

$statusMapper = new CallableMapper(
    serialize: fn($status) => $status->value,
    deserialize: fn($data) => Status::from($data),
);
```

#### UnionMapper — Polymorphic Types

When a single field may contain objects of different types:

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

#### PoolMapper — Pool Mapping

```php
use DiBify\DiBify\Mappers\PoolMapper;

$balanceMapper = new PoolMapper(IntPool::class, IntMapper::getInstance());
// IntPool { current: 100, pool: 50 } ↔ ['current' => 100, 'pool' => 50]
```

### Complete Model Mapper Example

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

### Data Migration via Mapper

By overriding `deserialize()`, you can handle schema changes:

```php
class ShopMapper extends ModelMapper
{
    public function __construct() { /* ... */ }

    public function deserialize($data)
    {
        // Field was previously called 'title', now it's 'name'
        if (isset($data->body['title']) && !isset($data->body['name'])) {
            $data->body['name'] = $data->body['title'];
            unset($data->body['title']);
        }

        return parent::deserialize($data);
    }
}
```

---

## 7. StorageData — Storage Data Object

`StorageData` is a transport object between Mapper, Repository, and Storage. It carries the serialized model data.

```php
namespace DiBify\DiBify\Repository\Storage;

class StorageData implements JsonSerializable
{
    public ?string $scope = null;   // Scope (multi-tenancy)
    public string $id;              // Model identifier
    public array $body;             // Serialized model body
    public array $metadata = [];    // Additional metadata
}
```

### Construction

```php
// Automatically uses the current scope from ModelManager
$data = new StorageData('42', ['name' => 'John', 'email' => 'john@example.com']);

// With explicit scope
$data = new StorageData('42', ['name' => 'John'], 'company_1');

// From array/JSON
$data = StorageData::fromArray(['id' => '42', 'body' => [...], 'scope' => 'company_1']);
$data = StorageData::fromJson('{"id":"42","body":{...}}');
```

`StorageData` is created by the mapper during `serialize()` and passed to the mapper during `deserialize()`.

---

## 8. Storage — Data Storage

`StorageInterface` defines the contract for working with a specific storage backend:

```php
namespace DiBify\DiBify\Repository\Storage;

interface StorageInterface extends FreeUpMemoryInterface
{
    public function findById(string $id): ?StorageData;
    public function findByIds($ids): array;            // indexed by id
    public function insert(StorageData $data, array $options = []): void;
    public function update(StorageData $data, array $options = []): void;
    public function delete(string $id, array $options = []): void;
    public function scope(): ?string;
}
```

### Storage Implementations

DiBify provides abstract storage classes for different backends:

#### MongoDB (package `dibify/storage-mongodb`)

```php
use DiBify\Storage\MongoDB\MongoStorage;

abstract class MongoStorage implements StorageInterface
{
    abstract protected function scope(): ?string;           // Tenant ID
    abstract protected function scopeKey(): string;         // Scope field name (e.g. "company")
    abstract protected function getCollection(): Collection; // MongoDB collection

    // Override to configure field types:
    protected function defaults(): array;    // Default values
    protected function dates(): array;       // Date fields (timestamp ↔ UTCDateTime)
    protected function uuids(): array;       // UUID fields (string ↔ Binary)
    protected function pools(): array;       // Pool fields
    protected function references(): array;  // Reference fields
    protected function ignore(): array;      // Fields to ignore
}
```

Implementation example:

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

    // Custom queries for this model
    public function findByEmail(string $email): ?StorageData
    {
        return $this->findOneByFilter(['email' => $email]);
    }
}
```

MongoDB storage automatically:
- Forms a compound `_id` from `scope` + `id`
- Converts dates (timestamp ↔ `UTCDateTime`)
- Converts UUIDs (string ↔ `Binary`)
- Filters all queries by current `scope`

#### Redis (package `dibify/storage-redis`)

Two variants:

**RedisStorage** — each document in a separate key:
```php
use DiBify\Storage\Redis\RedisStorage;

abstract class RedisStorage implements StorageInterface
{
    abstract protected function keyPrefix(): string;  // Key prefix
    abstract protected function scope(): ?string;     // Scope
    protected function ttl(): ?int;                   // TTL in seconds (null = no expiry)
}
// Key: "keyPrefix:scope:id" → JSON data
```

**RedisHashStorage** — all documents in a single hash:
```php
use DiBify\Storage\Redis\RedisHashStorage;

abstract class RedisHashStorage implements StorageInterface
{
    abstract protected function keyPrefix(): string;
    abstract protected function scope(): ?string;
}
// Key: "keyPrefix:scope" → Hash { id1: JSON, id2: JSON, ... }
```

### Custom Storage

You can implement `StorageInterface` for any backend (Elasticsearch, Clickhouse, files, etc.):

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

    // Custom methods
    public function searchByFullText(string $query): array { /* ... */ }
}
```

---

## 9. Replicator — Data Replication

Replicator coordinates writing data to multiple storages simultaneously.

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

Built-in implementation — writes synchronously to all storages:

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

When `insert()`, `update()`, or `delete()` is called:
1. The operation is performed on the **primary**
2. Then on each **secondary** in order

### Accessing Storages via Replicator

```php
// Primary — main storage (for reads and writes)
$primary = $replicator->getPrimary();

// Secondary — additional storages (for specialized queries)
$elastic = $replicator->getSecondaryByName('elastic');
$redis = $replicator->getSecondaryByName('redis');
```

### When Multiple Storages Are Needed

| Primary (MongoDB) | Secondary (Redis) | Secondary (Elasticsearch) |
|---|---|---|
| Source of truth | Fast cache for frequently accessed data | Full-text search, complex filtering |
| CRUD operations | ID lookups | Analytical queries |

---

## 10. Repository

Repository is the data access layer for a specific model type. It implements the **Identity Map** pattern: each model is loaded only once and reused.

### Base Class

```php
namespace DiBify\DiBify\Repository;

abstract class Repository
{
    protected array $registered = [];  // Identity Map
    protected ReplicatorInterface $replicator;

    public function __construct(ReplicatorInterface $replicator);

    // Queries
    public function findById($id): ?ModelInterface;
    public function findByIds($ids): array;

    // Refresh from DB
    public function refresh(ModelInterface ...$models): SplObjectStorage;

    // Persistence
    public function commit(Transaction $transaction): void;

    // Cache cleanup
    public function freeUpMemory(): void;

    // Abstract — implement per model
    abstract protected function getMapper(): MapperInterface;
    abstract public function classes(): array;
}
```

### Creating a Repository

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

    // Custom query methods
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

### One Repository — Multiple Models

If multiple models are stored in the same collection:

```php
class RoleRepo extends Repository
{
    public function classes(): array
    {
        return [Role::class, AdminRole::class]; // Both types served by this repository
    }

    protected function getMapper(): MapperInterface
    {
        // UnionMapper distinguishes types during deserialization
        return new UnionMapper([
            new UnionRule($adminRoleMapper, /* ... */),
            new UnionRule($roleMapper, /* ... */),
        ]);
    }
}
```

### Identity Map

Repository automatically caches loaded models:

```php
$user1 = $repo->findById('42');
$user2 = $repo->findById('42');
$user1 === $user2; // true — same object from cache
```

This guarantees that model changes are visible everywhere the model is referenced.

### populateOne / populateMany Methods

Protected methods for converting `StorageData` to models with automatic Identity Map registration:

```php
// In a custom repository method
public function findActive(): array
{
    $storage = $this->replicator->getPrimary();
    $dataArray = $storage->findByFilter(['isActive' => true]);
    return $this->populateMany($dataArray); // Deserializes and registers
}
```

### Refreshing Models from DB

```php
// Refresh a single model
$freshUser = $modelManager->refreshOne($user);

// Refresh multiple models
$changes = $modelManager->refreshMany($user1, $user2);
// $changes[$user1] — refreshed version of $user1 (or null if deleted)
```

---

## 11. Pool — Accumulative Operations

Pool is a pattern for safely modifying numeric values under concurrent access. Instead of overwriting the current value, changes accumulate in a "pool" and are applied atomically on commit.

### Why Pool Is Needed

Problem without Pool:
```
Process A: reads balance = 100
Process B: reads balance = 100
Process A: writes balance = 100 + 50 = 150
Process B: writes balance = 100 - 30 = 70  ← Process A's data LOST!
```

With Pool:
```
Process A: adds +50 to pool, writes: current=100, pool=+50
Process B: adds -30 to pool, writes: current=100, pool=-30
Storage applies: 100 + 50 = 150, then 150 - 30 = 120  ← CORRECT
```

### PoolInterface

```php
namespace DiBify\DiBify\Pool;

interface PoolInterface
{
    public function getCurrent();   // Current (committed) value
    public function getPool();      // Accumulated changes
    public function getResult();    // current + pool
    public function add($value): void;
    public function subtract($value): void;
    public function merge(): void;  // Apply pool to current
}
```

### Implementations

```php
use DiBify\DiBify\Pool\IntPool;
use DiBify\DiBify\Pool\FloatPool;

// Integer pool (balance, stock quantity, etc.)
$balance = new IntPool(1000); // current=1000, pool=0
$balance->add(500);           // current=1000, pool=500
$balance->subtract(200);      // current=1000, pool=300
$balance->getResult();        // 1300
$balance->merge();            // current=1300, pool=0

// Float pool (rating, weight, etc.)
$rating = new FloatPool(4.5);
$rating->add(0.3);
$rating->getResult(); // 4.8
```

### Pool in a Model

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

### Pool in a Mapper

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

`PoolMapper` serializes to format `['current' => 1000, 'pool' => 300]`.

> **Important:** `PoolMapper::merge()` is called automatically on successful commit (`ModelManager::commit()`), merging `pool` into `current` for all pools involved in the transaction.

---

## 12. Locker — Model Locking

Locker provides concurrency control for models. It prevents simultaneous modification of the same model by multiple processes.

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

### Lock — Lock Object

```php
use DiBify\DiBify\Locker\Lock\Lock;

$lock = new Lock(
    locker: $currentUser,      // Who is locking (ModelInterface or Reference)
    identity: 'edit-profile',  // Unique operation identifier (optional)
    timeout: 30                // Lock lifetime in seconds (optional)
);
```

### ServiceLock — System Lock

`ServiceLock` is a special `Lock` subtype that is automatically released after commit:

```php
use DiBify\DiBify\Locker\Lock\ServiceLock;

$lock = new ServiceLock($currentUser, 'order-processing', 10);

// After ModelManager::commit($transaction, $lock) —
// the lock is automatically released
```

### Redis Implementation (package `dibify/locker-redis`)

```php
use DiBify\Locker\Redis\Locker;

$locker = new Locker(
    redis: $redis,
    keyPrefix: 'Locker:',
    defaultTimeout: 5,   // Default seconds
    maxTimeout: 60        // Maximum timeout
);
```

### Usage Examples

#### Locking a Model

```php
$lock = new Lock($currentUser, 'editing', 30);

if ($locker->lock($shop, $lock)) {
    try {
        $shop->setName('New Name');
        $modelManager->commit(new Transaction([$shop]));
    } finally {
        $locker->unlock($shop, $lock);
    }
} else {
    echo "Shop is being edited by another user";
}
```

#### Lock with Exception

```php
// If locking fails — an exception is thrown
$locker->lock($shop, $lock, new LockedModelException('Shop is busy'));
```

#### Waiting for a Lock

```php
// Wait up to 10 seconds for the model to become available
$locked = $locker->waitForLock(
    models: [$shop],
    waitTimeout: 10,
    lock: $lock,
    throwable: new LockedModelException('Failed to acquire lock')
);
// Internally — polls every second
```

#### Passing a Lock

```php
// Transfer lock from one owner to another
$currentLock = new Lock($user1, 'processing', 30);
$newLock = new Lock($user2, 'processing', 30);

$locker->passLock($shop, $currentLock, $newLock);
```

#### Automatic Locking on Commit

When passing a `Lock` to `ModelManager::commit()`, all transaction models are automatically locked before writing:

```php
$lock = new ServiceLock($currentUser, 'save-order', 10);

$transaction = new Transaction([$shop, $shopOwner]);
$modelManager->commit($transaction, $lock);
// 1. All models are locked
// 2. Data is saved
// 3. ServiceLock is automatically released
```

---

## 13. Transaction

`Transaction` groups model changes for atomic persistence. It implements the **Unit of Work** pattern.

### Creating a Transaction

```php
use DiBify\DiBify\Manager\Transaction;

// Models to persist
$transaction = new Transaction([$user, $shop]);

// Models to delete
$transaction = new Transaction(deleted: [$oldShop]);

// Mixed transaction
$transaction = new Transaction(
    persisted: [$user, $newShop],
    deleted: [$oldShop]
);
```

### Adding Models After Creation

```php
$transaction = new Transaction();

$transaction->persists($user);           // Add for persistence
$transaction->persists($shop1, $shop2);  // Multiple at once

$transaction->delete($oldShop);          // Add for deletion
```

> **Note:** If a model is added for persistence, it is automatically removed from the deletion list, and vice versa.

### Getting Models

```php
// All models (persisted and deleted)
$all = $transaction->getModels();

// Only persisted
$persisted = $transaction->getPersisted();

// Persisted of specific type
$users = $transaction->getPersisted(User::class);

// Single model of specific type
$user = $transaction->getPersistedOne(User::class);

// Only deleted
$deleted = $transaction->getDeleted();
$deletedShop = $transaction->getDeletedOne(Shop::class);
```

### Transaction Metadata

Metadata allows passing additional information through the processing chain:

```php
$transaction->setMetadata('disable_logs', true);
$transaction->setMetadata('initiator', 'cron-job');

$value = $transaction->getMetadata('disable_logs'); // true
```

### Event Handlers

```php
use DiBify\DiBify\Manager\TransactionEvent;

$transaction->addEventHandler(TransactionEvent::BEFORE_COMMIT, function (Transaction $t) {
    // Called before writing to storage
    echo "Saving " . count($t->getPersisted()) . " models";
});

$transaction->addEventHandler(TransactionEvent::AFTER_COMMIT, function (Transaction $t) {
    // Called after successful persistence
    $user = $t->getPersistedOne(User::class);
    sendWelcomeEmail($user);
});

$transaction->addEventHandler(TransactionEvent::COMMIT_EXCEPTION, function (Transaction $t) {
    // Called on error
    logError("Transaction save error " . $t->id());
});
```

> **Important:** Handlers are cleared after firing, so they execute only once.

### Model Relationships

Static `Transaction` methods allow declaratively linking model lifecycles:

#### persistsWith — Persist Together

```php
// When $shop is added for persistence — $shopSettings is also automatically added
Transaction::persistsWith($shop, $shopSettings);

$transaction = new Transaction();
$transaction->persists($shop); // $shopSettings is also added
```

#### deleteWith — Delete Together

```php
// When $shop is deleted — $shopSettings is also deleted
Transaction::deleteWith($shop, $shopSettings);

$transaction = new Transaction();
$transaction->delete($shop); // $shopSettings is also deleted
```

#### withDeletePersists — On Delete of One, Persist Another

```php
// When $membership is deleted — $user (with updated data) is persisted
Transaction::withDeletePersists($membership, $user);
```

#### withPersistsDelete — On Persist of One, Delete Another

```php
// When $newMembership is persisted — $oldMembership is deleted
Transaction::withPersistsDelete($newMembership, $oldMembership);
```

### RetryPolicy — Retry Policy

```php
use DiBify\DiBify\Manager\RetryPolicy;

$retryPolicy = new RetryPolicy(
    isRetryRequiredCallable: fn(Transaction $t, Throwable $e, int $attempt) => $attempt <= 3,
    beforeRetryCallable: fn(Transaction $t, Throwable $e, int $attempt, ModelManager $mm) => true,
    retries: 3,
    delay: 500000  // 0.5 seconds between attempts
);

// For a specific transaction
$transaction = new Transaction([$user], retryPolicy: $retryPolicy);

// Or globally for ModelManager
$modelManager->setRetryPolicy($retryPolicy);
```

---

## 14. ConfigManager — Configuration Registry

`ConfigManager` is the central registry mapping model classes to their repositories and ID generators.

```php
use DiBify\DiBify\Manager\ConfigManager;

$config = new ConfigManager();

// Map model → repository + ID generator
$config->add(
    $userRepo,                          // Repository or callable
    [User::class],                      // Model classes
    $redisIdGenerator                   // ID generator
);

// Multiple models in one repository
$config->add(
    $roleRepo,
    [Role::class, AdminRole::class],
    $redisIdGenerator
);

// Lazy repository initialization (via callable)
$config->add(
    fn() => $container->get(ShopRepo::class),
    [Shop::class],
    $redisIdGenerator
);
```

### Retrieving from ConfigManager

```php
// By model, Reference, or alias/class
$repo = $config->getRepository($user);              // by model
$repo = $config->getRepository($userReference);      // by Reference
$repo = $config->getRepository(User::class);         // by class
$repo = $config->getRepository('user');              // by alias

$generator = $config->getIdGenerator(User::class);

// All registered model classes
$classes = $config->getModelClasses();
```

> **Note:** If a repository is registered as a callable, it will be initialized on first access (lazy loading).

---

## 15. ModelManager — Central Manager

`ModelManager` is the entry point for all ORM operations. It is a singleton that coordinates all components.

### Construction

```php
use DiBify\DiBify\Manager\ModelManager;

$modelManager = ModelManager::construct(
    configManager: $configManager,
    locker: $locker,
    onBeforeCommit: function (Transaction $transaction) {
        // Global pre-commit hook (e.g., start DB transaction)
    },
    onAfterCommit: function (Transaction $transaction) {
        // Global post-commit hook (e.g., commit DB transaction)
    },
    onCommitException: function (Transaction $transaction, Throwable $e) {
        // Error handling (e.g., rollback DB transaction)
    },
    retryPolicy: $retryPolicy,  // Optional
);
```

### Core Operations

#### Persisting Models

```php
$user = new User('John', 'john@example.com');
$shop = new Shop('My Shop', $user);

$transaction = new Transaction([$user, $shop]);
$modelManager->commit($transaction);

// After commit: $user->id()->isAssigned() === true
```

#### Persisting with Lock

```php
$lock = new ServiceLock($currentUser, 'update', 10);
$modelManager->commit(new Transaction([$shop]), $lock);
```

#### Deleting Models

```php
$modelManager->commit(new Transaction(deleted: [$shop]));
```

#### Finding Models

```php
// Via repository
$userRepo = $modelManager->getRepository(User::class);
$user = $userRepo->findById('42');
$users = $userRepo->findByIds(['1', '2', '3']);

// Via Reference (static method)
$user = ModelManager::findByReference($userRef);

// Multiple references at once
$models = ModelManager::findByReferences($ref1, $ref2, $ref3);

// Universal find by any type of identifier
$user = $modelManager->findByAnyTypeId('42', User::class);
$user = $modelManager->findByAnyTypeId($reference);
$user = $modelManager->findByAnyTypeId($userObject); // returns the object itself
```

#### Refreshing from DB

```php
// Refresh a single model
$freshUser = $modelManager->refreshOne($user);

// Refresh multiple
$changes = $modelManager->refreshMany($user1, $user2, $shop);
$freshUser1 = $changes[$user1]; // refreshed version (or null if deleted)
```

#### Memory Management

```php
// Clear all caches (Identity Map, References, Pools)
$modelManager->freeUpMemory();
```

#### Accessing the Locker

```php
$locker = $modelManager->getLocker();
$locker->lock($shop, $lock);
```

---

## 16. Model Persistence Lifecycle

When `ModelManager::commit($transaction, $lock)` is called, the following occurs:

```
1. ID Assignment
   IdGenerator is called for each persisted model.
   New models receive their unique identifier.

2. Model::onBeforeCommit()
   For models implementing ModelBeforeCommitEventInterface,
   onBeforeCommit() is called — validation, data normalization.

3. Replicator::onBeforeCommit()
   Replicator hooks for preparing storages.

4. Transaction::triggerEvent(BEFORE_COMMIT)
   User-defined BEFORE_COMMIT handlers fire.

5. onBeforeCommit callback (global)
   ModelManager's global hook is called.
   Typical use: start DB transaction.

6. ──── Commit begins (commitInternal) ────

   6a. Model locking
       All transaction models are locked via Locker.

   6b. Repository::commit()
       For each model type:
       - Persisted: Mapper.serialize() → Replicator.insert/update()
       - Deleted: Replicator.delete()

   6c. onAfterCommit callback (global)
       Typical use: commit DB transaction.

   6d. Replicator::onAfterCommit()
       Replicator hooks after writing.

   6e. Model::onAfterCommit()
       For models implementing ModelAfterCommitEventInterface.

   6f. Transaction::triggerEvent(AFTER_COMMIT)
       User-defined AFTER_COMMIT handlers fire.

   6g. PoolMapper::merge()
       All pools involved in the transaction merge pool into current.

   6h. ServiceLock release
       If a ServiceLock was provided — locks are released.

7. ──── On error ────

   7a. onCommitException callback (global)
       Typical use: rollback DB transaction.

   7b. Transaction::triggerEvent(COMMIT_EXCEPTION)

   7c. RetryPolicy (if configured)
       Retry commit according to the configured strategy.

   7d. ServiceLock release when attempts are exhausted.
```

---

## 17. Scope — Multi-Tenancy

DiBify supports multi-tenancy via the `scope` mechanism. Scope is a "visibility area" identifier (e.g., company ID) that is automatically added to all queries.

### Setting Scope

```php
// Set current scope (typically during request handling)
ModelManager::setScope('company_42');

// Get current scope
$scope = ModelManager::getScope(); // 'company_42'

// Reset scope
ModelManager::setScope(null);
```

### How Scope Works

1. When creating `StorageData`, scope is automatically injected:
   ```php
   $data = new StorageData('1', ['name' => 'John']);
   $data->scope; // 'company_42' — taken from ModelManager::getScope()
   ```

2. Storages use scope for data isolation:
    - In MongoDB: compound `_id = {company: 'company_42', id: '1'}`
    - In Redis: key `prefix:company_42:1`

3. All queries are automatically filtered by current scope.

> **Important:** When scope changes, all caches are automatically cleared (`freeUpMemory()`), as data from the old scope is no longer relevant.

---

## 18. Additional Packages

### dibify/id-generator-redis

Sequential numeric ID generation via Redis.

```php
use DiBify\IdGenerator\Redis\IdGenerator;

$generator = new IdGenerator($redis, 'IdCounter');
// For User: 1, 2, 3, ...
// For Shop: 1, 2, 3, ... (independent counters)
```

The `setCounterValue()` method allows resetting the counter (e.g., after duplicate key conflicts).

### dibify/locker-redis

Distributed locks via Redis.

```php
use DiBify\Locker\Redis\Locker;

$locker = new Locker($redis, 'Lock:', defaultTimeout: 5, maxTimeout: 60);
```

Uses Redis `SET NX` for atomic lock acquisition with TTL.

### dibify/storage-mongodb

MongoDB storage with scope support, automatic date and UUID conversion.

### dibify/storage-redis

Redis storage in two variants: string keys (with TTL) and hashes.

### dibify/migrations

Migration system with Symfony Console:

```bash
# Create a migration
php console migration:new add_users_index

# Run migrations
php console migration:run
```

Migrations are stored in `migrations/`, progress is tracked in `_applied.json`.

---

## 19. Complete Application Example

Let's walk through a complete example with `User`, `Role`, and `Shop` models.

### Models

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

### Mappers

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

### Repositories

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

### Configuration

```php
use DiBify\DiBify\Manager\ConfigManager;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Replicator\DirectReplicator;
use DiBify\IdGenerator\Redis\IdGenerator;
use DiBify\Locker\Redis\Locker;

// ID generator
$idGenerator = new IdGenerator($redis);

// Storages
$roleMongoStorage = new RoleMongoStorage($mongoClient, $mongoDb);
$userMongoStorage = new UserMongoStorage($mongoClient, $mongoDb);
$shopMongoStorage = new ShopMongoStorage($mongoClient, $mongoDb);
$userRedisStorage = new UserRedisStorage($redis);

// Repositories with replicators
$roleRepo = new RoleRepo(new DirectReplicator($roleMongoStorage));
$userRepo = new UserRepo(new DirectReplicator($userMongoStorage, ['redis' => $userRedisStorage]));
$shopRepo = new ShopRepo(new DirectReplicator($shopMongoStorage));

// ConfigManager — link models to repositories
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

### Usage

```php
// Set scope (company)
ModelManager::setScope('company_1');

// Creating models
$role = new Role('Administrator', ['users.manage', 'shops.manage']);
$user = new User('John Smith', 'john@example.com', $role);
$shop = new Shop('Book Store', $user, 'The best books in town');

// Link: when shop is persisted — also persist the user
Transaction::persistsWith($shop, $user);

// Save
$transaction = new Transaction([$role, $shop]);
// $user will also be saved thanks to persistsWith
$modelManager->commit($transaction);

echo $role->id()->get(); // '1'
echo $user->id()->get(); // '1'
echo $shop->id()->get(); // '1'

// Finding
$userRepo = $modelManager->getRepository(User::class);
$foundUser = $userRepo->findById('1');
$foundUser === $user; // true — Identity Map

// Updating
$lock = new ServiceLock($user, 'update-shop', 10);
$shop->setName('New Name');
$modelManager->commit(new Transaction([$shop]), $lock);

// Balance deposit (Pool)
$user->deposit(1000);
$modelManager->commit(new Transaction([$user]));
echo $user->getBalance(); // 1000

// Deletion
$modelManager->commit(new Transaction(deleted: [$shop]));

// Reference preloading
$shops = $shopRepo->findByOwner($user);
Reference::preload(...array_map(fn($s) => $s->getOwnerReference(), $shops));
foreach ($shops as $shop) {
    echo $shop->getOwner()->getName(); // No N+1
}

// Memory cleanup (for long-running processes)
$modelManager->freeUpMemory();
```
