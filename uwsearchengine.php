<html>
<head>
    <title>University of Washington Search Engine</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #4b2e83;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .page-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .main-content {
            flex: 3;
            padding: 30px;
        }
        .recommended-queries {
            flex: 1;
            background-color: #f7f7f7;
            padding: 20px;
            border-left: 1px solid #e0e0e0;
        }
        h1 {
            color: #32006e;
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        .recommended-queries h2 {
            color: #32006e;
            border-bottom: 2px solid #4b2e83;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        p {
            text-align: center;
            color: #32006e;
            margin-bottom: 20px;
        }
        form {
            text-align: center;
            margin: 30px 0;
        }
        input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 2px solid #cbd5e0;
            border-radius: 5px;
            font-size: 16px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background-color: #4299e1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        input[type="submit"]:hover {
            background-color: #2b6cb0;
        }
        a {
            color: #32006e;
            text-decoration: none;
            display: block;
            margin: 10px 0;
        }
        a:hover {
            text-decoration: underline;
        }
        .recommended-query {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #e6e6f2;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .recommended-query:hover {
            background-color: #d1d1e0;
        }
	.query-freq {
            color: #666;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="main-content">
        <h1>University of Washington Search Engine</h1>
	<p>Explore a 1,132-document collection of sites, articles, and course topics from the University of Washington, spanning topics from Calculus 1 to Computer Science. Try searching for terms like "Informatics", "biology", or "Machine Learning" to get a sense of the insights this search engine can offer! Historical user queries are logged to a text file, and we use the frequency of logged queries for recommendation. This collection has been indexed from <a href="https://washington.edu">washington.edu</a>.</p>
        <form action="uwsearchengine.php" method="post">
            <input type="text" size=40 name="search_string" value="<?php echo $_POST["search_string"];?>"/>
            <input type="submit" value="Search"/>
        </form>
        <?php
            function fetchQA($document_text, $query) {
                $api_key = "INSERT_OPENAI_KEY";
                $url = "https://api.openai.com/v1/chat/completions";
            
                $data = [
                    "model" => "gpt-4o-mini",
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => "Query from Search: $query\n Document url: $document_text\n\nGenerate a JSON RESPONSE with a possible question and its answer about this page. If there is something specific about the query in the page, generate the question and answer based of off that; if not, generate either a summary of the page or somehting specific in the page. MAKE SURE WHATEVER YOU'RE RETURNING IS IN THE PAGE. format for return {question: ...., answer: ....}"
                        ]
                    ],
                    "temperature" => 0.1,
                    "max_tokens" => 250
                ];
            
                $ch = curl_init($url);
            
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Authorization: Bearer $api_key"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
                $response = curl_exec($ch);
            
                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    curl_close($ch);
                    return ["question" => "Error", "answer" => "cURL error: $error_msg"];
                }
            
                curl_close($ch);
            
                $response_data = json_decode($response, true);
                $content = $response_data['choices'][0]['message']['content'];
                $qa = json_decode(trim(str_replace(['```json', '```'], '', $content)), true);
            
                if (!isset($response_data['choices'][0]['message']['content'])) {
                    return ["question" => "Error", "answer" => "Invalid response from OpenAI API"];
                }
            
                return $qa;
            }




            if (isset($_POST["search_string"])) {
                $search_string = $_POST["search_string"];
                file_put_contents("logs.txt", $search_string.PHP_EOL, FILE_APPEND | LOCK_EX);

                $qfile = fopen("query.py", "w");
                fwrite($qfile, "import pyterrier as pt\nif not pt.started():\n\tpt.init()\n\n");
                fwrite($qfile, "import pandas as pd\nqueries = pd.DataFrame([[\"q1\", \"$search_string\"]], columns=[\"qid\",\"query\"])\n");
                fwrite($qfile, "index = pt.IndexFactory.of(\"./uw_index/\")\n");
                fwrite($qfile, "tf_idf = pt.BatchRetrieve(index, wmodel=\"TF_IDF\")\n");
                fwrite($qfile, "results = tf_idf.transform(queries)\n");
                for ($i = 0; $i < 5; $i++) {
                    fwrite($qfile, "print(index.getMetaIndex().getItem(\"filename\",results.docid[$i]))\n");
                    fwrite($qfile, "if index.getMetaIndex().getItem(\"title\", results.docid[$i]).strip() != \"\":\n");
                    fwrite($qfile, "\tprint(index.getMetaIndex().getItem(\"title\",results.docid[$i]))\n");
                    fwrite($qfile, "else:\n\tprint(index.getMetaIndex().getItem(\"filename\",results.docid[$i]))\n");
                }
                fclose($qfile);
                exec("/Users/nadyashechtman/Documents/INFO376/pyterrier_env/bin/python3 query.py > output 2>errors", $output, $return_var);
                if (!file_exists("output") || $return_var !== 0) {
                    echo "Error: Output file not created or Python script failed.<br>";
                    echo nl2br(file_get_contents("output"));
                    exit();
                }
                $stream = fopen("output", "r");
                $counter = 1;
                echo "<ol>";
                $last_url = "";
                while (($line = fgets($stream)) !== false) {
                    $clean_line = trim($line);
                    if (strpos($clean_line, "./") === 0) {
                        $last_url = "http://" . ltrim($clean_line, "./");
                    } else {
                        // $document_text = $last_url;
                        // echo '<pr>';
                        // echo($document_text);
                        // echo '<pr>';
                        $filename = $last_url;
                        $content = file_get_contents($filename);
                        preg_match_all('/<p[^>]*>(.*?)<\/p>/', $content, $matches);
                        $document_text = implode("\n", $matches[1]);

                        // echo '<pr>';
                        // echo($document_text);
                        // echo '<pr>';


                        $qa = fetchQA($document_text, $search_string);
                        if (!isset($qa['question']) || !isset($qa['answer'])) {
                            $qa = [
                                "question" => "No question generated",
                                "answer" => "No answer generated"
                            ];
                        }                        
                        if (!empty($last_url)) {
                            echo "<li>
                                <a href=\"$last_url\" target=\"_blank\">$clean_line</a>
                                <details style='margin-top: 10px; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f7f7f7;'>
                                    <summary style='font-weight: bold; font-size: 1.1em; cursor: pointer;'> {$qa['question']}</summary>
                                    <div style='margin-top: 10px;'>
                                        <p style='margin: 5px 0;'><strong>Answer:</strong> {$qa['answer']}</p>
                                    </div>
                                </details>
                            </li>";
                            $last_url = "";
                        }
                    }
                }
                echo "</ol>";
                fclose($stream);

                exec("rm query.py");
                exec("rm output");
            }
            exec("sort logs.txt | uniq -c | sort -nr | head -n 5 > top_queries.txt");
            
        ?>
    </div>
    <div class="recommended-queries">
        <h2>Recommended Queries</h2>
	<?php
            $top_queries = file('top_queries.txt', FILE_IGNORE_NEW_LINES);
            if ($top_queries) {
                foreach ($top_queries as $query_line) {
                    if (preg_match('/^\s*(\d+)\s+(.+)$/', $query_line, $matches)) {
                        $frequency = $matches[1];
                        $query = $matches[2];
                        echo "<div class='recommended-query'>
                                <form action='uwsearchengine.php' method='post'>
                                    <input type='hidden' name='search_string' value='$query'>
                                    <button type='submit' style='all: unset; cursor: pointer;'>$query</button>
                                </form>
                                <span class='query-freq'>freq: $frequency</span>
                              </div>";
                    }
                }
            } else {
                echo "<div class='recommended-query'>No recent queries</div>";
            }
        ?>
    </div>
</div>
</body>
</html>