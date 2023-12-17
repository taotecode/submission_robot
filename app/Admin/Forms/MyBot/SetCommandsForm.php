<?php

namespace App\Admin\Forms\MyBot;

use App\Enums\Commands;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class SetCommandsForm extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        // dump($input);

        // return $this->response()->error('Your error message.');

        return $this
				->response()
				->success('Processed successfully.')
				->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->listbox('default', '私聊命令')->options(Commands::DEFAULT_OPTIONS)->required()->default([
            0,
            1,
        ])->help('将您需要展示给用户的命令点击添加到右侧列表中。不需要的展示的可以点击右侧列表添加到左侧列表中。');
        $this->listbox('all_group_chats', '群组命令')->options(Commands::ALL_GROUP_OPTIONS)->required()->default([
            0
        ])->help('将您需要展示给用户的命令点击添加到右侧列表中。不需要的展示的可以点击右侧列表添加到左侧列表中。');
        $this->listbox('all_group_chats', '群组命令')->options(Commands::ALL_GROUP_OPTIONS)->required()->default([
            0
        ])->help('将您需要展示给用户的命令点击添加到右侧列表中。不需要的展示的可以点击右侧列表添加到左侧列表中。');
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'name'  => 'John Doe',
            'email' => 'John.Doe@gmail.com',
        ];
    }
}
