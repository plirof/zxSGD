<?php
/*
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

======
ToDO:
-Add extra image for showing .SCR files
-Add .TXT display after the images (maybe if pressed ??) 
-Show POKES and try to create a temp file for POKES

*/


$host = $_SERVER['HTTP_HOST'];//."/img";

//$dat_file='SGD_sample.dat';
//$dat_file='/opt/lampp/htdocs/zx/ROMSMINE/_SGD304/Arcade4.dat';
$dat_file='ROMSMINE/_SGD304/webtestsgd.dat';
#$qaopURL='http://localhost/zx/QAOP/qaop.html';
$qaopURL='QAOP/qaop.html';
$snapPATH="ROMSMINE/";
//$snapsURL='http://localhost/zx/'.$snapPATH;
//$snapsURL='http://'.$host.'/zx/'.$snapPATH;//ORIG localhost
$snapsURL='https://'.$host .'/'/*.'/zx/' */.$snapPATH; //for WEB
$SGDfilePathPrefix='E:\\\OPT\\\SPECTRUM\\\ROMSMINE';

//$iniFilePath = '/opt/lampp/htdocs/zx/ROMSMINE/_SGD304/sgd.ini';
$iniFilePath = 'ROMSMINE/_SGD304/sgd.ini';
//echo "</td><td><a href=".$qaopURL."?#l=".$snapsURL.get_path($fields[4]).$fields[4]. ">".$fields[4]."</a> "; //added Letter path ROMSMINE
// These are for finding REAL filename (with correct CASE) - Note this slows
$getActualFileNameENABLED=true; // SLOW.This will scan all drives for the actual REAL filename (in DB all files are stored in UPPERCASE. In linux are stored mixed case)
$create_sgd_filename_csv = false; // SLOW. This will create a lookup table for stored and real filenames (for linux)
// Path to the CSV file
$csvFilePath = 'sgd_filenames_index.csv';



//==========get SGD.ini paths :=======================

// Define the path to your .ini file


// Define the new prefix you want to set
$newPrefix = 'NEW_PREFIX_FOR_PATH'; // Change this to what you need

// Initialize an array to hold the GameDir paths
$gameDirs = [];

// Open the file for reading
if ($fileHandle = fopen($iniFilePath, 'r')) {
    // Read each line of the file
    while (($line = fgets($fileHandle)) !== false) {
        // Trim whitespace from the line
        $line = trim($line);
        
        // Check if the line starts with 'GameDir'
        if (strpos($line, 'GameDir') === 0) {
            // Extract the path after the '=' sign
            $parts = explode('=', $line);
            if (count($parts) === 2) {
                $path = trim($parts[1]);
                // Adjust path with the new prefix
                ///$newPath = preg_replace('/^E:\\OPT\\SPECTRUM\\ROMSMINE\\/', $newPrefix . '\\', $path);
                // Store the modified path
                $gameDirs[]=$path;
                //$gameDirs[] = $newPath;
            }
        }
    }
    // Close the file handle
    fclose($fileHandle);
} else {
    echo "Unable to open the file.";
}
// Convert PHP array to JSON
$jsonGameDirs = json_encode($gameDirs);

// Output the modified GameDir paths
foreach ($gameDirs as $gameDir) {
    //echo $gameDir . PHP_EOL."<BR>";
}

//==END of========get SGD.ini paths :=======================











// Database field definitions
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

// Helper function to extract a field from a line
/* ORIGINAL
function getField($line, $field) {
    global $fieldDefinitions;
    $start = $fieldDefinitions[$field]['start'];
    $length = $fieldDefinitions[$field]['length'];
    return trim(substr($line, $start - 1, $length));
} */

// Helper function to extract a field from a line
function getField($line, $field) {
    global $fieldDefinitions;
    $start = $fieldDefinitions[$field]['start'];
    $length = $fieldDefinitions[$field]['length'];
    $line = str_replace("'", " ", $line); //250805 single quote showed error in console

    // Special case for Memory field, pad it to the left with spaces if needed
    if ($field === 'Memory' || $field === 'Score' ) {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 3, ' ', STR_PAD_LEFT); // Ensure it is always 3 characters and right-aligned
    }
    if ($field === 'FloppyId') {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 4, ' ', STR_PAD_LEFT); // Ensure it is always 3 characters and right-aligned
    } 
    if ($field === 'FileSize') {
        $value = trim(substr($line, $start - 1, $length));
        return str_pad($value, 7, ' ', STR_PAD_LEFT); // Ensure it is always 3 characters and right-aligned
    }    



    return trim(substr($line, $start - 1, $length));
}//end ofgetField
/*
//Get the filename with correct case from the filesystem
function getActualFileName($dir, $filename,$sgd_path_index) {
    // Scan the directory
    $files = scandir($dir);

    // Iterate through the files in the directory
    foreach ($files as $file) {
        // Check if the file matches the name exactly (case-sensitive)
        if (strtolower($file) == strtolower($filename)) {
            return $file; // Return the actual filename with correct capitalization
        }
    }
    return false; // Return false if the file doesn't exist
}
*/

