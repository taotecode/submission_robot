<?php

namespace App\Admin\Actions\Grid\MyBot;

use App\Admin\Forms\MyBot\SetCommandsForm;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class SetCommands extends RowAction
{
    /**
     * @return string
     */
	protected $title = '设置命令';

    public function render()
    {
        // 实例化表单类并传递自定义参数
        $form = SetCommandsForm::make()->payload(['id' => $this->getKey()]);

        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button($this->title);
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
