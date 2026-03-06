<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins',
        new admin_externalpage(
            'local_solicitacoes_settings',
            get_string('pluginname', 'local_solicitacoes'),
            new moodle_url('/local/solicitacoes/configuracoes.php'),
            'moodle/site:config'
        )
    );

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