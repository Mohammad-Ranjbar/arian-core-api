import io from 'socket.io-client'
const socket = io ()
this.socket.on('connect', () => {
    console.log('im connected to socket io')
})