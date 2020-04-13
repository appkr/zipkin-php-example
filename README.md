## zipkin-php-exmaple

Clone
```bash
$ git clone git@github.com:appkr/zipkin-php-example.git
```

Run zipkin and local web server
```bash
$ docker run -p 9411:9411 -d openzipkin/zipkin

# For backend
$ php -S localhost:8001

# For frontend
$ php -S localhost:8000
```

Backend test
```bash
$ curl -s -i http://localhost:8001/backend.php

# HTTP/1.0 200 OK
# Host: localhost:8001
# Date: Mon, 13 Apr 2020 15:29:18 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: 7ee50161650c4ec2
# x-b3-spanid: 7ee50161650c4ec2
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Mon, 13 Apr 2020 15:29:18 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}

$ curl -s -i \
    -H "X-B3-TraceId: d4ca90093540675a" \
    -H "X-B3-SpanId: d4ca90093540675a" \
    -H "X-B3-Sampled: 1" \
    -H "X-B3-Flags: 0" \
    http://localhost:8001/backend.php

# HTTP/1.0 200 OK
# Host: localhost:8001
# Date: Mon, 13 Apr 2020 15:24:22 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: d4ca90093540675a
# x-b3-spanid: 97def06b8aae6dda
# x-b3-parentspanid: d4ca90093540675a
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Mon, 13 Apr 2020 15:24:22 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}
```

