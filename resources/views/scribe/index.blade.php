<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>DOLE RO9 Payroll System API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.8.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.8.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-sit">
                                <a href="#endpoints-GETapi-sit">Look up a salary amount from the Salary Index Table (SIT).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-payrolls">
                                <a href="#endpoints-GETapi-v1-payrolls">GET api/v1/payrolls</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-payrolls">
                                <a href="#endpoints-POSTapi-v1-payrolls">POST api/v1/payrolls</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-payrolls--payroll_id-">
                                <a href="#endpoints-GETapi-v1-payrolls--payroll_id-">GET api/v1/payrolls/{payroll_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-payrolls--payroll_id-">
                                <a href="#endpoints-DELETEapi-v1-payrolls--payroll_id-">DELETE api/v1/payrolls/{payroll_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-tevs">
                                <a href="#endpoints-GETapi-v1-tevs">List all TEV requests with optional filtering by track, status, and year.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-tevs">
                                <a href="#endpoints-POSTapi-v1-tevs">Persist a new TEV request and auto-submit it to the accountant queue.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-tevs--tev-">
                                <a href="#endpoints-GETapi-v1-tevs--tev-">Display a single TEV request with its full approval timeline.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-tevs--tev-">
                                <a href="#endpoints-PUTapi-v1-tevs--tev-">Update and resubmit a rejected TEV request.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-tevs--tev-">
                                <a href="#endpoints-DELETEapi-v1-tevs--tev-">TEV records may not be deleted.</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: June 27, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-sit">Look up a salary amount from the Salary Index Table (SIT).</h2>

<p>
</p>

<p>Called via AJAX by sit-lookup.js on the employee form.
Year defaults to the latest available year in the table if omitted.</p>
<p>Query params: sg (1–33), step (1–8), year (optional)
Success: { "amount": "48313.00", "sg": 19, "step": 1, "year": 2021 }
Failure: { "error": "..." } with 404</p>

<span id="example-requests-GETapi-sit">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/sit" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"sg\": 21,
    \"step\": 4,
    \"year\": 1291
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/sit"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "sg": 21,
    "step": 4,
    "year": 1291
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-sit">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
set-cookie: XSRF-TOKEN=eyJpdiI6ImdCSWpRYlk2emdvV3V3SjI0djErZGc9PSIsInZhbHVlIjoibmc2Wlo3S2NndWNtTGFUWmFlOHBCN0l6NGpCZVVvOHBMMFFSWlNhYVIySkF1NUI5bzZlYko1V2hWbjVmS2E2NjEzamF0SG9WUUlPVHFRWEpsZDU3dzNFUmlsaW1FcU81RVhpZEU0T2EyYjhLQ09GNnFnUi9HbUs3OFg3MjhnYUYiLCJtYWMiOiIyNjM1MjVlMWViOGE4ODlhYThiMjdjY2NhMmMxYTIxYTk2NjZkMzM0N2QzMzQ0ZTBmZDgxNjY2MzJlZjkyZmIxIiwidGFnIjoiIn0%3D; expires=Sat, 27 Jun 2026 14:36:36 GMT; Max-Age=7200; path=/; samesite=lax; dole_ro9_payroll_system_session=eyJpdiI6ImtNNzV4NjhDc2JKMnlua1J0TGdSWXc9PSIsInZhbHVlIjoiS3Q1MHhIcWgrMTZLZWcrOXpIMXUvM3Y3dDVxTzhrdi80RnRmVG1aMG1UR05RajdJeURkcFpLRTlFa1ZGbWxHZDRoZHlaSTBBL1RYYWNYMWNXM00zZDI2RVFEbkFwMVZIZTVoZUJIdkRMT1k4M0pwSjlVTU1HalFyUWh1UzFkRHQiLCJtYWMiOiJmNjU5NGU1ZWRjYzgxNjZhMmNiZmU1YjUxZTUwMGJlYTE3ZDdiZWY4ZTNmYjk0NDJkYjYwNjBiZjhhYWQ4MDZkIiwidGFnIjoiIn0%3D; expires=Sat, 27 Jun 2026 14:36:36 GMT; Max-Age=7200; path=/; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-sit" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-sit"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-sit"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-sit" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-sit">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-sit" data-method="GET"
      data-path="api/sit"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-sit', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-sit"
                    onclick="tryItOut('GETapi-sit');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-sit"
                    onclick="cancelTryOut('GETapi-sit');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-sit"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/sit</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-sit"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-sit"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sg</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sg"                data-endpoint="GETapi-sit"
               value="21"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 33. Example: <code>21</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>step</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="step"                data-endpoint="GETapi-sit"
               value="4"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 8. Example: <code>4</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>year</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="year"                data-endpoint="GETapi-sit"
               value="1291"
               data-component="body">
    <br>
<p>Must be at least 2021. Example: <code>1291</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-payrolls">GET api/v1/payrolls</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payrolls">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payrolls" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payrolls"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payrolls">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payrolls" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payrolls"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payrolls"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payrolls" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payrolls">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payrolls" data-method="GET"
      data-path="api/v1/payrolls"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payrolls', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payrolls"
                    onclick="tryItOut('GETapi-v1-payrolls');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payrolls"
                    onclick="cancelTryOut('GETapi-v1-payrolls');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payrolls"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payrolls</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payrolls"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payrolls"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-payrolls">POST api/v1/payrolls</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-payrolls">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/payrolls" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"period_year\": 21,
    \"period_month\": 6,
    \"period_start\": \"2107-07-27\",
    \"period_end\": \"2107-07-27\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payrolls"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "period_year": 21,
    "period_month": 6,
    "period_start": "2107-07-27",
    "period_end": "2107-07-27"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-payrolls">
</span>
<span id="execution-results-POSTapi-v1-payrolls" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-payrolls"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-payrolls"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-payrolls" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-payrolls">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-payrolls" data-method="POST"
      data-path="api/v1/payrolls"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-payrolls', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-payrolls"
                    onclick="tryItOut('POSTapi-v1-payrolls');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-payrolls"
                    onclick="cancelTryOut('POSTapi-v1-payrolls');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-payrolls"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/payrolls</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-payrolls"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-payrolls"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>period_year</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="period_year"                data-endpoint="POSTapi-v1-payrolls"
               value="21"
               data-component="body">
    <br>
<p>Must be at least 2020. Must not be greater than 2099. Example: <code>21</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>period_month</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="period_month"                data-endpoint="POSTapi-v1-payrolls"
               value="6"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 12. Example: <code>6</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>period_start</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="period_start"                data-endpoint="POSTapi-v1-payrolls"
               value="2107-07-27"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>-01-01</code>. Example: <code>2107-07-27</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>period_end</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="period_end"                data-endpoint="POSTapi-v1-payrolls"
               value="2107-07-27"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>period_start</code>. Example: <code>2107-07-27</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-payrolls--payroll_id-">GET api/v1/payrolls/{payroll_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-payrolls--payroll_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/payrolls/28" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payrolls/28"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-payrolls--payroll_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-payrolls--payroll_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-payrolls--payroll_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-payrolls--payroll_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-payrolls--payroll_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-payrolls--payroll_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-payrolls--payroll_id-" data-method="GET"
      data-path="api/v1/payrolls/{payroll_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-payrolls--payroll_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-payrolls--payroll_id-"
                    onclick="tryItOut('GETapi-v1-payrolls--payroll_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-payrolls--payroll_id-"
                    onclick="cancelTryOut('GETapi-v1-payrolls--payroll_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-payrolls--payroll_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/payrolls/{payroll_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-payrolls--payroll_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-payrolls--payroll_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payroll_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payroll_id"                data-endpoint="GETapi-v1-payrolls--payroll_id-"
               value="28"
               data-component="url">
    <br>
<p>The ID of the payroll. Example: <code>28</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-v1-payrolls--payroll_id-">DELETE api/v1/payrolls/{payroll_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-payrolls--payroll_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/payrolls/28" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/payrolls/28"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-payrolls--payroll_id-">
</span>
<span id="execution-results-DELETEapi-v1-payrolls--payroll_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-payrolls--payroll_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-payrolls--payroll_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-payrolls--payroll_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-payrolls--payroll_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-payrolls--payroll_id-" data-method="DELETE"
      data-path="api/v1/payrolls/{payroll_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-payrolls--payroll_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-payrolls--payroll_id-"
                    onclick="tryItOut('DELETEapi-v1-payrolls--payroll_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-payrolls--payroll_id-"
                    onclick="cancelTryOut('DELETEapi-v1-payrolls--payroll_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-payrolls--payroll_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/payrolls/{payroll_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-payrolls--payroll_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-payrolls--payroll_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>payroll_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="payroll_id"                data-endpoint="DELETEapi-v1-payrolls--payroll_id-"
               value="28"
               data-component="url">
    <br>
<p>The ID of the payroll. Example: <code>28</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-tevs">List all TEV requests with optional filtering by track, status, and year.</h2>

<p>
</p>

<ul>
<li>Officers: Can see all requests</li>
<li>Employees: Can only see their own requests</li>
</ul>

<span id="example-requests-GETapi-v1-tevs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/tevs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/tevs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tevs">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tevs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tevs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tevs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tevs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tevs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tevs" data-method="GET"
      data-path="api/v1/tevs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tevs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tevs"
                    onclick="tryItOut('GETapi-v1-tevs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tevs"
                    onclick="cancelTryOut('GETapi-v1-tevs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tevs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tevs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tevs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tevs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-tevs">Persist a new TEV request and auto-submit it to the accountant queue.</h2>

<p>
</p>

<ul>
<li>HRMO: Files TEVs on behalf of employees using office orders</li>
<li>Employees: File TEVs for themselves directly</li>
</ul>

<span id="example-requests-POSTapi-v1-tevs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/v1/tevs" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "office_order_id=17"\
    --form "track=reimbursement"\
    --form "purpose=mqeopfuudtdsufvyvddqa"\
    --form "destination=mniihfqcoynlazghdtqtq"\
    --form "travel_type=national"\
    --form "travel_date_start=2026-06-27T20:36:36"\
    --form "travel_date_end=2107-07-27"\
    --form "remarks=mqeopfuudtdsufvyvddqa"\
    --form "lines[][travel_date]=2026-06-27T20:36:36"\
    --form "lines[][origin]=vmqeopfuudtdsufvyvddq"\
    --form "lines[][destination]=amniihfqcoynlazghdtqt"\
    --form "lines[][departure_time]=20:36"\
    --form "lines[][arrival_time]=20:36"\
    --form "lines[][mode_of_transport]=qxbajwbpilpmufinllwlo"\
    --form "lines[][transportation_cost]=2"\
    --form "lines[][per_diem_amount]=72"\
    --form "lines[][is_half_day]="\
    --form "documents[]=@C:\Users\ACER\AppData\Local\Temp\php8C2F.tmp" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/tevs"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('office_order_id', '17');
body.append('track', 'reimbursement');
body.append('purpose', 'mqeopfuudtdsufvyvddqa');
body.append('destination', 'mniihfqcoynlazghdtqtq');
body.append('travel_type', 'national');
body.append('travel_date_start', '2026-06-27T20:36:36');
body.append('travel_date_end', '2107-07-27');
body.append('remarks', 'mqeopfuudtdsufvyvddqa');
body.append('lines[][travel_date]', '2026-06-27T20:36:36');
body.append('lines[][origin]', 'vmqeopfuudtdsufvyvddq');
body.append('lines[][destination]', 'amniihfqcoynlazghdtqt');
body.append('lines[][departure_time]', '20:36');
body.append('lines[][arrival_time]', '20:36');
body.append('lines[][mode_of_transport]', 'qxbajwbpilpmufinllwlo');
body.append('lines[][transportation_cost]', '2');
body.append('lines[][per_diem_amount]', '72');
body.append('lines[][is_half_day]', '');
body.append('documents[]', document.querySelector('input[name="documents[]"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-tevs">
</span>
<span id="execution-results-POSTapi-v1-tevs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-tevs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-tevs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-tevs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-tevs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-tevs" data-method="POST"
      data-path="api/v1/tevs"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-tevs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-tevs"
                    onclick="tryItOut('POSTapi-v1-tevs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-tevs"
                    onclick="cancelTryOut('POSTapi-v1-tevs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-tevs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/tevs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-tevs"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-tevs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>office_order_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="office_order_id"                data-endpoint="POSTapi-v1-tevs"
               value="17"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the office_orders table. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>track</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="track"                data-endpoint="POSTapi-v1-tevs"
               value="reimbursement"
               data-component="body">
    <br>
<p>Example: <code>reimbursement</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>cash_advance</code></li> <li><code>reimbursement</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="POSTapi-v1-tevs"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>destination</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="destination"                data-endpoint="POSTapi-v1-tevs"
               value="mniihfqcoynlazghdtqtq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>mniihfqcoynlazghdtqtq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_type"                data-endpoint="POSTapi-v1-tevs"
               value="national"
               data-component="body">
    <br>
<p>Example: <code>national</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>local</code></li> <li><code>regional</code></li> <li><code>national</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_date_start</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_date_start"                data-endpoint="POSTapi-v1-tevs"
               value="2026-06-27T20:36:36"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-06-27T20:36:36</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_date_end</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_date_end"                data-endpoint="POSTapi-v1-tevs"
               value="2107-07-27"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>travel_date_start</code>. Example: <code>2107-07-27</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>remarks</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="remarks"                data-endpoint="POSTapi-v1-tevs"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>documents</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="documents[0]"                data-endpoint="POSTapi-v1-tevs"
               data-component="body">
        <input type="file" style="display: none"
               name="documents[1]"                data-endpoint="POSTapi-v1-tevs"
               data-component="body">
    <br>
<p>Must be a file. Must not be greater than 5120 kilobytes.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>lines</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Must have at least 1 items.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>travel_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.travel_date"                data-endpoint="POSTapi-v1-tevs"
               value="2026-06-27T20:36:36"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-06-27T20:36:36</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>origin</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.origin"                data-endpoint="POSTapi-v1-tevs"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>destination</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.destination"                data-endpoint="POSTapi-v1-tevs"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>departure_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.departure_time"                data-endpoint="POSTapi-v1-tevs"
               value="20:36"
               data-component="body">
    <br>
<p>Must be a valid date in the format <code>H:i</code>. Example: <code>20:36</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>arrival_time</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.arrival_time"                data-endpoint="POSTapi-v1-tevs"
               value="20:36"
               data-component="body">
    <br>
<p>Must be a valid date in the format <code>H:i</code>. Example: <code>20:36</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>mode_of_transport</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lines.0.mode_of_transport"                data-endpoint="POSTapi-v1-tevs"
               value="qxbajwbpilpmufinllwlo"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>qxbajwbpilpmufinllwlo</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>transportation_cost</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lines.0.transportation_cost"                data-endpoint="POSTapi-v1-tevs"
               value="2"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>2</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>per_diem_amount</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lines.0.per_diem_amount"                data-endpoint="POSTapi-v1-tevs"
               value="72"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>72</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>is_half_day</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-tevs" style="display: none">
            <input type="radio" name="lines.0.is_half_day"
                   value="true"
                   data-endpoint="POSTapi-v1-tevs"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-tevs" style="display: none">
            <input type="radio" name="lines.0.is_half_day"
                   value="false"
                   data-endpoint="POSTapi-v1-tevs"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-tevs--tev-">Display a single TEV request with its full approval timeline.</h2>

<p>
</p>

<ul>
<li>Officers: Can view any TEV request</li>
<li>Employees: Can only view their own requests</li>
</ul>

<span id="example-requests-GETapi-v1-tevs--tev-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/tevs/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/tevs/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-tevs--tev-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-tevs--tev-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-tevs--tev-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-tevs--tev-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-tevs--tev-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-tevs--tev-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-tevs--tev-" data-method="GET"
      data-path="api/v1/tevs/{tev}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-tevs--tev-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-tevs--tev-"
                    onclick="tryItOut('GETapi-v1-tevs--tev-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-tevs--tev-"
                    onclick="cancelTryOut('GETapi-v1-tevs--tev-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-tevs--tev-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/tevs/{tev}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tev</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tev"                data-endpoint="GETapi-v1-tevs--tev-"
               value="consequatur"
               data-component="url">
    <br>
<p>The tev. Example: <code>consequatur</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-tevs--tev-">Update and resubmit a rejected TEV request.</h2>

<p>
</p>

<p>Clears the denial status and returns the TEV to 'submitted' status
for re-approval through the workflow.</p>

<span id="example-requests-PUTapi-v1-tevs--tev-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/v1/tevs/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"track\": \"reimbursement\",
    \"purpose\": \"vmqeopfuudtdsufvyvddq\",
    \"destination\": \"amniihfqcoynlazghdtqt\",
    \"travel_type\": \"local\",
    \"travel_date_start\": \"2026-06-27T20:36:36\",
    \"travel_date_end\": \"2107-07-27\",
    \"remarks\": \"mqeopfuudtdsufvyvddqa\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/tevs/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "track": "reimbursement",
    "purpose": "vmqeopfuudtdsufvyvddq",
    "destination": "amniihfqcoynlazghdtqt",
    "travel_type": "local",
    "travel_date_start": "2026-06-27T20:36:36",
    "travel_date_end": "2107-07-27",
    "remarks": "mqeopfuudtdsufvyvddqa"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-tevs--tev-">
</span>
<span id="execution-results-PUTapi-v1-tevs--tev-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-tevs--tev-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-tevs--tev-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-tevs--tev-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-tevs--tev-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-tevs--tev-" data-method="PUT"
      data-path="api/v1/tevs/{tev}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-tevs--tev-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-tevs--tev-"
                    onclick="tryItOut('PUTapi-v1-tevs--tev-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-tevs--tev-"
                    onclick="cancelTryOut('PUTapi-v1-tevs--tev-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-tevs--tev-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/tevs/{tev}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/tevs/{tev}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tev</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tev"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="consequatur"
               data-component="url">
    <br>
<p>The tev. Example: <code>consequatur</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>track</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="track"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="reimbursement"
               data-component="body">
    <br>
<p>Example: <code>reimbursement</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>cash_advance</code></li> <li><code>reimbursement</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>purpose</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="purpose"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="vmqeopfuudtdsufvyvddq"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>vmqeopfuudtdsufvyvddq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>destination</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="destination"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="amniihfqcoynlazghdtqt"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>amniihfqcoynlazghdtqt</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_type"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="local"
               data-component="body">
    <br>
<p>Example: <code>local</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>local</code></li> <li><code>international</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_date_start</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_date_start"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="2026-06-27T20:36:36"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-06-27T20:36:36</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_date_end</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_date_end"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="2107-07-27"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>travel_date_start</code>. Example: <code>2107-07-27</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>remarks</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="remarks"                data-endpoint="PUTapi-v1-tevs--tev-"
               value="mqeopfuudtdsufvyvddqa"
               data-component="body">
    <br>
<p>Must not be greater than 500 characters. Example: <code>mqeopfuudtdsufvyvddqa</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-tevs--tev-">TEV records may not be deleted.</h2>

<p>
</p>

<p>Once filed, a TEV is part of the financial audit trail and must be
retained. Rejection or liquidation are the appropriate terminal states.</p>

<span id="example-requests-DELETEapi-v1-tevs--tev-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/v1/tevs/consequatur" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/tevs/consequatur"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-tevs--tev-">
</span>
<span id="execution-results-DELETEapi-v1-tevs--tev-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-tevs--tev-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-tevs--tev-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-tevs--tev-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-tevs--tev-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-tevs--tev-" data-method="DELETE"
      data-path="api/v1/tevs/{tev}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-tevs--tev-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-tevs--tev-"
                    onclick="tryItOut('DELETEapi-v1-tevs--tev-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-tevs--tev-"
                    onclick="cancelTryOut('DELETEapi-v1-tevs--tev-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-tevs--tev-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/tevs/{tev}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-tevs--tev-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tev</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tev"                data-endpoint="DELETEapi-v1-tevs--tev-"
               value="consequatur"
               data-component="url">
    <br>
<p>The tev. Example: <code>consequatur</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
