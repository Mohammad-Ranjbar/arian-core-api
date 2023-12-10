import {Server} from "socket.io"
import {createAdapter} from "@socket.io/redis-adapter"
import {createClient} from "redis"
import axios from "axios"

const io = new Server();
const pubClient = createClient();
const subClient = pubClient.duplicate();


Promise.all([pubClient.connect(), subClient.connect()]).then(() => {
    io.adapter(createAdapter(pubClient, subClient));
    subClient.pSubscribe('laravel*', (message, channel) => {
        const json = JSON.parse(message)
        const room = channel.replace('laravel_database_private-', '').replace('laravel_database_', '');
        io.to(room).emit(json.event, json.data)
    })
    io.use((socket, next) => {
        if(socket.handshake && socket.handshake.auth && socket.handshake.auth.token){
            axios.get('http://localhost/api/channels', {
                headers: {
                    'Authorization': 'Bearer ' + socket.handshake.auth.token,
                }
            }).then((response) => {
                for (let i in response.data) {
                    socket.join(response.data[i])
                }
                next()
            }).catch(() => {
                next(new Error('Authentication failed'));
            })
        }else {
            next(new Error('Authentication failed'));
        }
    }).on('connection', (socket) => {
        console.log('new user connected')
    })
    io.listen(6060);
});