//Get the filename with correct case from the filesystem
function getActualFileName($dir, $filename,$sgd_path_index) {
    global $gameDirs,$getActualFileNameENABLED; // Access the global $gameDirs array
    //if ($sgd_path_index>100) $sgd_path_index="1"; // Some games have PathIndex=255
    if(!$getActualFileNameENABLED || $sgd_path_index>100 ) return $filename; //if disabled don't do the check
    // Extract the last folder from the GameDir path based on PathIndex

    // Step 1: Check if the CSV file exists
    $csvFilePath = 'sgd_filenames_index.csv';  // Define the path to the CSV file
    if (file_exists($csvFilePath)) {
        // Step 2: Open the CSV file and check each line
        if (($handle = fopen($csvFilePath, 'r')) !== false) {
            // Read the file line by line
            while (($data = fgetcsv($handle)) !== false) {
                // Expecting the CSV format to be: PC-Name,RealPCName
                $pcName = $data[0];
                $realPcName = $data[1];

                // Step 3: Check if the filename matches the PC-Name (case-insensitive)
                //if (strtolower($filename) == strtolower($pcName)) {
                if (str_replace(' ', '', strtolower($filename))  == str_replace(' ', '',strtolower($pcName))) {

                    fclose($handle); // Close the CSV file before returning
                    return $realPcName; // Return the RealPCName if found
                }
            }
            fclose($handle); // Close the CSV file after reading all lines
        }
    }

    // Step 1: Normalize the path (replace backslashes with forward slashes)
    $normalizedPath = str_replace("\\", "/", trim($gameDirs[$sgd_path_index]));
    $DOSprefixPath="E:/OPT/SPECTRUM/ROMSMINE/";
    // Step 2: Find the position of the last slash
    $lastSlashPos = strrpos($normalizedPath, '/');
    $lastSlashPos = strrpos($normalizedPath, $DOSprefixPath);

    // Step 3: Extract the last folder after the last slash
    if ($lastSlashPos !== false) {
        $lastFolder = substr($normalizedPath, $lastSlashPos + 1);
        $lastFolder = substr($normalizedPath, $lastSlashPos + strlen($DOSprefixPath));
    } else {
        // If there's no slash, the whole path is the last folder
        $lastFolder = $normalizedPath;
    }
    //echo "<h2>lastFolder=$lastFolder, gamedirs=".$gameDirs[$sgd_path_index].", normalizedPath =$normalizedPath </h2>";
    // Construct the full path to scan
    $scanDir = $dir . $lastFolder . '/';// . $filename;
     //echo "==normalizedPath=$normalizedPath, <BR>scandir= $scanDir ,<BR>lastFolder=$lastFolder<BR>";
    // Scan the directory
    $files = @scandir($scanDir);
    if(!$files) return $filename; //If scandir failed just return the original stored name
    // Iterate through the files in the directory
    foreach ($files as $file) {
        // Check if the file matches the name exactly (case-sensitive)
        if (str_replace(' ', '', strtolower($file))  == str_replace(' ', '',strtolower($filename))) {

            return $file; // Return the actual filename with correct capitalization
        }
    }
    return $filename;
    return false; // Return false if the file doesn't exist
} //END of getActualFilenam

