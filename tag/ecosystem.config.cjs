module.exports = {
  apps: [
    {
      name: 'tag2-queue',
      script: 'artisan',
      args: 'queue:work --tries=3 --backoff=5 --sleep=3 --max-jobs=500 --max-time=3600',
      cwd: '/var/www/tag2/tag',
      interpreter: 'php',
      autorestart: true,
      max_restarts: 10,
      restart_delay: 5000,
      max_memory_restart: '256M',
      // Variables NO secretas (seguras para git)
      env: {
        APP_ENV: 'production',
        APP_DEBUG: 'false',
      },
      error_file: '/var/www/tag2/tag/storage/logs/pm2-queue-error.log',
      out_file: '/var/www/tag2/tag/storage/logs/pm2-queue-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
    },
    {
      name: 'tag2-reverb',
      script: 'artisan',
      args: 'reverb:start --host=0.0.0.0 --port=8080',
      cwd: '/var/www/tag2/tag',
      interpreter: 'php',
      autorestart: true,
      max_restarts: 10,
      restart_delay: 5000,
      max_memory_restart: '256M',
      // Variables NO secretas (seguras para git)
      env: {
        APP_ENV: 'production',
        APP_DEBUG: 'false',
      },
      error_file: '/var/www/tag2/tag/storage/logs/pm2-reverb-error.log',
      out_file: '/var/www/tag2/tag/storage/logs/pm2-reverb-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
    },
  ],
};
