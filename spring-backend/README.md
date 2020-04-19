## Inter-working with a non-php framework

Run the backend

```bash
~/zipkin-php-example/spring-backend $ ./gradlew clean bootRun
# 2020-04-18 22:20:00.000  INFO [spring-backend,,,] 48428 --- [           main] o.s.b.w.embedded.tomcat.TomcatWebServer  : Tomcat started on port(s): 8001
```

Run the frontend

```bash
~/zipkin-php-example $ php -S localhost:8000
```

Test

```bash
$ curl -s -i \
        -H "X-B3-TraceId: d4ca90093540675a" \
        -H "X-B3-SpanId: d4ca90093540675a" \
        http://localhost:8000/frontend

# HTTP/1.0 200 OK
# Host: localhost:8000
# Date: Sat, 18 Apr 2020 13:23:04 +0000
# Connection: close
# X-Powered-By: PHP/7.1.33
# x-b3-traceid: d4ca90093540675a
# x-b3-spanid: f9dbf6159795ae1e
# x-b3-parentspanid: eb9d10fab22a14e1
# x-b3-sampled: 0
# x-b3-flags: 0
# Cache-Control: no-cache, private
# Date: Sat, 18 Apr 2020 13:23:04 GMT
# Content-Type: application/json
# 
# {"foo":"bar"}
```

```bash
# Frontend logs (timezone:UTC) 
[2020-04-18 13:23:04] frontend.INFO: request received [] {"traceId":"d4ca90093540675a","spanId":"ac508ea0fadc7c41","parentSpanId":"d4ca90093540675a"}
[2020-04-18 13:23:04] frontend.INFO: calling backend [] {"traceId":"d4ca90093540675a","spanId":"eb9d10fab22a14e1","parentSpanId":"ac508ea0fadc7c41"}
[2020-04-18 13:23:04] frontend.INFO: response received from backend [] {"traceId":"d4ca90093540675a","spanId":"f9dbf6159795ae1e","parentSpanId":"eb9d10fab22a14e1"}

# Backend logs (timezone:Asia/Seoul)
2020-04-18 22:23:04.084  INFO [spring-backend,d4ca90093540675a,eb9d10fab22a14e1,false] 4225 --- [nio-8001-exec-3] d.a.springbackend.ResponseHeaderFilter   : request header from frontend {x-b3-parentspanid=ac508ea0fadc7c41, x-b3-traceid=d4ca90093540675a, x-b3-spanid=eb9d10fab22a14e1, x-b3-sampled=null}
2020-04-18 22:23:04.088  INFO [spring-backend,d4ca90093540675a,eb9d10fab22a14e1,false] 4225 --- [nio-8001-exec-3] ication$$EnhancerBySpringCGLIB$$29ae1b4d : request received
```

```bash
#                           {traceId         , spanId          , message}
curl request                {d4ca90093540675a, d4ca90093540675a, } 
    -> frontend             {d4ca90093540675a, ac508ea0fadc7c41, request received}
        -> guzzle request   {d4ca90093540675a, eb9d10fab22a14e1, calling backend}
            -> spring filter{d4ca90093540675a, eb9d10fab22a14e1, request header from frontend}
            -> spring contrl{d4ca90093540675a, eb9d10fab22a14e1, request received}
        <- guzzle response  {d4ca90093540675a, f9dbf6159795ae1e, response received from backend}     
    <- frontend response    {d4ca90093540675a, f9dbf6159795ae1e, }
```
