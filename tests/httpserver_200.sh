#!/bin/bash

while true; do { echo -e 'HTTP/1.1 200 OK\r\n';  echo -n 'hallo'; } | nc -l 1111 > http.result; done

