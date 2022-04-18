ARG NGINX_VERSION=1.21.6-alpine
FROM nginx:${NGINX_VERSION}

COPY ./conf.d/default.conf /etc/nginx/conf.d
COPY ./nginx.conf /etc/nginx/nginx.conf

ARG APP_CODE_PATH=/var/www
RUN mkdir -p /var/www
RUN sed -i "s#root __NGINX_ROOT;#root $APP_CODE_PATH/public;#" /etc/nginx/conf.d/default.conf