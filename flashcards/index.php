<?php

session_start(); // Начинаем сессию

require_once 'inc/helper-functions.php';

$directory = 'leksika/';
$file_name = isset($_SESSION['filename']) ? basename($_SESSION['filename']) : '';
$cards = array();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['filename'])) {
    $filename = basename($_POST['filename']);
    $filepath = $directory . $filename;

    if (file_exists($filepath)) {
        // Сохраняем данные в сессии
        $_SESSION['filename'] = $_POST['filename'];
    }

    // Редирект на ту же страницу
    header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    exit;
}

// var_dump(  $_SESSION['filename']);

function listFiles($dir) {
    $files = array();
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if (is_dir($dir . $entry)) {
                    $files[$entry] = listFiles($dir . $entry . '/');
                } else {
                    $files[] = $entry;
                }
            }
        }
        closedir($handle);
    }
    return $files;
}

$fileStructure = listFiles($directory);

// Отображение данных из сессии
if (!empty($_SESSION['filename'])) {
    $statuses = getFileStatuses($_SESSION['filename']);
    // var_dump(123);
    $filename = basename($_SESSION['filename']);
    $filepath = $directory . $filename;

    if (file_exists($filepath)) {
        $fileContent = file($filepath, FILE_IGNORE_NEW_LINES);

        foreach ($fileContent as $line) {
            list($english, $translation) = explode(';', $line);
            if ($english && $translation) {
                $learned = isset($statuses[$english]) ? $statuses[$english] : 0;
                $cards[] = array('english' => $english, 'translation' => $translation, 'learned' => $learned);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Word Learning App</title>

    <link rel="stylesheet" href="css/wcl-style.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</head>

<body>
    <div class="data-b1">
        <form id="loadForm" method="POST">
            <input type="hidden" name="filename" id="filename">
            <button type="submit" id="loadButton" disabled>Load Selected File</button>
        </form>

        <div class="data-sidebar" id="sidebar">
            <ul>
                <?php

                function renderFileList($files, $parent = '') {
                    foreach ($files as $key => $value) {

                        if (is_array($value)) {
                            echo '<li>' . $key . '<ul>';
                            renderFileList($value, $parent . $key . '/');
                            echo '</ul></li>';
                        } else {
                            $activeClass = ($_SESSION['filename'] == $value) ? ' active' : '';

                            echo '<li class="' . $activeClass . '" onclick="selectFile(\'' . $parent . $value . '\')" data-file="' . $parent . $value . '">' . $value . '</li>';
                        }
                    }
                }

                renderFileList($fileStructure);
                ?>
            </ul>
        </div>
    </div>


    <div class="container">
        <div id="flashcard" class="flashcard" onclick="flipCard()">
            <div id="front"></div>
            <div id="back" class="hidden"></div>
        </div>

        <div class="data-btns">
            <div class="data-btns-item data-btns-1">
                <button onclick="prevCard()">Previous</button>
                <button onclick="nextCard()">Next</button>
            </div>

            <div class="data-btns-item data-btns-2">
                <button class="switch-button" onclick="switchDirection()"> Direction</button>
                <button class="done-button" onclick="toggleLearnedStatus()"> Done</button>
            </div>
        </div>
    </div>

    <input type="hidden" id="current-index" value="0">
    <input type="hidden" id="file-name" value="<?php echo htmlspecialchars($file_name); ?>">
    <input type="hidden" id="cards-data" value='<?php echo json_encode($cards); ?>'>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.data-sidebar li').forEach(element => {
                element.addEventListener('click', function(e) {

                    document.querySelectorAll('.data-sidebar li.active').forEach(element => {
                        element.classList.remove('active')
                    });

                    if (this.classList.contains('active')) {
                        element.classList.remove('active')
                    } else {
                        element.classList.add('active')
                    }
                })
            });
        });


        let cards = JSON.parse(document.getElementById('cards-data').value);
        let currentIndex = parseInt(document.getElementById('current-index').value, 10);
        let direction = 'en-to-ru'; // 'en-to-ru' or 'ru-to-en'


        function selectFile(fileName) {
            document.getElementById('filename').value = fileName;
            document.getElementById('loadButton').disabled = false;
        }

        function toggleStatus(element) {
            element.classList.toggle('not-learned');
            element.classList.toggle('learned');
            element.textContent = element.classList.contains('not-learned') ? 'Not Learned' : 'Learned';
        }

        function flipCard() {
            const flashcard = document.getElementById('flashcard');
            const front = document.getElementById('front');
            const back = document.getElementById('back');

            if (front.classList.contains('hidden')) {
                front.classList.remove('hidden');
                back.classList.add('hidden');
            } else {
                front.classList.add('hidden');
                back.classList.remove('hidden');
            }
        }

        function updateCard() {
            const front = document.getElementById('front');
            const back = document.getElementById('back');

            if (direction === 'en-to-ru') {
                front.innerText = cards[currentIndex].english;
                back.innerText = cards[currentIndex].translation;
            } else {
                front.innerText = cards[currentIndex].translation;
                back.innerText = cards[currentIndex].english;
            }

            back.classList.add('hidden');
            front.classList.remove('hidden');

            // Add or remove "Mark as Done" button functionality based on the word's status
            document.querySelector('.done-button').innerText = cards[currentIndex].learned ? 'Not Done' : 'Done';

            if (cards[currentIndex].learned) {
                document.querySelector('.done-button').classList.add('not-learned')
            } else {
                if (document.querySelector('.done-button').classList.contains('not-learned')) {
                    document.querySelector('.done-button').classList.remove('not-learned')
                }
            }
        }

        function prevCard() {
            currentIndex = (currentIndex - 1 + cards.length) % cards.length;
            document.getElementById('current-index').value = currentIndex;
            updateCard();
        }

        function nextCard() {
            currentIndex = (currentIndex + 1) % cards.length;
            document.getElementById('current-index').value = currentIndex;
            updateCard();
        }

        function toggleLearnedStatus() {
            const isLearned = cards[currentIndex].learned;
            cards[currentIndex].learned = isLearned ? 0 : 1;

            // console.log(cards[currentIndex].english)
            // console.log(cards[currentIndex].english)
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'inc/update_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function(data) {
                if (xhr.status === 200) {
                    console.log(xhr.responseText)
                    //console.log('Status updated');
                }
            };
            xhr.send('file_name=' + encodeURIComponent(document.getElementById('file-name').value) + '&word=' + encodeURIComponent(cards[currentIndex].english) + '&learned=' + cards[currentIndex].learned);

            updateCard();
        }

        function switchDirection() {
            direction = direction === 'en-to-ru' ? 'ru-to-en' : 'en-to-ru';
            updateCard();
        }

        // Initialize the first card
        updateCard();
    </script>


</body>

</html>