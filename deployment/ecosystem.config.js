const path = require('path');

module.exports = {
    apps: [{
        name: 'laravel-horizon',
        cwd: path.resolve(__dirname, '..'),
        script: 'artisan',
        args: 'horizon',
        interpreter: process.env.HORIZON_PHP_BINARY || 'php',
        exec_mode: 'fork',
        instances: 1,
        autorestart: true,
        watch: false,
        // Signal only the master; Horizon gracefully terminates its own children.
        treekill: false,
        // Milliseconds: allow the longest job and graceful worker shutdown to finish.
        kill_timeout: 1900000,
        restart_delay: 1000,
        time: true,
    }],
};
