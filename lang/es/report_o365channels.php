<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     report_o365channels
 * @category    string
 * @copyright   2021 Enrique Castro @ULPGC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addteam'] = 'Crear Equipo';
$string['addgroup'] = 'Crear grupo Outlook';
$string['channel'] = 'Canal en Teams';
$string['channeladded'] = 'Nuevo canal añadido';
$string['channeldeleted'] = 'El canal se ha eliminado de Teams.';
$string['confirmunlink'] = 'Ha solicitado borrar el Canal asociado a este grupo "{$a}" en Teams. <br />
¿Desea proceder al borrado? <br />El grupo de moodle no se eliminará. ';
$string['confirmunlinkgroup'] = 'Ha solicitado borrar el Grupo asociado a este grupo "{$a}" en Outlook/Sharepoint. <br />
¿Desea proceder al borrado? <br />El grupo de moodle no se eliminará. ';
$string['error_delnonexisting'] = 'NO existe un objeto o365 asociado que borrar.';
$string['error_noaddexisting'] = 'Ya existe un ítem o365 asociado al grupo {$a}.';
$string['error_noitem'] = 'No se ha especificado un grupo.';
$string['eventchannelcreated'] = 'Añadido Canal Teams en curso.';
$string['eventchanneldeleted'] = 'Borrado un Canal Teams en curso.';
$string['eventchannelsynced'] = 'Actualizados miembros del canal';
$string['eventteamcreated'] = 'Añadido Teams al curso';
$string['eventteamsynced'] = 'Actualizados miembros del Teams';
$string['link_calendar'] = 'Calendario'; 
$string['link_conversations'] = 'Outlook'; 
$string['link_notebook'] = 'Notebook'; 
$string['link_onedrive'] = 'OneDrive'; 
$string['mail'] = 'Crear un grupo Outlook asociado al grupo de moodle';
$string['membersupdated'] = 'Participantes actualizados: añadidos {$a->added}/{$a->toadd} miembros y eliminados {$a->removed}/{$a->toremove} usuarios.';
$string['nocourseteam'] = 'No hay un Equipo de MS-Teams vinculado esta asignatura.';
$string['notavailable'] = 'Las conexión a o365 NO estás configurada o la sincronización de Equipos/Grupos esta deshabilitada.';
$string['notdone'] = 'Ha fallado la acción sobre el grupo; No se ha cambiado nada. Razón: {$a}.';
$string['noteam'] = 'Ha fallado la acción sobre el Equipo; No se ha creado nada.';
$string['o365channels:manage'] = 'Gestiona y actualiza Canales para grupos';
$string['o365channels:view'] = 'Ver la página de Grupos y Canales';
$string['pluginname'] = 'Canales Teams en o365';
$string['referencesdeleted'] = 'Eliminadas {$a} referencias a recursos o365 que no existen o han sido borrados.';
$string['resynch'] = 'Actualizar miembros del canal asociado desde el grupo';
$string['syncall'] = 'Actualizar todo (canales o grupos) ';
$string['syncallcourses'] = 'Actualizar todos los cursos';
$string['syncallcourses_desc'] = 'Si se marca, entonces TODOS los cursos con un objeto o365 asociado se incluirán en la actualización de usuarios, no solo los modificados desde la última ejecución.';
$string['synch'] = 'Crear un Canal privado asociado al grupo de moodle';
$string['syncteam'] = 'Actualizar Equipo';
$string['task_teamschannelsynch'] = 'Actualizar usuarios en Teams y Canales';
$string['teamschannelsynch'] = 'Sincronizar usuarios en Teams y Canales con moodle';
$string['unlink'] = 'Eliminar el Canal asociado al grupo de moodle';
$string['unlinkgroup'] = 'Eliminar el Grupo Outlook.';
$string['usergroup'] = 'Grupo Outlook';
$string['usergroupadded'] = 'Añadido nuevo grupo Outlook.';
$string['usergroupdeleted'] = 'El grupo Outlook se ha eliminado de o365.';
