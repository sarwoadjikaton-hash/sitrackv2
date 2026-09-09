FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:1.27-alpine
COPY --from=assets /app/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

FROM nginx:1.27-alpine
COPY --from=assets /app/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
RUN mkdir -p /var/www/html/storage/app/public \
    && ln -sfn ../storage/app/public /var/www/html/public/storage