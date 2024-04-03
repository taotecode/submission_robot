<?php

/**
 * A helper file for Dcat Admin, to provide autocomplete information to your IDE
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author jqh <841324345@qq.com>
 */
namespace Dcat\Admin {
    use Illuminate\Support\Collection;

    /**
     * @property Grid\Column|Collection id
     * @property Grid\Column|Collection name
     * @property Grid\Column|Collection type
     * @property Grid\Column|Collection version
     * @property Grid\Column|Collection detail
     * @property Grid\Column|Collection created_at
     * @property Grid\Column|Collection updated_at
     * @property Grid\Column|Collection is_enabled
     * @property Grid\Column|Collection parent_id
     * @property Grid\Column|Collection order
     * @property Grid\Column|Collection icon
     * @property Grid\Column|Collection uri
     * @property Grid\Column|Collection extension
     * @property Grid\Column|Collection permission_id
     * @property Grid\Column|Collection menu_id
     * @property Grid\Column|Collection slug
     * @property Grid\Column|Collection http_method
     * @property Grid\Column|Collection http_path
     * @property Grid\Column|Collection role_id
     * @property Grid\Column|Collection user_id
     * @property Grid\Column|Collection value
     * @property Grid\Column|Collection username
     * @property Grid\Column|Collection password
     * @property Grid\Column|Collection avatar
     * @property Grid\Column|Collection remember_token
     * @property Grid\Column|Collection userId
     * @property Grid\Column|Collection role
     * @property Grid\Column|Collection bot_id
     * @property Grid\Column|Collection user_data
     * @property Grid\Column|Collection appellation
     * @property Grid\Column|Collection token
     * @property Grid\Column|Collection tail_content
     * @property Grid\Column|Collection tail_content_button
     * @property Grid\Column|Collection review_num
     * @property Grid\Column|Collection status
     * @property Grid\Column|Collection webhook_status
     * @property Grid\Column|Collection is_auto_keyword
     * @property Grid\Column|Collection keyword
     * @property Grid\Column|Collection lexicon
     * @property Grid\Column|Collection submission_timeout
     * @property Grid\Column|Collection channel_id
     * @property Grid\Column|Collection group
     * @property Grid\Column|Collection uuid
     * @property Grid\Column|Collection connection
     * @property Grid\Column|Collection queue
     * @property Grid\Column|Collection payload
     * @property Grid\Column|Collection exception
     * @property Grid\Column|Collection failed_at
     * @property Grid\Column|Collection message_id
     * @property Grid\Column|Collection text
     * @property Grid\Column|Collection posted_by
     * @property Grid\Column|Collection posted_by_id
     * @property Grid\Column|Collection is_anonymous
     * @property Grid\Column|Collection data
     * @property Grid\Column|Collection appendix
     * @property Grid\Column|Collection approved
     * @property Grid\Column|Collection reject
     * @property Grid\Column|Collection one_approved
     * @property Grid\Column|Collection one_reject
     * @property Grid\Column|Collection tokenable_type
     * @property Grid\Column|Collection tokenable_id
     * @property Grid\Column|Collection abilities
     * @property Grid\Column|Collection last_used_at
     * @property Grid\Column|Collection expires_at
     * @property Grid\Column|Collection review_group_id
     * @property Grid\Column|Collection auditor_id
     * @property Grid\Column|Collection group_id
     * @property Grid\Column|Collection email
     * @property Grid\Column|Collection email_verified_at
     *
     * @method Grid\Column|Collection id(string $label = null)
     * @method Grid\Column|Collection name(string $label = null)
     * @method Grid\Column|Collection type(string $label = null)
     * @method Grid\Column|Collection version(string $label = null)
     * @method Grid\Column|Collection detail(string $label = null)
     * @method Grid\Column|Collection created_at(string $label = null)
     * @method Grid\Column|Collection updated_at(string $label = null)
     * @method Grid\Column|Collection is_enabled(string $label = null)
     * @method Grid\Column|Collection parent_id(string $label = null)
     * @method Grid\Column|Collection order(string $label = null)
     * @method Grid\Column|Collection icon(string $label = null)
     * @method Grid\Column|Collection uri(string $label = null)
     * @method Grid\Column|Collection extension(string $label = null)
     * @method Grid\Column|Collection permission_id(string $label = null)
     * @method Grid\Column|Collection menu_id(string $label = null)
     * @method Grid\Column|Collection slug(string $label = null)
     * @method Grid\Column|Collection http_method(string $label = null)
     * @method Grid\Column|Collection http_path(string $label = null)
     * @method Grid\Column|Collection role_id(string $label = null)
     * @method Grid\Column|Collection user_id(string $label = null)
     * @method Grid\Column|Collection value(string $label = null)
     * @method Grid\Column|Collection username(string $label = null)
     * @method Grid\Column|Collection password(string $label = null)
     * @method Grid\Column|Collection avatar(string $label = null)
     * @method Grid\Column|Collection remember_token(string $label = null)
     * @method Grid\Column|Collection userId(string $label = null)
     * @method Grid\Column|Collection role(string $label = null)
     * @method Grid\Column|Collection bot_id(string $label = null)
     * @method Grid\Column|Collection user_data(string $label = null)
     * @method Grid\Column|Collection appellation(string $label = null)
     * @method Grid\Column|Collection token(string $label = null)
     * @method Grid\Column|Collection tail_content(string $label = null)
     * @method Grid\Column|Collection tail_content_button(string $label = null)
     * @method Grid\Column|Collection review_num(string $label = null)
     * @method Grid\Column|Collection status(string $label = null)
     * @method Grid\Column|Collection webhook_status(string $label = null)
     * @method Grid\Column|Collection is_auto_keyword(string $label = null)
     * @method Grid\Column|Collection keyword(string $label = null)
     * @method Grid\Column|Collection lexicon(string $label = null)
     * @method Grid\Column|Collection submission_timeout(string $label = null)
     * @method Grid\Column|Collection channel_id(string $label = null)
     * @method Grid\Column|Collection group(string $label = null)
     * @method Grid\Column|Collection uuid(string $label = null)
     * @method Grid\Column|Collection connection(string $label = null)
     * @method Grid\Column|Collection queue(string $label = null)
     * @method Grid\Column|Collection payload(string $label = null)
     * @method Grid\Column|Collection exception(string $label = null)
     * @method Grid\Column|Collection failed_at(string $label = null)
     * @method Grid\Column|Collection message_id(string $label = null)
     * @method Grid\Column|Collection text(string $label = null)
     * @method Grid\Column|Collection posted_by(string $label = null)
     * @method Grid\Column|Collection posted_by_id(string $label = null)
     * @method Grid\Column|Collection is_anonymous(string $label = null)
     * @method Grid\Column|Collection data(string $label = null)
     * @method Grid\Column|Collection appendix(string $label = null)
     * @method Grid\Column|Collection approved(string $label = null)
     * @method Grid\Column|Collection reject(string $label = null)
     * @method Grid\Column|Collection one_approved(string $label = null)
     * @method Grid\Column|Collection one_reject(string $label = null)
     * @method Grid\Column|Collection tokenable_type(string $label = null)
     * @method Grid\Column|Collection tokenable_id(string $label = null)
     * @method Grid\Column|Collection abilities(string $label = null)
     * @method Grid\Column|Collection last_used_at(string $label = null)
     * @method Grid\Column|Collection expires_at(string $label = null)
     * @method Grid\Column|Collection review_group_id(string $label = null)
     * @method Grid\Column|Collection auditor_id(string $label = null)
     * @method Grid\Column|Collection group_id(string $label = null)
     * @method Grid\Column|Collection email(string $label = null)
     * @method Grid\Column|Collection email_verified_at(string $label = null)
     */
    class Grid {}

