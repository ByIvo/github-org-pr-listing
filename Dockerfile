FROM php:7.1
WORKDIR /opt/project/public
CMD ["php", "-S", "0.0.0.0:8080", "index.php"]
