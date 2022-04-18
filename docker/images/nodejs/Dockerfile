ARG NODEJS_VERSION=lts-alpine
FROM node:${NODEJS_VERSION}

WORKDIR "/var/www"

RUN npm install --force --global yarn

CMD ["yarn","install"]