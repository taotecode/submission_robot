<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\BatchPublishManuscript;
use App\Admin\Repositories\Manuscript;
use App\Enums\ManuscriptStatus;
use App\Enums\ObjectType;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Manuscript as ManuscriptModel;
use App\Services\SendTelegramMessageService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class ManuscriptController extends AdminController
{
    use SendTelegramMessageService;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Manuscript(['channel', 'bot']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('bot.name', '机器人')->display(function ($name) {
                return $name ? '@'.$name : '-';
            });
            $grid->column('channel.name', '频道')->display(function ($name) {
                return $name ? '@'.$name : '-';
            });
            $grid->column('type')->display(function ($type) {
                return ObjectType::data[$type];
            })->label();
            $grid->column('text')->limit(50)->title();
            $grid->column('message_id')->display(function ($messageId) {
                if (!$messageId || !$this->channel || !$this->channel->name) {
                    return '-';
                }
                return "<a href='https://t.me/{$this->channel->name}/{$messageId}' target='_blank'>{$messageId}</a>";
            });
            $grid->column('backup_message_ids', '备份频道消息')->display(function ($backupMessageIds) {
                if (empty($backupMessageIds)) {
                    return '-';
                }
                return count($backupMessageIds) . ' 个备份频道';
            })->expand(function () {
                if (empty($this->backup_message_ids)) {
                    return '无备份频道消息';
                }
                
                $html = "<div style='padding:10px 10px'>";
                foreach ($this->backup_message_ids as $backupId => $backup) {
                    $channelId = $backup['channel_id'] ?? '-';
                    $messageId = $backup['message_id'] ?? '-';
                    $chatId = $backup['chat_id'] ?? '-';
                    $isPublic = ($backup['is_public'] ?? 1) ? '公开' : '私有';
                    
                    // 获取频道名称
                    $backupChannel = \App\Models\BackupChannel::find($channelId);
                    $channelName = $backupChannel ? $backupChannel->backup_appellation : '未知频道';
                    
                    $messageLink = $isPublic == '公开' && $chatId && $messageId
                        ? "<a href='https://t.me/{$chatId}/{$messageId}' target='_blank'>{$messageId}</a>"
                        : $messageId;
                    
                    $html .= "<p>· {$channelName} [{$isPublic}] - 消息ID: {$messageLink}</p>";
                }
                return $html.'</div>';
            });
            
            $grid->column('posted_by')->display(function ($posted_by) {
                return get_posted_by($posted_by);
            })->expand(function () {
                // 返回显示的详情
                $uid = $this->posted_by['id'] ?? '';
                $first_name = $this->posted_by['first_name'] ?? '';
                $last_name = $this->posted_by['last_name'] ?? '';
                $username = $this->posted_by['username'] ?? '';

                return "<div style='padding:10px 10px'><p>UID: $uid</p><p>first name: $first_name</p><p>last name: $last_name</p><p>用户名: $username</p></div>";
            });
            
            $grid->column('is_anonymous')->display(function($is_anonymous) {
                return $is_anonymous ? '是' : '否';
            });
            
            $grid->column('approved')->display(function ($approved) {
                return count($approved);
            })->expand(function () {
                $html = "<div style='padding:10px 10px'>";
                foreach ($this->approved as $key => $item) {
                    $id = $item['id'] ?? '';
                    $first_name = $item['first_name'] ?? '';
                    $last_name = $item['last_name'] ?? '';
                    $html .= "<p>· {$id} | {$first_name} {$last_name}</p>";
                }

                return $html.'</div>';
            });
            $grid->column('reject')->display(function ($reject) {
                return count($reject);
            })->expand(function () {
                $html = "<div style='padding:10px 10px'>";
                foreach ($this->reject as $key => $item) {
                    $id = $item['id'] ?? '';
                    $first_name = $item['first_name'] ?? '';
                    $last_name = $item['last_name'] ?? '';
                    $html .= "<p>· {$id} | {$first_name} {$last_name}</p>";
                }

                return $html.'</div>';
            });
            
            $grid->column('status')->display(function ($status) {
                return ManuscriptStatus::ALL_NAME[$status];
            })->label([
                ManuscriptStatus::PENDING => 'orange',
                ManuscriptStatus::APPROVED => 'success',
                ManuscriptStatus::REJECTED => 'danger',
                ManuscriptStatus::DELETE => 'default',
            ]);
            
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            // 操作按钮
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // 添加发布按钮
                if ($actions->row->status != ManuscriptStatus::APPROVED) {
                    $actions->append('<a href="javascript:void(0);" data-url="'.admin_url('manuscript/publish/'.$actions->row->getKey()).'" class="grid-row-action" data-confirm="确定要发布此稿件吗？将同步发送到所有频道。"><i class="feather icon-paper-plane"></i> 发布</a>');
                }
                
                // 添加更新按钮
                if ($actions->row->status == ManuscriptStatus::APPROVED) {
                    $actions->append('<a href="javascript:void(0);" data-url="'.admin_url('manuscript/update-channel/'.$actions->row->getKey()).'" class="grid-row-action" data-confirm="确定要更新频道消息吗？"><i class="feather icon-refresh-cw"></i> 更新频道</a>');
                }
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('id');
                $filter->equal('bot_id', '机器人')->select(Bot::query()->pluck('appellation', 'id'));
                $filter->equal('channel_id', '频道')->select(Channel::query()->pluck('appellation', 'id'));
                $filter->equal('type', '类型')->select(ObjectType::data);
                $filter->like('text', '内容');
                $filter->equal('is_anonymous', '是否匿名')->select([0 => '否', 1 => '是']);
                $filter->equal('status', '状态')->select(ManuscriptStatus::ALL_NAME);
                $filter->between('created_at', '创建时间')->datetime();
            });
            
            // 添加批量操作
            $grid->batchActions(function (Grid\Tools\BatchActions $batch) {
                $batch->add(new BatchPublishManuscript('批量发布'));
            });
            
            // 添加工具按钮
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<a href="' . admin_url('manuscript/create') . '" class="btn btn-primary btn-sm"><i class="feather icon-plus"></i> 新建稿件</a>');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Manuscript(['channel', 'bot']), function (Show $show) {
            $show->field('id');
            $show->field('bot.name', '机器人');
            $show->field('channel.name', '频道');
            $show->field('type')->as(function ($type) {
                return ObjectType::data[$type] ?? $type;
            });
            $show->field('is_anonymous')->as(function ($is_anonymous) {
                return $is_anonymous ? '是' : '否';
            });
            $show->field('text');
            $show->field('posted_by')->as(function ($posted_by) {
                return is_array($posted_by) ? get_posted_by($posted_by) : $posted_by;
            });
            $show->field('data')->as(function ($data) {
                return is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $data;
            });
            $show->field('backup_message_ids')->unescape()->as(function ($backupMessageIds) {
                if (empty($backupMessageIds)) {
                    return '无备份频道消息';
                }
                
                $html = "<div>";
                foreach ($backupMessageIds as $backupId => $backup) {
                    $channelId = $backup['channel_id'] ?? '-';
                    $messageId = $backup['message_id'] ?? '-';
                    $chatId = $backup['chat_id'] ?? '-';
                    $isPublic = ($backup['is_public'] ?? 1) ? '公开' : '私有';
                    
                    // 获取频道名称
                    $backupChannel = \App\Models\BackupChannel::find($channelId);
                    $channelName = $backupChannel ? $backupChannel->backup_appellation : '未知频道';
                    
                    $messageLink = $isPublic == '公开' && $chatId && $messageId
                        ? "<a href='https://t.me/{$chatId}/{$messageId}' target='_blank'>{$messageId}</a>"
                        : $messageId;
                    
                    $html .= "<p>· {$channelName} [{$isPublic}] - 消息ID: {$messageLink}</p>";
                }
                return $html.'</div>';
            });
            $show->field('message_id')->unescape()->as(function ($messageId) {
                if (!$messageId || !$this->channel || !$this->channel->name) {
                    return '-';
                }
                return "<a href='https://t.me/{$this->channel->name}/{$messageId}' target='_blank'>{$messageId}</a>";
            });
            $show->field('status')->as(function ($status) {
                return ManuscriptStatus::ALL_NAME[$status] ?? $status;
            });
            $show->field('created_at');
            $show->field('updated_at');
            
            // 添加操作按钮
            $show->tools(function (Show\Tools $tools) use ($show) {
                $id = $show->getKey();
                $manuscript = ManuscriptModel::find($id);
                
                if ($manuscript && $manuscript->status != ManuscriptStatus::APPROVED) {
                    $tools->append('<a href="'.admin_url("manuscript/publish/{$id}").'" class="btn btn-primary btn-sm"><i class="feather icon-paper-plane"></i> 发布</a>');
                }
                
                if ($manuscript && $manuscript->status == ManuscriptStatus::APPROVED) {
                    $tools->append('<a href="'.admin_url("manuscript/update-channel/{$id}").'" class="btn btn-primary btn-sm"><i class="feather icon-refresh-cw"></i> 更新频道</a>');
                }
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Manuscript(), function (Form $form) {
            $form->display('id');
            
            // 选择机器人和频道
            $form->select('bot_id', '机器人')
                ->options(Bot::query()->pluck('appellation', 'id'))
                ->required()
                ->load('channel_id', 'admin/api/channels');
                
            $form->select('channel_id', '频道')
                ->options(function ($id) {
                    $botId = request()->get('bot_id');
                    if ($botId) {
                        $bot = Bot::find($botId);
                        if ($bot && !empty($bot->channel_ids)) {
                            return Channel::whereIn('id', $bot->channel_ids)->pluck('appellation', 'id');
                        }
                    }
                    return [];
                })
                ->required();
                
            // 选择类型
            $form->radio('type', '内容类型')
                ->options(ObjectType::data)
                ->default('text')
                ->required();
                
            // 是否匿名
            $form->switch('is_anonymous', '匿名发布')
                ->default(0)
                ->help('开启后将不显示投稿人信息');
                
            // 文本内容
            $form->textarea('text', '内容')
                ->rows(5)
                ->required()
                ->help('支持HTML格式，请谨慎编辑');
                
            // 状态
            $form->radio('status', '状态')
                ->options(ManuscriptStatus::ALL_NAME)
                ->default(0);
                
            // 投稿人信息
            if ($form->isEditing()) {
                $form->display('posted_by', '投稿人')->with(function ($value) {
                    return $value ? get_posted_by($value) : '-';
                });
                
                $form->display('message_id', '频道消息ID');
                
                $form->display('created_at');
                $form->display('updated_at');
            } else {
                // 新建时自动设置投稿人为admin
                $form->hidden('posted_by')->default(json_encode([
                    'id' => 0,
                    'first_name' => 'Admin',
                    'last_name' => '',
                    'username' => 'admin',
                    'is_bot' => false,
                ]));
                
                $form->hidden('posted_by_id')->default(0);
                $form->hidden('data')->default(json_encode([]));
                $form->hidden('appendix')->default(json_encode([]));
                $form->hidden('approved')->default(json_encode([]));
                $form->hidden('reject')->default(json_encode([]));
                $form->hidden('one_approved')->default(json_encode([]));
                $form->hidden('one_reject')->default(json_encode([]));
            }
            
            // 保存后发布
            $form->saved(function (Form $form, $result) {
                // 如果状态是已通过，则发布到频道
                if ($form->status == ManuscriptStatus::APPROVED) {
                    $manuscript = ManuscriptModel::find($form->getKey());
                    
                    if ($manuscript) {
                        $this->publishToChannel($manuscript);
                    }
                }
                
                return $result;
            });
        });
    }
    
    /**
     * 发布稿件到频道
     */
    public function publish($id, Content $content)
    {
        $manuscript = ManuscriptModel::find($id);
        
        if (!$manuscript) {
            return $content->warning('稿件不存在');
        }
        
        $result = $this->publishToChannel($manuscript);
        
        if ($result) {
            return $content->success('发布成功')->redirect('manuscript');
        } else {
            return $content->error('发布失败')->redirect('manuscript');
        }
    }
    
    /**
     * 更新频道消息
     */
    public function updateChannel($id, Content $content)
    {
        $manuscript = ManuscriptModel::find($id);
        
        if (!$manuscript) {
            return $content->warning('稿件不存在');
        }
        
        $result = $this->publishToChannel($manuscript, true);
        
        if ($result) {
            return $content->success('更新成功')->redirect('manuscript');
        } else {
            return $content->error('更新失败')->redirect('manuscript');
        }
    }
    
    /**
     * 发布稿件到频道的内部实现
     */
    public function publishToChannel(ManuscriptModel $manuscript, $isUpdate = false)
    {
        try {
            // 设置状态为已通过
            $manuscript->status = ManuscriptStatus::APPROVED;
            
            // 如果数据为空，构造基本数据
            if (empty($manuscript->data)) {
                $manuscript->data = [
                    'text' => $manuscript->text,
                    'disable_message_preview' => 1,
                    'disable_notification' => 0,
                    'protect_content' => 0,
                ];
            }
            
            // 获取机器人
            $bot = $manuscript->bot;
            
            if (!$bot) {
                return false;
            }
            
            // 创建Telegram API实例
            $telegram = new Api($bot->token);
            
            // 使用sendChannelMessage发送到频道
            $result = $this->sendChannelMessage($telegram, $bot, $manuscript);
            
            if ($result && is_array($result)) {
                // 保存消息ID
                $manuscript->message_id = isset($result['message_id']) ? $result['message_id'] : 
                    (isset($result[0]['message_id']) ? $result[0]['message_id'] : null);
                    
                $manuscript->save();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('发布稿件失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取频道列表的API
     */
    public function channels(Request $request)
    {
        $botId = $request->get('q');
        
        if (!$botId) {
            return [];
        }
        
        $bot = Bot::find($botId);
        
        if (!$bot || empty($bot->channel_ids)) {
            return [];
        }
        
        $channels = Channel::whereIn('id', $bot->channel_ids)->get(['id', 'appellation']);
        
        $options = [];
        foreach ($channels as $channel) {
            $options[] = [
                'id' => $channel->id,
                'text' => $channel->appellation,
            ];
        }
        
        return $options;
    }
}
