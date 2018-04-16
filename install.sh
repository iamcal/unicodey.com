#!/bin/bash

ln -s /var/www/html/unicodey.com/site.conf /etc/apache2/sites-available/unicodey.com.conf
a2ensite unicodey.com
service apache2 reload

ln -s /home/ubuntu/emoji-data /var/www/html/unicodey.com/www/emoji-data
ln -s /home/ubuntu/js-emoji /var/www/html/unicodey.com/www/js-emoji
ln -s /home/ubuntu/php-emoji /var/www/html/unicodey.com/www/php-emoji


