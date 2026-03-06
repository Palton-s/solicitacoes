<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once($CFG->libdir . '/coursecatlib.php');

    $settings = new admin_settingpage(
        'local_solicitacoes_settings',
        get_string('pluginname', 'local_solicitacoes')
    );

    if ($ADMIN->fulltree) {
        $categoryoptions = [0 => get_string('none')];
        $categoryoptions += core_course_category::make_categories_list();

        $settings->add(new admin_setting_configselect(
            'local_solicitacoes/hidden_course_category',
            get_string('hidden_course_category', 'local_solicitacoes'),
            get_string('hidden_course_category_desc', 'local_solicitacoes'),
            0,
            $categoryoptions
        ));
    }

    $ADMIN->add('localplugins', $settings);

    // Página principal de gerenciar solicitações
    $ADMIN->add('localplugins',
        new admin_externalpage(
            'local_solicitacoes_manage',
            get_string('list_title', 'local_solicitacoes'),
            new moodle_url('/local/solicitacoes/gerenciar.php'),
            'local/solicitacoes:manage'
        )
    );
}