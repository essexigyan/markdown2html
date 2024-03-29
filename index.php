<?php
class MarkdownToHtmlConverter
{
    const LINE_LIMIT = 10000;
    const BYTES = 32;
    /**
     * generateCSRFToken creates a token to validate requests
     * @return string
     */
    public static function generateCSRFToken()
    {
        return bin2hex(random_bytes(self::BYTES));
    }

    /**
     * validateCSRFToken compares session token to passed token
     * @param mixed $token
     * @return bool
     */
    public static function validateCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * markdownToHtmlMap stores the key:value pair 
     * for a markdown to html regex pattern and its corresponding html equivalent
     * @var array
     * todo Make more readable, convert regex key to markdown symbol
     */
    private static $markdownToHtmlMap = array(
        '/^# (.*)$/m' => '<h1>$1</h1>', //heading 1
        '/^## (.*)$/m' => '<h2>$1</h2>', //heading 2
        '/^###### (.*)$/m' => '<h6>$1</h6>', //heading 6
        '/\.{3}/' => '&hellip;', //ellipses
        '/\[(.*?)\]\((.*?)\)/' => '<a href="$2">$1</a>', // link
    );

    /**
     * wrapUnformattedTextInParagraph replace unformatted text with p tags
     * @param mixed $markdown
     * @return string
     */
    private static function wrapUnformattedTextInParagraph($markdown)
    {
        // Explode the markdown into lines
        $lines = explode("\n", $markdown); // O(n)

        // Process each line
        foreach ($lines as &$line) { // O(n)
            // Check if the line contains Markdown
            // If no markdown, wrap in p tags
            if (!preg_match('/[*_#`]/', $line)) {
                // Wrap the line in <p> tags if it is unformatted markdown
                // Make sure the length is greater than 1 to prevent empty p tag creation
                if (strlen($line) > 1) {
                    $line = "<p>$line</p>";
                }

            }
        }
        // Bring everything back together
        $html = implode("\n", $lines); // O(n)

        return $html;
    }

    /**
     * conversion of markdown to html
     * @param mixed $markdown
     * @throws \InvalidArgumentException
     * @return array|string|null
     */
    public static function convert($markdown)
    {
        if (empty($markdown)) {
            trigger_error('Please place markdown in text area for processing.', E_USER_WARNING);
        } elseif (strlen($markdown) >= self::LINE_LIMIT) {
            trigger_error('Please consider a smaller input', E_USER_ERROR);
        }

        $markdown = self::wrapUnformattedTextInParagraph($markdown); // O(n)

        foreach (self::$markdownToHtmlMap as $markdownPattern => $htmlReplacement) { // O(n)
            $markdown = preg_replace($markdownPattern, $htmlReplacement, $markdown);
        }

        return $markdown;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['markdown'])) {
    $markdown = $_POST['markdown'];
    $html = MarkdownToHtmlConverter::convert($markdown);
} else {
    $html = '';
}

//    public function testConvertLink() {
//         $markdown = '[Foo Text](https://foo.com)';
//         $expectedHtml = '<a href="https://foo.com">Foo Text</a>';
//         $this->assertEquals($expectedHtml, MarkdownToHtmlConverter::convert($markdown));
//     }

//      function sanitizeInput($data)
//      {
//          $data = trim($data);
//          $data = stripslashes($data);
//          return $data;
//       }

//     public function testConvertHeading() {
//         $markdown = '###### Heading 6';
//         $expectedHtml = '<h6>Heading 6</h6>';
//         $this->assertEquals($expectedHtml, MarkdownToHtmlConverter::convert($markdown));
//     }


// // Initialize or regenerate CSRF token
// if (!isset($_SESSION['csrf_token'])) {
//     $_SESSION['csrf_token'] = MarkdownToHtmlConverter::generateCSRFToken();
// }

// // Process and check token
// if (
//     $_SERVER["REQUEST_METHOD"] == "POST"
//     && isset($_POST['markdown'])
//     && !empty($_POST['csrf_token'])
//     && MarkdownToHtmlConverter::validateCSRFToken($_POST['csrf_token'])
// ) {
//     $markdown = $_POST['markdown'];
//     $html = MarkdownToHtmlConverter::convert($markdown);
// } elseif (
//     $_SERVER["REQUEST_METHOD"] == "POST"
//     && !MarkdownToHtmlConverter::validateCSRFToken($_POST['csrf_token'])
// ) {
//     // CSRF token validation failed, handle the error here (e.g., redirect, log, etc.)
//     die("CSRF token validation failed!");
// } else {

//     $html = '';

// } 

?>

<?php
session_start();
?>


<!DOCTYPE html>
<html>

<head>
    <title>Markdown => HTML converter</title>
</head>

<body>
    <h2>Markdown => HTML converter</h2>
    <form method="post" action="<?php echo htmlentities($_SERVER["PHP_SELF"]); ?>">
        <textarea id="convert" name="markdown" rows="20"
            cols="50"><?php echo isset($_POST['markdown']) ? htmlentities($_POST['markdown']) : ''; ?></textarea><br>
        <input type="submit" value="Convert">
        <!-- <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> -->
        <input type="button" onclick="clearConvertBox()" value="Reset">
        <!-- <?php echo "<br><b>Token</b>: " . $_SESSION['csrf_token']; ?> -->

    </form>

    <hr />
    <textarea id="foo" name="markdown" rows="20"
        cols="50"><?php echo isset($_POST['markdown']) ? $html : ''; ?></textarea><br>
    <br>
    <div>
        <?php echo $html ?>
    </div>
    <script>
        function clearConvertBox() { document.getElementById("convert").value = "" }
    </script>
</body>

</html>