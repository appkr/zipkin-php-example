package dev.appkr.springbackend;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.HashMap;
import java.util.Map;

@SpringBootApplication
@RestController
public class SpringBackendApplication {

	private Logger log = LoggerFactory.getLogger(getClass());

	@GetMapping("/")
	public ResponseEntity<Map<String, String>> getFoo() {
		log.info("request received");
		Map<String, String> body = new HashMap<String, String>() {{
			put("foo", "bar");
		}};

		return ResponseEntity.ok(body);
	}

	public static void main(String[] args) {
		SpringApplication.run(SpringBackendApplication.class, args);
	}

}