//+++++++++++++++++++++++++++++++++++++++
//Get the filename with correct case from the filesystem
function getActualExtrasFileName($dir, $filename,$sgd_path_index,$subfolder="INLAYS") {
    global $gameDirs,$getActualFileNameENABLED; // Access the global $gameDirs array
    $known_ext=["gif","jpg","png","scr","pok","txt"];
	//if ($sgd_path_index>100) $sgd_path_index="1"; // Some games have PathIndex=255
    if(!$getActualFileNameENABLED || $sgd_path_index>100 ) return $filename; //if disabled don't do the check
    // Extract the last folder from the GameDir path based on PathIndex
	//250809 - REMOVED check for CSV pre-filled file for images/POK and 
	$path_parts = pathinfo($filename);
	// The filename without extensio
	$filenamebase= $path_parts['filename'];
	//echo "<h4>".$basename_without_extension."</h4>"; // outputs: my file
	
    // Step 1: Normalize the path (replace backslashes with forward slashes)
    $normalizedPath = str_replace("\\", "/", trim($gameDirs[$sgd_path_index]));
    $DOSprefixPath="E:/OPT/SPECTRUM/ROMSMINE/";
    // Step 2: Find the position of the last slash
    $lastSlashPos = strrpos($normalizedPath, '/');
    $lastSlashPos = strrpos($normalizedPath, $DOSprefixPath);

    // Step 3: Extract the last folder after the last slash
    if ($lastSlashPos !== false) {
        $lastFolder = substr($normalizedPath, $lastSlashPos + 1);
        $lastFolder = substr($normalizedPath, $lastSlashPos + strlen($DOSprefixPath));
    } else {
        // If there's no slash, the whole path is the last folder
        $lastFolder = $normalizedPath;
    }
    //echo "<h2>lastFolder=$lastFolder, gamedirs=".$gameDirs[$sgd_path_index].", normalizedPath =$normalizedPath </h2>";
    // Construct the full path to scan
    $scanDir = $dir . $lastFolder . '/'.$subfolder.'/';// . $filename;
     //echo "==normalizedPath=$normalizedPath, <BR>scandir= $scanDir ,<BR>lastFolder=$lastFolder<BR>";
    // Scan the directory
    $files = @scandir($scanDir);
	//print_r($files);
    if(!$files) return $filename; //If scandir failed just return the original stored name
    // Iterate through the files in the directory
    foreach ($files as $file) {
        // Check if the file matches the name exactly (case-sensitive)
		foreach ($known_ext as $extension) {
    $fullFilename = $filenamebase . '.' . $extension;
			///if (str_replace(' ', '', strtolower($file))  == str_replace(' ', '',strtolower($filename))) {
			//echo "\n||| file=$file  AND  fullFilename=$fullFilename";
			if (str_replace(' ', '', strtolower($file))  == str_replace(' ', '',strtolower($fullFilename))) {
				return $file; // Return the actual filename with correct capitalization
			}
		}
    }
    return $filenamebase.".GIF";
    return false; // Return false if the file doesn't exist
}


//-------------------------------------




// Read the file and build the games list
/*
$file = fopen($dat_file, 'r');
$games = [];
while (($line = fgets($file)) !== false) {
    $game = [];
    foreach ($fieldDefinitions as $field => $definition) {
        $game[$field] = getField($line, $field);

    }
    $game['PC-Name']=getActualFileName("/opt/spectrum/ROMSMINE/", $game['PC-Name'],$game['PathIndex']);

    $games[] = $game;
}
fclose($file);
*/

// Assuming create_sgd_filename_csv is defined somewhere
//$create_sgd_filename_csv = true; // Example flag (set according to your condition)


// Read the file and build the games list
$file = fopen($dat_file, 'r');
$games = [];

