import io from 'socket.io-client'

const socket = io({
    auth: {
        token: localStorage.getItem('token')
    }
});

socket.on('connect', () => {
    console.log('im connected to socket io')
})