## zipkin-php-exmaple

Clone
```bash
$ git clone git@github.com:appkr/zipkin-php-example.git
$ cd zipkin-php-example
~/zipkin-php-example $ composer install 
```

Run zipkin and local web server
```bash
~/zipkin-php-example $ docker run -p 9411:9411 -d openzipkin/zipkin

# For backend
~/zipkin-php-example $ php -S localhost:8001

# For frontend
~/zipkin-php-example $ php -S localhost:8000
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
```

Frontend test
```bash
$ curl -s -i http://localhost:8000/frontend.php

# HTTP/1.0 200 OK
# Host: localhost:8000
# Date: Mon, 13 Apr 2020 15:55:30 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: 13325bd5dd9f355c
# x-b3-spanid: 13325bd5dd9f355c
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Mon, 13 Apr 2020 15:55:30 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}
```

```bash
$ tail -f logs/app.log
[2020-04-13 15:55:30] backend.INFO: Response Headers {"x-b3-traceid":"13325bd5dd9f355c","x-b3-spanid":"26f672f1624931c2","x-b3-parentspanid":"25d92536c9df7a22","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:55:30] frontend.INFO: Current Context {"x-b3-traceid":"13325bd5dd9f355c","x-b3-spanid":"13325bd5dd9f355c","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:55:30] frontend.INFO: Child Context {"x-b3-traceid":"13325bd5dd9f355c","x-b3-spanid":"25d92536c9df7a22","x-b3-parentspanid":"13325bd5dd9f355c","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:55:30] frontend.INFO: Response from Backend {"header":{"Host":"localhost:8001","Date":"Mon, 13 Apr 2020 15:55:30 +0000","Connection":"close","X-Powered-By":"PHP/7.1.33","x-b3-traceid":"13325bd5dd9f355c","x-b3-spanid":"26f672f1624931c2","x-b3-parentspanid":"25d92536c9df7a22","x-b3-sampled":"1","x-b3-flags":"0","Cache-Control":"no-cache, private","Content-Type":"application/json"},"body":{"foo":"bar"}} []
```

Frontend test with request headers
```bash
$ curl -s -i \
      -H "X-B3-TraceId: d4ca90093540675a" \
      -H "X-B3-SpanId: d4ca90093540675a" \
      -H "X-B3-Sampled: 1" \
      -H "X-B3-Flags: 0" \
      http://localhost:8000/frontend.php

# HTTP/1.0 200 OK
# Host: localhost:8000
# Date: Mon, 13 Apr 2020 15:59:21 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: d4ca90093540675a
# x-b3-spanid: 893dfa58f3b09fd5
# x-b3-parentspanid: d4ca90093540675a
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Mon, 13 Apr 2020 15:59:21 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}
```

```bash
~/zipkin-php-example $ tail -f logs/app.log
[2020-04-13 15:59:21] backend.INFO: Response Headers {"x-b3-traceid":"d4ca90093540675a","x-b3-spanid":"6593554f14328557","x-b3-parentspanid":"1a5b62d8bcd00807","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:59:21] frontend.INFO: Current Context {"x-b3-traceid":"d4ca90093540675a","x-b3-spanid":"893dfa58f3b09fd5","x-b3-parentspanid":"d4ca90093540675a","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:59:21] frontend.INFO: Child Context {"x-b3-traceid":"d4ca90093540675a","x-b3-spanid":"1a5b62d8bcd00807","x-b3-parentspanid":"893dfa58f3b09fd5","x-b3-sampled":"1","x-b3-flags":"0"} []
[2020-04-13 15:59:21] frontend.INFO: Response from Backend {"header":{"Host":"localhost:8001","Date":"Mon, 13 Apr 2020 15:59:21 +0000","Connection":"close","X-Powered-By":"PHP/7.1.33","x-b3-traceid":"d4ca90093540675a","x-b3-spanid":"6593554f14328557","x-b3-parentspanid":"1a5b62d8bcd00807","x-b3-sampled":"1","x-b3-flags":"0","Cache-Control":"no-cache, private","Content-Type":"application/json"},"body":{"foo":"bar"}} []
```

```bash
            {traceId         , spanId          }
       curl {d4ca90093540675a, d4ca90093540675a} 
-> frontend {d4ca90093540675a, 893dfa58f3b09fd5}
-> guzzle   {d4ca90093540675a, 1a5b62d8bcd00807}
-> backend  {d4ca90093540675a, 6593554f14328557}
```