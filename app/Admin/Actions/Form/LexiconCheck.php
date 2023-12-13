<?php

namespace App\Admin\Actions\Form;

use App\Admin\Forms\LexiconCheck as LexiconCheckForm;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Form\AbstractTool;
use Dcat\Admin\Traits\HasPermissions;
use Dcat\Admin\Widgets\Modal;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LexiconCheck extends AbstractTool
{
    /**
     * @return string
     */
    protected $title = '词库验证';

    /**
     * Handle the action request.
     *
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        // dump($this->getKey());

        return $this->response()
            ->success('Processed successfully.')
            ->redirect('/');
    }

    public function render()
    {
        // 实例化表单类
        $form = LexiconCheckForm::make();

        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button('<button class="btn btn-sm btn-primary" style="margin-right: 5px;">'.$this->title.'</button>');
    }

    /**
     * @param  Model|Authenticatable|HasPermissions|null  $user
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
