<?php

namespace App\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;
    protected Builder $query;
    protected ?int $take;
    protected array $with = [];
    protected array $wheres = [];
    protected array $whereIns = [];
    protected array $orderBys = [];
    protected array $scopes = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->makeModel();
    }

    abstract public function model(): mixed;

    /**
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function makeModel(): Model
    {
        $model = app()->make($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of " . Model::class);
        }

        return $this->model = $model;
    }

    public function all(array $columns = ['*']): Collection|static
    {
        $this->newQuery()->eagerLoad();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    public function count(): int
    {
        return $this->get()->count();
    }

    public function create(array $data): Model
    {
        $this->unsetClauses();

        $model = $this->model->create($data);

        return $model->fresh();
    }

    public function createMultiple(array $data): Collection
    {
        $models = new Collection();

        foreach ($data as $d) {
            $models->push($this->create($d));
        }

        return $models;
    }

    public function delete(): mixed
    {
        $this->newQuery()->setClauses()->setScopes();

        $result = $this->query->delete();

        $this->unsetClauses();

        return $result;
    }

    public function deleteByUuid(string $uuid): ?bool
    {
        $this->unsetClauses();

        return $this->getByUuid($uuid)?->delete();
    }

    public function deleteMultipleById(array $uuids): int
    {
        return $this->model->destroy($uuids);
    }

    public function first(array $columns = ['*']): Model|static
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $model = $this->query->firstOrFail($columns);

        $this->unsetClauses();

        return $model;
    }

    public function get(array $columns = ['*']): Collection|static
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    public function find(string $uuid): ?Model
    {
        return $this->model->find($uuid);
    }

    public function getByUuid(string $uuid, array $columns = ['*']): ?Model
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->find($uuid, $columns);
    }

    public function save(Model $model): Model
    {
        $model->save();

        return $model;
    }

    public function getByColumn(
        mixed $item,
        string $column,
        array $columns = ['*']
    ): null|Model|static {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->where($column, $item)->first($columns);
    }

    public function paginate(
        int $limit = 25,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        return $models;
    }

    public function updateByUuid(
        string $uuid,
        array $data,
        array $options = []
    ): ?Model {
        $this->unsetClauses();

        $model = $this->getByUuid($uuid);

        if (array_key_exists('time', $data)) {
            $data['time'] = Carbon::parse($data['time'])->format('H:i:s');
        }

        $model?->update($data, $options);

        return $model;
    }

    public function updateOrCreate(
        ?array $where,
        ?array $data = null
    ): Model {
        $this->unsetClauses();
        $model = new $this->model;

        if (is_null($data)) {
            if (!isset($where['uuid'])) {
                return $model->create($where);
            }

            return $model->updateOrCreate([
                'uuid' => $where['uuid'],
            ], $where);
        }

        return $model->updateOrCreate($where, $data);
    }

    public function limit(int $limit): self
    {
        $this->take = $limit;

        return $this;
    }

    public function orderBy(
        string $column,
        string $direction = 'asc'
    ): self {
        $this->orderBys[] = compact('column', 'direction');

        return $this;
    }

    public function where(
        string $column,
        string $value,
        string $operator = '='
    ): static {
        $this->wheres[] = compact('column', 'value', 'operator');

        return $this;
    }

    public function whereIn(
        string $column,
        mixed $values
    ): static {
        $values = is_array($values) ? $values : [$values];

        $this->whereIns[] = compact('column', 'values');

        return $this;
    }

    public function with(mixed $relations): static
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->with = $relations;

        return $this;
    }

    protected function newQuery(): static
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    protected function eagerLoad(): static
    {
        foreach ($this->with as $relation) {
            $this->query->with($relation);
        }

        return $this;
    }

    protected function setClauses(): static
    {
        foreach ($this->wheres as $where) {
            $this->query->where($where['column'], $where['operator'], $where['value']);
        }

        foreach ($this->whereIns as $whereIn) {
            $this->query->whereIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->orderBys as $orders) {
            $this->query->orderBy($orders['column'], $orders['direction']);
        }

        if (isset($this->take) and !is_null($this->take)) {
            $this->query->take($this->take);
        }

        return $this;
    }

    protected function setScopes(): static
    {
        foreach ($this->scopes as $method => $args) {
            $this->query->$method(implode(', ', $args));
        }

        return $this;
    }

    protected function unsetClauses(): static
    {
        $this->wheres = [];
        $this->whereIns = [];
        $this->scopes = [];
        $this->take = null;

        return $this;
    }
}
