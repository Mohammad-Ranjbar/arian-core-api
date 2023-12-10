
import Echo from 'laravel-echo';
import socketio from 'socket.io-client'
window.io = require('socket.io-client');

window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: '/socket.io',
    client: socketio,
});