if ($create_sgd_filename_csv) {
    // Check if the CSV file exists. If not, create it.
    if (!file_exists($csvFilePath)) {
        // Create the file if it doesn't exist.
        fopen($csvFilePath, 'w');
    }
    
    // Read existing entries from the CSV file to avoid duplicates
    $existingEntries = [];
    if ($handle = fopen($csvFilePath, 'r')) {
        while (($line = fgets($handle)) !== false) {
            // Get the PC-Name (the first field in each line) and store in the array
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

    // AAAAAAAAA
    
    
    // If the CSV file creation flag is true, check for duplicate entries
    if ($create_sgd_filename_csv) {
        // If the PC-Name is not already in the CSV, add it
        if (!isset($existingEntries[$game['PC-Name']])) {
            //$entry = $game['PC-Name'] . ',' . getActualFileName("/opt/spectrum/ROMSMINE/", $game['PC-Name'], $game['PathIndex']) . "\n";
            $entry = $game['PC-Name'] . ',' . getActualFileName("./ROMSMINE/", $game['PC-Name'], $game['PathIndex']) . "\n";

            // Append the new entry to the CSV file
            file_put_contents($csvFilePath, $entry, FILE_APPEND);
            // Add the new entry to the in-memory array to prevent future duplicates in this session
            $existingEntries[$game['PC-Name']] = true;
        }
    }
    //$game['PC-Name'] = getActualFileName("/opt/spectrum/ROMSMINE/", $game['PC-Name'], $game['PathIndex']);
    $game['PC-Name'] = getActualFileName("./ROMSMINE/", $game['PC-Name'], $game['PathIndex']);
	$game['PC-Inlays'] = getActualExtrasFileName("./ROMSMINE/", $game['PC-Name'], $game['PathIndex']);
	$game['PC-txt'] = getActualExtrasFileName("./ROMSMINE/", $game['PC-Name'], $game['PathIndex'],"TXT");
    $games[] = $game;
}

fclose($file);


// Handle game update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gameData = $_POST;
    $lineIndex = $gameData['lineIndex']; // Index of the game to update (those that file was not found)
    unset($gameData['lineIndex']); // Remove the lineIndex from POST data

    // Update the game data logic here
    $fileLines = file($dat_file);
    $lineToUpdate = $fileLines[$lineIndex];
    foreach ($fieldDefinitions as $field => $definition) {
        $start = $definition['start'] - 1;
        $length = $definition['length'];
        $lineToUpdate = substr_replace($lineToUpdate, str_pad($gameData[$field], $length), $start, $length);
    }
    $fileLines[$lineIndex] = $lineToUpdate;
    file_put_contents($dat_file, implode('', $fileLines));

    // Return the updated game as well
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
            #font-family: Arial, sans-serif;
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
            #flex: 1;
            position: fixed;
            top: 10px; /* Distance from the top */
            right: 10px; /* Distance from the right */
            background-color: gainsboro; /* Semi-transparent background */
            border: 1px solid #ccc;
            padding: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000; /* Make sure it appears above other content */
            max-height: calc(100vh - 20px); /* max height minus top and bottom offsets */
            overflow-y: auto; /* Enable vertical scrolling when needed */
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
        // Assign the PHP JSON array to a JavaScript variable
        const gameDirs = <?php echo $jsonGameDirs; ?>;
        selectedGameDirectoryDisplay=''; //This variable will be used in file opening
        // Now you can use `gameDirs` in your JavaScript code
        //console.log(gameDirs);
        // Function to update display based on PathIndex input
        function updateGameDirectoryDisplay() {
            const pathIndex = document.getElementById('PathIndex').value;
            const displayElement = document.getElementById('gameDirectoryDisplay');
            //console.log('updateGameDirectoryDisplay()');
            // Validate the input to ensure it's a valid index
            const index = parseInt(pathIndex, 10);
            if (!isNaN(index) && index >= 0 && index < gameDirs.length) {
                displayElement.textContent = gameDirs[index]; // Display the corresponding game directory
                selectedGameDirectoryDisplay =gameDirs[index]; 
            } else {
                displayElement.textContent = 'Invalid index'; // Show an error message
            }
        }
    </script>

    <script>

document.addEventListener('DOMContentLoaded', () => {
    const gameItems = document.querySelectorAll('.game-item');
    const form = document.getElementById('game-form');
    const fields = <?php echo json_encode(array_keys($fieldDefinitions)); ?>;
    const gameImage = document.getElementById('game-screenshot');
    const gameImageContainer = document.getElementById('game-image');
    const filterInput = document.getElementById('filter-input');
    const filterSelect = document.getElementById('filter-select');
    const toggleRightPanelButton = document.getElementById('toggle-right-panel');
    const rightPanel = document.getElementById('right-panel');
    const originalGames = <?php echo json_encode($games); ?>; // Store the original games from the PHP response
    const host = '<?php echo $host; ?>';

    // Attach click handlers to game items
    gameItems.forEach(item => {
        item.addEventListener('click', () => {
            // Remove active class from all items
            document.querySelectorAll('.game-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // Load game data into form
            const gameData = JSON.parse(item.dataset.game);
            fields.forEach(field => {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) input.value = gameData[field];
            });
            form.querySelector('[name="lineIndex"]').value = item.dataset.index; // Store the index for the form submission

            // Load and display the game image
            const letterDir = gameData.letterDir; // Get the letter directory from the game data
            //myimagename = gameData['PC-Name'].slice(0, -4).toUpperCase().replace(/\s+/g, '');
			myimagename = gameData['PC-Inlays'];
            ///mypath = 'ROMSMINE/' + letterDir + '/INLAYS/' + myimagename + '.GIF';
			mypath = 'ROMSMINE/' + letterDir + '/INLAYS/' + myimagename;
            imagePath = "https://" + host + '/' + mypath; // Create the image path
            console.log("============line335:===== imagepath=" + imagePath);

            // Set the image source
            ///gameImage.src = imagePath;
			gameImage.src = mypath;
            // Check if the image exists, then display it
            const img = new Image();
            img.onload = () => {
                gameImage.style.display = 'block'; // Show the image if it loads
                gameImage.src = imagePath; // Set the image source
            };
            img.onerror = () => {
                gameImage.style.display = 'none'; // Hide image if it doesn't exist
            };
            img.src = imagePath; // Trigger the image load
            updateGameDirectoryDisplay();//console.log("updateGameDirectoryDisplay();");
        });
    });

    // Handle form submission
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
                    // Update the left panel with the updated game
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

    // Filter game list based on multiple input fields
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

        // Update the game list in the left panel
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

            // Attach click handler to the newly created item
            gameItem.addEventListener('click', () => {
                // Remove active class from all items
                document.querySelectorAll('.game-item').forEach(i => i.classList.remove('active'));
                gameItem.classList.add('active');

                // Load game data into form
                const gameData = JSON.parse(gameItem.dataset.game);
                fields.forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) input.value = gameData[field];
                });
                form.querySelector('[name="lineIndex"]').value = gameItem.dataset.index; // Store the index for the form submission

                const letterDir = gameData.letterDir; // Get the letter directory from the game data
                myimagename = gameData['PC-Name'].slice(0, -4).toUpperCase().replace(/\s+/g, '');
                mypath = 'ROMSMINE/' + letterDir + '/INLAYS/' + myimagename + '.GIF';
                imagePath = "https://" + host + '/' + mypath; // Create the image path
                console.log("============line335:===== imagepath=" + imagePath);
                // Set the image source
                ///gameImage.src = imagePath;//250807ok worked
				gameImage.src = mypath;

                // Check if the image exists, then display it
                const img = new Image();
                img.onload = () => {
                    gameImage.style.display = 'block'; // Show the image if it loads
                    gameImage.src = imagePath; // Set the image source
                };
                img.onerror = () => {
                    gameImage.style.display = 'none'; // Hide image if it doesn't exist
                };
                img.src = imagePath; // Trigger the image load

                // Call to update the game directory display based on the PathIndex
                updateGameDirectoryDisplay();
            });
        });
    }

    // Toggle right panel visibility
    toggleRightPanelButton.addEventListener('click', () => {
        if (rightPanel.style.display === 'none' || rightPanel.style.display === '') {
            rightPanel.style.display = 'block';
        } else {
            rightPanel.style.display = 'none';
        }
    });
});



