#!/bin/bash
service nginx stop
cp /home/site/wwwroot/config/nginx.conf /etc/nginx/nginx.conf
service nginx start
