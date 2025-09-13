<?php
// zxApplyPoke.php

// Get parameters
$fileUrlEncoded = $_GET['file'] ?? '';
$pokesParam = $_GET['pokes'] ?? '';

// Decode URL
$fileUrl = urldecode($fileUrlEncoded);
$pokesStr = urldecode($pokesParam);

// Validate parameters
if (empty($fileUrl) || empty($pokesStr)) {
    die("Missing parameters.");
}

// Download the original file
$tempFile = tempnam(sys_get_temp_dir(), 'zx');
file_put_contents($tempFile, file_get_contents($fileUrl));

// Read file content
$data = file_get_contents($tempFile);
if ($data === false) {
    die("Failed to download or read file.");
}

// Detect file type by extension
$pathParts = pathinfo($fileUrl);
$extension = strtolower($pathParts['extension'] ?? '');

$isSNA = ($extension === 'sna');
$isZ80 = ($extension === 'z80');

if (!$isSNA && !$isZ80) {
    // Fallback: check file size or headers if needed
    // For simplicity, assume extension
    die("Unknown file type. Supported: .SNA, .Z80");
}

// Parse POKEs
// Format: XXXXXX,YYY;ZZZZZZ,KKK;...
$pokeList = explode(';', $pokesStr);
$pokes = [];
foreach ($pokeList as $pokeStr) {
    $parts = explode(',', $pokeStr);
    if (count($parts) != 2) continue;
    $addrHex = trim($parts[0]);
    $valueHex = trim($parts[1]);
    // Convert hex to int
    $addr = hexdec($addrHex);
    $value = hexdec($valueHex);
    $pokes[] = ['addr' => $addr, 'value' => $value];
}

// Helper functions
function isSNA($data) {
    return strlen($data) === 49152; // 48K snapshot
}

function isZ80($data) {
    // Basic check: Z80 files usually have a header of 27 bytes + memory
    // We'll check for a valid header or size
    return strlen($data) >= 27;
}

// Apply POKEs
if ($isSNA) {
    // For SNA, the memory is at offset 27, size 49152
    $memoryOffset = 27;
    foreach ($pokes as $poke) {
        $addr = $poke['addr'];
        $value = $poke['value'];
        if ($addr >= 0x4000 && $addr < 0x10000) {
            $offsetInFile = $memoryOffset + ($addr - 0x4000);
            if ($offsetInFile >= 0 && $offsetInFile < strlen($data)) {
                $data[$offsetInFile] = chr($value & 0xFF);
            }
        }
    }
} elseif ($isZ80) {
    // For Z80, header is 27 bytes
    $headerSize = 27;
    foreach ($pokes as $poke) {
        $addr = $poke['addr'];
        $value = $poke['value'];
        if ($addr >= 0x4000 && $addr < 0x10000) {
            $offsetInFile = $headerSize + ($addr - 0x4000);
            if ($offsetInFile >= 0 && $offsetInFile < strlen($data)) {
                $data[$offsetInFile] = chr($value & 0xFF);
            }
        }
    }
} else {
    die("Unknown file format");
}

// Save modified file
$originalName = $pathParts['basename'];
$newFilename = preg_replace('/(\.sna|\.z80)$/i', '', $originalName) . '_POKED.' . $extension;
file_put_contents($newFilename, $data);

// Prepare the URL for the modified file
// For this example, assume the file is accessible at the same URL (or implement upload if needed)
// For simplicity, let's assume the file is hosted somewhere accessible, or you can adapt this part
// For demonstration, we'll assume the file is accessible at a base URL, e.g.:
$baseUrl = 'https://yourserver.com/path/to/files/'; // <-- set your server path

// Save the file to your server directory if needed, then generate URL
// For example, move the file to a web-accessible directory
// Here, just output a placeholder URL:
$modifiedFileUrl = $baseUrl . urlencode($newFilename);

// Output JavaScript to open qaop.html with the file URL
echo "<script>
    window.onload = function() {
        var url = 'https://zxsgd.sxoleio.win/QAOP/qaop.html?#l=' + encodeURIComponent('$modifiedFileUrl');
        window.location.href = url;
    }
</script>";
?>