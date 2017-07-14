FROM nginx

RUN apt-get update && apt-get -y install openssl \
    && openssl req -x509 -sha256 -newkey rsa:2048 -keyout /etc/ssl/private/cert.key \
    -out /etc/ssl/private/cert.pem -days 1024 -nodes -subj '/CN=localhost'

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
