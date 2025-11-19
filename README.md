#how to run container
docker compose up -d

#check supervisor
docker compose exec web.app supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status