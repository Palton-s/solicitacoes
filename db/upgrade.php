<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Database upgrade script for the Solicitações plugin.
 *
 * @package    local_solicitacoes
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_solicitacoes_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Upgrade para versão 2025111702 - Nova estrutura de campos
    if ($oldversion < 2025111702) {
        
        $table = new xmldb_table('local_solicitacoes');
        
        // Remover campos antigos se existirem
        $field_assunto = new xmldb_field('assunto');
        if ($dbman->field_exists($table, $field_assunto)) {
            $dbman->drop_field($table, $field_assunto);
        }
        
        $field_mensagem = new xmldb_field('mensagem');
        if ($dbman->field_exists($table, $field_mensagem)) {
            $dbman->drop_field($table, $field_mensagem);
        }
        
        // Adicionar novos campos
        $field_tipo_acao = new xmldb_field('tipo_acao', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'timemodified');
        if (!$dbman->field_exists($table, $field_tipo_acao)) {
            $dbman->add_field($table, $field_tipo_acao);
        }
        
        $field_curso_nome = new xmldb_field('curso_nome', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'tipo_acao');
        if (!$dbman->field_exists($table, $field_curso_nome)) {
            $dbman->add_field($table, $field_curso_nome);
        }
        
        $field_curso_id = new xmldb_field('curso_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'curso_nome');
        if (!$dbman->field_exists($table, $field_curso_id)) {
            $dbman->add_field($table, $field_curso_id);
        }
        
        $field_usuarios_nomes = new xmldb_field('usuarios_nomes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'curso_nome');
        if (!$dbman->field_exists($table, $field_usuarios_nomes)) {
            $dbman->add_field($table, $field_usuarios_nomes);
        }
        
        $field_papel = new xmldb_field('papel', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'usuarios_nomes');
        if (!$dbman->field_exists($table, $field_papel)) {
            $dbman->add_field($table, $field_papel);
        }
        
        $field_observacoes = new xmldb_field('observacoes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'papel');
        if (!$dbman->field_exists($table, $field_observacoes)) {
            $dbman->add_field($table, $field_observacoes);
        }
        
        // Adicionar índice para tipo_acao
        $index = new xmldb_index('tipo_acao_idx', XMLDB_INDEX_NOTUNIQUE, array('tipo_acao'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025111702, 'local', 'solicitacoes');
    }

    // Upgrade para versão 2025120901 - Reestruturação para modelo relacional
    if ($oldversion < 2025120901) {
        
        // 1. Criar tabela local_curso_solicitacoes
        $table = new xmldb_table('local_curso_solicitacoes');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('solicitacao_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('curso_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('solicitacao_id', XMLDB_KEY_FOREIGN, array('solicitacao_id'), 'local_solicitacoes', array('id'));
        $table->add_key('curso_id', XMLDB_KEY_FOREIGN, array('curso_id'), 'course', array('id'));
        
        $table->add_index('solicitacao_curso_idx', XMLDB_INDEX_NOTUNIQUE, array('solicitacao_id', 'curso_id'));
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // 2. Criar tabela local_usuarios_solicitacoes
        $table = new xmldb_table('local_usuarios_solicitacoes');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('solicitacao_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usuario_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('solicitacao_id', XMLDB_KEY_FOREIGN, array('solicitacao_id'), 'local_solicitacoes', array('id'));
        $table->add_key('usuario_id', XMLDB_KEY_FOREIGN, array('usuario_id'), 'user', array('id'));
        
        $table->add_index('solicitacao_usuario_idx', XMLDB_INDEX_NOTUNIQUE, array('solicitacao_id', 'usuario_id'));
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // 3. Migrar dados existentes
        $solicitacoes = $DB->get_records('local_solicitacoes');
        
        foreach ($solicitacoes as $solicitacao) {
            $timecreated = time();
            
            // Migrar curso_id para a nova tabela
            if (!empty($solicitacao->curso_id)) {
                $curso_record = new stdClass();
                $curso_record->solicitacao_id = $solicitacao->id;
                $curso_record->curso_id = $solicitacao->curso_id;
                $curso_record->timecreated = $timecreated;
                $DB->insert_record('local_curso_solicitacoes', $curso_record);
            }
            
            // Migrar usuarios_nomes para a nova tabela (tentar extrair IDs se possível)
            if (!empty($solicitacao->usuarios_nomes)) {
                // Tentar extrair IDs dos usuários do formato "Nome (username) - email"
                $linhas = explode("\n", $solicitacao->usuarios_nomes);
                foreach ($linhas as $linha) {
                    $linha = trim($linha);
                    if (!empty($linha)) {
                        // Tentar extrair username entre parênteses
                        if (preg_match('/\(([^)]+)\)/', $linha, $matches)) {
                            $username = $matches[1];
                            $user = $DB->get_record('user', array('username' => $username), 'id');
                            if ($user) {
                                $usuario_record = new stdClass();
                                $usuario_record->solicitacao_id = $solicitacao->id;
                                $usuario_record->usuario_id = $user->id;
                                $usuario_record->timecreated = $timecreated;
                                $DB->insert_record('local_usuarios_solicitacoes', $usuario_record);
                            }
                        }
                    }
                }
            }
        }
        
        // 4. Remover campos antigos da tabela principal
        $table = new xmldb_table('local_solicitacoes');
        
        $field = new xmldb_field('curso_nome');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        $field = new xmldb_field('curso_id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        $field = new xmldb_field('usuarios_nomes');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2025120901, 'local', 'solicitacoes');
    }

    // Upgrade para versão 2026012515 - Adicionar campo motivo_negacao
    if ($oldversion < 2026012515) {
        $table = new xmldb_table('local_solicitacoes');
        
        $field = new xmldb_field('motivo_negacao', XMLDB_TYPE_TEXT, null, null, null, null, null, 'adminid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2026012515, 'local', 'solicitacoes');
    }

    return true;
}