#!/bin/bash

while true; do { echo -e 'HTTP/1.1 404 Not Found\r\n';  echo -n 'hallo'; } | nc -l 1112; done

