CREATE DATABASE drupal;
CREATE USER 'drupal'@'localhost' IDENTIFIED BY 'drupal';
GRANT ALL PRIVILEGES ON drupal.* to 'drupal'@'localhost';
FLUSH PRIVILEGES;
