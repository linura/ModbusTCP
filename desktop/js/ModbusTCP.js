
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

$('#bt_stopModbusTCPDemon').on('click', function() {
	stopModbusTCPDemon();
});

$('#bt_startModbusTCPDemon').on('click', function() {
	startModbusTCPDemon();
});

function stopModbusTCPDemon() {
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // methode de transmission des données au fichier php
		url: "plugins/ModbusTCP/core/ajax/ModbusTCP.ajax.php", // url du fichier php
		data: {
			action: "deamon_stop",
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function(data) { // si l'appel a bien fonctionné
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alert').showAlert({message: 'Le démon a été correctement arreté', level: 'success'});
		}
	});
}

function startModbusTCPDemon() {
	$.ajax({// fonction permettant de faire de l'ajax
		type: "POST", // methode de transmission des données au fichier php
		url: "plugins/ModbusTCP/core/ajax/ModbusTCP.ajax.php", // url du fichier php
		data: {
			action: "deamon_start",
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function(data) { // si l'appel a bien fonctionné
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alert').showAlert({message: 'Le démon a été correctement lancé', level: 'success'});
		}
	});
}

$('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
	if ($(this).value() == 'action') {
		$(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=request]').show();
	} else {
		$(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=request]').hide();
    }
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd").delegate(".listEquipementInfo", 'click', function() {
	var el = $(this);
	jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
		var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=request]');
		calcul.atCaret('insert', result.human);
	});
});
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr +=	'<td>';
	tr +=		'<input class="cmdAttr form-control input-sm" data-l1key="id"  style="display : none;">';
	tr +=		'<div class="row">';
	tr +=			'<div class="col-sm-6">';
	tr +=				'<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 150px;">';
	tr +=			'</div>';
	tr +=		'</div>';
	tr +=		'<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="La valeur de la commande vaut par défaut la commande">';
	tr +=			'<option value="">Aucune</option>';
	tr +=		'</select>';
	tr +=	'</td>';
	tr +=	'<td class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType();
	tr +=		'<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr +=	'</td>';
	tr +=	'<td>';
	tr +=		'<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="location">';
	tr +=	'</td>';
	tr +=	'<td >'
	tr +=		'<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" />';
	tr +=	'</td>';
	tr +=	'<td>';
	tr +=		'<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
	tr +=		'<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
	tr +=	'</td>';
	tr +=	'<td>';
	tr +=		'<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite"  style="width : 100px;" placeholder="Unité" title="{{Unité}}">';
	tr +=		'<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 100px;"> ';
	tr +=		'<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 100px;">';
	tr +=	'</td>';
	tr +=	'<td>';
	if (is_numeric(_cmd.id)) {
		tr +=	'<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		tr +=	'<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
	tr +=		'<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
	tr +=	'</td>';
	tr +='</tr>';

	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));

	var tr = $('#table_cmd tbody tr:last');
	jeedom.eqLogic.builSelectCmd({
		id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
		filter: {type: 'info'},
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result);
			tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
			tr.setValues(_cmd, '.cmdAttr');
			jeedom.cmd.changeType(tr, init(_cmd.subType));
			initTooltips();
		}
	});
}