<?php
/*
v0904b6 250919 Added SCRSHOT display
v0904b5 250915 Rewrite with AI to load ActualFilenames on demand (not all at once at start of session)
v0904b4 250915 Inlays FIX
v0904b3 250914 Fixed PathIndex for Images (now get it correctly from the PathIndex)
v0904b2 250910 relative image path + QAOP path (locahost)
v0904b1 250809b- PortoPalace getActualExtFilemane works for images.
v0904a2 250804c MixedCase snapshot filename detected - must fix not image mixed case
v09a03b_test1 250804a (WEB online test) - Letter pth for GIF seem to work 
v09a03 - create_sgd_filename_csv, getActualFileName seem to work  241201b
v09a01 - 241201a getActualFileName seem to work
v008a5 - QAOP running (Case sensitive - Must do a search to check capitalazation in filesystem)
v008a4 - Fixed BUG008a2 . Now filtered games are saved correctly
v008a3 - Fixed Memrory and Score alligment issues after update
v008a2 - BUG008a2 when you modify a game that was filtered it clones it.
v008a - Filter works, pathIndex shows (needs SGD.ini)
v007a2 241120b
*/

$host = $_SERVER['HTTP_HOST'];

$dat_file = 'ROMSMINE/_SGD304/Arcade4.dat';
$qaopURL = 'QAOP/qaop.html';
$snapPATH = "ROMSMINE/";
$snapsURL = '../' . $snapPATH;
$SGDfilePathPrefix = 'E:\\\OPT\\\SPECTRUM\\\ROMSMINE';
$SGDfilePathPrefix2 = 'E:\OPT\SPECTRUM\ROMSMINE';
$iniFilePath = 'ROMSMINE/_SGD304/sgd.ini';
$getActualFileNameENABLED = true;
$create_sgd_filename_csv = false;
$csvFilePath = 'sgd_filenames_index.csv';


//==========get SGD.ini paths :=======================
$gameDirs = [];
if ($fileHandle = fopen($iniFilePath, 'r')) {
    while (($line = fgets($fileHandle)) !== false) {
        $line = trim($line);
        if (strpos($line, 'GameDir') === 0) {
            $parts = explode('=', $line);
            if (count($parts) === 2) {
                $path = trim($parts[1]);
                $gameDirs[] = $path;
            }
        }
    }
    fclose($fileHandle);
} else {
    echo "Unable to open the file.";
}
$jsonGameDirs = json_encode($gameDirs);
//==END of========get SGD.ini paths :=======================

$fieldDefinitions = [
    'Name' => ['start' => 1, 'length' => 36],
    'Year' => ['start' => 38, 'length' => 4],
    'Publisher' => ['start' => 43, 'length' => 36],
    'Memory' => ['start' => 80, 'length' => 3],
    'NoOfPlayers' => ['start' => 84, 'length' => 1],
    'Together' => ['start' => 86, 'length' => 1],
    'Joysticks' => ['start' => 88, 'length' => 5],
    'PC-Name' => ['start' => 94, 'length' => 12],
    'Type' => ['start' => 107, 'length' => 7],
    'PathIndex' => ['start' => 115, 'length' => 3],
    'FileSize' => ['start' => 119, 'length' => 7],
    'Orig_screen' => ['start' => 127, 'length' => 1],
    'Origin' => ['start' => 129, 'length' => 1],
    'FloppyId' => ['start' => 131, 'length' => 4],
    'Emul_override' => ['start' => 136, 'length' => 2],
    'AYSound' => ['start' => 139, 'length' => 1],
    'MultiLoad' => ['start' => 141, 'length' => 1],
    'Language' => ['start' => 143, 'length' => 3],
    'Score' => ['start' => 147, 'length' => 3],
    'Author' => ['start' => 151, 'length' => 100],
];

