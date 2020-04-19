## zipkin-php-exmaple

[zipkin-php's Offcial example can be found here](https://github.com/openzipkin/zipkin-php-example)

---

Clone
```bash
$ git clone git@github.com:appkr/zipkin-php-example.git
$ cd zipkin-php-example
~/zipkin-php-example $ composer install 
```

Run local web server (OPTIONAL, and zipkin server)
```bash
# The example uses b3-* tracing only, and does not use zipkin reporting
# If you want it to be enabled, uncomment corresponding line in \App\Tracer::initZipkin
# ~/zipkin-php-example $ docker run -p 9411:9411 -d openzipkin/zipkin

# For backend
~/zipkin-php-example $ php -S localhost:8001

# For frontend
~/zipkin-php-example $ php -S localhost:8000
```

Backend test
```bash
$ curl -s -i http://localhost:8001/backend

# HTTP/1.0 200 OK
# Host: localhost:8001
# Date: Fri, 17 Apr 2020 13:58:18 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: 45b1865a93c6db9c
# x-b3-spanid: 936f217141dff31a
# x-b3-parentspanid: 45b1865a93c6db9c
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Fri, 17 Apr 2020 13:58:18 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}
```

Frontend test
```bash
$ curl -s -i http://localhost:8000/frontend

# HTTP/1.0 200 OK
# Host: localhost:8000
# Date: Fri, 17 Apr 2020 13:58:59 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# Host: localhost:8001
# Date: Fri, 17 Apr 2020 13:58:59 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: 47af379a85465242
# x-b3-spanid: adb239bcaaef6ca4
# x-b3-parentspanid: eaf92c88cced67c6
# x-b3-sampled: 1
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Content-Type: application/json
# 
# {"foo":"bar"}
```

Frontend test with request headers
```bash
$ curl -s -i \
        -H "X-B3-TraceId: d4ca90093540675a" \
        -H "X-B3-SpanId: d4ca90093540675a" \
        http://localhost:8000/frontend

#  HTTP/1.0 200 OK
#  Host: localhost:8000
#  Date: Fri, 17 Apr 2020 15:38:04 +0000
#  Connection: close
#  X-Powered-By: PHP/7.1.33
#  x-b3-traceid: d4ca90093540675a
#  x-b3-spanid: 145631c0092db202
#  x-b3-parentspanid: bc7bf5e72caa10b8
#  x-b3-flags: 0
#  Cache-Control: no-cache, private
#  Date: Fri, 17 Apr 2020 15:38:04 GMT
#  Content-Type: application/json
#  
#  {"foo":"bar"}
```

```bash
~/zipkin-php-example $ tail -f logs/app.log

# [2020-04-17 15:38:04] frontend.INFO: request received [] {"traceId":"d4ca90093540675a","spanId":"6ea5c89f18962288","parentSpanId":"d4ca90093540675a"}
# [2020-04-17 15:38:04] frontend.INFO: calling backend [] {"traceId":"d4ca90093540675a","spanId":"c15d463189780fb4","parentSpanId":"6ea5c89f18962288"}
# [2020-04-17 15:38:04] backend.INFO: request received [] {"traceId":"d4ca90093540675a","spanId":"86617a8fb1363bc6","parentSpanId":"c15d463189780fb4"}
# [2020-04-17 15:38:04] backend.INFO: querying database [] {"traceId":"d4ca90093540675a","spanId":"bc7bf5e72caa10b8","parentSpanId":"86617a8fb1363bc6"}
# [2020-04-17 15:38:04] frontend.INFO: response received from backend [] {"traceId":"d4ca90093540675a","spanId":"145631c0092db202","parentSpanId":"bc7bf5e72caa10b8"}
```

```bash
#                           {traceId         , spanId          , message}
curl request                {d4ca90093540675a, d4ca90093540675a, } 
    -> frontend             {d4ca90093540675a, 6ea5c89f18962288, request received}
        -> guzzle request   {d4ca90093540675a, c15d463189780fb4, calling backend}
            -> backend      {d4ca90093540675a, 86617a8fb1363bc6, request received}
                -> db query {d4ca90093540675a, bc7bf5e72caa10b8, querying database}
        <- guzzle response  {d4ca90093540675a, 145631c0092db202, response received from backend}     
    <- frontend response    {d4ca90093540675a, 145631c0092db202, }
```

---

[Inter-working with a backend written in Java Spring Framework](https://github.com/appkr/zipkin-php-example/tree/master/spring-backend)