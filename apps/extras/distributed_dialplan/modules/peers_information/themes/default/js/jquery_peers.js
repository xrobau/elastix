var module_name = null;

$(document).ready(function() {
    module_name = 'peers_information';
    if (typeof getCurrentElastixModule == 'function')
        module_name = getCurrentElastixModule();

    // IMPLEMENTACIÓN VIEJA, QUITAR
    if ($(".resp").length > 0) {
        probe_conn_status();
        var refreshId = setInterval(probe_conn_status, 15000);
    }

    // Verificación inicial del estado de peers
    if ($("input:radio[name^='peerid']").length > 0) {
        var k = ['accept', 'reject', 'connect', 'disconnect'];
        for (var i in k) {
            buttonActionSetStatus($('input[name=peer_'+k[i]+'], button[name=peer_'+k[i]+']'), false);
        }
        checkPeerStatus();
        setInterval(checkPeerStatus, 15000);
    }

    $('input[type=radio][name=peerid]').click(function() {
        var tr_peer = $(this).parents('tr').first();
        trSetStatus(tr_peer);
    });
});

function trSetStatus(tr_peer)
{
    var k = ['accept', 'reject', 'connect', 'disconnect'];
    for (var i in k) {
        var btn = $('input[name=peer_'+k[i]+'], button[name=peer_'+k[i]+']');
        buttonActionSetStatus(btn, false);
        if (tr_peer.hasClass('peer-'+k[i]))
            buttonActionSetStatus(btn, true);
    }
}

function buttonActionSetStatus(btn, status)
{
    var parent_div = btn.parents('div').first();
    if (status) {
        btn.attr('disabled', false);
        parent_div
            .css('opacity', '')
            .css('filter', '');
    } else {
        btn.attr('disabled', true);
        parent_div
            .css('opacity', '0.25')
            .css('filter', 'alpha(opacity=25)');
    }
}

function checkPeerStatus()
{
    $.get('index.php', {
        menu:           module_name,
        action:         'peerstatus',
        rawmode:        'yes',
        'peerid[]':     $("input:radio[name^='peerid']").map(function() { return $(this).val(); }).get()
    }, function(response) {
        $('img.peer-loading').hide();

        for (var i in response.message) updateOnePeerRow(response.message[i]);
    });
}

function updateOnePeerRow(message)
{
    var radio_peer = $("input:radio[name^='peerid'][value='" +message.id+"']");
    var tr_peer = radio_peer.parents('tr').first();
    tr_peer.find('div.peer-company').text(message.company);
    var span_status_txt = tr_peer.find('span.peer-status-txt');

    // Esconder todos los controles, reactivar según el mensaje
    tr_peer.find('a.peer-view, a.peer-newrequest')
        .hide();

    // Operaciones permitidas sobre este peer
    var k = ['accept', 'reject', 'connect', 'disconnect'];
    for (var i in k) {
        tr_peer.removeClass('peer-'+k[i]);
    }
    for (var j in message.trclasses) {
        tr_peer.addClass(message.trclasses[j]);
    }
    if (radio_peer.is(':checked')) trSetStatus(tr_peer);

    tr_peer.find('span.peer-status').fadeOut('slow', function() {
        span_status_txt.text(message.status_txt);
        for (var j in message.ctlenable) tr_peer.find(message.ctlenable[j]).show();
        $(this).css('color', message.color);
        $(this).fadeIn('slow');
    });
}
