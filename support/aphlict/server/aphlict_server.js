var net = require('net');
var http  = require('http');
var url = require('url');
var querystring = require('querystring');
var fs = require('fs');

// set up log file
logfile = fs.createWriteStream('/var/log/aphlict.log',
        { flags: 'a',
          encoding: null,
          mode: 0666 });
logfile.write('----- ' + (new Date()).toLocaleString() + ' -----\n');

function log(str) {
    console.log(str);
    logfile.write(str + '\n');
}


function getFlashPolicy() {
  return [
    '<?xml version="1.0"?>',
    '<!DOCTYPE cross-domain-policy SYSTEM ' +
      '"http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">',
    '<cross-domain-policy>',
    '<allow-access-from domain="*" to-ports="2600"/>',
    '</cross-domain-policy>'
  ].join('\n');
}

net.createServer(function(socket) {
  socket.on('data', function() {
    socket.write(getFlashPolicy() + '\0');
  });
}).listen(843);



function write_json(socket, data) {
  var serial = JSON.stringify(data);
  var length = Buffer.byteLength(serial, 'utf8');
  length = length.toString();
  while (length.length < 8) {
    length = '0' + length;
  }
  socket.write(length + serial);
}


var clients = {};
// According to the internet up to 2^53 can
// be stored in javascript, this is less than that
var MAX_ID = 9007199254740991;//2^53 -1

// If we get one connections per millisecond this will
// be fine as long as someone doesn't maintain a
// connection for longer than 6854793 years.  If
// you want to write something pretty be my guest

function generate_id() {
  if (typeof generate_id.current_id == 'undefined'
      || generate_id.current_id > MAX_ID) {
    generate_id.current_id = 0;
  }
  return generate_id.current_id++;
}

var send_server = net.createServer(function(socket) {
  client_id = generate_id();

  socket.on('connect', function() {
    clients[client_id] = socket;
    log(client_id + ': connected');
  });

  socket.on('close', function() {
    delete clients[client_id];
    log(client_id + ': closed');
  });
}).listen(2600);



var receive_server = http.createServer(function(request, response) {
  response.writeHead(200, {'Content-Type' : 'text/plain'});

  if (request.method == 'POST') { // Only pay attention to POST requests
    var body = '';

    request.on('data', function (data) {
      body += data;
    });

    request.on('end', function () {
      var data = querystring.parse(body);
      // I think this should be done on the PHP side...
      data.pathname = data.pathname.replace(/^\s+|\s+$/g, '');
      broadcast(data);
      log('notification: ' + JSON.stringify(data));
      response.end();
    });
  }
}).listen(22281, '127.0.0.1');

function broadcast(data) {
  for(var client_id in clients) {
    write_json(clients[client_id], data);
  }
}



// TODO Add admin interface to view server stats
// var status_server = http.createServer(function(request, response) {
//   response.writeHead(200, {'Content-Type' : 'text/plain'});
//   var stats = [];
//   stats.push('Number of Clients Connected: ' + num_connections);
//   stats.push('Next ID: ' + current_id);

//   response.end(stats.join('\n'));
// }).listen(22282, '127.0.0.1');

