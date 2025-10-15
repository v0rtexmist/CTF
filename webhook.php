<?php
// Read raw POST body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
header('Content-type: text/html');

// If the incoming JSON decodes to a string (the payload posts plain text blocks),
// remove the unwanted labels from the text before appending to data.txt.
if (is_string($data)) {
  // Remove any lines that contain the listed labels (case-insensitive), preserving other content.
  // Also remove lines that exactly match "OS CPU: undefined" (common undefined value).
  $pattern = '/^.*(?:Browser Name|Browser CodeName|RAM|CPU Cores|Screen Width|Screen Height).*$(\r?\n)?/mi';
  $filtered = preg_replace($pattern, '', $data);
  if ($filtered === null) {
    $filtered = preg_replace('/^OS CPU:\s*undefined$(\r?\n)?/mi', '', $data);
  } else {
    $filtered = preg_replace('/^OS CPU:\s*undefined$(\r?\n)?/mi', '', $filtered);
  }
  // On regex error for subsequent filters we fall back later when writing.

  // Additionally, normalize any "User Agent:" line so that only the parenthetical
  // platform token is kept (e.g. "Windows NT 10.0; Win64; x64"). If no parentheses
  // are present, keep the original value.
  $filtered = preg_replace_callback('/^(User Agent:\s*)(.*)$/mi', function($m) {
    $prefix = $m[1];
    $ua = $m[2];
    if (preg_match('/\(([^)]+)\)/', $ua, $mm)) {
      return $prefix . trim($mm[1]);
    }
    return $prefix . trim($ua);
  }, $filtered);

  file_put_contents('data.txt', $filtered, FILE_APPEND);
} else {
  // If not a string (object/array or something else), write the raw request body unchanged.
  file_put_contents('data.txt', $raw, FILE_APPEND);
}

?>