    class MiniGrid extends Grid {}

    /**
     * @property Show\Field|Collection id
     * @property Show\Field|Collection name
     * @property Show\Field|Collection type
     * @property Show\Field|Collection version
     * @property Show\Field|Collection detail
     * @property Show\Field|Collection created_at
     * @property Show\Field|Collection updated_at
     * @property Show\Field|Collection is_enabled
     * @property Show\Field|Collection parent_id
     * @property Show\Field|Collection order
     * @property Show\Field|Collection icon
     * @property Show\Field|Collection uri
     * @property Show\Field|Collection extension
     * @property Show\Field|Collection permission_id
     * @property Show\Field|Collection menu_id
     * @property Show\Field|Collection slug
     * @property Show\Field|Collection http_method
     * @property Show\Field|Collection http_path
     * @property Show\Field|Collection role_id
     * @property Show\Field|Collection user_id
     * @property Show\Field|Collection value
     * @property Show\Field|Collection username
     * @property Show\Field|Collection password
     * @property Show\Field|Collection avatar
     * @property Show\Field|Collection remember_token
     * @property Show\Field|Collection userId
     * @property Show\Field|Collection role
     * @property Show\Field|Collection bot_id
     * @property Show\Field|Collection user_data
     * @property Show\Field|Collection appellation
     * @property Show\Field|Collection token
     * @property Show\Field|Collection tail_content
     * @property Show\Field|Collection tail_content_button
     * @property Show\Field|Collection review_num
     * @property Show\Field|Collection status
     * @property Show\Field|Collection webhook_status
     * @property Show\Field|Collection is_auto_keyword
     * @property Show\Field|Collection keyword
     * @property Show\Field|Collection lexicon
     * @property Show\Field|Collection submission_timeout
     * @property Show\Field|Collection channel_id
     * @property Show\Field|Collection group
     * @property Show\Field|Collection uuid
     * @property Show\Field|Collection connection
     * @property Show\Field|Collection queue
     * @property Show\Field|Collection payload
     * @property Show\Field|Collection exception
     * @property Show\Field|Collection failed_at
     * @property Show\Field|Collection message_id
     * @property Show\Field|Collection text
     * @property Show\Field|Collection posted_by
     * @property Show\Field|Collection posted_by_id
     * @property Show\Field|Collection is_anonymous
     * @property Show\Field|Collection data
     * @property Show\Field|Collection appendix
     * @property Show\Field|Collection approved
     * @property Show\Field|Collection reject
     * @property Show\Field|Collection one_approved
     * @property Show\Field|Collection one_reject
     * @property Show\Field|Collection tokenable_type
     * @property Show\Field|Collection tokenable_id
     * @property Show\Field|Collection abilities
     * @property Show\Field|Collection last_used_at
     * @property Show\Field|Collection expires_at
     * @property Show\Field|Collection review_group_id
     * @property Show\Field|Collection auditor_id
     * @property Show\Field|Collection group_id
     * @property Show\Field|Collection email
     * @property Show\Field|Collection email_verified_at
     *
     * @method Show\Field|Collection id(string $label = null)
     * @method Show\Field|Collection name(string $label = null)
     * @method Show\Field|Collection type(string $label = null)
     * @method Show\Field|Collection version(string $label = null)
     * @method Show\Field|Collection detail(string $label = null)
     * @method Show\Field|Collection created_at(string $label = null)
     * @method Show\Field|Collection updated_at(string $label = null)
     * @method Show\Field|Collection is_enabled(string $label = null)
     * @method Show\Field|Collection parent_id(string $label = null)
     * @method Show\Field|Collection order(string $label = null)
     * @method Show\Field|Collection icon(string $label = null)
     * @method Show\Field|Collection uri(string $label = null)
     * @method Show\Field|Collection extension(string $label = null)
     * @method Show\Field|Collection permission_id(string $label = null)
     * @method Show\Field|Collection menu_id(string $label = null)
     * @method Show\Field|Collection slug(string $label = null)
     * @method Show\Field|Collection http_method(string $label = null)
     * @method Show\Field|Collection http_path(string $label = null)
     * @method Show\Field|Collection role_id(string $label = null)
     * @method Show\Field|Collection user_id(string $label = null)
     * @method Show\Field|Collection value(string $label = null)
     * @method Show\Field|Collection username(string $label = null)
     * @method Show\Field|Collection password(string $label = null)
     * @method Show\Field|Collection avatar(string $label = null)
     * @method Show\Field|Collection remember_token(string $label = null)
     * @method Show\Field|Collection userId(string $label = null)
     * @method Show\Field|Collection role(string $label = null)
     * @method Show\Field|Collection bot_id(string $label = null)
     * @method Show\Field|Collection user_data(string $label = null)
     * @method Show\Field|Collection appellation(string $label = null)
     * @method Show\Field|Collection token(string $label = null)
     * @method Show\Field|Collection tail_content(string $label = null)
     * @method Show\Field|Collection tail_content_button(string $label = null)
     * @method Show\Field|Collection review_num(string $label = null)
     * @method Show\Field|Collection status(string $label = null)
     * @method Show\Field|Collection webhook_status(string $label = null)
     * @method Show\Field|Collection is_auto_keyword(string $label = null)
     * @method Show\Field|Collection keyword(string $label = null)
     * @method Show\Field|Collection lexicon(string $label = null)
     * @method Show\Field|Collection submission_timeout(string $label = null)
     * @method Show\Field|Collection channel_id(string $label = null)
     * @method Show\Field|Collection group(string $label = null)
     * @method Show\Field|Collection uuid(string $label = null)
     * @method Show\Field|Collection connection(string $label = null)
     * @method Show\Field|Collection queue(string $label = null)
     * @method Show\Field|Collection payload(string $label = null)
     * @method Show\Field|Collection exception(string $label = null)
     * @method Show\Field|Collection failed_at(string $label = null)
     * @method Show\Field|Collection message_id(string $label = null)
     * @method Show\Field|Collection text(string $label = null)
     * @method Show\Field|Collection posted_by(string $label = null)
     * @method Show\Field|Collection posted_by_id(string $label = null)
     * @method Show\Field|Collection is_anonymous(string $label = null)
     * @method Show\Field|Collection data(string $label = null)
     * @method Show\Field|Collection appendix(string $label = null)
     * @method Show\Field|Collection approved(string $label = null)
     * @method Show\Field|Collection reject(string $label = null)
     * @method Show\Field|Collection one_approved(string $label = null)
     * @method Show\Field|Collection one_reject(string $label = null)
     * @method Show\Field|Collection tokenable_type(string $label = null)
     * @method Show\Field|Collection tokenable_id(string $label = null)
     * @method Show\Field|Collection abilities(string $label = null)
     * @method Show\Field|Collection last_used_at(string $label = null)
     * @method Show\Field|Collection expires_at(string $label = null)
     * @method Show\Field|Collection review_group_id(string $label = null)
     * @method Show\Field|Collection auditor_id(string $label = null)
     * @method Show\Field|Collection group_id(string $label = null)
     * @method Show\Field|Collection email(string $label = null)
     * @method Show\Field|Collection email_verified_at(string $label = null)
     */
    class Show {}

    /**
     
     */
    class Form {}

}

namespace Dcat\Admin\Grid {
    /**
     
     */
    class Column {}

    /**
     
     */
    class Filter {}
}

namespace Dcat\Admin\Show {
    /**
     
     */
    class Field {}
}
