<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Navbar;

/**
 * Dcat-admin - admin builder based on Laravel.
 *
 * @author jqh <https://github.com/jqhph>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 *
 * extend custom field:
 * Dcat\Admin\Form::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Column::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Filter::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 */
Admin::navbar(function (Navbar $navbar) {

    $navbar->right(<<<'HTML'
<button type="button" class="btn btn-primary" onclick="clearCache()">清除缓存</button>

<script>
function clearCache() {
    $.ajax({
        url: '/admin/cache/clear',
        type: 'POST',
        success: function (data) {
            if (data.status) {
                Dcat.success(data.message);
            } else {
                Dcat.error(data.message);
            }
        }
    });
}
</script>
HTML);
});