/* Also exists in header (don't know which is used)
function updateGameDirectoryDisplay() {
    const pathIndex = document.getElementById('PathIndex').value;
    const displayElement = document.getElementById('gameDirectoryDisplay');
    //console.log('updateGameDirectoryDisplay()');
    // Validate the input to ensure it's a valid index
    const index = parseInt(pathIndex, 10);
    if (!isNaN(index) && index >= 0 && index < gameDirs.length) {
        displayElement.textContent = gameDirs[index]; // Display the corresponding game directory
        selectedGameDirectoryDisplay=gameDirs[index];
    } else {
        displayElement.textContent = 'Invalid index'; // Show an error message
    }
}
*/
function processFilename(str) {
    var SGDfilePathPrefix='<?php echo $SGDfilePathPrefix; ?>';
    var filepath=selectedGameDirectoryDisplay;
    var filepath=selectedGameDirectoryDisplay.replace(SGDfilePathPrefix+'\\', "");
    filepath.replace(/\s+/g, '');
    str=str.replace(/\s+/g, '');// Removes all spaces from the string
    str=filepath+"/"+str;
    str='<?php echo $qaopURL; ?>?#l=<?php echo $snapsURL; ?>'+str;
    console.log("======================================");
    console.log('filepath='+filepath+"____selectedGameDirectoryDisplay="+selectedGameDirectoryDisplay+"_______-SGDfilePathPrefix="+SGDfilePathPrefix+"_____str="+str);
    console.log("======================================");
    return str;
    //return str.replace(/\s+/g, ''); // Removes all spaces from the string
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
				    // Extract the first letter from PC-Name to use as the directory
				    $letterDir = strtoupper(substr($game['PC-Name'], 0, 1));
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
<!-- 
            <button type="button" onclick="window.open('<?php echo $qaopURL; ?>?#l=<?php echo $snapsURL; ?>' + processFilename(document.getElementById('PC-Name').value), '_blank');">PLAY</button>

    -->
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
            <!-- Image Placeholder -->
        <div id="game-image" style="margin-bottom: 20px; text-align: center;">
            <img id="game-screenshot" src="" alt="Game Screenshot" style="max-width: 100%; height: auto; display: none;">
        </div>

    </div>
	aaaaaaaaaaaaaaaaaaaaaaaaaaaa
<?php $d='ROMSMINE'; foreach(array_filter(iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d)), false), fn($f) => in_array(strtolower($f->getExtension()), ['gif','z80','scr'])) as $f) echo $f->getPathname()."\n<BR>"; ?>	bbbbbbbbbbbbbbbbbbbbbbbbbb
</body>
</html>