function getField($line, $field) {
    global $fieldDefinitions;
    $start = $fieldDefinitions[$field]['start'];
    $length = $fieldDefinitions[$field]['length'];
    $line = str_replace("'", " ", $line);
    if ($field === 'Memory' || $field === 'Score') {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 3, ' ', STR_PAD_LEFT);
    }
    if ($field === 'FloppyId') {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 4, ' ', STR_PAD_LEFT);
    }
    if ($field === 'FileSize') {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 7, ' ', STR_PAD_LEFT);
    }
    return trim(substr($line, $start - 1, $length));
}

function getActualFileName($dir, $filename, $sgd_path_index) {
    global $gameDirs, $getActualFileNameENABLED;
    if (!$getActualFileNameENABLED || $sgd_path_index > 100) return $filename;
    $csvFilePath = 'sgd_filenames_index.csv';
    if (file_exists($csvFilePath)) {
        if (($handle = fopen($csvFilePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $pcName = $data[0];
                $realPcName = $data[1];
                if (str_replace(' ', '', strtolower($filename)) == str_replace(' ', '', strtolower($pcName))) {
                    fclose($handle);
                    return $realPcName;
                }
            }
            fclose($handle);
        }
    }
    $normalizedPath = str_replace("\\", "/", trim($gameDirs[$sgd_path_index]));
    $DOSprefixPath = "E:/OPT/SPECTRUM/ROMSMINE/";
    $lastSlashPos = strrpos($normalizedPath, $DOSprefixPath);
    if ($lastSlashPos !== false) {
        $lastFolder = substr($normalizedPath, $lastSlashPos + strlen($DOSprefixPath));
    } else {
        $lastFolder = $normalizedPath;
    }
    $scanDir = $dir . $lastFolder . '/';
    $files = @scandir($scanDir);
    if (!$files) return $filename;
    foreach ($files as $file) {
        if (str_replace(' ', '', strtolower($file)) == str_replace(' ', '', strtolower($filename))) {
            return $file;
        }
    }
    return $filename;
}

function getActualExtrasFileName($dir, $filename, $sgd_path_index, $subfolder = "INLAYS") {
    global $gameDirs, $getActualFileNameENABLED;
    $known_ext = ["gif", "jpg", "png", "scr", "pok", "txt","SCR"];
    if (!$getActualFileNameENABLED || $sgd_path_index > 100) return $filename;
    $path_parts = pathinfo($filename);
    $filenamebase = $path_parts['filename'];
    $normalizedPath = str_replace("\\", "/", trim($gameDirs[$sgd_path_index]));
    $DOSprefixPath = "E:/OPT/SPECTRUM/ROMSMINE/";
    $lastSlashPos = strrpos($normalizedPath, $DOSprefixPath);
    if ($lastSlashPos !== false) {
        $lastFolder = substr($normalizedPath, $lastSlashPos + strlen($DOSprefixPath));
    } else {
        $lastFolder = $normalizedPath;
    }
    $scanDir = $dir . $lastFolder . '/' . $subfolder . '/';
    
    //file_put_contents("aaa.txt", $scanDir, FILE_APPEND);
    $files = @scandir($scanDir);
    if (!$files) return $filenamebase . ".GIF";
    foreach ($files as $file) {
        foreach ($known_ext as $extension) {
            $fullFilename = $filenamebase . '.' . $extension;

            if (str_replace(' ', '', strtolower($file)) == str_replace(' ', '', strtolower($fullFilename))) {
            	//file_put_contents("aaa.txt", $file, FILE_APPEND); //seems correct output for SCRSHOT
                return $file;
            }
        }
    }
    return $filenamebase . ".GIF";
}



// Read the file and build the games list (modified to NOT call getActualFileName during initial load)
$file = fopen($dat_file, 'r');
$games = [];
if ($create_sgd_filename_csv) {
    $existingEntries = [];
    if ($handle = fopen($csvFilePath, 'r')) {
        while (($line = fgets($handle)) !== false) {
            $parts = explode(',', $line);
            if (!empty($parts[0])) {
                $existingEntries[trim($parts[0])] = true;
            }
        }
        fclose($handle);
    }
}
while (($line = fgets($file)) !== false) {
    $game = [];
    foreach ($fieldDefinitions as $field => $definition) {
        $game[$field] = getField($line, $field);
    }
    if ($create_sgd_filename_csv) {
        if (!isset($existingEntries[$game['PC-Name']])) {
            $entry = $game['PC-Name'] . ',' . getActualFileName("./ROMSMINE/", $game['PC-Name'], $game['PathIndex']) . "\n";
            file_put_contents($csvFilePath, $entry, FILE_APPEND);
            $existingEntries[$game['PC-Name']] = true;
        }
    }
    $games[] = $game;
}
fclose($file);


// Handle AJAX request for filenames
if (isset($_GET['action']) && $_GET['action'] === 'getFilenames') {
    if (isset($_GET['filename']) && isset($_GET['pathIndex'])) {
        $filename = $_GET['filename'];
        $pathIndex = $_GET['pathIndex'];
        $actualFilename = getActualFileName("./ROMSMINE/", $filename, $pathIndex);
        $actualInlayFilename =   getActualExtrasFileName("./ROMSMINE/", $filename, $pathIndex);
        $actualTxtFilename =     getActualExtrasFileName("./ROMSMINE/", $filename, $pathIndex, "INFO");
        $actualSCRSHOTFilename = getActualExtrasFileName("./ROMSMINE/", $filename, $pathIndex,"SCRSHOT");
        echo json_encode([
            'actualFilename' 		=> $actualFilename,
            'actualInlayFilename' 	=> $actualInlayFilename,
            'actualTxtFilename' 	=> $actualTxtFilename,
            'actualScrshotFilename' => $actualSCRSHOTFilename,
        ]);
        exit;
    } else {
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }
}


// Handle game update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gameData = $_POST;
    $lineIndex = $gameData['lineIndex'];
    unset($gameData['lineIndex']);
    $fileLines = file($dat_file);
    $lineToUpdate = $fileLines[$lineIndex];
    foreach ($fieldDefinitions as $field => $definition) {
        $start = $definition['start'] - 1;
        $length = $definition['length'];
        $lineToUpdate = substr_replace($lineToUpdate, str_pad($gameData[$field], $length), $start, $length);
    }
    $fileLines[$lineIndex] = $lineToUpdate;
    file_put_contents($dat_file, implode('', $fileLines));
    $updatedGame = [];
    foreach ($fieldDefinitions as $field => $definition) {
        $updatedGame[$field] = $gameData[$field];
    }
    echo json_encode(['status' => 'success', 'updatedGame' => $updatedGame, 'lineIndex' => $lineIndex]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spectrum Game Database Web (SGD)</title>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: lightgrey;
            font-family: "Courier New", Courier, monospace;
        }
        #left-panel, #right-panel {
            padding: 20px;
            overflow-y: auto;
        }
        #left-panel {
            width: 60%;
            border-right: 1px solid #ccc;
            background-color: lightgrey;
        }
        #right-panel {
            flex: 1;
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: gainsboro;
            border: 1px solid #ccc;
            padding: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: calc(100vh - 20px);
            overflow-y: auto;
        }
        .game-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        .game-item:hover {
            background-color: #eee;
        }
        .game-item.active {
            background-color: #dcdcdc;
        }
        .form-field {
            margin-bottom: 10px;
        }
        .form-field label {
            font-weight: bold;
        }
        .form-field input, .form-field textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: Silver;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
    <script>
        const gameDirs = <?php echo $jsonGameDirs; ?>;
        selectedGameDirectoryDisplay = '';
        function updateGameDirectoryDisplay() {
            const pathIndex = document.getElementById('PathIndex').value;
            const displayElement = document.getElementById('gameDirectoryDisplay');
            const index = parseInt(pathIndex, 10);
            if (!isNaN(index) && index >= 0 && index < gameDirs.length) {
                displayElement.textContent = gameDirs[index];
                selectedGameDirectoryDisplay = gameDirs[index];
            } else {
                displayElement.textContent = 'Invalid index';
            }
        }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', () => {
    const gameItems = document.querySelectorAll('.game-item');
    const form = document.getElementById('game-form');
    const fields = <?php echo json_encode(array_keys($fieldDefinitions)); ?>;
    const gameImage = document.getElementById('game-screenshot');
    const gameImage2 = document.getElementById('game-screenshot2');
    const gameImageContainer = document.getElementById('game-image');
    const filterInput = document.getElementById('filter-input');
    const filterSelect = document.getElementById('filter-select');
    const toggleRightPanelButton = document.getElementById('toggle-right-panel');
    const rightPanel = document.getElementById('right-panel');
    const originalGames = <?php echo json_encode($games); ?>;
    const host = '<?php echo $host; ?>';
    gameItems.forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.game-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            const gameData = JSON.parse(item.dataset.game);
            fields.forEach(field => {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) input.value = gameData[field];
            });
            form.querySelector('[name="lineIndex"]').value = item.dataset.index;
            //const letterDir = gameData.letterDir;
            const letterDir = gameData.letterDir.replace(/\\/g, '');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=getFilenames&filename=' + encodeURIComponent(gameData['PC-Name']) + '&pathIndex=' + gameData['PathIndex'])
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error("Error fetching filenames:", data.error);
                        return;
                    }
                    console.log('actualTxtFilename='+data.actualTxtFilename);
                    myimagename = data.actualInlayFilename;
                    mypath = 'ROMSMINE/' + letterDir + '/INLAYS/' + myimagename;
                    console.log(mypath);
                    imagePath = mypath;
                    const img = new Image();
                    img.onload = () => {
                        gameImage.style.display = 'block';
                        gameImage.src = imagePath;
                    };
                    img.onerror = () => {
                        gameImage.style.display = 'none';
                    };
                    img.src = imagePath;

                    //jon 250919a
                    console.log('data.actualScrshotFilename='+data.actualScrshotFilename);
                    myimagename2 = data.actualScrshotFilename;
                    //mypath2 = 'ROMSMINE/' + letterDir + '/SCRSHOT/scr2png.php?scrimg=' + myimagename2;
                    mypath2 = 'scr2png.php?scrimg='+'ROMSMINE/' + letterDir + '/SCRSHOT/' + myimagename2;
                    //console.log(' mypath2='+mypath2);
                    imagePath2 = mypath2;
                    const img2 = new Image();
                    img2.onload = () => {
                        gameImage2.style.display = 'block';
                        gameImage2.src = imagePath2;
                    };
                    img2.onerror = () => {
                        gameImage2.style.display = 'none';
                    };
                    img2.src = imagePath2;



                    updateGameDirectoryDisplay();
                });
        });
    });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const updatedGame = data.updatedGame;
                    const lineIndex = data.lineIndex;
                    const updatedGameItem = document.querySelector(`.game-item[data-index="${lineIndex}"]`);
                    if (updatedGameItem) {
                        updatedGameItem.dataset.game = JSON.stringify(updatedGame);
                        updatedGameItem.querySelector('strong').textContent = updatedGame.Name;
                    }
                    alert('Game updated successfully!');
                }
            });
    });
    const nameFilterInput = document.getElementById('nameFilter');
    const typeFilterInput = document.getElementById('typeFilter');
    const yearFilterInput = document.getElementById('yearFilter');
    const firstLetterFilterInput = document.getElementById('firstLetterFilter');
    nameFilterInput.addEventListener('input', filterGames);
    typeFilterInput.addEventListener('input', filterGames);
    yearFilterInput.addEventListener('input', filterGames);
    firstLetterFilterInput.addEventListener('input', filterGames);
    function filterGames() {
        const nameFilterValue = nameFilterInput.value.toLowerCase();
        const typeFilterValue = typeFilterInput.value.toLowerCase();
        const yearFilterValue = yearFilterInput.value.toLowerCase();
        const firstLetterFilterValue = firstLetterFilterInput.value.toLowerCase();
        const filteredGames = originalGames.filter(game => {
            const nameMatch = game.Name.toLowerCase().includes(nameFilterValue);
            const typeMatch = game.Type.toLowerCase().includes(typeFilterValue);
            const yearMatch = game.Year.includes(yearFilterValue);
            const firstLetterMatch = game.Name.toLowerCase().startsWith(firstLetterFilterValue);
            return (
                (nameFilterValue === '' || nameMatch) &&
                (typeFilterValue === '' || typeMatch) &&
                (yearFilterValue === '' || yearMatch) &&
                (firstLetterFilterValue === '' || firstLetterMatch)
            );
        });
        const gameList = document.getElementById('game-list');
        gameList.innerHTML = '';
        filteredGames.forEach((game, index) => {
            const gameItem = document.createElement('tr');
            gameItem.classList.add('game-item');
            const originalIndex = originalGames.indexOf(game);
            gameItem.dataset.game = JSON.stringify(game);
            gameItem.dataset.index = originalIndex;
            gameItem.innerHTML = `
                <td><strong>${game.Name}</strong></td>
                <td>${game.Type}</td>
                <td>${game.Year}</td>
                <td>${game['PC-Name']}</td>
                <td>${game.Author}</td>
            `;
            gameList.appendChild(gameItem);
            gameItem.addEventListener('click', () => {
                document.querySelectorAll('.game-item').forEach(i => i.classList.remove('active'));
                gameItem.classList.add('active');
                const gameData = JSON.parse(gameItem.dataset.game);
                fields.forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) input.value = gameData[field];
                });
                form.querySelector('[name="lineIndex"]').value = gameItem.dataset.index;
                const letterDir = gameData.letterDir;
                myimagename = gameData['PC-Name'].slice(0, -4).toUpperCase().replace(/\s+/g, '');
                mypath = 'ROMSMINE/' + letterDir + '/INLAYS/' + myimagename + '.GIF';
                imagePath = "https://" + host + '/' + mypath;
                const img = new Image();
                img.onload = () => {
                    gameImage.style.display = 'block';
                    gameImage.src = imagePath;
                };
                img.onerror = () => {
                    gameImage.style.display = 'none';
                };
                img.src = imagePath;
                updateGameDirectoryDisplay();
            });
        });
    }
    toggleRightPanelButton.addEventListener('click', () => {
        if (rightPanel.style.display === 'none' || rightPanel.style.display === '') {
            rightPanel.style.display = 'block';
        } else {
            rightPanel.style.display = 'none';
        }
    });
});
function processFilename(str) {
    var SGDfilePathPrefix = '<?php echo $SGDfilePathPrefix; ?>';
    var filepath = selectedGameDirectoryDisplay;
    filepath = selectedGameDirectoryDisplay.replace(SGDfilePathPrefix + '\\', "");
    filepath.replace(/\s+/g, '');
    str = str.replace(/\s+/g, '');
    str = filepath + "/" + str;
    str = '<?php echo $qaopURL; ?>?#l=<?php echo $snapsURL; ?>' + str;
    return str;
}
    </script>
