<?php
/** 
 * HttpRequest - A simple PHP class using cURL for Ajax-like HTTP requests
 * by Hay Kranen <hay at bykr dot org>
 * Released under the MIT / X11 license
 * version 1.0
 *
 * Use:
 * $r = new HttpRequest($method, $url, $data, $args)
 * $method: "get" / "post"
 * $url: duh :)
 * $data: an array of values you want to send as query parameters or POST values
 * $args: array of possible extra arguments for the request 
 * 
 * Then, check for errors with $r->getError() and response with $r->getResponse()
 * eg with the public tweets from twitter
 * $r = new HttpRequest("get", "http://twitter.com/statuses/public_timeline.json");
 * if ($r->getError()) {
 *     echo "sorry, an error occured";
 * } else {
 *     // parse json and show tweets
 *     $tweets = json_decode($r->getResponse());
 *     var_dump($tweets);
 * }
 * 
 * == License (MIT/X11) ==
 * 
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */
class HttpRequest {
    private $method, $url, $data = false;
    private $error, $hasError = false, $response, $status;
    private $requestInfo, $curlError, $headers = array();

    // Default arguments
    private $args = array(
        "followRedirect" => true,
    );

    function __construct($method, $url, $data = false, $args = false) {
        $method = strtolower($method);
        if ($method == "post" || $method == "get") {
            $this->method = $method;
        } else {
            $this->setError("Invalid method: $method");
            return;
        }

        $this->url  = $url;
        $this->data = $data;

        if (is_array($args)) {
            // Add arguments to the already available default arguments
            foreach($args as $key => $value) {
                $this->args[$key] = $value;
            }
        }

        $this->doRequest();
    }

    function hasError() {
        return $this->hasError;
    }

    private function setError($msg) {
        $this->error = $msg;
        $this->hasError = true;
    }

    function getError() {
        return $this->error;
    }

    function getStatus() {
        return $this->status;
    }

    function getResponse() {
        return $this->response;
    }

    function getRequestInfo() {
        return $this->requestInfo;
    }

    function toString() {
        var_dump($this);
    }

    private function doRequest() {
        $this->doCurl();

        if ($this->status != "200") {
            $this->setError("Response error: " . $this->status . " (" . $this->curlError . ")");
        }
    }

    private function doCurl() {
        $c = curl_init();

        // Maybe we want to rewrite the url for data arguments in GET requests
        if ($this->method == "get" && $this->data) {
            $this->url .= "?" . http_build_query($this->data);
        }

        // Default values
        curl_setopt($c, CURLOPT_URL, $this->url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER ,true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, $this->args['followRedirect']);
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Expect:'));

		//register a callback function which will process the headers
		//this assumes your code is into a class method, and uses $this->readHeader as the callback //function
		curl_setopt($c, CURLOPT_HEADERFUNCTION, array(&$this,'readHeader'));

        // Authentication
        if (isset($this->args['username']) && isset($this->args['password'])) {
            curl_setopt($c, CURLOPT_USERPWD, $this->args['username'] . ':' . $this->args['password']);
        }

        // POST
        if($this->method == "post") {
            curl_setopt($c, CURLOPT_POST, true);
            // Always escape HTTP data dammit!
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($this->data));
        }

        // Many servers require this to output decent HTML
        if (empty($this->args['useragent'])) {
            curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0");
        } else {
            curl_setopt($c, CURLOPT_USERAGENT, $this->args['useragent']);
        }

        $this->response    = curl_exec($c);
        $this->status      = curl_getinfo($c, CURLINFO_HTTP_CODE);
        $this->curlError   = curl_errno($c) . ": ". curl_error($c);
        $this->requestInfo = curl_getinfo($c);
		$this->headers     = array_merge($this->requestInfo, $this->headers);
        curl_close($c);
    }

	private function readHeader($ch, $header) {
        $key = trim(substr($header, 0, strpos($header, ":")));
        $val = trim(substr($header, strpos($header, ":") + 1));
        if (!empty($key) && !empty($val)) {
            $this->headers[$key] = $val;
        }
        return strlen($header);
	}
	
	function getHeaders($key = false) {
        if ($key) {
            return $this->headers[$key];
        } else {
    		return $this->headers;
        }
	}
}
?>