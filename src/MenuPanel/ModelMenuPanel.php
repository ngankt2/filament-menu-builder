<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Closure;
use Illuminate\Database\Eloquent\Model;

class ModelMenuPanel extends AbstractMenuPanel
{
    /**
     * @var \Illuminate\Database\Eloquent\Model&\Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable
     */
    protected Model $model;

    protected Closure $urlUsing;

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model&\Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable>  $model
     */
    public function model(string $model): static
    {
        $this->model = new $model;

        return $this;
    }

    public function getName(): string
    {
        return $this->model->getMenuPanelName();
    }

    public function getItems(): array
    {
        return ($this->model->getMenuPanelModifyQueryUsing())($this->model->newQuery())
            ->get()// Sử dụng paginate thay vì get để phân trang, bạn có thể thay số 10 thành bất kỳ số trang nào bạn muốn.
            ->map(fn (Model $model) => [
                'title' => $model->{$this->model->getMenuPanelTitleColumn()},
                'linkable_type' => $model::class,
                'linkable_id' => $model->getKey(),
            ])->all();
    }
    public function getItemsWithPaginate(): array
    {

        return ($this->model->getMenuPanelModifyQueryUsing())($this->model->newQuery())
            ->paginate(10)  // Sử dụng paginate thay vì get để phân trang, bạn có thể thay số 10 thành bất kỳ số trang nào bạn muốn.
            ->through(fn (Model $model) => [
                'title' => $model->{$this->model->getMenuPanelTitleColumn()},
                'linkable_type' => $model::class,
                'linkable_id' => $model->getKey(),
            ]);
    }
}
