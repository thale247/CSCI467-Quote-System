#!/bin/bash
service nginx stop
cp /home/site/wwwroot/nginx.conf /etc/nginx/nginx.conf
service nginx start
