FROM nginx:alpine

RUN mkdir -p /etc/nginx/ssl

RUN apk update && apk add openssl

RUN openssl req -x509 -subj '/CN=cerebrate' -nodes -newkey rsa:4096 -keyout /etc/nginx/ssl/key.pem -out /etc/nginx/ssl/cert.pem -days 365

# copy actual codebase
COPY app /var/www/app