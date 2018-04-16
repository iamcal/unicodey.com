#!/bin/bash

ln -s /var/www/html/unicodey.com/site.conf /etc/apache2/sites-available/unicodey.com.conf
a2ensite unicodey.com
service apache2 reload
