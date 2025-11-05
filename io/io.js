import {Server} from "socket.io"
import {createAdapter} from "@socket.io/redis-adapter"
import {createClient} from "redis"
import axios from "axios"

const io = new Server();

const pubClient = createClient({
    socket: {
        host: 'redis'
    }
});
const subClient = pubClient.duplicate();

const getToken = (socket) => {
    if(socket.handshake && socket.handshake.auth && socket.handshake.auth.token){
        return socket.handshake.auth.token
    }
    if(socket.handshake && socket.handshake.headers && socket.handshake.headers.token){
        return socket.handshake.headers.token
    }
    return false
}

Promise.all([pubClient.connect(), subClient.connect()]).then(() => {
    io.adapter(createAdapter(pubClient, subClient));
    subClient.pSubscribe('laravel*', (message, channel) => {
        const json = JSON.parse(message)
        const room = channel.replace('laravel_database_private-', '').replace('laravel_database_', '');
        io.to(room).emit(json.event, json.data)
    })
    io.use((socket, next) => {
        const token = getToken(socket)
        if(!token) {
            next(new Error('Token required'));
        }
        axios.get('https://web/api/channels', {
            headers: {
                'Authorization': 'Bearer ' + token,
            }
        }).then((response) => {
            for (let i in response.data) {
                socket.join(response.data[i])
            }
            next()
        }).catch(() => {
            next(new Error('Authentication failed'));
        })

    }).on('connection', (socket) => {
        console.log('new user connected')
    })
    io.listen(3000);
    console.log('io server run on port 3000')
});


