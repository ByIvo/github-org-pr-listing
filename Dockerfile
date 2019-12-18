FROM php:7.1
COPY . /opt/project
WORKDIR /opt/project/public
CMD ["php", "-S", "0.0.0.0:9000", "index.php"]
