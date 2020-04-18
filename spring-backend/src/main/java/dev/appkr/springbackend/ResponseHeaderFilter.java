package dev.appkr.springbackend;

import brave.Tracer;
import brave.propagation.TraceContext;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Component;

import javax.servlet.*;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.util.HashMap;

@Component
public class ResponseHeaderFilter implements Filter {

    private final Logger log = LoggerFactory.getLogger(getClass());
    private final Tracer tracer;

    public ResponseHeaderFilter(Tracer tracer) {
        this.tracer = tracer;
    }

    @Override
    public void doFilter(ServletRequest request, ServletResponse response, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest req = (HttpServletRequest) request;
        log.info("request header from frontend {}", new HashMap<String, String>() {{
            put("x-b3-traceid", req.getHeader("x-b3-traceid"));
            put("x-b3-spanid", req.getHeader("x-b3-spanid"));
            put("x-b3-parentspanid", req.getHeader("x-b3-parentspanid"));
            put("x-b3-sampled", req.getHeader("x-b3-sampled"));
        }});

        HttpServletResponse res = (HttpServletResponse) response;
        TraceContext context = tracer.currentSpan().context();
        res.addHeader("x-b3-traceid", context.traceIdString());
        res.addHeader("x-b3-spanid", context.spanIdString());
        res.addHeader("x-b3-parentspanid", context.parentIdString());
        res.addHeader("x-b3-sampled", context.sampled() ? "1" : "0");

        chain.doFilter(request, response);
    }
}
