<?php declare(strict_types=1);

$sourceLibrary = getSourceLibraryKeyById();
$targetLibrary = openAndSkipFirstLine(__DIR__ . '/target_lib.csv', 'r');

$duplicates = [];
while ($line = fgetcsv($targetLibrary)) {
    [$id, $fileName, $sourceId] = parseLineFromTarget($line);

    if (isset($duplicates[$sourceId])) {
        echo "Duplicate -- SourceId: '{$sourceId}', TargetId: '{$id}', Filename: '{$fileName}'", PHP_EOL;
        continue;
    }

    $duplicates[$sourceId] = true;
}

foreach ($sourceLibrary as $sourceId => $filename) {
    if (!isset($duplicates[$sourceId])) {
        echo "Missing in target -- SourceId: '{$sourceId}', Filename: '{$fileName}'", PHP_EOL;
        continue;
    }
}


function parseLineFromTarget(array $line): array
{
    [$id, $fileName, $base64encodedId] = $line;
    $decodedId = base64_decode($base64encodedId);
    $sourceId = json_decode($decodedId, true, flags: JSON_THROW_ON_ERROR)['identifier'];
    return [$id, $fileName, $sourceId];
}

/**
 * @param string $fileName
 * @param string $mode
 * @return resource
 */
function openAndSkipFirstLine(string $fileName, string $mode = 'r')
{
    $resource = fopen($fileName, $mode);
    fgetcsv($resource);
    return $resource;
}

function getSourceLibraryKeyById(): array
{
    $inputFile = openAndSkipFirstLine(__DIR__ . '/source_library.csv', 'r');

    $data = [];
    while ($line = fgetcsv($inputFile)) {
        [$id, $fileName] = $line;
        $data[(int)$id] = $fileName;
    }

    return $data;
}

