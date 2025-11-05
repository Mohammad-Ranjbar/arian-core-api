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
    console.log(1)
    io.adapter(createAdapter(pubClient, subClient));
    subClient.pSubscribe('laravel*', (message, channel) => {
        const json = JSON.parse(message)
        const room = channel.replace('laravel_database_private-', '').replace('laravel_database_', '');
        io.to(room).emit(json.event, json.data)
        console.log(2)
    })
    console.log(3)
    io.use((socket, next) => {
        console.log(4)
        const token = getToken(socket)
        console.log('token:',token)
        if(!token) {
            next(new Error('Token required'));
            console.log(5)
        }
        axios.get('https://ariancore.arian-capital.ir/api/channels', {
            headers: {
                'Authorization': 'Bearer ' + token,
            }
        }).then((response) => {
            console.log(6)
            for (let i in response.data) {
                socket.join(response.data[i])
            }
            next()
        }).catch((e) => {
            console.log(7)
            console.log(e)
            next(new Error('Authentication failed'));
        })

    }).on('connection', (socket) => {
        console.log(8)
        console.log('new user connected')
    })
    io.listen(3000);
    console.log('io server run on port 3000')
});


