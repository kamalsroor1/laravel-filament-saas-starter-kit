<?php

declare(strict_types=1);

use App\Core\Actions\BaseAction;
use App\Core\DTOs\BaseDTO;
use App\Core\Exceptions\DomainException;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Repositories\BaseRepository;
use App\Core\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    Schema::dropIfExists('core_repository_test_models');
    Schema::create('core_repository_test_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

it('executes handle through base action', function (): void {
    $action = new class extends BaseAction {
        public function handle(mixed ...$arguments): mixed
        {
            return strtoupper((string) $arguments[0]);
        }
    };

    expect($action->handle('phase1'))->toBe('PHASE1');
});

it('creates dto from array and serializes it back', function (): void {
    $dto = TestUserDTO::fromArray(['name' => 'Ava', 'age' => 30]);

    expect($dto->name)->toBe('Ava')
        ->and($dto->age)->toBe(30)
        ->and($dto->toArray())->toBe(['name' => 'Ava', 'age' => 30]);
});

it('injects logger into base service', function (): void {
    $logger = app(LoggerInterface::class);
    $service = new class($logger) extends BaseService {
        public function loggerClass(): string
        {
            return $this->logger::class;
        }
    };

    expect($service->loggerClass())->toBe($logger::class);
});

it('performs repository CRUD operations', function (): void {
    $repository = new class(new CoreRepositoryTestModel()) extends BaseRepository {
    };

    $created = $repository->create(['name' => 'First']);
    $found = $repository->findById($created->getKey());
    $updated = $repository->update($created->getKey(), ['name' => 'Updated']);
    $all = $repository->findAll();
    $deleted = $repository->delete($created->getKey());

    expect($found?->name)->toBe('First')
        ->and($updated->name)->toBe('Updated')
        ->and($all->count())->toBe(1)
        ->and($deleted)->toBeTrue();
});

it('returns false on delete when record does not exist', function (): void {
    $repository = new class(new CoreRepositoryTestModel()) extends BaseRepository {
    };

    expect($repository->delete(999999))->toBeFalse();
});

it('keeps exception inheritance chain intact', function (): void {
    expect(new ValidationException('invalid'))->toBeInstanceOf(DomainException::class)
        ->and(new NotFoundException('missing'))->toBeInstanceOf(DomainException::class);
});

final readonly class TestUserDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public int $age = 0,
    ) {
    }
}

final class CoreRepositoryTestModel extends Model
{
    protected $table = 'core_repository_test_models';

    protected $fillable = ['name'];
}