</head>
<body>
    <div id="left-panel">
        <h3>Game List</h3>
        <div>
            <input type="text" id="nameFilter" placeholder="Filter by Name">
            <input type="text" id="typeFilter" placeholder="Filter by Type">
            <input type="text" id="yearFilter" placeholder="Filter by Year">
            <input type="text" id="firstLetterFilter" placeholder="Filter by First Letter">
            <BR><button id="toggle-right-panel">Toggle Right Panel</button>
        </div>
        <table id="game-list">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Year</th>
                    <th>PC-Name</th>
                    <th>Author</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $index => $game):
                    if ($game['PathIndex'] > 100) $game['PathIndex'] = 1;
                    $letterDir = str_replace($SGDfilePathPrefix2, '', $gameDirs[$game['PathIndex']]);
                ?>
                    <tr class="game-item" data-game='<?php echo json_encode(array_merge($game, ['letterDir' => $letterDir])); ?>' data-index="<?php echo $index; ?>">
                        <td><strong><?php echo htmlspecialchars($game['Name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($game['Type']); ?></td>
                        <td><?php echo htmlspecialchars($game['Year']); ?></td>
                        <td><?php echo htmlspecialchars($game['PC-Name']); ?></td>
                        <td><?php echo htmlspecialchars($game['Author']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="right-panel">
        <h3>Game Details</h3>
        <form id="game-form">
            <input type="hidden" name="lineIndex" value="">
            <button type="button" onclick="window.open(processFilename(document.getElementById('PC-Name').value), '_blank');">PLAY</button>
            <button type="submit">SAVE</button>
            <div class="form-field">
                <label for="Name">Name:</label>
                <input type="text" id="Name" name="Name" value="" maxlength="36" size="36" />
            </div>
            <div class="form-field">
                <label for="Publisher">Publisher:</label>
                <input type="text" id="Publisher" name="Publisher" value="" maxlength="36" size="36" />
            </div>
            <div class="form-field">
                <label for="Author">Author:</label>
                <input type="text" id="Author" name="Author" value="" maxlength="50" size="50" />
            </div>
            <div class="form-field"></div>
            <div class="form-field">
                <label for="Year">Year:</label>
                <input type="text" id="Year" name="Year" value="" maxlength="4" size="4" />
                <label for="Memory">Memory:</label>
                <input type="text" id="Memory" name="Memory" value="" maxlength="3" size="3" />
            </div>
            <div class="form-field">
                <label for="NoOfPlayers">NoOfPlayers:</label>
                <input type="text" id="NoOfPlayers" name="NoOfPlayers" value="" maxlength="1" size="1" />
                <label for="Together">Together:</label>
                <input type="text" id="Together" name="Together" value="" maxlength="1" size="1" />
                <label for="Score">Score:</label>
                <input type="text" id="Score" name="Score" value="" maxlength="3" size="3" />
            </div>
            <div class="form-field">
                <label for="PC-Name">PC-Name:</label>
                <input type="text" id="PC-Name" name="PC-Name" value="" maxlength="12" size="12" />
                <label for="Type">Type:</label>
                <input type="text" id="Type" name="Type" value="" maxlength="7" size="7" />
            </div>
            <div class="form-field">
                <label for="Joysticks">Joysticks:</label>
                <input type="text" id="Joysticks" name="Joysticks" value="" maxlength="5" size="5" />
            </div>
            <div class="form-field">
                <label for="Orig_screen">Orig_screen:</label>
                <input type="text" id="Orig_screen" name="Orig_screen" value="" maxlength="1" size="1" />
                <label for="Origin">Origin:</label>
                <input type="text" id="Origin" name="Origin" value="" maxlength="1" size="1" />
            </div>
            <div class="form-field">
                <label for="AYSound">AYSound:</label>
                <input type="text" id="AYSound" name="AYSound" value="" maxlength="1" size="1" />
                <label for="MultiLoad">MultiLoad:</label>
                <input type="text" id="MultiLoad" name="MultiLoad" value="" maxlength="1" size="1" />
                <label for="FloppyId">FloppyId:</label>
                <input type="text" id="FloppyId" name="FloppyId" value="" maxlength="4" size="4" />
            </div>
            <div class="form-field">
                <label for="Language">Language:</label>
                <input type="text" id="Language" name="Language" value="" maxlength="3" size="3" />
                <label for="Emul_override">Emul_override:</label>
                <input type="text" id="Emul_override" name="Emul_override" value="" maxlength="2" size="2" />
            </div>
            <div class="form-field">
                <label for="PathIndex">PathIndex:</label>
                <input type="text" id="PathIndex" name="PathIndex" value="" maxlength="3" size="3" />
                <label for="FileSize">FileSize:</label>
                <input type="text" id="FileSize" name="FileSize" value="" maxlength="7" size="7" />
            </div>
            <div id="gameDirectoryDisplay"></div>
            <div class="form-field"></div>
            <button type="submit">SAVE</button>
        </form>
        <div id="game-image" style="margin-bottom: 20px; text-align: center;">
            <img id="game-screenshot" src="" alt="Game Screenshot" style="max-width: 100%; height: auto; display: none;">
            <img id="game-screenshot2" src="" alt="Game Screenshot" style="max-width: 100%; height: auto; display: none;">
        </div>
    </div>
</body>
</html>
